<?php
	require "header.php";
	if(!defined('_IN_ADMIN_HEADER_'))
		die;

	if(isset($_GET['page']))
	{
		if($_GET['page'] == "alias")
				require "alias.php";
		else if($_GET['page'] == "alias_edit")
				require "alias_edit.php";
		else if($_GET['page'] == "reported_posts")
				require "reported_posts.php";
		else if($_GET['page'] == "reported_comments")
				require "reported_comments.php";
		else if($_GET['page'] == "add_group")
				require "add_group.php";
		else if($_GET['page'] == "edit_group")
				require "edit_group_permission.php";
		else if($_GET['page'] == "ban_user")
				require "ban_user.php";					
		else if($_GET['page'] == "remove_posts")
				require "remove_posts.php";
		else if($_GET['page'] == "tag_ops")
				require "tag_ops.php";
		else if($_GET['page'] == "tag_categories")
				require "tag_categories.php";
		else if($_GET['page'] == "tag_category_change")
				require "tag_category_edit.php";
		else if($_GET['page'] == "batch_add")
				require "batch_add.php";
	}
	else
	{
?>
		<style>
			.phpprop:hover {
				text-decoration: underline;
			}
		</style>

		<h2>Server settings</h2>
		<span>System Timezone: <?php echo file_get_contents("/etc/timezone"); ?></span>
		<br/><br/>

		<h2>Config settings</h2>
		<span>Default timezone: <?php echo date_default_timezone_get() ?></span>
		<br/><br/>

		<h2>PHP settings</h2>
		<span class="phpprop" title="file_uploads">Uploads</span>: <strong><?php echo (ini_get("file_uploads") == "1") ? "Enabled" : "Disabled" ?></strong><br/><br/>

		<span class="phpprop" title="max_execution_time">Max script execution time:</span> <?php echo (ini_get("max_execution_time") == "0") ? "Unlimited" : ini_get("max_execution_time") ?><br/>
		<span class="phpprop" title="max_input_time">Max input time</span>: <?php echo (ini_get("max_input_time") == "-1") ? "Unlimited" : ini_get("max_input_time") ?><br/><br/>

		<span class="phpprop" title="memory_limit">Memory limit</span>: <?php echo ini_get("memory_limit"); ?><br/>
		<span class="phpprop" title="upload_max_filesize">Max upload size</span>: <?php echo ini_get("upload_max_filesize"); ?><br/>
		<span class="phpprop" title="post_max_size">Max POST size</span>: <?php echo ini_get("post_max_size"); ?><br/><br/>

		<h2>PHP settings (background)</h2>
<?php
		$str = "";
		exec("php -f stats.php", $str);
		echo implode("", $str);
	}
?>
<br></body></html>
