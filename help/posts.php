<?php
	require "../inv.header.php";
	require "../includes/header.php";
?>

<div id="content">
<div class="help">
  <h1>Help: Posts</h1>
  <p>A post represents a single file that's been uploaded. Each post can have several tags, comments, and notes. If you have an account, you can also add a post to your favorites.</p>
  
<div class="section">
	<h4>Search</h4>
	<p>Searching for posts is straightforward. Simply enter the tags you want to search for, separated by spaces. For example, searching for <code>original panties</code> will return every post that has both the original tag <strong>AND</strong> the panties tag.</p>

	<h5>Search parameters</h5>
	<p>In addition to searching for tags, you can search by the following post properties:</p>
	<dl>

		<dt>parent</dt>
		<dd>Add <code>parent:&lt;id&gt;</code> to show all of the given post's children. A post's ID is displayed beneath its title.</dd>

		<dt>rating</dt>
		<dd><code>rating:&lt;rating&gt;</code>. See <a href="ratings.php">ratings help page</a>.</dd>

		<dt>user/uploader</dt>
		<dd>Add <code>user:&lt;username&gt;</code> to show all posts uploaded by the given user.</dd>

		<dt>score</dt>
		<dd>Add <code>score:&lt;number&gt;</code> to search for posts with that exact score. Adding &gt; or &lt; after the colon will show posts with a score above or below the given value.</dd>
	</dl>
</div>
  
<div class="section">
	<h4>Tag List</h4>
	<strong>Example</strong><br/>
	<img src="taglinks.png" style="border-color:#ccc;border-width:9px;border-style:solid;" />
    <p>In both the listing page and the show page you'll notice a list of tag links with characters next to them. Here's an explanation of what the characters do:</p>
    <dl>
      
      <dt>plus (+)</dt>
      <dd>This adds the tag to the current search.</dd>

      <dt>minus (&ndash;)</dt>
      <dd>This adds the negated tag to the current search.</dd>
           
      <dt>950</dt>
      <dd>The number next to the tag represents how many posts there are. This isn't always the total number of posts for that tag. It may be slightly out of date as cache isn't always refreshed.</dd>

    </dl>
    <p>When you're not searching for a tag, by default the tag list will show the last few tags added to the database. When you are searching for tags, the tag list will show related tags, alphabetically.</p>
</div>
  
</div></div></body></html>
