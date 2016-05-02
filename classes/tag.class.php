<?php
	class tag
	{
		function __construct()
		{}
		
		function addindextag($tag, $qt = 1)
		{
			if($qt <= 0)
				return;
			global $db, $tag_index_table, $tag_category_table;
			$tag = $db->real_escape_string($tag);
			if($tag != "")
			{
				$query = "SELECT * FROM $tag_index_table WHERE tag='$tag'";
				$result = $db->query($query);
				if($result->num_rows == 1)
				{
					$row = $result->fetch_assoc();
					$query = "UPDATE $tag_index_table SET index_count='".($row['index_count'] + $qt)."' WHERE tag='$tag'";
				}
				else
				{
					$query = "INSERT INTO $tag_index_table(tag, index_count) VALUES('$tag', '$qt')";
					$db->query($query);
					$query = "UPDATE $tag_category_table SET tag_count = tag_count + 1 WHERE category_name='generic'";
				}
				$db->query($query);
			}
		}
		
		function deleteindextag($tag, $qt = 1)
		{
			global $db, $tag_index_table, $tag_category_table;
			$tag = $db->real_escape_string($tag);
			if($tag != "")
			{
				$query = "SELECT index_count,category FROM $tag_index_table WHERE tag='$tag'";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				if($row['index_count'] - $qt > 0)
					$query = "UPDATE $tag_index_table SET index_count='".($row['index_count'] - $qt)."' WHERE tag='$tag'";
				else
				{
					$query = "DELETE FROM $tag_index_table WHERE tag='$tag'";
					$db->query($query);
					$query = "UPDATE $tag_category_table SET tag_count = tag_count - 1 WHERE category_name='".$row['category']."'";
				}
				$db->query($query);
			}
		}
		
		function alias($tag)
		{
			global $db, $alias_table;
			$tag = $db->real_escape_string($tag);
			$query = "SELECT tag FROM $alias_table WHERE alias='$tag' AND status='accepted'";
			$result = $db->query($query);
			$row = $result->fetch_assoc();
			if($row['tag'] != "" && $row['tag'] != NULL)
				return $row['tag'];
			return false;
		}
		
		function filter_tags($tags, $current, $ttags)
		{
			if(substr_count($tags, $current) > 1)
			{
				$temp_array = array();
				$key_array = array_keys($ttags, $current);
				$count = count($key_array)-1;
				for($i = 1; $i <= $count; $i++)
					$ttags[$key_array[$i]] = '';
				foreach($ttags as $current)
				{
					if($current != "" && $current != " ")
						$temp_array[] = $current;
				}
				$ttags = $temp_array;
			}
			return $ttags;
		}
	}
?>
