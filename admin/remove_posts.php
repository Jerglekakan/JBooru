<?php
	if(!defined('_IN_ADMIN_HEADER_'))
		die;
	if(!isset($_GET['by']) || $_GET['by'] == '')
	{
		include "removeForm.html";
		exit;
	}
		
	$debug = false;
	$delete_count = 0;
	$fail_count = 0;
	$where_clause = "";
	if($debug)
	{
		echo "<h4>Input Parameters</h4>";
		print_r($_GET);
		echo "<br/><br/>";
	}
	
	switch($_GET['by'])
	{
		case "tags":
			if(empty($_GET['tags']))
			{
				echo "<h3 style='background-color:#f4afaf'>Put in some tags ya putz!</h3>";
				include "removeForm.html";
				exit;
			}
			$where_clause .= "(";
			$tags = explode(" ", $db->real_escape_string($_GET['tags']));
			if(!isset($_GET['require']) || $_GET['require'] == "all")
				$bool_op = "AND";
			else
				$bool_op = "OR";
			foreach($tags as $cur_tag)
			{
				$tmp_tag = str_replace("_", "\_", $cur_tag);
				$tmp_tag = str_replace("*", "%", $tmp_tag);
				$tmp_tag = str_replace("?", "_", $tmp_tag);
				if($cur_tag === end($tags))
					$where_clause .= "tags LIKE '% $tmp_tag %')";
				else
					$where_clause .= "tags LIKE '% $tmp_tag %' $bool_op ";
			}
			break;
		case "ID":
			if(empty($_GET['images']))
			{
				echo "<h3 style='background-color:#f4afaf'>Put in some IDs ya putz!</h3>";
				include "removeForm.html";
				exit;
			}
			//do nothing
			break;
		case "title":
			if(empty($_GET['title']))
			{
				echo "<h3 style='background-color:#f4afaf'>Put in a title ya putz!</h3>";
				include "removeForm.html";
				exit;
			}
			if(!isset($_GET['type']) || $_GET['type'] == "string_search")
			{
				$tmp_str = $db->real_escape_string($_GET['title']);
				$tmp_tag = str_replace("_", "\_", $cur_tag);
				$tmp_str = str_replace("*", "%", $tmp_str);
				$tmp_str = str_replace("?", "_", $tmp_str);
				$where_clause .= "title LIKE '%$tmp_str%'";
			}
			else
			{
				$tmp_str = $db->real_escape_string($_GET['title']);
				//$tmp_str = str_replace("\\", "\\\\", $_GET['text']);
				$where_clause .= "title REGEXP '$tmp_str'";
			}
			break;
		case "rating":
			if ($_GET['rating'] === "safe" || $_GET['rating'] === "questionable" || $_GET['rating'] === "explicit")
				$rating = $_GET['rating'];
			$where_clause = "rating = '$rating'";
			break;
		default:
			header("Location:".$site_url);
			break;
	}
	if($_GET['images'] != "")
	{
		if($_GET['by'] != "ID")
			$where_clause .= " AND (";
		$tmp_arr = explode(" ", $db->real_escape_string($_GET['images']));
		$image_set = array();
		$parent_ids = array();
		if($debug)
			echo "Image IDs provided:<br/>";
		foreach($tmp_arr as $thing)
		{
			if($debug)
				echo "<b>$thing</b><br/>";
		        if(($ofst = strpos($thing, '-')) !== false)
		        {
		                $range = explode("-", $thing);
		                $start = (int) $range[0];
		                $end = (int) $range[1];
				if($debug)
					echo "Adding images $start through $end<br/><br/>";
		                foreach(range($start, $end) as $num)
		                        $image_set[] = $num;
		        }
			else if(($ofst = strpos($thing, 'parent:')) !== false)
			{
				$parent_ids[] = substr($thing, $ofst+7);
			}
		        else
		        {
		                if(is_numeric($thing))
					$image_set[] = $thing;
		        }
		}
		foreach($parent_ids as $thing)
		{
			if($thing === end($parent_ids))
			{
				$where_clause .= "parent = $thing";
				if(empty($image_set))
					$where_clause .= ")";
				else
					$where_clause .= " OR ";
			}
			else
				$where_clause .= "parent = $thing OR ";
		}
		foreach($image_set as $thing)
		{
			if($thing === end($image_set)) {
				$where_clause .= "id = $thing";
				if($_GET['by'] != 'ID')
					$where_clause .= ')';
			}
			else {
				$where_clause .= "id = $thing OR ";
			}
		}
	}
	
	$sql_query = "SELECT id,tags,title from $post_table WHERE $where_clause;";
	if($debug)
		echo "<strong>SQL query:</strong><br/>$sql_query<br/><br/>";
	if($results = $db->query($sql_query))
	{
		$tag_obj = new tag();
		$user = new user();
		$cache = new cache();
		$image = new image();
		echo "Query returned ".$results->num_rows." rows<br/>";
		$post = $results->fetch_assoc();
		while(!is_null($post))
		{
			if($debug || isset($_GET['display_ids']))
			{
				echo "ID: '".$post['id']."' Title: '".$post['title']."'<br/>&nbsp;&nbsp;&nbsp;&nbsp;Tags: ".$post['tags']."<br/><br/>";
			}
			else
			{
				if($image->removeimage($post['id']) == true) {
					$delete_count++;
					echo "<span style=\"color: rgb(0, 255, 0);\">Image ".$post['id']." successfully deleted!</span><br/>";
					//copied the rest from remove.php
					$cache->destroy_page_cache("cache/".$post['id']);
					$query = "SELECT id FROM $post_table WHERE id < ".$post['id']." ORDER BY id DESC LIMIT 1";
					if($result = $db->query($query))
					{
						$row = $result->fetch_assoc();
						$prev_id = $row['id'];
						$result->free_result();
					}
					else
					{
						echo "<strong>No previous ID, Cannot remove image from cache!</strong><br/>
						Here's the query: $query<br/>
						and here's the error(".$db->errno."): ".$db->error."<br/><br/>";
						$post = $results->fetch_assoc();
						continue;
					}
					$query = "SELECT id FROM $post_table WHERE id > ".$post['id']." ORDER BY id ASC LIMIT 1";
					if($result = $db->query($query))
					{
						$row = $result->fetch_assoc();
						$next_id = $row['id'];
						$result->free_result();
					}
					else
					{
						echo "<strong>No next ID, Cannot remove image from cache!</strong><br/>
						Here's the query: $query<br/>
						and here's the error(".$db->errno."): ".$db->error."<br/><br/>";
						$post = $results->fetch_assoc();
						continue;
					}
					$date = date("Ymd");
					if(is_dir("$main_cache_dir".""."cache/".$prev_id) && "$main_cache_dir".""."cache/".$prev_id != "$main_cache_dir".""."cache/")
					$cache->destroy_page_cache("cache/".$prev_id);
					if(is_dir("$main_cache_dir".""."cache/".$next_id) && "$main_cache_dir".""."cache/".$next_id != "$main_cache_dir".""."cache/")				
					$cache->destroy_page_cache("cache/".$next_id);
				} else {
					$fail_count++;
					echo "<span style=\"color: rgb(255, 0, 0);\">removeimage() failed for image ID '".$row['id']."'</span><br/>;
					Error: $error<br/>";
				}
			}
			$post = $results->fetch_assoc();
		}
		$results->free();
		echo "<br/><hr><br/>";
		echo "Deleted files: $delete_count<br/>Failures: $fail_count<br/>";
	}
	else
	{
		echo "<h4>SQL Query returned empty result set!</h4>";
	}
?>
