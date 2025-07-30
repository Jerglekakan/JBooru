<?php
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

	$buckets = array();
	$image_dir = "./$image_folder/";
	$thumb_dir = "./$thumbnail_folder/";
	$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($image_dir));
	foreach($rii as $file)
	{
		if($file->isDir())
			continue;

		$imgpath = $file->getPathname();
		$img_href = $file->getPathname();
		$fname = $file->getFilename();
		$ext = $file->getExtension();
		if($ext == "svg")
			continue;
		if($fname == "404.jpg")
			continue;
		$bucket = strrchr($file->getPath(), "/");
		$bucket = substr($bucket, 1);
		$thumb = $thumb_dir.$bucket."/thumbnail_".$fname;
		if($ext == "webm" || $ext == "mp4")
			$thumb = str_replace(".$ext", ".png", $thumb);
		$thumb_href = substr($thumb, 2);
		if (image::validext($ext) && !file_exists($thumb)) 
		{
			print "<br/><a href='$img_href'>$imgpath</a><br/>thumb: $thumb<br/>";
			$image = new image();
			if(!$image->load($imgpath))
			{
				$fail_count++;
				print "Error loading metadata for $imgpath: ".$image->geterror()."<br/><br/>";
				continue;
			}
			if(!is_dir($thumb_dir.$bucket))
				$image->makethumbnailfolder($bucket);
			if( $image->thumbnail("$bucket/$fname") )
			{
				$new_count++;
				print "<span style='color:green'>$thumb</span>&nbsp;<a href='$thumb_href'>link</a><br><br/>";
			}
			else
			{
				$fail_count++;
				print "<span style='color:red'>$thumb failed</span>. <a href='$img_href'>Source image</a> might be corrupted<br/><br/>";
			}
		}
	}
	print "<hr/>
	Thumbnails created: $new_count<br/>
	Thumbnails failed: $fail_count<br/>";
?>
