<?php
	class misc
	{
		public function short_url($text,$newWindow = 0) 
		{
			$targetBlank = "";
			if($newWindow == 1)
				$targetBlank = ' target="_blank"';
			
			//https://drupal.org/node/1868588			
			$numMatches = $this->mb_preg_match_all("/(http:\/\/|https:\/\/)[a-zA-Z0-9@:%_+*~#?&=.,\/;\-\[\]]+[a-zA-Z0-9@:%_+*~#&=\/;\-\[\]]/ui", $text, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE);
			if(count($matches) > 0) 
			{
				$offset = 0;
				
				foreach($matches[0] as $match) 
				{
					$url = preg_replace("/(http:\/\/|https:\/\/)/ui","",$match[0]);
					//Adjust the offset as we replace links in order to ensure 
					//that we replace at the correct location.
					$match[1] += $offset;

					if((mb_strlen($url, 'UTF-8') > 60)) 
					{
						$part1 = mb_substr($url, 0, 25, 'UTF-8');
						$part2 = mb_substr($url, -25, mb_strlen($url, 'UTF-8'), 'UTF-8');
						$link = '<a href="'.$match[0].'" rel="nofollow"'.$targetBlank.'>'.$part1.'...'.$part2.'</a>';
					} 
					else 
					{
						$link = '<a href="'.$match[0].'" rel="nofollow"'.$targetBlank.'>'.$url.'</a>';
					}
					
					$offset += (mb_strlen($link, 'UTF-8') - mb_strlen($match[0], 'UTF-8'));
					
					$text = $this->mbStrReplacePos($match[0], $link, $match[1], $text);
				}
			}
			
			return $text;
		}
		
		public function mb_preg_match_all($ps_pattern, $ps_subject, &$pa_matches, $pn_flags = PREG_PATTERN_ORDER, $pn_offset = 0, $ps_encoding = NULL) 
		{
			//http://php.net/manual/en/function.preg-match-all.php#71572
			// WARNING! - All this function does is to correct offsets, nothing else:
			
			//Use default internal encoding if none is provided
			if (is_null($ps_encoding))
				$ps_encoding = mb_internal_encoding();

			$pn_offset = strlen(mb_substr($ps_subject, 0, $pn_offset, $ps_encoding));
			$ret = preg_match_all($ps_pattern, $ps_subject, $pa_matches, $pn_flags, $pn_offset);

			if ($ret && ($pn_flags & PREG_OFFSET_CAPTURE))
				foreach($pa_matches as &$ha_match)
					foreach($ha_match as &$ha_match)
						$ha_match[1] = mb_strlen(substr($ps_subject, 0, $ha_match[1]), $ps_encoding);

			// (code is independent of PREG_PATTER_ORDER / PREG_SET_ORDER)

			return $ret;
		}
		
		public function mbStrReplacePos($pattern, $replacement, $pos, $haystack) 
		{
			return mb_substr($haystack, 0, $pos, 'UTF-8').$replacement.mb_substr($haystack, $pos + mb_strlen($pattern, 'UTF-8'), mb_strlen($haystack, 'UTF-8'), 'UTF-8');
		}
		
		function linebreaks($text)
		{
			if(mb_strpos($text,"\r\n",0,'UTF-8') !== false)
			{
				return str_replace("\r\n","<br />",$text);
			}
			return nl2br($text);
		}

		function send_mail($reciver, $subject, $body)
		{
			require "config.php";
			global $site_email, $site_url3;
			$headers = "";
			$eol = "\r\n";
			$headers .= "From: no-reply at ".$site_url3." <$site_email>".$eol; 
			$headers .= "X-Mailer:  Microsoft Office Outlook 12.0".$eol;
			$headers .= "MIME-Version: 1.0".$eol;
			$headers .= 'Content-Type: text/html; charset="UTF-8"'.$eol;
			$headers .= "Content-Transfer-Encoding: 8bit".$eol.$eol;
			if(substr($body,-8,strlen($body)) != $eol.$eol)
				$body = $body.$eol.$eol;
			if(@mail($reciver,$subject,$body,$headers))
				return true;
			else
				return false;
		}

		function windows_filename_fix($new_tag_cache)
		{
			if(strpos($new_tag_cache,";") !== false)
				$new_tag_cache = str_replace(";","&#059;",$new_tag_cache);
			if(strpos($new_tag_cache,".") !== false)
				$new_tag_cache = str_replace(".","&#046;",$new_tag_cache);
			if(strpos($new_tag_cache,"*") !== false)
				$new_tag_cache = str_replace("*","&#042;",$new_tag_cache);
			if(strpos($new_tag_cache,"|") !== false)
				$new_tag_cache = str_replace("|","&#124;",$new_tag_cache);
			if(strpos($new_tag_cache,"\\") !== false)
				$new_tag_cache = str_replace("\\","&#092;",$new_tag_cache);
			if(strpos($new_tag_cache,"/") !== false)
				$new_tag_cache = str_replace("/","&#047;",$new_tag_cache);
			if(strpos($new_tag_cache,":") !== false)
				$new_tag_cache = str_replace(":","&#058;",$new_tag_cache);
			if(strpos($new_tag_cache,'"') !== false)
				$new_tag_cache = str_replace('"',"&quot;",$new_tag_cache);
			if(strpos($new_tag_cache,"<") !== false)
				$new_tag_cache = str_replace("<","&lt;",$new_tag_cache);
			if(strpos($new_tag_cache,">") !== false)
				$new_tag_cache = str_replace(">","&gt;",$new_tag_cache);
			if(strpos($new_tag_cache,"?") !== false)
				$new_tag_cache = str_replace("?","&#063;",$new_tag_cache);
			return $new_tag_cache;	
		}
		
		function ReadHeader($socket)
		{
			$i=0;
			$header = "";
			while( true && $i<20 && !feof($socket))
			{
			   $s = fgets( $socket, 4096 );
			   $header .= $s;
			   if( strcmp( $s, "\r\n" ) == 0 || strcmp( $s, "\n" ) == 0 )
				   break;
			   $i++;
			}
			if( $i >= 20 )
			   return false;
			return $header;
		}

		function getRemoteFileSize($header)
		{
			if(strpos($header,"Content-Length:") === false)
				return 0;
			$count = preg_match($header,'/Content-Length:\s([0-9].+?)\s/',$matches);
			if($count > 0)
			{
				if(is_numeric($matches[1]))
					return $matches[1];
				else
					return 0;
			}
			else
				return 0;
		}
		
		function swap_bbs_tags($data)
		{
			$pattern = array();
			$replace = array();
			$pattern[] = '/\[quote\](.*?)\[\/quote\]/i';
			$replace[] = '<div class="quote">$1</div>';
			$pattern[] = '/\[b\](.*?)\[\/b\]/i';
			$replace[] = '<b>$1</b>';
			$pattern[] = '/\[spoiler\](.*?)\[\/spoiler\]/i';
			$replace[] = '<span class="spoiler">$1</span>';
			$pattern[] = '/\[post\](.*?)\[\/post\]/i';
			$replace[] = '<a href="index.php?page=post&s=view&id=$1">post #$1</a>';
			$pattern[] = '/\[forum\](.*?)\[\/forum\]/i';
			$replace[] = '<a href="index.php?page=forum&s=view&id=$1">forum #$1</a>';
			$count = count($pattern)-1;
			for($i=0;$i<=$count;$i++)
			{
				while(preg_match($pattern[$i],$data) == 1)
					$data =  preg_replace($pattern[$i], $replace[$i], $data);
			}
			return $data;
		}
		
		function date_words($date_now)
		{
			$hour_now = date('g:i:s A',$date_now);
			if($date_now+60*60*24 >= time())
				$date_now = "Today"; 
			else if($date_now+60*60*48 >= time()) 
				$date_now = "Yesterday"; 
			else if(((int)((time()-$date_now)/(24*60*60)))<=7)
			{
				$a = time()-$date_now; 
				$a = (int)($a/(24*60*60));
				$date_now = $a." days ago"; 
			}
			else if(((int)((time()-$date_now)/(24*60*60)))<=31)
			{
				$a = time()-$date_now; 
				$a = (int)($a/(24*60*60*7));
				$date_now = $a." weeks ago";
			}
			else if(((int)((time()-$date_now)/(24*60*60)))<=365)
			{
				$a = time()-$date_now; 
				$a = (int)($a/(24*60*60*31));
				$date_now = $a." months ago";
			}
			else
			{
				$a = time()-$date_now;
				$a = ((int)($a/(24*60*60*365)));
				$date_now = $a." years ago";
			}
			$date_now = '<span title="'.$hour_now.'">'.$date_now.'</span>';
			return $date_now;
		}
		
		public function is_html($data)
		{
			if(preg_match("#<script|<html|<head|<title|<body|<pre|<table|<a\s+href|<img|<plaintext|<div|<frame|<iframe|<li|type=#si", $data) == 1)
				return true;
			else
				return false;
		}
		
		function pagination($page_type,$sub = false,$id = false,$limit = false,$page_limit = false,$count = false,$page = false,$tags = false, $query = false)
		{
			$has_id = "";
			$has_tags = "";
			if(isset($id) && $id > 0)
				$has_id = '&amp;id='.$id.'';
			if(isset($tags) && $tags !="" && $tags)
				$has_tags = '&amp;tags='.str_replace(" ","+",urlencode($tags)).'';
			if(isset($sub) && $sub !="" && $sub)
				$sub = '&amp;s='.$sub.'';
			if(isset($query) && $query != "" && $query)
				$query = '&amp;query='.urlencode($query).'';
			$pages = intval($count/$limit);
			if ($count%$limit)
				$pages++;
			$current = ($page/$limit) + 1;
			$total = $pages;
			if ($pages < 1 || $pages == 0 || $pages == "")
				$total = 1;
				
			$first = $page + 1;
			$last = $count;
			if (!((($page + $limit) / $limit) >= $pages) && $pages != 1)
				$last = $page + $limit;
			$output = "";
			if($page == 0)
				$start = 1;
			else
				$start = ($page/$limit) + 1;
			$tmp_limit = $start + $page_limit;
			if($tmp_limit > $pages)
				$tmp_limit = $pages;
			if($pages > $page_limit)
			{
				$lowerlimit = $pages - $page_limit;
				if($start > $lowerlimit)
					$start = $lowerlimit;
			}
			$lastpage = $limit*($pages - 1);
			if($page != 0 && !((($page+$limit) / $limit) > $pages)) 
			{ 
				$back_page = $page - $limit;
				$output .=  '<a href="?page='.$page_type.''.$sub.''.$query.''.$has_id.''.$has_tags.'&amp;pid=0" alt="first page">&lt;&lt;</a><a href="?page='.$page_type.''.$sub.''.$query.''.$has_id.''.$has_tags.'&amp;pid='.$back_page.'" alt="back">&lt;</a>';
			}
			for($i=$start; $i <= $tmp_limit; $i++)
			{
				$ppage = $limit*($i - 1);
				if($ppage >= 0)
				{
					if ($ppage == $page)
						$output .=  ' <b>'.$i.'</b> ';
					else
						$output .=  '<a href="?page='.$page_type.''.$sub.''.$query.''.$has_id.''.$has_tags.'&amp;pid='.$ppage.'">'.$i.'</a>';
				}
			}
			if (!((($page+$limit) / $limit) >= $pages) && $pages != 1) 
			{ 
				// If last page don't give next link.
				$next_page = $page + $limit;
				$output .= '<a href="?page='.$page_type.''.$sub.''.$query.''.$has_id.''.$has_tags.'&amp;pid='.$next_page.'" alt="next">&gt;</a><a href="?page='.$page_type.''.$sub.''.$query.''.$has_id.''.$has_tags.'&amp;pid='.$lastpage.'" alt="last page">&gt;&gt;</a>';
			}
			return $output;
		}
	}
?>
