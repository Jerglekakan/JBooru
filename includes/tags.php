<?php
	require "includes/header.php";
	header("Cache-Control: no-store, no-cache");
	header("Pragma: no-cache");

	$debug = false;
	$order_values = array(1 => "asc", 2 => "desc");
	$sort_values = array("name" => "tag", "count" => "index_count", "category" => "category");

	if($debug)
	{
		print_r($_GET);
		echo "<br/>";
	}

	//Parse inputs
		if(isset($_GET['results']) && ((int) $_GET['results']) >= 0)
			$tag_limit = (int) $db->real_escape_string($_GET['results']);
		else
			$tag_limit = 100;
		if(isset($_GET['sort']) && array_key_exists($_GET['sort'], $sort_values))
			$order_by = $db->real_escape_string($sort_values[$_GET['sort']]);
		else
			$order_by = "tag";
		if(isset($_GET['order']) && in_array($_GET['order'], $order_values))
			$query_order = $db->real_escape_string($_GET['order']);
		else
			$query_order = "ASC";
		if(isset($_GET['tags']) && $_GET['tags'] != "")
		{
			$where_clause = "";
			$negate = "";
			$search_tags = urldecode($db->real_escape_string($_GET['tags']));
			$tmp_arr = explode(" ", $search_tags);
			foreach($tmp_arr as $cur_tag)
			{
				$ttag = str_replace("_", "\_", $cur_tag);
				$ttag =	str_replace("*", "%", "$ttag");
				$ttag = str_replace("?", "_", "$ttag");
				if($where_clause == "")
					$where_clause .= "(".$sort_values['name']." LIKE '%$ttag%'";
				else
					$where_clause .= " AND ".$sort_values['name']." LIKE '%$ttag%'";
			}
			if($negate != "")
				$where_clause .= $negate.")";
			else
				$where_clause .= ")";
		}
		else
		{
			$where_clause = "";
		}
		if($tag_limit > 0 && isset($_GET['pg']) && $_GET['pg'] > 1)
			$select_ofst = ($_GET['pg'] - 1) * $tag_limit;
		else
			$select_ofst = 0;
		if(isset($_GET['category']) && $_GET['category'] != "all" && $_GET['category'] != "All")
		{
			$cat = $db->real_escape_string($_GET['category']);
			if($where_clause == "")
				$where_clause = "category='$cat'";
			else
				$where_clause .= " AND category='$cat'";
		}
		else
			$cat = "all";
	if($debug)
	{
		echo "<strong>Order:</strong>$query_order<br/>
		<strong>Sorting by:</strong>$order_by<br/>
		<strong>Tags per page:</strong>$tag_limit<br/>";
	}



	//Build query
		$sql_string = "SELECT tag, index_count, category FROM $tag_index_table";
		if($where_clause != "")
			$sql_string .= " WHERE ".$where_clause;
		if($query_order != "")
			$sql_string .= " ORDER BY $order_by $query_order";
		if($tag_limit > 0)
			$sql_string .= " LIMIT ".$select_ofst.",".$tag_limit;
		$sql_string .= ";";
		if($debug)
			echo "Query:".$sql_string."<br/>";



	$order_field = "<h4>Order</h4><select name='order'><option value='asc'";
	if($query_order != "desc") $order_field .= "selected='selected'";
	$order_field .= ">Ascending</option><option value='desc'";
	if($query_order == "desc") $order_field .= "selected='selected'";
	$order_field .= ">Descending</option></select><br/>";


	$sort_field = "<h4>Sort</h4><select name='sort'><option value='name'";
	if($order_by == "tag") $sort_field .= "selected='selected'";
	$sort_field .= ">Name</option><option value='count'";
	if($order_by == "index_count") $sort_field .= "selected='selected'";
	$sort_field .= ">Count</option><option value='category'";
	if($order_by == "category") $sort_field .= "selected='selected'";
	$sort_field .= ">Category</option></select><br/>";


	$second_string = "SELECT category_name from `$tag_category_table`";
	$result = $db->query($second_string);
	$category_field = "<h4>Category</h4><select name='category'><option value='all'";;
	if(!isset($_GET['category']) || $_GET['category'] == "all") $category_field .= " selected='selected'";
	$category_field .= ">All</option><option value='generic'";
	if(isset($_GET['category']) && $_GET['category'] == "generic") $category_field .= " selected='selected'";
	$category_field .= ">Generic</option>";
	while($row = $result->fetch_assoc()) {
		if($row['category_name'] == 'generic') continue;
		$category_field .= "<option value='".$row['category_name']."'";
		if(isset($_GET['category']) && $_GET['category'] == $row['category_name'])
			$category_field .= "selected='selected'";
		$category_field .= ">".$row['category_name']."</option>";
	}
	$result->free_result();
	$category_field .= "</select><br/>";


	echo "<form method='get' action=index.php?page=tags&s=list>
		<input type='hidden' value='tags' name='page'></input>
		<input type='hidden' value='list' name='s'></input>
		<h4>Name</h4><input id='name' type='text' name='tags' value='".str_replace('+', ' ', $_GET['tags'])."'></input><br/>
		$order_field
		$sort_field
		$category_field
		<h4>Results per page</h4><input type='text' name='results' value='$tag_limit'></input><br/>
		<input type='submit' value='Search'></input>
	</form>";

	echo "<table class='highlightable' width='100%'>
		<tr>
		<th>Tag</th>
		<th>Count</th>
		<th>Category</th>
		</tr>";
	$result = $db->query($sql_string);
	while($row = $result->fetch_assoc())
	{
		echo "<tr>
			<td>".$row['tag']."</td>
			<td>".$row['index_count']."</td>
			<td>".$row['category']."</td>
		</tr>";
	}
	$result->free_result();
	echo "</table>
	<div id='paginator'>";

	//Pagination vars
		$cnt_sql = "select count(*) from $tag_index_table";
		if($where_clause != "") $cnt_sql .= " WHERE $where_clause;";
		else $cnt_sql .= ";";
		$result = $db->query($cnt_sql);
		if($debug) echo "<strong>Tag count query:</strong><br/>$cnt_sql<br/>";
		$tag_count = $result->fetch_row()[0];
		$result->free_result();
		//$tag_count = 1500;
		$page_count = ceil($tag_count/$tag_limit);
		if(!isset($_GET['pg']) || $_GET['pg'] <= 1)//First Page
		{
			$cur_page = $istart = 1;
			if($cur_page+11 >= $page_count+1)
				$istop = $page_count+1;
			else
				$istop = $cur_page+11;
		}
		else if($_GET['pg'] >= $page_count)//Last Page
		{
			$cur_page = $page_count;
			if($cur_page-10 <= 1)
				$istart = 1;
			else
				$istart = $cur_page-10;
			$istop = $cur_page+1;
		}
		else//Somewhere in-between
		{
			$cur_page = $_GET['pg'];
			if($cur_page-5 <= 1)
				$istart = 1;
			else
				$istart = $cur_page-5;
			if($cur_page+6 >= $page_count+1)
				$istop = $page_count+1;
			else
				$istop = $cur_page+6;
		}
		$base_link = "?page=tags";
		if(isset($_GET['order'])) $base_link .= "&order=$query_order";
		if(isset($_GET['sort'])) $base_link .= "&sort=$order_by";
		if(isset($_GET['results'])) $base_link .= "&results=$tag_limit";
		if(isset($_GET['category'])) $base_link .= "&category=$cat";
		if(isset($_GET['tags']))
			$base_link .= "&tags=".str_replace(' ', '+', htmlentities($_GET['tags'], ENT_QUOTES, 'UTF-8'));

	if($debug)
		echo "<strong>Tag count:</strong>$tag_count<br/>
		<strong>Page count:</strong>$page_count<br/>
		<strong>istart:</strong>$istart<br/>
		<strong>istop:</strong>$istop<br/>";

	//pages
	if($cur_page > 1)
		echo "<a alt='first page' href='$base_link'><<</a>
		<a alt='back' href='$base_link&pg=".($cur_page-1)."'><</a>";
	for($i=$istart; $i<$istop; $i++)
	{
		if($i == $cur_page)
			echo "<b>$i</b>";
		else
			echo "<a href='$base_link&pg=$i'>$i</a>";
	}
	if($cur_page < $page_count)
		echo "<a alt='next' href='$base_link&pg=".($cur_page+1)."'>></a>
		<a alt='last page' href='$base_link&pg=$page_count'>>></a>";

	echo "</div>";
?>
