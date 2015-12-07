<?php
	if(!defined('_IN_ADMIN_HEADER_'))
		die;
	if(!isset($_GET['action']) || $_GET['action'] == '')
	{
		include "tagOpsForm.html";
		exit;
	}
	
	$debug = true;
	if($_GET['action'] == 'none')
		$action = 'none';
	else if($_GET['action'] === 'remove' || $_GET['action'] === 'replace' || $_GET['action'] === 'add')
		$action = $_GET['action'];
	$tags = explode(" ", $db->real_escape_string($_GET['tags']));
?>
