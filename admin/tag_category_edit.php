<?php
	if(!defined('_IN_ADMIN_HEADER_'))
		die;
	$debug = false;

	if(isset($_GET['tag']) && isset($_GET['category']))
	{
		$input = $db->real_escape_string(htmlentities($_GET['tag'], ENT_QUOTES, 'UTF-8'));
		$tags = explode(" ", $input);
		foreach($tags as $tag)
		{
			$query = "SELECT category from $tag_index_table WHERE tag = '$tag'";
			if($debug) echo "$query<br/>";
			$result = $db->query($query) or die($db->error);
			$current_category = $result->fetch_assoc()['category'];
			$result->free_result();
			if($debug) echo "Current category: $current_category<br/>";

			if($current_category == $_GET['category']) {
				echo "Tag \"$tag\" already belongs to chosen category<br/>";
				continue;
			}


			$new_category = urlencode($db->real_escape_string($_GET['category']));
			$query = "UPDATE $tag_index_table SET category='$new_category' WHERE tag='$tag'";
			if($debug)
			{
				echo "$query<br/>";
			} else {
				$db->query($query) or die("Error while changing tag category<br/>Error: ".$db->error);
				if($db->affected_rows > 0)
				{
					$tclass = new tag();
					$query = "UPDATE $tag_category_table SET tag_count = tag_count - 1 WHERE category_name = '$current_category'";
					$db->query($query) or die($db->error);
					$query = "UPDATE $tag_category_table SET tag_count = tag_count + 1 WHERE category_name = '$new_category'";
					$db->query($query) or die($db->error);
					echo "Tag category change for \"$tag\" successfull<br/>";
				} else {
					echo "Tag category change for \"$tag\" <strong>unsucessful!</strong><br/>";
				}
			}
		}
		echo "<a href='?page=tag_category_change'>Go Back</a>";
	}
	else
	{
		$query = "SELECT category_name FROM `$tag_category_table`";
		$result = $db->query($query);

		echo "<form method='get' action='index.php'>
		<input type='hidden' name='page' value='tag_category_change'>
		<span>If changing multiple tags, separate them with a space</span><br/>
		Tag name: <input type='text' name='tag'><br/>
		Category <select name='category'><option value='generic' selected='selected'>Generic</option>";

		while($row = $result->fetch_assoc())
		{
			print "<option value='".$row['category_name']."'>".$row['category_name']."</option>";
		}
		echo "</select><br/><input type='Submit' name='Submit' value='Submit'>";
		$result->free_result();
	}
	$db->close();
?>
</table></div>
