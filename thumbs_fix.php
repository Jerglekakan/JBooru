<?php
	set_time_limit(0);
	require "inv.header.php";
	$user = new user();
	if(!$user->gotpermission('is_admin'))
	{
		header('Location: index.php');
		exit;
	}

	$image = new image();
	$new_count = 0;
	$fail_count = 0;
	function is_valid_extension($img)
	{
		$ext = strrchr($img,".");
		$ext = substr($ext, 1);
		switch($ext)
		{
			case "jpg":
			case "gif":
			case "png":
			case "bmp":
			case "webp":
			case "webm":
			case "mp4":
				return true;
			break;

			default:
				return false;
		}
	}
	$dirs = array();
	$dir = "./$image_folder/";
	$dir_contents = scandir($dir);
	foreach ($dir_contents as $item) 
	{
		if (is_dir($dir.$item) && $item != '.' && $item != '..') 
		{
			$dirs[] = $item;
		}
	}
	foreach($dirs as $current)
	{
		$dir_contents = scandir("./$image_folder/".$current."/");
		foreach ($dir_contents as $item) 
		{
			$thumb = "./$thumbnail_folder/$current/thumbnail_$item";
			$ext = strrchr($item,".");
			$ext = substr($ext, 1);
			if($ext == "webm" || $ext == "mp4")
			{
				$thumb = str_replace(".$ext", ".png", $thumb);
			}
			if ($item != '.' && $item != '..' && !is_dir($dir.$item) && is_valid_extension($item) && !file_exists($thumb)) 
			{
				print "<br/><a href='$image_folder/$current/$item'>$image_folder/$current/$item</a><br/>thumb: $thumb<br/>";
				$image = new image();
				if(!$image->load("./$image_folder/$current/$item"))
				{
					$fail_count++;
					print "Error loading metadata for $current/$item: ".$image->geterror()."<br/><br/>";
					continue;
				}
				if(!is_dir("./$thumbnail_folder/".$current."/"))
					$image->makethumbnailfolder($current);
				if( $image->thumbnail($current."/".$item) )
				{
					$new_count++;
					print "<span style='color:green'>$thumb</span>&nbsp;<a href='$thumb'>link</a><br><br/>";
				}
				else
				{
					$fail_count++;
					print "<span style='color:red'>$thumb failed</span>. <a href='./$image_folder/$current/$item'>Source image</a> might be corrupted<br/><br/>";
				}
			}
		
		}
	}
	print "<hr/>
	Thumbnails created: $new_count<br/>
	Thumbnails failed: $fail_count<br/>";
?>
