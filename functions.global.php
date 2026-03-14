<?php
	function install_query($mysql_db,$table,$array,$key = '')
	{
		$query = 'CREATE TABLE IF NOT EXISTS '.$mysql_db.'.'.$table.'(';
		$max = count($array);
		$count = 0;
		foreach($array as $current)
		{
			if($max <= 1)
				$query .= $current;
			else
			{
				if($count < ($max-1))
					$query .= $current.',';
				else
					$query .= $current;
				$count++;
			}
		}
		if($key != '')
			$query .= ',PRIMARY KEY('.$key.')';
		$query .= ')';
		print $query.'<br />';
		$db->query($query) or print $db->error.'<br />';
		foreach($array as $current) 
		{
			$query = "ALTER TABLE $mysql_db.$table ADD COLUMN $current";
			print $query.'<br />';
			$db->query($query) or print $db->error.'<br />';
		}
		if($key != '')
		{
			$query = "ALTER TABLE $mysql_db.$table ADD PRIMARY KEY($key)";
			print $query.'<br />';
			$db->query($query) or print $db->error.'<br />';
		}
	}
?>
