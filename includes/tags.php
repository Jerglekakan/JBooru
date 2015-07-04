<?php
	/*TODO:
	  *Show links to other "pages" when not all tags are being displayed
	  *Actually change what tags are displayed based on the contents of the name field
*/
	require "includes/header.php";
	header("Cache-Control: no-store, no-cache");
	header("Pragma: no-cache");

	$debug = false;
	$order_values = array(1 => "asc", 2 => "desc");
	$sort_values = array("name" => "tag", "count" => "index_count");
	if(isset($_GET['results']) && ((int) $_GET['results']) > 0)
		$tag_limit = (int) $_GET['results'];
	else
		$tag_limit = 0;
	if(isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_values))
		$order_by = $sort_values[$_GET['sort']];
	else
		$order_by = "tag";
	if(isset($_GET['order']) && array_key_exists($_GET['order'], $order_values))
		$query_order = $_GET['order'];
	else
		$query_order = "ASC";

	$sql_string = "SELECT tag, index_count FROM tag_index ORDER BY ".$order_by." ".$query_order;
	if($tag_limit > 0)
		$sql_string .= " LIMIT ".$tag_limit;
	$sql_string .= ";";
	if($debug)
		echo "Query:".$sql_string."<br/>";

	echo "<form method='get' action=index.php?page=tags&s=list>
		<input type='hidden' value='tags' name='page'></input>
		<input type='hidden' value='list' name='s'></input>
		<h4>Name</h4><input id='name' type='text' name='tags'></input><br/>
		<h4>Order</h4><select name='order'>
				<option selected='selected' value='asc'>Ascending</option>
				<option value='desc'>Descending</option>
		</select><br/>
		<h4>Sort</h4><select name='sort'>
				<option selected='selected' value='name'>Name</option>
				<option value='count'>Count</option>
		</select><br/>
		<h4>Results per page</h4><input type='text' name='results' value='0'></input><br/>
		<input type='submit' value='Search'></input>
	</form>";

	echo "<table class='highlightable' width='100%'>
		<tr>
		<th>Tag</th>
		<th>Count</th>
		</tr>";
	$result = $db->query($sql_string);
	while($row = $result->fetch_assoc())
	{
		echo "<tr>
			<td>".$row['tag']."</td>
			<td>".$row['index_count']."</td>
		</tr>";
	}
	echo "</table>";
?>
