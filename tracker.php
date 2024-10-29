<?php
$curr_path = dirname( __FILE__ );
$affimg_path = str_replace("\\", "/", strstr($curr_path, 'wp-content'));
$count     = substr_count(trim($affimg_path, '/'), '/');
if ( $count > 0 )
 for ($i=0; $i<=$count; $i++)
  $_affimage_path .= "../";
require_once($_affimage_path.'wp-config.php');

$image_id = $_GET['id'];
$aff_image_tracker_table = $table_prefix.'mbp_affiliate_image_tracker';  

$sql = "SELECT link_url FROM $aff_image_tracker_table WHERE image_id='$image_id'";
$rs = mysql_query($sql);
$redirect_url = mysql_result($rs,0,'link_url');

if ( strpos($redirect_url,'http://') === false && strpos($redirect_url,'https://') === false ) $redirect_url = 'http://'.$redirect_url;

if ( !is_admin() && !is_feed() && !is_user_logged_in() ) {
	$sql = "UPDATE $aff_image_tracker_table SET aff_hits=aff_hits+1 WHERE image_id='$image_id'";
	mysql_query($sql);
}

header("location: $redirect_url");
die();
?>