<?php
	$s = $_GET['s'];

	switch ($s) {
		case "add":
			require "includes/post_add.php";
			break;
		case "view":
			require "includes/post_view.php";
			break;
		case "list":
			require "includes/post_list.php";
			break;
		case "vote":
			require "includes/post_vote.php";
			break;
		case "random":
			require "post_random.php";
			break;
	}
?>
