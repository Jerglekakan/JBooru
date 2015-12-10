<?php
	if(!defined('_IN_ADMIN_HEADER_'))
		die;
	if(!isset($_GET['action']) || $_GET['action'] == '' || $_GET['action'] == 'none')
	{
		include "tagOpsForm.html";
		exit;
	}

	$debug = true;
	$all_posts = true;
	if(isset($_GET['images']) && $_GET['images'] != '')
	{
		if($debug)
			echo "Image set provided.<br/>";
		$all_posts = false;
		$image_set = explode(" ", $db->real_escape_string($_GET['images']));
		if($debug)
		{
			echo "All Posts: $debug<br/>
			Image IDs provided:<br/>";
			print_r($image_set);
			echo "<br/>";
		}
	}

	if($_GET['action'] == "add")
	{
		if($debug)
			echo "<h4>Adding Tags</h4><br/>";
		$newtags = explode(" ", $db->real_escape_string($_GET['add_tags']));
		if($debug)
		{
			echo "Tags:";
			print_r($newtags);
			echo "<br/>";
		}
	}
	else if($_GET['action'] == "remove")
	{
		if($debug)
			echo "<h4>Removing Tags</h4><br/>";
		$losetags = explode(" ", $db->real_escape_string($_GET['remove_tags']));
		if($debug)
		{
			echo "Tags:<br/>";
			print_r($losetags);
			echo "<br/>";
		}
	}
	else if($_GET['action'] == "replace")
	{
		if($debug)
			echo "<h4>Replacing Tags</h4><br/>";
		$losetags = explode(" ", $db->real_escape_string($_GET['replace_tags']));
		$newtags = explode(" ", $db->real_escape_string($_GET['new_tags']));
		if($debug)
		{
			echo "Replacing:<br/>";
			print_r($losetags);
			echo "<br/>With:<br/>";
			print_r($newtags);
			echo "<br/>";
		}
	}
	else
	{
		include "tagOpsForm.html";
		exit;
	}
	
?>
