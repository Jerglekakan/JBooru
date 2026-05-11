<?php
echo '<span class="phpprop" title="file_uploads">Uploads</span>: <strong>'.((ini_get("file_uploads") == "1") ? "Enabled" : "Disabled").'</strong><br/><br/>';

echo '<span class="phpprop" title="max_execution_time">Max script execution time:</span>'.((ini_get("max_execution_time") == "0") ? "Unlimited" : ini_get("max_execution_time")).'<br/>';
echo '<span class="phpprop" title="max_input_time">Max input time</span>:'.((ini_get("max_input_time") == "-1") ? "Unlimited" : ini_get("max_input_time")).'<br/><br/>';

echo '<span class="phpprop" title="memory_limit">Memory limit</span>:'.ini_get("memory_limit").'<br/>';
echo '<span class="phpprop" title="upload_max_filesize">Max upload size</span>: '.ini_get("upload_max_filesize").'<br/>';
echo '<span class="phpprop" title="post_max_size">Max POST size</span>:'.ini_get("post_max_size").'<br/>';
?>
