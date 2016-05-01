<?php
	if(!defined('_IN_ADMIN_HEADER_'))
		die;


	if(isset($_GET['action'])) {
		switch($_GET['action']) {
			case 'add':
				if(!isset($_GET['confirm'])) {
					echo "<form method='get' action='index.php'>
					<input type='hidden' name='page' value='tag_categories'>
					<input type='hidden' name='action' value='add'>
					<input type='hidden' name='confirm' value='1'>
					Category name <input type='text' name='category'/><br/>
					<input type='Submit' name='Submit' value='Submit'/>";
				} else if($_GET['confirm'] == 1) {
					$statement = $db->prepare("INSERT INTO `$tag_category_table`(category_name,tag_count) VALUES (?,0)");
					$statement->bind_param('s', $_GET['category']);
					$statement->execute();
					header("Location: $site_url/admin/?page=tag_categories");
				}
			break;
			case 'delete':
				if(!isset($_GET['confirm'])) {
					echo "Are you sure you want to delete the category '".$_GET['category']."'?<br/>
					<form method='get' action='index.php'>
					<input type='hidden' name='page' value='tag_categories'>
					<input type='hidden' name='action' value='delete'>
					<input type='hidden' name='category' value='".$_GET['category']."'>
					No <input type='radio' name='confirm' value='0'/><br/>
					Yes <input type='radio' name='confirm' value='1'/><br/>
					<input type='Submit' name='Submit' value='Submit'/>";
				} else if($_GET['confirm'] == 0) {
					header("Location: $site_url");
				} else {
					echo "<h2>Yet to implement category deletion</h2><a href='".$site_url."admin'>Go Back</a>";
					//Prepare SQL stuff
					//$statement = $db->prepare("DELETE FROM `$tag_category_table` WHERE category_name=?");
					//$statement->bind_param('s', $_GET['category']);
				}
			break;
		}
	} else {
		$query = "SELECT category_name, tag_count FROM `$tag_category_table`";
		$result = $db->query($query);
		print '<div class="content"><table width="100%" border="0" class="highlightable">
		<tr><th>Category name</th>
		<th>With _!/$*STYLE*\/$%</th>
		<th>Tags in category</th>
		<th>Actions</th></tr>';
		while($row = $result->fetch_assoc()) {
			print '<tr><td>'.$row['category_name'].'</td><td>'.$row['category_name'].'</td><td>'.$row['tag_count'].'</td>';
			if($row['category_name'] != 'generic')
				echo '<td><a href="?page=tag_categories&action=delete&category='.$row['category_name'].'">Delete</a></td>';
			echo '</tr>';
		}
		echo "<tr><td><a href='?page=tag_categories&action=add'>Add category</a></td></a>";
		$result->free_result();
	}
	$db->close();
?>
</table></div>
