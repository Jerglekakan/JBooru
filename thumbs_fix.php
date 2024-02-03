<?php
	set_time_limit(0);
	require "inv.header.php";
	$user = new user();
	if(!$user->gotpermission('is_admin'))
	{
		header('Location: index.php');
		exit;
	}

	$dir = "./$image_folder/";
	$dirs = array();
	$image = new image();
	$new_count = 0;
	$fail_count = 0;
	function is_valid_extension($img)
	{
		$ext = substr($img,-3,10);
		if($ext == "jpg")
			return true;
		else if($ext == "gif")
			return true;
		else if($ext == "png")
			return true;
		else if($ext == "bmp")
			return true;
		else if($ext == "webp")
			return true;
		else
			return false;
	}
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
			if ($item != '.' && $item != '..' && !is_dir($dir.$item) && is_valid_extension($item) && !file_exists("./$thumbnail_folder/$current/thumbnail_$item")) 
			{
				$image = new image();			
				if(!is_dir("./$thumbnail_folder/".$current."/"))
					$image->makethumbnailfolder($current);
				if( $image->thumbnail($current."/".$item) )
				{
					$new_count++;
					print "<span style='color:green'>./$thumbnail_folder/$current/thumbnail_$item</span><br>";
				}
				else
				{
					$fail_count++;
					print "<span style='color:red'>./$thumbnail_folder/$current/thumbnail_$item failed</span>. <a href='./$image_folder/$current/$item'>Source image</a> might be corrupted<br/>";
				}
			}
		
		}
	}
	print "<hr/>
	Thumbnails created: $new_count<br/>
	Thumbnails failed: $fail_count<br/>";
?>
