<!DOCTYPE html>
<html>
<head>
	<script type="text/javascript">
	function _clk(e)
	{
		let sql = e.currentTarget.nextElementSibling;
		if(sql.classList.contains("hidden"))
			sql.classList.remove("hidden");
		else
			sql.classList.add("hidden")
	}
	document.addEventListener("DOMContentLoaded", function(e){
		for(let ele of document.querySelectorAll(".blah"))
		{
			ele.addEventListener("click", _clk);
		}
	});
	</script>
	<style>
		.hidden
		{
			display: none;
		}
		.sql
		{
			padding: 8px;
			background-color: #CCC;
			font-family: monospace;
		}
	</style>
</head>
<body>
<?php
	set_time_limit(0);
	require "inv.header.php";
	$user = new user();
	if(!$user->gotpermission('is_admin'))
	{
		header('Location: index.php');
		exit;
	}

	$search = new search();
	$changes = [];
	$q = "SELECT * from $tag_index_table;";
	$result = $db->query($q) or die("Could not query tag index table '$tag_index_table'");
	if($result !== false)
	{
		$res = "";
		while($row = $result->fetch_assoc())
		{
//			$t = $db->real_escape_string(mb_strtolower(str_replace('%','',htmlentities($row['tag'], ENT_QUOTES, 'UTF-8'))));
//			$q = "select count(*) from $post_table where match(tags) against(' +\"$t\"' in boolean mode)>0.9";
//			$q = $search->prepare_tags($t);
//			$q = str_replace("id, image, directory, score, rating, tags, owner", "count(*)", $q);
			$t = $row['tag'];
			$q = "select count(*) from $post_table where match(tags) against(' +\" $t \"' in boolean mode)>0.9";
			$res = $db->query($q) or die("Failed to tally tag: ".$row['tag']."<br/>sql: $q");
			$real = $res->fetch_array()[0];
			if($real != $row['index_count'])
				$changes[] = [$row['tag'], $row['index_count'], $real, $q];
		}
	}

	print "Discrepancies:<br/><div id=\"tags\">";
	foreach($changes as $set)
	{
		print "<div class=\"blah\">".$set[0]."&nbsp;=>&nbsp;<span style='color:red;'>".$set[1]."</span> -- <span style='color:green'>".$set[2]."</span></div><div class=\"sql hidden\">".$set[3]."</div>
";
	}
	print "</div>";
	/*print "<style>
	[data-tt]:hover::after {
		display: block;
		content: attr(data-tt);
	}
	</style>";*/
?>
</body>
</html>
