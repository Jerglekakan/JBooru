<?php
	require "inv.header.php";
	//Give credit to which user?
	$user = "Anonymous";	
	$path = "import/";
	$image = new image();
	$folders = scandir($path);
	print "Processing folder for files... Please wait.<br><br>";
	$tmpPath = "";
	$cur_folder;
	$tags2;
	$processed_files = 0;
	$uploaded_files = 0;
	$rejected_files = 0;
	$i = 0;
	
	function getTagString($tagString) {
		$tagString = str_replace(" ", "_", $tagString);
		$tagString = str_replace("/", " ", $tagString);
		while($tagString[0] == ' ' || $tagString[0] == '.') {
			$tagString = substr($tagString, 1);
		}
		return $tagString;
	}
	
	//Recursively scan directory for folders. Exclude . and ..
	function rScanDir($scanMe) {
		global $path, $tmpPath, $cur_folder, $tags2;
		//echo "Now Scanning ".$scanMe."<br/>";
		foreach($scanMe as $folder)
		{
			//echo "&nbsp;&nbsp;&nbsp;&nbsp;Found File: ".$folder."&nbsp;&nbsp;==>".$path.$tmpPath.$folder;
			if(is_dir($path.$tmpPath.$folder) && $folder !="." && $folder !="..")
			{
				$cur_folder[] = $tmpPath.$folder;
				//echo "<br/>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".$path.$tmpPath.$folder.": added to folder array<br/>";
				$tags2[] = getTagString($tmpPath.$folder);
				$tmpPath .= $folder."/";
				//echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Temp path changed to: ".$tmpPath."<br/>";
				rScanDir(scandir($path.$tmpPath));
			}
		}
		//echo "tmpPath:".$tmpPath."<br/>substr:".substr($tmpPath, 0, strrpos($tmpPath, "/"))."<br/>";
		$tmpPath = substr($tmpPath, 0, strrpos($tmpPath, "/"));
		$tmpPath = substr($tmpPath, 0, strrpos($tmpPath, "/")+1);
	}
	rScanDir($folders);
	/*echo "Printing the folders array<br/>";
	print_r($cur_folder);
	echo "<br/>Printing the tags array<br/>";
	print_r($tags2);
	echo "<br/><br/>";*/

	foreach($cur_folder as $current_folder)
	{
		//Check for images in folder and add them one by one.
		$files = scandir($path.$current_folder);
		foreach($files as $file)
		{
			$extension = explode(".",$file);
			if($extension['1'] == "jpg" || $extension['1'] == "jpeg" || $extension['1'] == "png" || $extension['1'] == "bmp" || $extension['1'] == "gif" || $extension['1'] == "JPG")
			{
				$uploaded_image = false;
				$processed_files++;
				//Extension looks good, toss it through the image processing section.
				//$dl_url = $site_url.$path.rawurlencode($current_folder)."/".rawurlencode($file);
				$dl_url = $site_url.$path.rawurlencode($current_folder)."/".rawurlencode($file);
				$dl_url = str_replace("%2F", "/", $dl_url);
				echo "URL:".$dl_url."<br/>";
				$iinfo = $image->getremoteimage($dl_url);
				if($iinfo === false)
					$error = $image->geterror()."<br />Could not add the image.";
				else
					$uploaded_image = true;	
				//Ok, download of image was successful! (yay?)
				if($uploaded_image == true)
				{
					echo "No errors encountered, commencing upload<br/>";
					$iinfo = explode(":",$iinfo);
					$tclass = new tag();
					$misc = new misc();
					$ext = strtolower(substr($iinfo[1],-4,10000));
					$source = $db->real_escape_string(htmlentities($_POST['source'],ENT_QUOTES,'UTF-8'));
					if(isset($_POST['title']))
						$title = $db->real_escape_string(htmlentities($_POST['title'],ENT_QUOTES,'UTF-8'));
					else
						$title = $file;
					$tags = strtolower($db->real_escape_string(str_replace('%','',htmlentities($tags2[$i],ENT_QUOTES,'UTF-8'))));
					$ttags = explode(" ",$tags);
					$tag_count = count($ttags);		
					if($tag_count == 0)
						$ttags[] = "tagme";
					if($tag_count < 5 && strpos($ttags,"tagme") === false)
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
						if($current != "" && $current != " " && !$misc->is_html($current))
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
						if($current != "" && $current != " " && !$misc->is_html($current))
						{
							$ttags = $tclass->filter_tags($tags,$current, $ttags);
							$tclass->addindextag($current);
							$cache = new cache();
							
							if(is_dir("$main_cache_dir".""."search_cache/".$current."/"))
							{
								$cache->destroy_page_cache("search_cache/".$current."/");
							}
							else
							{
								if(is_dir("$main_cache_dir".""."search_cache/".$misc->windows_filename_fix($current)."/"))
									$cache->destroy_page_cache("search_cache/".$misc->windows_filename_fix($current)."/");		
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
					$query = "INSERT INTO $post_table(creation_date, hash, image, title, owner, height, width, ext, rating, tags, directory, source, active_date, ip) VALUES(NOW(), '".md5_file("./images/".$iinfo[0]."/".$iinfo[1])."', '".$iinfo[1]."', '$title', '$user', '".$isinfo[1]."', '".$isinfo[0]."', '$ext', '$rating', '$tags', '".$iinfo[0]."', '$source', '".date("Ymd")."', '$ip')";
					if(!is_dir("./thumbnails/".$iinfo[0]."/"))
						$image->makethumbnailfolder($iinfo[0]);
					if(!$image->thumbnail($iinfo[0]."/".$iinfo[1]))
						print "Thumbnail generation failed! A serious error occured and the image could not be resized.<br /><br />";
					if(!$db->query($query))
					{
						print "failed to upload image.";
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
						$cache = new cache();				
						if($parent != '' && is_numeric($parent))
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
								$cache->destroy("cache/".$parent."/post.cache");	
							}
						}				
						if(is_dir("$main_cache_dir".""."cache/".$row['id']))
							$cache->destroy_page_cache("cache/".$row['id']);
						$query = "SELECT id FROM $post_table WHERE id < ".$row['id']." ORDER BY id DESC LIMIT 1";
						$result = $db->query($query);
						$row = $result->fetch_assoc();
						$cache->destroy_page_cache("cache/".$row['id']);

						$query = "UPDATE $post_count_table SET last_update='20060101' WHERE access_key='posts'";
						$db->query($query);
						print "<span style=\"color: rgb(52, 170, 0);\">Image added.</span><br/>";
						$uploaded_files++;
					}
				}
				else
				{
					$rejected_files++;
					print "<span style=\"color: rgb(255, 0, 0);\">getRemoteImage() failed, file not uploaded</span><br/>";
				}
				print "Valid Extension<br>".$tags2[$i]." | ";
				print $file."<br><br>";
			}
		}
		$i++;
	}
	print "<hr>Images found:".$processed_files."<br/>".
	"Images successfully uploaded:".$uploaded_files."<br/>".
	"Images failed:".$rejected_files;
?>
