<?php
	if(!defined('_IN_ADMIN_HEADER_'))
		die;
	
	$debug = false;

	if($debug)
	{
		echo "<strong>GET dump</strong><br/>";
		var_dump($_GET);
		echo "<br/>";
	}

	if(isset($_GET['action']))
	{
		if(isset($_GET['confirm']) && $_GET['confirm'] == 1)
		{
			if($_GET['action'] == "delete")
			{
				$alias = $db->real_escape_string(htmlentities($_GET['alias'], ENT_QUOTES, 'UTF-8'));
				$tag = $db->real_escape_string(htmlentities($_GET['tag'], ENT_QUOTES, 'UTF-8'));
				$query = "DELETE FROM $alias_table WHERE alias = '$alias' AND tag = '$tag';";
				if($debug)
				{
					echo "<h2>I will Delete</h2>
					<strong>SQL Query:</strong><br/>$query<br/>
					<a href='?page=alias_edit'>Back</a>";
				}
				else
				{
					$db->query($query) or die($db->error);
					if($db->affected_rows > 0)
						echo "<strong>Deletion successful!</strong><br/>
						<a href='?page=alias_edit'>Back</a>";
					else
						echo "<strong>Deletion failed!</strong><br/>
						<a href='?page=alias_edit'>Back</a>";
				}
			}
			else if($_GET['action'] == "edit")
			{
				$alias = $db->real_escape_string(htmlentities($_GET['alias'], ENT_QUOTES, 'UTF-8'));
				$tag = $db->real_escape_string(htmlentities($_GET['tag'], ENT_QUOTES, 'UTF-8'));
				$status = $db->real_escape_string(htmlentities($_GET['status'], ENT_QUOTES, 'UTF-8'));
				$query = "UPDATE $alias_table SET status = '$status' WHERE alias = '$alias' AND tag = '$tag';";
				if($debug)
				{
					echo "<h2>I will change status</h2>
					<strong>SQL Query:</strong><br/>$query<br/>
					<a href='?page=alias_edit'>Back</a>";
				}
				else
				{
					$db->query($query) or die($db->error);
					echo "Query affected ".$db->affected_rows." rows<br/>";
					if($status == "accepted")
					{
						$tagc = new tag();
						$query = "SELECT * FROM $post_table WHERE tags LIKE '% ".str_replace('%','\%',str_replace('_','\_',$alias))." %'";
						$result = $db->query($query) or die($db->error);
						while($row = $result->fetch_assoc())
						{
							$tags = explode(" ",$row['tags']);
							foreach($tags as $current)
								$tagc->deleteindextag($current);
							$tmp = str_replace(' '.$alias.' ',' '.$tag.' ',$row['tags']);
							$tags = implode(" ",$tagc->filter_tags($tmp,$tag,explode(" ",$tmp)));
							$tags = mb_trim(str_replace("  ","",$tags));
							$tags2 = explode(" ",$tags);
							foreach($tags2 as $current)
								$tagc->addindextag($current);
							$tags = " $tags ";
							$query = "UPDATE $post_table SET tags='$tags' WHERE id='".$row['id']."'";
							$db->query($query);
						}
					}
					echo "<a href='?page=alias_edit'>Back</a>";
				}
			}
		}
		else
		{
			if($debug && isset($_GET['confirm'])) echo "<br/><strong>Confirm is not true, but '".$_GET['confirm']."'</strong><br/>";
			if($_GET['action'] == "delete")
			{
				echo "<strong>Are you sure you want to delete the alias \"".$_GET['alias']."\" for the tag \"".$_GET['tag']."\"?</strong><br/>
				<a href=\"?page=alias_edit&action=delete&tag=".$_GET['tag']."&alias=".$_GET['alias']."&confirm=1\">Delete Alias</a><br/><br/>
				<a href='?page=alias_edit'>Cancel</a>";
			}
			else if($_GET['action'] == "edit")
			{
				echo "<form method='get' action='index.php' name='edit_form'>
				<input type='hidden' name='page' value='alias_edit'>
				<input type='hidden' name='action' value='edit'>
				<input type='hidden' name='confirm' value='1'>
				Search term: <input type='text' name='alias' value=\"".$_GET['alias']."\"><br/>
				Alias for: <input type='text' name='tag' value=\"".$_GET['tag']."\"><br/>
				Status
				<select name='status'>
					<option value='pending'";
				if($_GET['status'] == "pending") echo "selected='selected'";
				echo ">Pending</option>
					<option value='accepted'";
				if($_GET['status'] == "accepted") echo "selected='selected'";
				echo ">Accepted</option>
					<option value='rejected'";
				if($_GET['status'] == "rejected") echo "selected='selected'";
				echo ">Rejected</option>
				</select><br/>
				<input type='submit' value='submit'></form>";
			}
		}
	}
	else
	{
		echo "<div class='content'><table width='100%' border='0' class='highlightable'>
		<tr><th>Alias [What they search for!]</th><th>Tag [What it should be!]</th><th>Status</th><th>Actions</th></tr>";
		$query = "SELECT tag,alias,status FROM $alias_table";
		/*if($debug)
			echo "<strong>SQL Query:</strong><br/>$query<br/>";*/
		$result = $db->query($query);
		while($row = $result->fetch_assoc())
		{
			echo "<tr><td>".$row['alias']."</td><td>".$row['tag']."</td><td>".$row['status']."</td>".
			"<td><a href=\"?page=alias_edit&action=edit&alias=".$row['alias']."&tag=".$row['tag']."&status=".$row['status']."\">Edit</a>,&nbsp;".
			"<a href=\"?page=alias_edit&action=delete&alias=".$row['alias']."&tag=".$row['tag']."\">Delete</a></td></tr>";
		}
		if($result->num_rows <= 0)
			echo "<tr><td><h1>No aliases in Database.</h1></td></tr>";
		$result->free_result();
		echo "</table></div>";
	}
?>
