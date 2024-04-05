<?php
	class image
	{
		private $image_path;
		private $thumbnail_path;
		private $dimension;
		private $width;
		private $height;
		private $extension;
		private $mime;
		public $error;
		function __construct()
		{
			global $image_folder;
			global $dimension;
			global $thumbnail_folder;
			$this->image_path = $image_folder;
			$this->thumbnail_path = $thumbnail_folder;
			$this->dimension = $dimension;
			$width = 0;
			$height = 0;
			$extension = "";
			$mime = "";
		}
		function ImageCreateFromBMP($filename)
		{
		/*********************************************/
		/* Fonction: ImageCreateFromBMP              */
		/* Author:   DHKold                          */
		/* Contact:  admin@dhkold.com                */
		/* Date:     The 15th of June 2005           */
		/* Version:  2.0B                            */
		/*********************************************/
		 //Ouverture du fichier en mode binaire
		   if (! $f1 = fopen($filename,"rb")) return FALSE;

		 //1 : Chargement des ent?tes FICHIER
		   $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
		   if ($FILE['file_type'] != 19778) return FALSE;

		 //2 : Chargement des ent?tes BMP
		   $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
						 '/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
						 '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
		   $BMP['colors'] = pow(2,$BMP['bits_per_pixel']);
		   if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
		   $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
		   $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
		   $BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
		   $BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
		   $BMP['decal'] = 4-(4*$BMP['decal']);
		   if ($BMP['decal'] == 4) $BMP['decal'] = 0;

		 //3 : Chargement des couleurs de la palette
		   $PALETTE = array();
		   if ($BMP['colors'] < 16777216 && $BMP['colors'] != 65536)
		   {
			$PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
			#nei file a 16bit manca la palette,
		   }

		 //4 : Cr?ation de l'image
		   $IMG = fread($f1,$BMP['size_bitmap']);
		   $VIDE = chr(0);

		   $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
		   $P = 0;
		   $Y = $BMP['height']-1;
		   while ($Y >= 0)
		   {
			$X=0;
			while ($X < $BMP['width'])
			{
			 if ($BMP['bits_per_pixel'] == 24)
				$COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
			 elseif ($BMP['bits_per_pixel'] == 16)
			 { 
				$COLOR = unpack("n",substr($IMG,$P,2));
				$blue  = (($COLOR[1] & 0x001f) << 3) + 7;
				$green = (($COLOR[1] & 0x03e0) >> 2) + 7;
				$red   = (($COLOR[1] & 0xfc00) >> 7) + 7;
				$COLOR[1] = $red * 65536 + $green * 256 + $blue;
			 }
			 elseif ($BMP['bits_per_pixel'] == 8)
			 { 
				$COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
				$COLOR[1] = $PALETTE[$COLOR[1]+1];
			 }
			 elseif ($BMP['bits_per_pixel'] == 4)
			 {
				$COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
				if (($P*2)%2 == 0) $COLOR[1] = ($COLOR[1] >> 4) ; else $COLOR[1] = ($COLOR[1] & 0x0F);
				$COLOR[1] = $PALETTE[$COLOR[1]+1];
			 }
			 elseif ($BMP['bits_per_pixel'] == 1)
			 {
				$COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
				if     (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
				elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
				elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
				elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
				elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
				elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
				elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
				elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
				$COLOR[1] = $PALETTE[$COLOR[1]+1];
			 }
			 else
				return FALSE;
			 imagesetpixel($res,$X,$Y,$COLOR[1]);
			 $X++;
			 $P += $BMP['bytes_per_pixel'];
			}
			$Y--;
			$P+=$BMP['decal'];
		   }

		 //Fermeture du fichier
		   fclose($f1);

		 return $res;
		}

		function imagecreatefromvideo($filename)
		{
			$command = "ffmpeg -i $filename -vf thumbnail=300 -frames:v 1 -c:v png -f rawvideo -";
			ob_start();
			if(passthru($command) !== null)
			{
				ob_end_clean();
				print "ffmpeg failed<br/>";
				return null;
			}
			$c = ob_get_contents();
			ob_end_clean();
			if(strlen($c) == 0)
			{
				print "No output from ffmpeg<br/>";
				return null;
			}
			$img = imagecreatefromstring($c);
			if($img === false)
			{
				print "imagecreatefromstring() failed<br/>";
				return null;
			}
			return $img;
		}
		
		function thumbnail($image)
		{
			$timage = explode("/",$image);
			$bucket = $timage[0];
			$fname = $timage[1]; 
			$ext = strrchr($fname, ".");
			$thumbnail_name = "thumbnail_".$fname;
			if($ext == ".jpg" || $ext == ".jpeg")
			{
				$img = imagecreatefromjpeg($fname);
			}
			else if($ext == ".gif")
			{
				$img = imagecreatefromgif($fname);
			}
			else if($ext == ".png")
			{
				$img = imagecreatefrompng($fname);
			}
			else if($ext == ".bmp")
			{
				$version = explode('.', PHP_VERSION);
				if($version[0] >= 7 && $version[1] >= 2)
					$img = imagecreatefrombmp($fname);
				else
					$img = $this->ImageCreateFromBMP($fname);
			}
			else if($ext == ".webp")
			{
				$img = imagecreatefromwebp($fname);
			}
			else if($ext == ".svg")
			{
				return true;
			}
			else if($ext == ".webm" || $ext == ".mp4")
			{
				$thumbnail_name = substr($thumbnail_name, 0, strrpos($thumbnail_name, "."));
				$thumbnail_name = $thumbnail_name.".png";
				$img = $this->imagecreatefromvideo("./".$this->image_path."/".$bucket."/".$fname);
			}
			else
			{
				return false;
			}
			if($img === NULL)
				return false;
				
			$max = ($this->width > $this->height) ? $this->width : $this->height;
			$scale = ($max < $this->dimension) ? 1 : $this->dimension / $max;
			$width = $this->width * $scale;
			$height = $this->height * $scale;
			$thumbnail = imagecreatetruecolor($width,$height);
			imagecopyresampled($thumbnail,$img,0,0,0,0,$width,$height,$this->width,$this->height);
			$ret = "";
			if($ext == ".jpg" || $ext == ".jpeg")
				$ret = imagejpeg($thumbnail,"./".$this->thumbnail_path."/".$bucket."/".$thumbnail_name,95);
			else if($ext == ".gif")
				$ret = imagegif($thumbnail,"./".$this->thumbnail_path."/".$bucket."/".$thumbnail_name);
			else if($ext == ".png" || $ext == ".webm" || $ext == ".mp4")
				$ret = imagepng($thumbnail,"./".$this->thumbnail_path."/".$bucket."/".$thumbnail_name);
			else if($ext == ".bmp")
				$ret = imagejpeg($thumbnail,"./".$this->thumbnail_path."/".$bucket."/".$thumbnail_name,95);
			else if($ext == ".webp")
				$ret = imagewebp($thumbnail,"./".$this->thumbnail_path."/".$bucket."/".$thumbnail_name,95);
			else
				return false;
			imagedestroy($img);
			imagedestroy($thumbnail);
			return $ret;
		}
		
		function getremoteimage($url)
		{
			$mem_limit = ini_get('memory_limit');
			if($mem_limit == -1) {
				$fh = fopen('/proc/meminfo', 'r');
				while($line = fgets($fh)) {
					if (preg_match('/^MemTotal:\s+(\d+)\skB$/', $line, $pieces)) {
						$mem_limit = $pieces[1] * 1024;
						break;
				    }
				}
				fclose($fh);
			}
			else if (preg_match('/^(\d+)(.)$/', $mem_limit, $matches)) {
			    if ($matches[2] == 'G') {
				$mem_limit = $matches[1] * 1024 * 1024 * 1024; // nnnG -> nnn GB
			    } else if ($matches[2] == 'M') {
				$mem_limit = $matches[1] * 1024 * 1024; // nnnM -> nnn MB
			    } else if ($matches[2] == 'K') {
				$mem_limit = $matches[1] * 1024; // nnnK -> nnn KB
			    }
			}
			$mem_cutoff = 1048576;  //Stop fread() when the current memory usage gets within ~1MB of the limit
						//Not doing this for any particular reason, just don't like the idea of getting
						//close to the memory limit since exceeding it is a fatal error
			$misc = new misc();
			if($url == "" || $url == " " || !$this->validext($url))
				return false;
			$ext = ".".$this->extension;
			$valid_download = false;
			$dl_count = 0;
			$name = basename($url);
			while(!$valid_download)
			{
				$data = '';
				$f = fopen($url,"rb");
				if($f == "")
				{
					$this->extension = "";
					return false;
				}
				while(!feof($f))
				{
					if($mem_limit - (memory_get_usage() + $mem_cutoff) > 4096) {
						$data .= fread($f,4096); 
					} else {
						fclose($f);
						unset($data);
						$this->error = "Image too large. Memory limit reached!<br/>";
						$this->extension = "";
						return false;
					}
				}
				fclose($f);
				if($dl_count == 0)
				{				
					$l = fopen("./tmp/".$name."0".$ext,"w");
					fwrite($l,$data);
					fclose($l);
				}				
				if($dl_count == 1)
				{
					$l = fopen("./tmp/".$name."1".$ext,"w");
					fwrite($l,$data);
					fclose($l);
				}
				if($dl_count == 1)
				{
					$tmp_size = filesize("./tmp/".$name."0".$ext);
					$size = filesize("./tmp/".$name."1".$ext);
					if($tmp_size >= $size)
					{
						$valid_download = true;
						unlink("./tmp/".$name."0".$ext);
						unlink("./tmp/".$name."1".$ext);
					}
					else
					{
						unlink("./tmp/".$name."0".$ext);
						copy("./tmp/".$name."1".$ext,"./tmp/".$name."0".$ext);
						unlink("./tmp/".$name."1".$ext);
						$dl_count = 0;
					}
				}
				$dl_count++;
			}
			$cdir = $this->getcurrentfolder();
			if(!is_dir("./images/".$cdir."/"))
				$this->makefolder($cdir);
			if($ext != ".svg" && preg_match("#<script|<html|<head|<title|<body|<pre|<table|<a\s+href|<img|<plaintext#si", $data) == 1)
			{
				$this->error = "Found HTML embedded in file!<br/>";
				$this->extension = "";
				return false;
			}
			$filename = hash('sha1',hash('md5',$url));
			$i = 0;
			while(file_exists("./images/".$cdir."/".$filename.$ext))
			{
				$i++;
				$filename = hash('sha1',hash('md5',$url.$i));
			}
			$img = "./images/$cdir/$filename$ext";
			$f = fopen($img,"w");
			if($f == "")
			{
				$this->extension = "";
				return false;
			}
			fwrite($f,$data);
			fclose($f);
			if($ext == ".webm" || $ext == ".mp4")
			{
				if(!$this->validvideo($img))
				{
					unlink($img);
					return false;
				}
			}
			else
			{
				if(!$this->validimage($img))
				{
					unlink($img);
					return false;
				}
			}
			if(!$this->checksum($img))
			{
				unlink($img);
				$this->width = 0;
				$this->height = 0;
				$this->mime = "";
				$this->extension = "";
				return false;
			}
			$this->folder_index_increment($cdir);
			return $cdir.":".$filename.$ext;
		}
		
		function getcurrentfolder()
		{
			global $db, $folder_index_table;
			$query = "SELECT name FROM $folder_index_table WHERE count < 1000 ORDER BY count DESC LIMIT 1";
			$result = $db->query($query);
			$row = $result->fetch_assoc();
			$name = $row['name'];
			if($name != "")
				return $name;
			else
			{
				$query = "SELECT name FROM $folder_index_table WHERE COUNT >= '1000' ORDER BY id DESC LIMIT 1";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				$nfolder = $row['name'] + 1;
				if($row['name'] == "")
				{
					$query = "INSERT INTO $folder_index_table(name, count) VALUES('1','0')";
					$db->query($query) or die($db->error);
					return '1';
				}
				return $nfolder;
			}
		}
		
		function makefolder($folder)
		{
			mkdir("./images/".$folder);
			copy("./images/index.html","./images/".$folder."/index.html");
			$this->makesqlfolder($folder);
		}
		
		function makesqlfolder($folder)
		{
			global $db, $folder_index_table;
			$query = "SELECT COUNT(*) FROM $folder_index_table WHERE name='$folder'";
			$result = $db->query($query) or die($db->error);
			$row = $result->fetch_assoc();
			if($row['COUNT(*)'] <= 0)
			{
				$query = "INSERT INTO $folder_index_table(name, count) VALUES('$folder','0')";
				$db->query($query) or die($db->error);
			}
		}
		
		function process_upload($upload)
		{
			if($upload == "")
			{
				$this->error = "upload is empty";
				return false;
			}
			if(!$this->validext($upload['name']))
			{
				$e = strrchr($upload['name'], ".");
				$e = substr($e, 1);
				$this->error = "Invalid extension: $e";
				return false;
			}
			$ext = ".".$this->extension;
			$fname = hash('sha1',hash_file('md5',$upload['tmp_name']));
			move_uploaded_file($upload['tmp_name'],"./tmp/".$fname.$ext);
			$tmpf = "./tmp/$fname$ext";
			$f = fopen($tmpf,"rb");
			if($f == "")
			{
				$this->error = "fopen() has failed";
				$this->extension = "";
				return false;
			}
			$data = '';
			while(!feof($f))
				$data .= fread($f,4096);
			fclose($f);
			if($ext != ".svg")
			{
				if(preg_match("#<script|<html|<head|<title|<body|<pre|<table|<a\s+href|<img|<plaintext#si", $data) == 1)
				{	
					$this->error = "found HTML in file";
					unlink($tmpf);
					$this->extension = "";
					return false;
				}
				if($ext == ".webm" || $ext == ".mp4")
				{
					if(!$this->validvideo($tmpf))
					{
						unlink($tmpf);
						$this->width = 0;
						$this->height = 0;
						$this->extension = "";
						$this->mime = "";
						return false;
					}
				}
				else if(!$this->validimage($tmpf))
				{
					unlink($tmpf);
					$this->width = 0;
					$this->height = 0;
					$this->extension = "";
					$this->mime = "";
					return false;
				}
			}
			$ffname = $fname;
			$cdir = $this->getcurrentfolder();
			$i = 0;
			if(!is_dir("./images/".$cdir."/"))
				$this->makefolder($cdir);
			while(file_exists("./images/".$cdir."/".$fname.$ext))
			{
				$i++;
				$fname = hash('sha1',hash('md5',$fname.$i));
			}
			$f = fopen("./images/".$cdir."/".$fname.$ext,"w");
			if($f == "")
			{
				$this->error = "fopen() number 2 has failed";
				$this->width = 0;
				$this->height = 0;
				$this->extension = "";
				$this->mime = "";
				return false;
			}
			fwrite($f,$data);
			fclose($f);
			$this->folder_index_increment($cdir);
			unlink($tmpf);
			return $cdir.":".$fname.$ext;
		}
		
		function folder_index_increment($folder)
		{
			global $db, $folder_index_table;
			$query = "UPDATE $folder_index_table SET count=count+1 WHERE name='$folder'";
			$db->query($query);
		}
		
		function folder_index_decrement($folder)
		{
			global $db, $folder_index_table;
			$query = "SELECT count FROM $folder_index_table WHERE name='$folder'";
			$result = $db->query($query) or die($db->error);
			$row = $result->fetch_assoc();
			if($row['count'] > 0)
			{
				$query = "UPDATE $folder_index_table SET count=count-1 WHERE name='$folder'";
				$db->query($query);
			}
		}
		
		function makethumbnailfolder($folder)
		{
			mkdir("./thumbnails/".$folder."/");
			copy("./thumbnails/index.html","./thumbnails/".$folder."/index.html");
		}
		
		function removeimage($id)
		{
			global $db, $post_table, $note_table, $note_history_table, $user_table, $group_table, $favorites_table, $favorites_count_table, $comment_table, $comment_vote_table, $deleted_image_table;
			$can_delete = false;
			$id = $db->real_escape_string($id);
			$query = "SELECT directory, image, owner, tags, hash FROM $post_table WHERE id='$id'";
			$result = $db->query($query);
			$row = $result->fetch_assoc();
			$image = $row['image'];
			$ext = strrchr($image, ".");
			$ext = substr($ext, 1);
			$dir = $row['directory'];
			$owner = $row['owner'];
			$tags = $row['tags'];
			$hash = $row['hash'];
			
			if(isset($_COOKIE['user_id']) && is_numeric($_COOKIE['user_id']) && isset($_COOKIE['pass_hash']))
			{
				$user_id = $db->real_escape_string($_COOKIE['user_id']);
				$pass_hash = $db->real_escape_string($_COOKIE['pass_hash']);
				$query = "SELECT user FROM $user_table WHERE id='$user_id' AND pass='$pass_hash'";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				$user = $row['user'];
				
				$query = "SELECT t2.delete_posts FROM $user_table AS t1 JOIN $group_table AS t2 ON t2.id=t1.ugroup WHERE t1.id='$user_id' AND t1.pass='$pass_hash'";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				if(strtolower($user) == strtolower($owner) && $user != "Anonymous" || $row['delete_posts'] == true)
					$can_delete = true;
			}
			
			if($can_delete == true)
			{
				$cache = new cache();
				$query = "SELECT parent FROM $post_table WHERE id='$id'";
				$result = $db->query($query);
				$row = $result->fetch_assoc();
				if($row['parent'] != "" && $row['parent'] != 0)
					$cache->destroy("../cache/".$row['parent']."/post.cache");	
				$query = "DELETE FROM $post_table WHERE id='$id'";
				$db->query($query);
				$query = "DELETE FROM $note_table WHERE post_id='$id'";
				$db->query($query);
				$query = "DELETE FROM $note_history_table WHERE post_id='$id'";
				$db->query($query);
				$query = "DELETE FROM $comment_table WHERE post_id='$id'";
				$db->query($query);
				$query = "DELETE FROM $comment_vote_table WHERE post_id='$id'";
				$db->query($query);
				$query = "SELECT user_id FROM $favorites_table WHERE favorite='$id' ORDER BY user_id";
				$result = $db->query($query);
				while($row = $result->fetch_assoc())
				{
					$ret = "UPDATE $favorites_count_table SET fcount=fcount-1 WHERE user_id='".$row['user_id']."'";
					$db->query($ret);
				}
				
				$query = "DELETE FROM $favorites_table WHERE favorite='$id'";
				$db->query($query);
				$query = "DELETE FROM $parent_child_table WHERE parent='$id'";
				$db->query($query);
				$query = "SELECT id FROM $post_table WHERE parent='$id'";
				$result = $db->query($query);
				while($row = $result->fetch_assoc())
					$cache->destroy("../cache/".$id."/post.cache");
				$query = "UPDATE $post_table SET parent='' WHERE parent='$id'";
				$db->query($query);
				unlink("../images/".$dir."/".$image);
				$thumb = "../thumbnails/".$dir."/thumbnail_".$image;
				if($ext == "webm" || $ext == "mp4")
					$thumb = substr($thumb, 0, strrpos($thumb, ".")).".png";
				unlink($thumb);
				$this->folder_index_decrement($dir);
				$itag = new tag();
				$tags = explode(" ",$tags);
				
				$misc = new misc();				
				foreach($tags as $tag)
				{
					if($tag != "")
					{
						$itag->deleteindextag($tag);
						if(is_dir("../search_cache/".$misc->windows_filename_fix($tag)."/"))
						$cache->destroy_page_cache("../search_cache/".$misc->windows_filename_fix($tag)."/");
					}
				}
				$query = "UPDATE $post_count_table SET last_update='20060101' WHERE access_key='posts'";
				$db->query($query);
				$query = "INSERT INTO $deleted_image_table(hash) VALUES('$hash')";
				$db->query($query);
				return true;
			}
			return false;
		}
		
		function checksum($file)
		{
			global $db, $post_table, $deleted_image_table;
			$i = 0;
			$tmp_md5_sum = md5_file($file);
			$query = "SELECT id FROM $post_table WHERE hash='$tmp_md5_sum'";
			$result = $db->query($query);
			if($row = $result->fetch_assoc())
				$i = $row['id'];
			else
				$i = null;
			
			$query = "SELECT COUNT(*) FROM $deleted_image_table WHERE hash='$tmp_md5_sum'";
			$result = $db->query($query);
			if($row = $result->fetch_assoc())
				$count = $row['COUNT(*)'];
			else
				$count = 0;
			
			//print $tmp_md5_sum;
			if($i != "" && $i != NULL)
			{
				$this->error = "That image already exists. You can find it <a href=\"index.php?page=post&s=view&id=$i\">here</a><br />";
				return false;
			}
			else if($count > 0)
			{
				$this->error = "That image has been deleted from the site. You cannot upload it again<br/>";
				return false;
			}
			else
				return true;
		}
		
		function geterror()
		{
			return $this->error;
		} 

		function getw()
		{
			return $this->width;
		}

		function geth()
		{
			return $this->height;
		}

		function validimage($image)
		{
			global $min_upload_width, $min_upload_height, $max_upload_width, $max_upload_height;
			$iinfo = getimagesize($image);
			$this->width = $iinfo[0];
			$this->height = $iinfo[1];
			$this->mime = $iinfo['mime'];

			if(substr($this->mime,0,5) != "image" && substr($this->mime,0,5) != "video")
			{
				$this->error = "Wrong mimetype";
				$this->width = 0;
				$this->height = 0;
				$this->mime = "";
				$this->extension = "";
				return false;
			}
			if($this->width < $min_upload_width && $min_upload_width != 0 || $this->width > $max_upload_width && $max_upload_width != 0)
			{
				$this->error = "Invalid width";
				$this->width = 0;
				$this->height = 0;
				$this->mime = "";
				$this->extension = "";
				return false;
			}
			if($this->height < $min_upload_height && $min_upload_height != 0 || $this->height > $max_upload_height && $max_upload_height != 0)
			{
				$this->error = "Invalid height";
				$this->width = 0;
				$this->height = 0;
				$this->mime = "";
				$this->extension = "";
				return false;
			}
			return true;
		}

		function validext($url)
		{
			$ext = strrchr($url, '.');
			$ext = substr($ext, 1);
			$ext = strtolower($ext);
			if($ext == "jpg" || $ext == "jpeg" || $ext == "gif" || $ext == "png" || $ext == "bmp" || $ext == "webp" || $ext == "svg" || $ext == "mp4" || $ext == "webm")
			{
				$this->extension = $ext;
				return true;
			}
			return false;
		}

		function validvideo($video)
		{
			global $min_upload_width, $min_upload_height, $max_upload_width, $max_upload_height;
			$output = [];
			$vstream_count = 0;
			$astream_count = 0;
			exec("ffprobe -show_streams -pretty -loglevel quiet -i $video", $output);
			foreach($output as $line)
			{
				if($line == "codec_type=video")
					$vstream_count++;
				else if($line == "codec_type=audio")
					$astream_count++;
				else if(substr($line, 0, 6) == "width=")
					$this->width = (int)substr($line, 6);
				else if(substr($line, 0, 7) == "height=")
					$this->height = (int)substr($line, 7);
			}
			$this->mime = mime_content_type($video);

			if($vstream_count != 1)
			{
				$this->error = "Only one video stream allowed";
				$this->extension = "";
				$this->width = 0;
				$this->height = 0;
				return false;
			}
			if($astream_count > 1)
			{
				$this->error = "No more than one audio stream allowed";
				$this->extension = "";
				$this->width = 0;
				$this->height = 0;
				return false;
			}

			if(substr($this->mime,0,5) != "image" && substr($this->mime,0,5) != "video")
			{
				$this->error = "Wrong mimetype";
				$this->width = 0;
				$this->height = 0;
				$this->mime = "";
				$this->extension = "";
				return false;
			}
			if($this->width < $min_upload_width && $min_upload_width != 0 || $this->width > $max_upload_width && $max_upload_width != 0)
			{
				$this->error = "Invalid width";
				$this->width = 0;
				$this->height = 0;
				$this->mime = "";
				$this->extension = "";
				return false;
			}
			if($this->height < $min_upload_height && $min_upload_height != 0 || $this->height > $max_upload_height && $max_upload_height != 0)
			{
				$this->error = "Invalid height";
				$this->width = 0;
				$this->height = 0;
				$this->mime = "";
				$this->extension = "";
				return false;
			}
			return true;
		}

		function load($path)
		{
			if(!$this->validext($path))
				return false;
			if($this->extension == "webm" || $this->extension == "mp4")
			{
				if(!$this->validvideo($path))
					return false;
			}
			else
			{
				if(!$this->validimage($path))
					return false;
			}
			return true;
		}
	}
?>
