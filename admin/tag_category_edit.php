<?php
	if(!defined('_IN_ADMIN_HEADER_'))
		die;


	if(isset($_GET['tag']) && isset($_GET['category']))
	{
		$query = "UPDATE $tag_index_table SET category='".$db->real_escape_string($_GET['category'])."' WHERE tag='".$db->real_escape_string($_GET['tag'])."'";
		$db->query($query) or die("Error while changing tag category<br/>Error: ".$db->error);
		echo "Tag category changed successfully<br/>
		<a href='?page=tag_category_change'>Go Back</a>";
	}
	else
	{
		$query = "SELECT category_name FROM `$tag_category_table`";
		$result = $db->query($query);

		echo "<form method='get' action='index.php'>
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
