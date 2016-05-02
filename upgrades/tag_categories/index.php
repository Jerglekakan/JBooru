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
			$tclass = new tag();

			//Add category to tag category table and change category for appropriate tags
			foreach($special_tags as $val) {
				if($val['tag'] == "generic") continue;
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

			//Take prefix off of all tags that have one
			$query = "SELECT tag from $tag_index_table WHERE SUBSTR(tag, 1, $l) = '$t"."_'";
			$result = $db->query($query) or die($db->error);
			$tmp_arr = array();
			while($row = $result->fetch_assoc())
			{
				$tmp_arr[] = $row['tag'];
			}
			$result->free_result();
			foreach($tmp_arr as $r) {
				//Replace all occurances of the old tag with the new one
				$newtag = substr($r, $l);
				//Don't touch posts that contain the new tag already
				$query = "UPDATE $post_table SET tags = REPLACE(tags, ' $r ', ' $newtag ') WHERE tags LIKE '% $r %' AND tags NOT LIKE '% $newtag %'";
				//Include posts that already have the new tag
				//$query = "UPDATE $post_table SET tags = REPLACE(tags, ' $r ', ' $newtag ') WHERE tags LIKE '% $r %' AND tags NOT LIKE '% $newtag %'";
				echo "$r ==> $newtag<br/>$query<br/>";
				$db->query($query) or die($db->error);
				$qt = $db->affected_rows;
				echo "Affected rows: $qt<br/>";
				if($qt > 0)
				{
					$tclass->deleteindextag($r, $qt);
					$tclass->addindextag($newtag, $qt);
					$query = "UPDATE $tag_index_table SET category = '$t' WHERE tag = '$newtag' AND category != '$t'";
					echo "Setting category to '$t' for new tag '$newtag'<br/><br/>";
					$db->query($query) or die($db->error);
					if($db->affected_rows > 0)
					{
						$query = "UPDATE $tag_category_table SET tag_count = tag_count + 1 WHERE category_name='$t'";
						$db->query($query) or die($db->error);
						$query = "UPDATE $tag_category_table SET tag_count = tag_count - 1 WHERE category_name='generic'";
						$db->query($query) or die($db->error);
					}

					//delete the old tag from any post that still has it
					$without = substr($r, $l);
					$query = "UPDATE $post_table SET tags = REPLACE(tags, ' $r ', ' ') WHERE tags LIKE '% $r %' AND tags LIKE '% $without %'";
					$db->query($query) or die($db->error);
					$tclass->deleteindextag($r, $db->affected_rows);
				}

			}
		}
	}
?>
