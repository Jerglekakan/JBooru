<?php
	require "../../inv.header.php";
	$user = new user();
	if($user->gotpermission('is_admin'))
	{
		if(!isset($tag_cateory_table))
			$tag_category_table = 'tag_categories';

		$query = "CREATE TABLE if not exists `$tag_category_table` (
		`category_name` VARCHAR(255),
		`tag_count` bigint(20) UNSIGNED DEFAULT 0,
		PRIMARY KEY (category_name)
		) ENGINE=InnoDB DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC
		";
		$db->query($query) or die($db->error);
		$query = "INSERT INTO `$tag_category_table` (category_name, tag_count) VALUES('generic',(select count(*) from `$tag_index_table`))";
		echo "First query: $query<br/>";
		$db->query($query) or die($db->error);
		echo "First query finished, ".$db->affected_rows." rows affected<br/><br/>";

		$query = "ALTER TABLE `$tag_index_table` ADD COLUMN category VARCHAR(255) NOT NULL DEFAULT 'generic'";
		//"ADD FOREIGN KEY catFK (category) REFERENCES `$tag_category_table`(category_name) ON DELETE SET NULL ON UPDATE CASCADE";
		echo "Second query: $query<br/>";
		$db->query($query) or die($db->error);
		echo "Second query finished, ".$db->affected_rows." rows affected<br/><br/>";

		if(isset($special_tags)) {
			foreach($special_tags as $val) {
				$t = $val['tag'];
				$l = strlen($t)+1;
				$sub = "select count(*) from `$tag_index_table` where substr(cast(`$tag_index_table`.tag as char),1,$l) = '$t"."_'";
				$query = "INSERT INTO `$tag_category_table` (category_name, tag_count) VALUES('$t',($sub))";
				echo "First query (foreach): $query<br/>";
				$db->query($query) or die($db->error);
				echo "First query (foreach) finished, ".$db->affected_rows." rows affected<br/><br/>";

				$wc = "SUBSTR(CAST(`$tag_index_table`.tag as char),1,$l) = '$t"."_'";
				$query = "UPDATE `$tag_index_table` SET category = '$t' WHERE $wc";
				echo "Second query (foreach): $query<br/>";
				$db->query($query) or die($db->error);
				echo "Second query (foreach) finished, ".$db->affected_rows." rows affected<br/><br/>";
			}
		}
	}
?>
