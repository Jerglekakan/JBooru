<?php
echo '<html><head>
<link rel="stylesheet" type="text/css" media="screen" href="'.$site_url.'default.css" title="default" />
<link rel="stylesheet" type="text/css" media="screen" href="'.$site_url.'categories.css" title="default" />
<title></title>
<meta http-equiv="content-type" content="text/html; charset=utf-8" />
</head>

<body>
	<div id="header">
		<h2 id="site-title"><a href="'.$site_url.'admin/">Moderation Tasks</a> / <a href="'.$site_url.'index.php?page=post&s=list"><i>View Board</i></a></h2><span style="color: #999999; padding-left: 20px;">Hover over links for more info.</span><br><br><br>
</div>
		<div id="content">
		<div id="post-list">
		<div class="sidebar">

		<h5>Moderator Tools:</h5><br />
		<ul id="tag-sidebar">
		<li><a href="?page=reported_posts" title="View the reported posts by users. I hope you do not see many here...">Reported Posts</a></li>
		<li><a href="?page=reported_comments" title="View the reported comments as well as other info related to the comment.">Reported Comments</a></li>
		<li><a href="?page=alias" title="Approve or reject pending aliases.">Alias Approve/Reject</a></li>
		<li><a href="?page=tag_categories" title="Add and Remove tag categories">Add/Delete Tag Categories</a></li>
		<li><a href="?page=tag_category_change" title="Change a tag\'s category">Edit Tag\'s Category</a></li>
		</ul>
		<br />';

if($user->gotpermission('is_admin'))
{
	echo '<h5>Admin Tools:</h5><br />
	<a href="?page=tag_ops" title="Bath operations on tags">Tag Operations</a><br/>
	<a href="?page=alias_edit" title="Edit and delete aliases">Edit/Delete Alias</a><br/>
	<a href="?page=remove_posts" title="Remove multiple posts">Remove Posts</a><br/><br/>
	<h6>User Group Tools:</h6>
	<ul id="tag-sidebar">
	<li><a href="?page=add_group" title="Add a user group.">Create New Group</a></li>
	<li><a href="?page=edit_group" title="Edit Group Permissions.">Edit Group Permissions</a></li>
	</ul>';
}
?>
</div></div>
