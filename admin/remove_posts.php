<?php
	if(!defined('_IN_ADMIN_HEADER_'))
		die;
	if(!isset($_GET['by']) || $_GET['by'] == '')
	{
		include "removeForm.html";
		exit;
	}
		
	$debug = true;
	$delete_count = 0;
	$fail_count = 0;
	$where_clause = "0";
	
	if($debug)
	{
		print_r($_GET);
		echo "<br/><br/>";
	}
	
	//Uncommenting this causes a 500 internal server error for some reason
	/*switch($_GET['by'])
	{
		case "tags":
			$tags = explode(" ", $_GET['tags']);
			if(!isset($_GET['require']) || $_GET['require'] == "all")
				$require = "all";
			else
				$require = "any";
			$where_clause = "WHERE tags ";
			break;
		case "title":
			if(!isset($_GET['type']) || $_GET['type'] == "string_search")
				$type = "string_search";
			else
				$type = "regex";
			$where_clause = "WHERE title ";
			if($type == "string_search"){
				$where_clause .= "LIKE ";
				$tmp_str = $db->real_escape_string($_GET['text']);
				$tmp_str = str_replace("*", "%", $tmp_str);
				$tmp_str = str_replace("?", "_", $tmp_str);
			} else {
				$where_clause .= "REGEXP ";
				$tmp_str = $db->real_escape_string($_GET['text']);
				//$tmp_str = str_replace("\\", "\\\\", $_GET['text']);
			}
			$where_clause .= "'".$tmp_str."';";
			break;
		case "rating":
			if(!isset($_GET['rating']))
				header("Location:".$site_url);
			else if ($_GET['rating'] == "asdf1" || $_GET['rating'] == "asdf2" || $_GET['rating'] == "Explicit")
				$rating = $_GET['rating'];
			$where_clause = "WHERE rating = '$rating;'";
			break;
		case "parent":
			if(!isset($_GET['parent']))
				header("Location:".$site_url);
			else
				$parentId = $_GET['parent'];
			$where_clause = "WHERE parent = '$parentId;'";
			break;
		default:
			header("Location:".$site_url);
			break;
	}
	
	$sql_query = "SELECT id,tags,title from $post_table $where_clause";
	
	if($debug)
		echo "SQL query:<br/>$sql_query<br/>";
	$results = $db->query($sql_query);
	if($results->num_rows == 0)
	{
		if($debug)
			echo "No tags returned";
		else
			echo "No tags to remove. Here is the SQL Query:<br/>$sql_query";
		die();
	}
	
	
	$tag_obj = new tag();
	$user = new user();
	$cacahe = new cache();
	$image = new image();
	if($debug)
		echo "Query returned ".$results->count." rows<br/>";
	while($post = $results->fetch_assoc())
	{
		if($debug)
		{
			echo "ID: '".$post['id']."' Title: '".$post['title']."'<br/>&nbsp;&nbsp;&nbsp;&nbsp;Tags: ".$post['tags']."<br/>";
		}
		else
		{
			if($image->removeimage($post['id']) == true) {
				$delete_count++;
				//copied the rest from remove.php
				$cache->destroy_page_cache("cache/".$post['id']);
				$query = "SELECT id FROM $post_table WHERE id < $id ORDER BY id DESC LIMIT 1";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				$prev_id = $row['id'];
				$result->free_result();
				$query = "SELECT id FROM $post_table WHERE id > $id ORDER BY id ASC LIMIT 1";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				$next_id = $row['id'];
				$date = date("Ymd");
				if(is_dir("$main_cache_dir".""."cache/".$prev_id) && "$main_cache_dir".""."cache/".$prev_id != "$main_cache_dir".""."cache/")
				$cache->destroy_page_cache("cache/".$prev_id);
				if(is_dir("$main_cache_dir".""."cache/".$next_id) && "$main_cache_dir".""."cache/".$next_id != "$main_cache_dir".""."cache/")				
				$cache->destroy_page_cache("cache/".$next_id);
			} else {
				$fail_count++;
				echo "removeimage() failed for image ID '$row['id']'<br/>";
			}
		}
	}
	echo "Deleted files: $delete_count<br/>Failures: $fail_count<br/>"
	printForm();*/
?>
