<?php
	if(!defined('_IN_ADMIN_HEADER_'))
		die;
	if(!isset($_GET['action']) || $_GET['action'] == '')
	{
		include "tagOpsForm.html";
		exit;
	}
	else if($_GET['action'] == 'none')
	{
		echo "<h3 style='background-color:#f4afaf'>Pick an action ya putz!</h3>";
		include "tagOpsForm.html";
		exit;
	}
	$debug = false;
	$all_posts = false;
	$query = "SELECT id FROM $post_table WHERE "; //Used to get image set to work on
	$regex_fields = array("tags","title",
			"source","parent",
			"creation_date",
			"owner","height",
			"width","ext");
	$image_set = array();
	$display_result_set = false;
	$tclass = new tag();


	//Populate $image_set
	if(isset($_GET['images']) && $_GET['images'] != '') //If the user provided a set
	{
		if($_GET['set_type'] === "id_list") //List of post IDs
		{
			$image_set = explode(" ", $db->real_escape_string($_GET['images']));
			$tmp_arr = array();
			foreach($image_set as $thing)
			{
				if(($ofst = strpos($thing, '-')) !== false)
				{
					$range = explode("-", $thing);
					$start = (int) $range[0];
					$end = (int) $range[1];
					foreach(range($start, $end) as $num)
						$tmp_arr[] = $num;
				}
				else
				{
					if(is_numeric($thing))
						$tmp_arr[] = $thing;
				}
			}
			$image_set = $tmp_arr;
		}
		else //Regular Expression
		{
			$col = $db->real_escape_string($_GET['regex_against']);
			$regex = $db->real_escape_string($_GET['images']);
			if(array_search($col, $regex_fields) !== false)
				$query .= "$col REGEXP '$regex';";
			else
			{
				echo "<h4>The \"$col\" field has not been enabled for regular expressions</h4><br/>";
				include "tagOpsForm.html";
				exit();
			}
			if($debug) echo "<strong>SQL Query</strong><br/>$query<br/>";
			$display_result_set = isset($_GET['display_regex']);
			if($result = $db->query($query))
			{
				if($debug)
				{
					echo "Rows in result set: ".$result->num_rows."<br/>";
					echo "<strong>Result dump:</strong><br/>";
					var_dump($result);
					echo "<br/>";
				}
				$row = $result->fetch_assoc();
				while(!is_null($row))
				{
					if($debug)
					{
						echo "Next row:<br/>";
						var_dump($row);
						echo "<br/><br/>";
					}
					$image_set[] = $row['id'];
					$row = $result->fetch_assoc();
				}
				if($display_result_set)
				{
					echo "Your regex will result in operation on these ".count($image_set)." posts<br/>".
					print_r($image_set, true)."<br/><br/>".
					"<a href=\"index.php?page=tag_ops\">Back to form page</a>";
					exit();
				}
				$result->free();
			}
			else
			{
				echo "SQL query returned empty result<br/>";
			}
		}
	}//END User given image set
	else
	{
		if($_GET['action'] === "add")
		{
			$all_posts = true;
		}
		else if($_GET['action'] === "remove" || $_GET['action'] === "replace")
		{
			$image_set = array();
			$field = $_GET['action'].'_tags';
			$itags = explode(" ", $_GET[$field]);
			foreach($itags as $cur_tag)
			{
				$tmp_tag = str_replace("_", "\_", $cur_tag);
                                $tmp_tag = str_replace("*", "%", $tmp_tag);
                                $tmp_tag = str_replace("?", "_", $tmp_tag);
				if($cur_tag === end($itags))
					$query .= "tags LIKE '% ".$db->real_escape_string($tmp_tag)." %';";
				else
					$query .= "tags LIKE '% ".$db->real_escape_string($tmp_tag)." %' OR ";
			}

			if($debug) echo "<strong>SQL Query</strong><br/>$query<br/>";
			if($result = $db->query($query))
			{
				if($debug)
				{
					echo "Rows in result set: ".$result->num_rows."<br/>";
					echo "<strong>Result dump:</strong><br/>";
					var_dump($result);
					echo "<br/>";
				}
				$row = $result->fetch_assoc();
				while(!is_null($row))
				{
					if($debug)
					{
						echo "Next row:<br/>";
						var_dump($row);
						echo "<br/><br/>";
					}
					$image_set[] = $row['id'];
					$row = $result->fetch_assoc();
				}
				$result->free();
			}
			else
			{
				echo "SQL Query returned empty result set<br/>";
			}
		}
	}// END $image_set population

	//Stop if there's no images to work on
	if(empty($image_set) && !$all_posts)
	{
		echo "<strong>No posts found!</strong> Double check your tags.<br/>";
		exit;
	}
	//Output image set
	if($debug)
	{
		if($all_posts)
		{
			echo "Will operate on all posts!<br/><br/>";
		}
		else
		{
			echo "Will operate on these ".count($image_set)." posts:<br/>".print_r($image_set, true);
			echo "<br/><br/>";
		}
	}


	if($_GET['action'] == "add")
	{
		$newtags = explode(" ", $db->real_escape_string($_GET['add_tags']));
		if($debug)
		{
			echo "<h4>Adding the following tags:</h4><br/>";
			print_r($newtags);
			echo "<br/><br/>";
		}

		foreach($newtags as $cur_tag)
		{
			//Build update query
			$query = "UPDATE $post_table SET tags = CONCAT(tags, '$cur_tag ') WHERE tags NOT LIKE '% $cur_tag %'";
			if($all_posts)
			{
				$query .= ";";
			}
			else
			{
				$query .= " AND (";
				foreach($image_set as $cur_id)
				{
					if($cur_id === end($image_set))
						$query .= "id = $cur_id);";
					else
						$query .= "id = $cur_id OR ";
				}
			}
	
			//Execute Query (if we're not in debug mode)
			if($debug)
			{
				echo "<strong>Update Query:</strong>$query<br/><br/>";
			}
			else
			{
				$db->query($query);
				$qt = $db->affected_rows;
				print "\"$cur_tag\" tag added to ".$qt." posts<br/><a href=\"index.php?page=tag_ops\">Go Back</a>";
				$tclass->addindextag($cur_tag, $qt);
			}
		}
	}
	else if($_GET['action'] == "remove")
	{
		$losetags = explode(" ", $db->real_escape_string($_GET['remove_tags']));
		if($debug)
		{
			echo "<h4>Removing the following tags</h4>";
			print_r($losetags);
			echo "<br/><br/>";
		}
		foreach($losetags as $cur_tag)
		{
			//Build update query
			$query = "UPDATE $post_table SET tags = REPLACE(tags, '$cur_tag ', '') WHERE tags LIKE '% $cur_tag %'";
			if($all_posts)
			{
				$query .= ";";
			}
			else
			{
				$query .= " AND (";
				foreach($image_set as $cur_id)
				{
					if($cur_id === end($image_set))
						$query .= "id = $cur_id);";
					else
						$query .= "id = $cur_id OR ";
				}
			}
	
			//Execute Query (if we're not in debug mode)
			if($debug)
			{
				echo "<strong>Update Query:</strong>$query<br/><br/>";
			}
			else
			{
				$db->query($query);
				$qt = $db->affected_rows;
				print "\"$cur_tag\" tag removed from ".$qt." posts<br/><a href=\"index.php?page=tag_ops\">Go Back</a>";
				$tclass->deleteindextag($cur_tag, $qt);
			}
		}
	}
	else if($_GET['action'] == "replace")
	{
		$losetag = $db->real_escape_string(htmlentities($_GET['replace_tags'], ENT_QUOTES, 'UTF-8'));
		$newtag = $db->real_escape_string(htmlentities($_GET['new_tags'], ENT_QUOTES, 'UTF-8'));
		if(strstr($losetag, " ") != FALSE || strstr($newtag, " ") != FALSE)
		{
			echo "<strong>When replacing one tag with another, neither of the input fields can contain a space</strong><br/>";
			exit;
		}
		if($debug)
		{
			echo "<strong>Replacing:</strong><br/>";
			print_r($losetag);
			echo "<br/><strong>With:</strong><br/>";
			print_r($newtag);
			echo "<br/><br/>";
		}

		//Build update query
		$query = "UPDATE $post_table SET tags = REPLACE(tags, ' $losetag ', ' $newtag ') WHERE tags LIKE '% $losetag %' AND tags NOT LIKE '% $newtag %'";
		if($all_posts)
		{
			$query .= ";";
		}
		else
		{
			$query .= " AND (";
			foreach($image_set as $cur_id)
			{
				if($cur_id === end($image_set))
					$query .= "id = $cur_id);";
				else
					$query .= "id = $cur_id OR ";
			}
		}

		//Execute Query (if we're not in debug mode)
		if($debug)
		{
			echo "<strong>Update Query:</strong>$query<br/><br/>";
		}
		else
		{
			$db->query($query);
			$qt = $db->affected_rows;
			print "\"$losetag\" tag replaced with \"$newtag\" in $qt posts<br/>";
			$tclass->deleteindextag($losetag, $qt);
			$tclass->addindextag($newtag, $qt);
		}
	}
	else
	{
		include "tagOpsForm.html";
		exit;
	}
?>
