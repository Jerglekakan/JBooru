<?php
	require "inv.header.php";

	//Give credit to which user?
	$user = "Anonymous";	
	$import_dir = "import/";
	$import_dir_len = strlen($import_dir);
	$image = new image();
	$file_count = 0;
	$uploaded_files = 0;
	$rejected_files = 0;
	$skipped_files = 0;
	$shouldPrint = ""; //"", "console", "html"

	//Log stuff
	if(file_exists("admin/jobs/pid"))
		die("already running");
	$timestamp = date('Y-m-d_H_i_s');
	$pidFile = fopen("admin/jobs/pid", "w") or die("Unable to open pid file");
	fwrite($pidFile, (string)getmypid());
	fclose($pidFile);
	$logFile = fopen("admin/jobs/$timestamp.json", "w") or die("Unable to open log file");
	fwrite($logFile, "[ {\"event\": \"info\", \"timezone\": \"".date_default_timezone_get()."\", \"prefix\": \"$import_dir\"} ");

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

	function conditional_log($str, $htmlStr)
	{
		global $shouldPrint;
		if($shouldPrint == "console")
			print $str;
		else if($shouldPrint == "html" && isset($htmlStr))
			print $htmlStr;
	}

	function logImport($filepath, $id, $thumb, $tags, $hash, $title, $source)
	{
		global $logFile;
		$obj = [];
		$obj["event"] = "import";
		$obj["filepath"] = $filepath;
		$obj["id"] = $id;
		$obj["thumbnail"] = $thumb;
		$obj["tags"] = $tags;
		$obj["hash"] = $hash;
		$obj["title"] = $title;
		$obj["source"] = $source;
		fwrite($logFile, ",".json_encode($obj));
	}
	
	function logFail($filepath, $errType, $errMsg, $hash)
	{
		global $logFile;
		$obj = [];
		$obj["event"] = "error";
		$obj["filepath"] = $filepath;
		$obj["errortype"] = $errType;
		$obj["errormessage"] = $errMsg;
		$obj["hash"] = $hash;
		fwrite($logFile, ",".json_encode($obj));
	}



	$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($import_dir));
	foreach($rii as $fileIt)
	{
		if($fileIt->isDir())
			continue;

		$file_count++;
		$file = $fileIt->getPathname();
		$path_info = pathinfo($file);
		$ext = $fileIt->getExtension();
		$tags = getTagString($file);
		conditional_log("$file\n", "<strong>$file</strong><br/>");
		conditional_log("Tags: $tags\n", "Tags | $tags<br/>");
		if(!image::validext($file))
		{
			conditional_log("Invalid extension\n", "<span style=\"color: rgb(255, 0, 0);\">Invalid Extension</span><br/><br/>");
			$skipped_files++;
			logFail($file, "Invalid Extension", $ext, "N/A");
			continue;
		}

		$uploaded_image = false;
		//Extension looks good, toss it through the image processing section.
		$dl_url = $site_url.rawurlencode($file);
		$dl_url = str_replace("%2F", "/", $dl_url);
		conditional_log("URL: $dl_url\n", "URL: <a href=\"".$dl_url."\">$dl_url</a><br/>");
		$iinfo = $image->getremoteimage($dl_url);
		if($iinfo !== false)
		{
			//Ok, download of image was successful! (yay?)
			conditional_log("getremoteimage() finished", "No errors encountered, commencing upload<br/>");
			$iinfo = explode(":",$iinfo);
			$tclass = new tag();
			$misc = new misc();
			$ext = ".$ext";
			//TODO - look for .txt file and grab source
			$source = "";
			$title = $db->real_escape_string(htmlentities($fileIt->getFilename(),ENT_QUOTES,'UTF-8'));
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
			$bThumbnail = $image->thumbnail($iinfo[0]."/".$iinfo[1]);
			if(!$bThumbnail)
				conditional_log("Error making thumbnail", "Thumbnail generation failed! A serious error occured and the image could not be resized.<br />");
			if(!$db->query($query))
			{
				$err = $db->error;
				$hash = md5_file($file);
				conditional_log("Error with SQL insert: $err\n", "<span style='color: rgb(255,0,0)'>failed to upload image.</span><br/>
				SQL Error: $err<br/>
				File Hash (md5):$hash<br/><br/>");
				unlink("./images/".$iinfo[0]."/".$iinfo[1]);
				$image->folder_index_decrement($iinfo[0]);
				$ttags = explode(" ",$tags);
				foreach($ttags as $current)
					$tclass->deleteindextag($current);
				$rejected_files++;
				logFail($file, "SQL Error", $err, $hash);
			}
			else
			{
				$hash = md5_file('./images/'.$iinfo[0]."/".$iinfo[1]);
				$query = "SELECT id FROM $post_table WHERE hash='$hash' AND image='".$iinfo[1]."' AND directory='".$iinfo[0]."'  LIMIT 1";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				$newPostId = $row['id'];
				if($enable_cache)
					$cache = new cache();				
				if(isset($parent) && strlen($parent) > 0 && strlen(trim($parent)) > 0 && is_numeric($parent))
				{
					$parent_check = "SELECT COUNT(*) FROM $post_table WHERE id='$parent'";
					$pres = $db->query($parent_check);
					$prow = $pres->fetch_assoc();
					if($prow['COUNT(*)'] > 0)
					{
						$temp = "INSERT INTO $parent_child_table(parent,child) VALUES('$parent','".$newPostId."')";
						$db->query($temp);
						$temp = "UPDATE $post_table SET parent='$parent' WHERE id='".$newPostId."'";
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
				conditional_log("Image added\n", "<span style=\"color: rgb(52, 170, 0);\">Image added.</span><br/><br/>");
				$uploaded_files++;
				logImport($file, $newPostId, $bThumbnail, $tags, $hash, $title, $source);
			}
		}
		else
		{
			$rejected_files++;
			$error = $image->geterror();
			$hash = md5_file($file);
			conditional_log("getRemoteImage() failed\n", "<span style=\"color: rgb(255, 0, 0);\">getRemoteImage() failed, file not uploaded</span><br/>
			Error: ".$error."File Hash (md5): $hash<br/><br/>");
			logFail($file, "getRemoteImage()", $error, $hash);
		}
	}

	fwrite($logFile, "]");
	fclose($logFile);

	$consolemsg = "total images: $file_count\n"
	."successful uploads: $uploaded_files\n"
	."failed uploads: $rejected_files\n"
	."skipped files: $skipped_files\n";
	$htmlmsg = "<hr>Images found:$file_count<br/>
	Images successfully uploaded:$uploaded_files<br/>
	Images failed:$rejected_files<br/>
	Images skipped (invalid extension):$skipped_files";
	conditional_log($consolemsg, $htmlmsg);

	unlink("admin/jobs/pid");
	if($file_count == 0)
		unlink("admin/jobs/$timestamp.json");
?>
