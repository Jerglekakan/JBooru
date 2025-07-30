<?php
	require "inv.header.php";
	//Give credit to which user?
	$user = "Anonymous";	
	$import_dir = "import/";
	$import_dir_len = strlen($import_dir);
	$image = new image();
	$files = [];
	$file_count = 0;
	$uploaded_files = 0;
	$rejected_files = 0;
	$skipped_files = 0;

	function getTagString($filepath)
	{
		global $import_dir, $import_dir_len;
		$ret = trim($filepath);
		if(substr(trim($filepath), 0, $import_dir_len) === $import_dir)
			$ret = substr(trim($filepath), $import_dir_len);
		$ret = substr($ret, 0, strrpos($ret, "/"));
		$ret = str_replace(" ", "_", $ret);
		$ret = str_replace("/", " ", $ret);
		return mb_convert_case($ret, MB_CASE_LOWER, "UTF-8");
	}
	
	function rScanDir($scanMe)
	{
		global $files, $file_count;
		foreach(scandir($scanMe) as $item)
		{
			if($item == "." || $item == "..")
				continue;
			if(is_dir($scanMe.$item))
				rScanDir($scanMe.$item."/");
			else
			{
				$files[] = $scanMe.$item;
				$file_count++;
			}
		}
	}


	rScanDir($import_dir);
	for($i = 0; $i < $file_count; $i++)
	{
		$file = $files[$i];
		$path_info = pathinfo($file);
		$ext = $path_info['extension'];
		$tags = getTagString($file);
		print "<strong>$file</strong><br/>";
		print "Tags | $tags<br/>";
		if(!image::validext($file))
		{
			print "<span style=\"color: rgb(255, 0, 0);\">Invalid Extension</span><br/><br/>";
			$skipped_files++;
			continue;
		}

		$uploaded_image = false;
		//Extension looks good, toss it through the image processing section.
		$dl_url = $site_url.rawurlencode($file);
		$dl_url = str_replace("%2F", "/", $dl_url);
		echo "URL:<a href=\"".$dl_url."\">$dl_url</a><br/>";
		$iinfo = $image->getremoteimage($dl_url);
		if($iinfo === false)
			$error = $image->geterror();
		else
			$uploaded_image = true;	
		//Ok, download of image was successful! (yay?)
		if($uploaded_image == true)
		{
			echo "No errors encountered, commencing upload<br/>";
			$iinfo = explode(":",$iinfo);
			$tclass = new tag();
			$misc = new misc();
			$ext = ".$ext";
			//TODO - look for .txt file and grab source
			$source = "";
			$title = $db->real_escape_string(htmlentities($path_info['basename'],ENT_QUOTES,'UTF-8'));
			//TODO - look for .txt file and override title if found
			$tags = $db->real_escape_string(str_replace('%','',htmlentities($tags,ENT_QUOTES,'UTF-8')));
			$ttags = explode(" ",$tags);
			$tag_count = count($ttags);
			if($tag_count == 0 || $tag_count < 7 && array_search("tagme", $ttags) === false)
				$ttags[] = "tagme";
			foreach($ttags as $current)
			{
				if(strpos($current,'parent:') !== false)
				{
					$current = '';
					$parent = str_replace("parent:","",$current);
					if(!is_numeric($parent))
						$parent = '';
				}
				if(strlen($current) > 0 && strlen(trim($current)) > 0 && !$misc->is_html($current))
				{
					$ttags = $tclass->filter_tags($tags,$current, $ttags);
					$alias = $tclass->alias($current);
					if($alias !== false)
					{
						$key_array = array_keys($ttags, $current);
						foreach($key_array as $key)
							$ttags[$key] = $alias;
					}
				}
			}
			$tags = implode(" ",$ttags);
			foreach($ttags as $current)
			{
				if(strlen($current) > 0 && strlen(trim($current)) > 0 && !$misc->is_html($current))
				{
					$ttags = $tclass->filter_tags($tags,$current, $ttags);
					$tclass->addindextag($current);

					if($enable_cache)
					{
						$cache = new cache();
						if(is_dir("$main_cache_dir".""."search_cache/".$current."/"))
							$cache->destroy_page_cache("search_cache/".$current."/");
						else
						{
							if(is_dir("$main_cache_dir".""."search_cache/".$misc->windows_filename_fix($current)."/"))
								$cache->destroy_page_cache("search_cache/".$misc->windows_filename_fix($current)."/");		
						}
					}
				}
			}
			asort($ttags);
			$tags = implode(" ",$ttags);
			$tags = mb_trim($tags);
			$tags = " $tags ";
			$rating = "Questionable";
			$ip = "127.0.0.1";
			$isinfo = getimagesize("./images/".$iinfo[0]."/".$iinfo[1]);
			$query = "INSERT INTO $post_table(creation_date, hash, image, title, owner, height, width, ext, rating, tags, directory, source, active_date, ip) VALUES(NOW(), '".md5_file("./images/".$iinfo[0]."/".$iinfo[1])."', '".$iinfo[1]."', '$title', '$user', '".$image->geth()."', '".$image->getw()."', '$ext', '$rating', '$tags', '".$iinfo[0]."', '$source', '".date("Ymd")."', '$ip')";
			if(!is_dir("./thumbnails/".$iinfo[0]."/"))
				$image->makethumbnailfolder($iinfo[0]);
			if(!$image->thumbnail($iinfo[0]."/".$iinfo[1]))
				print "Thumbnail generation failed! A serious error occured and the image could not be resized.<br />";
			if(!$db->query($query))
			{
				print "<span style='color: rgb(255,0,0)'>failed to upload image.</span><br/>
				SQL Error: ".$db->error."<br/>
				File Hash (md5):".md5_file($file)."<br/>";
				unlink("./images/".$iinfo[0]."/".$iinfo[1]);
				$image->folder_index_decrement($iinfo[0]);
				$ttags = explode(" ",$tags);
				foreach($ttags as $current)
					$tclass->deleteindextag($current);
				$rejected_files++;
			}
			else
			{
				$query = "SELECT id FROM $post_table WHERE hash='".md5_file('./images/'.$iinfo[0]."/".$iinfo[1])."' AND image='".$iinfo[1]."' AND directory='".$iinfo[0]."'  LIMIT 1";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				if($enable_cache)
					$cache = new cache();				
				if(isset($parent) && strlen($parent) > 0 && strlen(trim($parent)) > 0 && is_numeric($parent))
				{
					$parent_check = "SELECT COUNT(*) FROM $post_table WHERE id='$parent'";
					$pres = $db->query($parent_check);
					$prow = $pres->fetch_assoc();
					if($prow['COUNT(*)'] > 0)
					{
						$temp = "INSERT INTO $parent_child_table(parent,child) VALUES('$parent','".$row['id']."')";
						$db->query($temp);
						$temp = "UPDATE $post_table SET parent='$parent' WHERE id='".$row['id']."'";
						$db->query($temp);
						if($enable_cache)
							$cache->destroy("cache/".$parent."/post.cache");	
					}
				}				
				if($enable_cache)
				{
					if(is_dir("$main_cache_dir".""."cache/".$row['id']))
						$cache->destroy_page_cache("cache/".$row['id']);
				}
				$query = "SELECT id FROM $post_table WHERE id < ".$row['id']." ORDER BY id DESC LIMIT 1";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				if($enable_cache)
					$cache->destroy_page_cache("cache/".$row['id']);

				$query = "UPDATE $post_count_table SET last_update='20060101' WHERE access_key='posts'";
				$db->query($query);
				print "<span style=\"color: rgb(52, 170, 0);\">Image added.</span><br/>";
				$uploaded_files++;
			}
		}
		else
		{
			$iinfo = explode(":",$iinfo);
			$rejected_files++;
			print "<span style=\"color: rgb(255, 0, 0);\">getRemoteImage() failed, file not uploaded</span><br/>
			Error: ".$error."File Hash (md5): ".md5_file($file)."<br/>";
		}
	}


	print "<hr>Images found:$file_count<br/>
	Images successfully uploaded:$uploaded_files<br/>
	Images failed:$rejected_files<br/>
	Images skipped (invalid extension):$skipped_files";
?>
