<?php
	//number of images/page
	$limit = 20;
	//number of pages to display
	$page_limit = 10;
	global $enable_cache;
	require "includes/header.php";
	$cache = new cache();
	$user = new user();
	$domain = $cache->select_domain();
?>
<script type="text/javascript">
//<![CDATA[
var posts = {}; var pignored = {}; var showing_IDs = false;
//]]>

function toggleIDs() {
	var thing, elements, i;
	if(showing_IDs) {
		elements = document.getElementsByClassName('postID');
		for(i = 0; i < elements.length; i++) {
			elements[i].style.visibility = "hidden";
		}
		showing_IDs = false;
	} else {
		elements = document.getElementsByClassName('postID');
		for(i = 0; i < elements.length; i++) {
			elements[i].style.visibility = "visible";
		}
		showing_IDs = true;
	}
}

function copyMe(node) {
	var rng = document.createRange();
	rng.selectNode(node);
	window.getSelection().addRange(rng);
	document.execCommand('copy');
}
</script>
<div id="content"><div id="post-list">
<div class="sidebar">
<div class="space">
<h5>Search</h5>
<form action="index.php?page=search" method="post">
<input id="tags" name="tags" size="20" type="text" value="<?php if(isset($_GET['tags']) && $_GET['tags'] != "all"){ print str_replace("%",'',str_replace("'","&#039;",str_replace('"','&quot;',$_GET['tags'])));}?>" />
<br /><input name="commit" style="margin-top: 3px; background: #fff; border: 1px solid #dadada; width: 172px;" type="submit" value="Search" />
</form>
<small>(Supports wildcard *)</small>
</div>
<div class="space"></div>
<div id="tag_list">
<h5>Tags</h5>
<ul>
<?php
	/*Begining of tag listing on left side of site.
	First let's get the current page we're on.	*/
	if(isset($_GET['pid']) && $_GET['pid'] != "" && is_numeric($_GET['pid']) && $_GET['pid'] >= 0)
		$page = $db->real_escape_string($_GET['pid']);
	else
		$page = 0;
	$search = new search();
	//No tags  have been searched for so let's check the last_update value to update our main page post count for parent posts. Updated once a day.
	if(!isset($_GET['tags']) || isset($_GET['tags']) && $_GET['tags'] == "all" || isset($_GET['tags']) && $_GET['tags'] == "")
	{
		$should_cache_response = false;
		$query = "SELECT pcount, last_update FROM $post_count_table WHERE access_key='posts'";
		$result = $db->query($query);
		$row = $result->fetch_assoc();
		$numrows = $row['pcount'];
		$date = date("Ymd");		
		if($row['last_update'] < $date)
		{
			$query = "SELECT COUNT(id) FROM $post_table WHERE parent = '0'";
			$result = $db->query($query);
			$row = $result->fetch_assoc();
			$numrows = $row['COUNT(id)'];
			$query = "UPDATE $post_count_table SET pcount='".$row['COUNT(id)']."', last_update='$date' WHERE access_key='posts'";
			$db->query($query);			
		}
	}
	else
	{
		//Searched some tag, deal with page caching of html files.
		$misc = new misc();
		$should_cache_response = true;
		$tags = $db->real_escape_string(str_replace("%",'',mb_trim(htmlentities($_GET['tags'], ENT_QUOTES, 'UTF-8'))));		
		$tags = explode(" ",$tags);
		$tag_count = count($tags);
		$new_tag_cache = urldecode($tags[0]);
		if(strpos(strtolower($new_tag_cache),"parent:") === false && strpos(strtolower($new_tag_cache),"user:") === false && strpos(strtolower($new_tag_cache),"rating:") === false && strpos($new_tag_cache,"*") === false)
			$new_tag_cache = $misc->windows_filename_fix($new_tag_cache);
		if($page == 0)
			$pagenum = 1;
		else
			$pagenum = ($_GET['pid']/$limit)+1;
		if($tag_count > 1 || !$enable_cache || !is_dir($main_cache_dir."search_cache/$new_tag_cache/") || !file_exists($main_cache_dir."search_cache/$new_tag_cache/$pagenum.html") || strpos(strtolower($new_tag_cache),"all") !== false || strpos(strtolower($new_tag_cache),"user:") !== false || strpos(strtolower($new_tag_cache),"rating:") !== false || substr($new_tag_cache,0,1) == "-" || strpos(strtolower($new_tag_cache),"*") !== false || strpos(strtolower($new_tag_cache),"parent:") !== false)
		{
			if($enable_cache && !is_dir($main_cache_dir."search_cache/"))
				@mkdir($main_cache_dir."search_cache");
			$query = $search->prepare_tags(implode(" ",$tags));
			$result = $db->query($query) or die($db->error);
			$numrows = $result->num_rows;
			$result->free_result();
			if($tag_count > 1 || strtolower($new_tag_cache) == "all" || strpos(strtolower($new_tag_cache),"user:") !== false || strpos(strtolower($new_tag_cache),"rating:") !== false || substr($new_tag_cache,0,1) == "-" || strpos(strtolower($new_tag_cache),"*") !== false || strpos(strtolower($new_tag_cache),"parent:") !== false)
				$should_cache_response = false;
			else
			{
				if($enable_cache && !is_dir($main_cache_dir."search_cache/$new_tag_cache/"))
					@mkdir($main_cache_dir."search_cache/$new_tag_cache/");				
				$should_cache_response = true;
			}
		}
		else
		{
			if($enable_cache && !is_dir($main_cache_dir."search_cache/"))
				mkdir($main_cache_dir."search_cache");
			$tags = $new_tag_cache;
			if(isset($_GET['pid']) && is_numeric($_GET['pid']) && $_GET['pid'] > 0)
				$pagenum = ($_GET['pid']/$limit)+1;
			else
				$pagenum = 0;
				
			$cache = new cache();
			$should_cache_response = true;
			if(is_dir($main_cache_dir."search_cache/$tags/") && file_exists($main_cache_dir."search_cache/$tags/$pagenum.html"))
			{
				$data = $cache->load("search_cache/$tags/$pagenum.html");
				echo $data;
				$numrows = 1;
				$should_cache_response = false;			
			}
		}
	}
	//No images found
	if($numrows == 0)
		print '</ul></div></div><div class="content"><div><h1>Nobody here but us chickens!</h1>';
	else
	{
		if(isset($_GET['pid']) && $_GET['pid'] != "" && is_numeric($_GET['pid']) && $_GET['pid'] >= 0)
			$page = $db->real_escape_string($_GET['pid']);
		else
			$page = 0;
		if(!isset($_GET['tags']) || isset($_GET['tags']) && $_GET['tags'] == "all" || isset($_GET['tags']) && $_GET['tags'] == "")
			//$query = "SELECT id, image, directory, score, rating, tags, owner FROM $post_table WHERE parent = '0' ORDER BY id DESC LIMIT $page, $limit";
			$query = "SELECT id, image, directory, score, rating, tags, owner FROM $post_table ORDER BY id DESC LIMIT $page, $limit";
		else
		{
			if($should_cache_response || isset($tag_count) && $tag_count > 1 || strpos(strtolower($new_tag_cache),"user:") !== false || strpos(strtolower($new_tag_cache),"rating:") !== false || substr($new_tag_cache,0,1) == "-" || strpos(strtolower($new_tag_cache),"*") !== false || strpos(strtolower($new_tag_cache),"parent:") !== false)
				$query = $query." LIMIT $page, $limit";			
		}
		if(!isset($_GET['tags']) || $should_cache_response || isset($tag_count) && $tag_count > 1 || strtolower($_GET['tags']) == "all" || strpos(strtolower($new_tag_cache),"user:") !== false || strpos(strtolower($new_tag_cache),"rating:") !== false || substr($new_tag_cache,0,1) == "-" || strpos(strtolower($new_tag_cache),"*") !== false || strpos(strtolower($new_tag_cache),"parent:") !== false)
		{
			if($should_cache_response && $enable_cache)
				ob_start();
						
			$gtags = array();
			$images = '';
			$tcount = 0;
			$result = $db->query($query) or die($db->error);
			//Limit main tag listing to 40 tags. Keep the loop down to the minimum really.
			while($row = $result->fetch_assoc())
			{
				$tags = mb_trim(html_entity_decode($row['tags'], ENT_QUOTES, "UTF-8"));
				if($tcount <= 40)
				{
					$ttags = explode(" ",$tags);
					foreach($ttags as $current)
					{
						if($current != "" && $current != " ")
						{
							$gtags[$current] = $current;
							++$tcount;
						}
					}
				}
				if(substr($row['image'], -4) == ".svg")
					$images .= '<span class="thumb"><a id="p'.$row['id'].'" href="index.php?page=post&amp;s=view&amp;id='.$row['id'].'"><img src="'.$site_url.$image_folder.'/'.$row['directory'].'/'.$row['image'].'" alt="post" width="150" height="150" border="0" title="'.$row['tags'].' score:'.$row['score'].' rating:'. $row['rating'].'"/></a>';
				else if(substr($row['image'], -5) == ".webm" || substr($row['image'], -4) == ".mp4")
				{
					$img = substr($row['image'],0,strrpos($row['image'], "."));
					$img .= ".png";
					$images .= '<span class="thumb"><a id="p'.$row['id'].'" href="index.php?page=post&amp;s=view&amp;id='.$row['id'].'"><img src="'.$thumbnail_url.'/'.$row['directory'].'/thumbnail_'.$img.'" alt="post" border="0" title="'.$row['tags'].' score:'.$row['score'].' rating:'. $row['rating'].'" class="vid_thumb"/></a>';
				}
				else
					$images .= '<span class="thumb"><a id="p'.$row['id'].'" href="index.php?page=post&amp;s=view&amp;id='.$row['id'].'"><img src="'.$thumbnail_url.'/'.$row['directory'].'/thumbnail_'.$row['image'].'" alt="post" border="0" title="'.$row['tags'].' score:'.$row['score'].' rating:'. $row['rating'].'"/></a>';
				$images .= '<script type="text/javascript">
				//<![CDATA[
				posts['.$row['id'].'] = {\'tags\':\''.strtolower(str_replace('\\',"&#92;",str_replace("'","&#039;",$tags))).'\'.split(/ /g), \'rating\':\''.$row['rating'].'\', \'score\':'.$row['score'].', \'user\':\''.str_replace('\\',"&#92;",str_replace(' ','%20',str_replace("'","&#039;",$row['owner']))).'\'}
				//]]>
				</script>';
				if($user->gotpermission('admin_panel'))
					$images .= '<br/><span class="postID" onClick="copyMe(this);">'.$row['id'].'</span>'; 
				$images .= "</span>";
				++$tcount;
			}
			$result->free_result();	
			if(isset($_GET['tags']) && $_GET['tags'] != "" && $_GET['tags'] != "all")
				$ttags = urldecode($_GET['tags']);
			else
				$ttags = "";
			asort($gtags);
			/*Tags have been sorted in ascending order
			Let's now grab the index count from database
			Needs to be escaped before query is sent!
			URL Decode and entity decode for the links
			*/
			foreach($gtags as $current)
			{
				$query = "SELECT index_count, category FROM $tag_index_table WHERE tag='".$db->real_escape_string(str_replace("'","&#039;",$current))."'";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				$t_decode = $ttags;
				$c_decode = urlencode($current);
				echo '<li><a href="index.php?page=post&amp;s=list&amp;tags='.$ttags."+".$c_decode.'">+</a><a href="index.php?page=post&amp;s=list&amp;tags='.$ttags."+-".$c_decode.'">-</a> <span style="color: #a0a0a0;">? <a href="index.php?page=post&amp;s=list&amp;tags='.$c_decode.'" class="'.$row['category'].'">'.str_replace('_',' ',$current).'</a> '.$row['index_count'].'</span></li>';
			}
			//Print out image results and filter javascript
			echo '<li><br /><br /></li></ul></div></div><div class="content"><div>';
			$images .= "</div><br /><br /><div style='margin-top: 550px; text-align: right;'><a id=\"pi\" href=\"#\" onclick=\"showHideIgnored('0','pi'); return false;\"></a></div><div id='paginator'>";
			$images .= '<script type="text/javascript">
			//<![CDATA[
			filterPosts(posts)
			//]]>
			</script>';
			echo $images;

			//Pagination function. This should work for the whole site... Maybe.
			$misc = new misc();
			$pg = false;
			if(array_key_exists("pid", $_GET))
				$pg = $_GET['pid'];
			$in_tags = false;
			if(array_key_exists("tags", $_GET))
				$in_tags = $_GET['tags'];
			echo $misc->pagination($_GET['page'],$_GET['s'],false,$limit,$page_limit,$numrows,$pg,$in_tags);
			
		}
		//Cache doesn't exist for search, make one.
		if($should_cache_response && $enable_cache)
		{
			$data = ob_get_contents();
			ob_end_clean();
			if(isset($_GET['pid']) && is_numeric($_GET['pid']) && $_GET['pid'] > 0)
				$page = ($_GET['pid']/$limit)+1;
			else
				$page = 0;
			if($new_tag_cache != "")
			{
				if(!is_dir("$main_cache_dir".""."search_cache/".$new_tag_cache))
					@mkdir("$main_cache_dir".""."search_cache/".$new_tag_cache);
				$cache->save("search_cache/".$new_tag_cache."/".$page.".html",$data);
			}
			echo $data;
		}
	}
?>
</div><div id="footer"><a href="index.php?page=post&amp;s=add">Add</a> | <a href="help/">Help</a> | <a href="#" onClick="toggleIDs();">Show IDs</a></div><br /><br />
</div></div></div></body></html>
