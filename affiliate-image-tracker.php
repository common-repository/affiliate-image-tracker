<?php
/*
 * Plugin Name:   Affiliate Image Tracker
 * Version:       3.0
 * Plugin URI:    http://wordpress.org/extend/plugins/random-image/
 * Description:   With this plugin you had the power to easily place your images in any position  you want to on your blog, you could also track your affiliate images, find out how good they really are doing. Adjust your settings <a href="options-general.php?page=affiliate-image-tracker">here</a>.
 * Author:        MaxBlogPress
 * Author URI:    http://www.maxblogpress.com
 *
 * License:       GNU General Public License
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * 
 * Copyright (C) 2007 www.maxblogpress.com
 *
 */
  
  
$mbpait_path     = preg_replace('/^.*wp-content[\\\\\/]plugins[\\\\\/]/', '', __FILE__);
$mbpait_path     = str_replace('\\','/',$mbpait_path);
$mbpait_dir      = substr($mban_path,0,strrpos($mbpait_path,'/'));
$mbpait_siteurl  = get_bloginfo('wpurl');
$mbpait_siteurl  = (strpos($mbpait_siteurl,'http://') === false) ? get_bloginfo('siteurl') : $mbpait_siteurl;
$mbpait_fullpath = $mbpait_siteurl.'/wp-content/plugins/'.$mbpait_dir.'';
$mbpait_fullpath  = $mbpait_fullpath.'affiliate-image-tracker/';
$mbpait_abspath  = str_replace("\\","/",ABSPATH); 
define('MBP_AIT_ABSPATH', $mbpait_abspath);
define('MBP_AIT_LIBPATH', $mbpait_fullpath);
define('MBP_AIT_NAME', 'Affiliate Image Tracker');
define('MBP_AIT_VERSION', '3.0');  
define('MBP_AIT_SITEURL', $mbpait_siteurl);

global $table_prefix;
$rdmimg_tbl = $table_prefix.'mbp_affiliate_image_tracker';
define('MBP_AFF_IMAGETRACKER_TBL', $rdmimg_tbl);

// Hook for adding admin menus
add_action('admin_menu', 'mbp_aff_image_menu');
add_filter('the_content', 'affimage_post');
add_action('activate_'.$mbpait_path, 'MBP_AffImg_active' );

function MBP_AffImg_active(){
	$db_check = mysql_query('SHOW TABLES LIKE '.MBP_AFF_IMAGETRACKER_TBL.'');
	$exists = mysql_fetch_row($db_check);
	if ( !$exists ) {
		$sql = "CREATE TABLE ".MBP_AFF_IMAGETRACKER_TBL." (                                        
		   `image_id` int(11) NOT NULL auto_increment,                           
		   `img_name` varchar(200) collate latin1_general_ci NOT NULL,           
		   `image_url` varchar(100) collate latin1_general_ci NOT NULL,          
		   `link_url` varchar(255) collate latin1_general_ci NOT NULL,           
		   `image_title` varchar(255) collate latin1_general_ci NOT NULL,        
		   `target_window` int(1) NOT NULL,                                      
		   `aff_hits` int(8) NOT NULL,                                           
		   `flag` enum('0','1') collate latin1_general_ci NOT NULL default '1',  
		   PRIMARY KEY  (`image_id`)                                          
		 )";
	mysql_query($sql);	 
	}
}

// action function for above hook
function mbp_aff_image_menu() {
	// Add a new submenu under Options:
	add_options_page('Affiliate Image Tracker', 'Affiliate Image Tracker', 8, 'affiliate-image-tracker', 'mbp_affimage_option_page');
}

/**
 * Creates a directory to upload banners
 */
function __affImageMakeDir() {
	$mbpimg_upload_path = MBP_AIT_ABSPATH.'wp-content/affiliate-images';
	if ( is_admin() && !is_dir($mbpimg_upload_path) ) {
		@mkdir($mbpimg_upload_path);
	}
	return $mbpimg_upload_path;
}

function mbp_affimage_option_page() {

	$mbp_alt_activate = get_option('mbp_alt_activate');
	$reg_msg = '';
	$mbp_alt_msg = '';
	$form_1 = 'mbp_alt_reg_form_1';
	$form_2 = 'mbp_alt_reg_form_2';
		// Activate the plugin if email already on list
	if ( trim($_GET['mbp_onlist']) == 1 ) {
		$mbp_alt_activate = 2;
		update_option('mbp_alt_activate', $mbp_alt_activate);
		$reg_msg = 'Thank you for registering the plugin. It has been activated'; 
	} 
	// If registration form is successfully submitted
	if ( ((trim($_GET['submit']) != '' && trim($_GET['from']) != '') || trim($_GET['submit_again']) != '') && $mbp_alt_activate != 2 ) { 
		update_option('mbp_alt_name', $_GET['name']);
		update_option('mbp_alt_email', $_GET['from']);
		$mbp_alt_activate = 1;
		update_option('mbp_alt_activate', $mbp_alt_activate);
	}
	if ( intval($mbp_alt_activate) == 0 ) { // First step of plugin registration
		global $userdata;
		mbp_altRegisterStep1($form_1,$userdata);
	} else if ( intval($mbp_alt_activate) == 1 ) { // Second step of plugin registration
		$name  = get_option('mbp_alt_name');
		$email = get_option('mbp_alt_email');
		mbp_altRegisterStep2($form_2,$name,$email);
	} else if ( intval($mbp_alt_activate) == 2 ) { // Options page
		if ( trim($reg_msg) != '' ) {
			echo '<div id="message" class="updated fade"><p><strong>'.$reg_msg.'</strong></p></div>';
		}

 // Start Execute  
	if( $_GET['action'] == 1 ){
		$imgname = mysql_query("select image_url from ".MBP_AFF_IMAGETRACKER_TBL." where image_id='".$_GET['id']."'");
		$delete_imgname = mysql_fetch_array($imgname);
		@unlink(MBP_AIT_ABSPATH.'wp-content/affiliate-images/'.$delete_imgname['image_url'].'');
		$db_sql = "delete from ".MBP_AFF_IMAGETRACKER_TBL." where image_id='".$_GET['id']."'";
		mysql_query($db_sql);
		$msg = '<font color="red">Image removed from system</font>';
	}
	
	if( $_GET['action'] == "pub"  || $_GET['action'] == "unpub" ){
	if( $_GET['action'] == "pub" ) $flag='0';
	elseif( $_GET['action'] == "unpub" ) $flag='1';
		$imgname = mysql_query("Update ".MBP_AFF_IMAGETRACKER_TBL." set flag='".$flag."' where image_id='".$_GET['id']."'");
	}

	if($_POST['submit'] == "Submit"){
		$grouprec = array(''.$_POST['img_option'].'',''.$_POST['width'].'',''.$_POST['height'].'');
		update_option('mbp_affimagetracker_advance_option', $grouprec);
		$advmsg = "<font color='red'>Advance Image Option Updated</font>";
	}
	
	if($_POST['submit'] == "Remove"){
		$pwdby = array(''.$_POST['pwdby'].'');
		update_option('mbp_affimagetracker_pwdby_option', $pwdby);
	}
	
	if($_POST['upload'] == "Upload"){
	if(!$_POST['alt']  || !$_POST['link'] || !$_POST['title'] ) $msg = 'Empty Fields';
	if($_POST['link'] && !preg_match('|^http(s)?://[a-z0-9-]+(\.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$|i', $_POST['link']) ) $msg = 'Not valid URL';
	if( $_GET['action'] == 'add' ){
	if($_FILES['url_local']['name'] == '' ) $msg = 'No image selected';
	}
	if(!$msg){ 
	if( $_GET['action'] == 'edit' ){
	$sql = "update ".MBP_AFF_IMAGETRACKER_TBL." set
			img_name = '".$_POST['alt']."',
			link_url = '".$_POST['link']."',
			image_title = '".$_POST['title']."',
			target_window = '".$_POST['in_new_win']."'
			where image_id ='".$_GET['id']."'";
	mysql_query($sql);
	echo '<script type="text/javascript">';
	echo 'window.location.href="?page=affiliate-image-tracker";';
	echo '</script>';
	}else{
	
	  //Upload from local computer
		$mbp_valid_file  = array("image/pjpeg", "image/png", "image/jpeg", "image/gif", "image/bmp");
		$mbpimg_upload_path = __affImageMakeDir();  
		$upload_name      = $_FILES['url_local']['name'];
		$upload_type      = $_FILES['url_local']['type'];
		$upload_size      = $_FILES['url_local']['size'];
		$upload_tmp_name  = $_FILES['url_local']['tmp_name'];
		
		$file_ext_pos     = strrpos($upload_name,'.');
		$filename         = substr($upload_name,0,$file_ext_pos);
		$extension        = substr($upload_name,$file_ext_pos+1);
		$upload_name      = $filename.'_'.date('YmdHis').'.'.$extension;
		$banner_path      = $mbpimg_upload_path.'/'.$upload_name;
		$banner_url       = MBP_AIT_SITEURL.'/wp-content/mbp-random-image/'.$upload_name; 
		$url              = $banner_url;
		if ( in_array($upload_type,$mbp_valid_file) ) {
			if ( move_uploaded_file($upload_tmp_name, $banner_path) ) {
				list($banner_width, $banner_height) = @getimagesize($banner_path);
				$sql = "insert into ".MBP_AFF_IMAGETRACKER_TBL."(img_name,image_url,link_url,image_title,target_window,flag ) values('".$_POST['alt']."','".$upload_name."','".$_POST['link']."','".$_POST['title']."','".$_POST['in_new_win']."','1')";
				mysql_query($sql);
				$msg = "Image uploaded from local computer.\n";
				chmod($banner_path, 0644);
				echo '<script type="text/javascript">';
				echo 'window.location.href="?page=affiliate-image-tracker";';
				echo '</script>';
			} else {
				$upload_err = 1;
				$msg = "Image couldn't be uploaded from local computer.";
			}
		} else {
			$upload_err = 1;
			$msg = "Image couldn't be uploaded from local computer. Invalid file type.";
		}
	}
	}
	}//Eof Upload

	
	$img_data = get_option('mbp_affimagetracker_advance_option');
	$pwdby = get_option('mbp_affimagetracker_pwdby_option');
	if( $img_data[0] == 'orig' ) $orig = 'checked'; 
	elseif( $img_data[0] == 'high' ) $high = 'checked'; 
	elseif( $img_data[0] == 'wide' ) $wide = 'checked'; 
	elseif( $img_data[0] == 'spec' ) $spec = 'checked';
	if( $pwdby[0] == 'pwdby' ) $pwdby = 'checked';
	
	$msg_ri_txt = "We request you to have the powered by link as this would be visible to your blog visitors and they would be benefited by this plugin as well.<br/><br/>If you want to remove the powered by link, we will appreciate a review post for this plugin in your blog. This will help lots of other people know about the plugin and get benefited by it. By the way, if for any reason you do not want to write a review post then its ok as well. No obligation. We will be much happy if you find out some other ways to spread the word for this plugin ";
		?>
<script>		
function __ShowHide(curr, img, path) {
	var curr = document.getElementById(curr);
	if ( img != '' ) {
		var img  = document.getElementById(img);
	}
	var showRow = 'block'
	if ( navigator.appName.indexOf('Microsoft') == -1 && curr.tagName == 'TR' ) {
		var showRow = 'table-row';
	}
	if ( curr.style == '' || curr.style.display == 'none' ) {
		curr.style.display = showRow;
		if ( img != '' ) img.src = path + 'image/minus.gif';
	} else if ( curr.style != '' || curr.style.display == 'block' || curr.style.display == 'table-row' ) {
		curr.style.display = 'none';
		if ( img != '' ) img.src = path + 'image/plus.gif';
	}
}
function ConfirmDelete(){
	boolReturn = confirm(" Are you sure you wish to delete this record?");
	if (boolReturn)
	return true;
	else
	return false;
}
</script>	
<script type="text/javascript" src="<?php echo MBP_AIT_LIBPATH;?>tooltip.js"></script>
<link href="<?php echo MBP_AIT_LIBPATH;?>tooltip.css" rel="stylesheet" type="text/css">
	
<style type="text/css">
<!--
.style1 {
	color: #0066CC;
	font-weight: bold;
}
-->
</style>
<div class="wrap">
<h2><?php echo MBP_AIT_NAME.' '.MBP_AIT_VERSION; ?></h2>
<br>
<strong><img src="<?php echo MBP_AIT_LIBPATH;?>image/how.gif" border="0" align="absmiddle" /> <a href="http://wordpress.org/extend/plugins/affiliate-image-tracker/other_notes/" target="_blank">How to use it</a>&nbsp;&nbsp;&nbsp;
		<img src="<?php echo MBP_AIT_LIBPATH;?>image/comment.gif" border="0" align="absmiddle" /> <a href="http://www.maxblogpress.com/forum/forumdisplay.php?f=27" target="_blank">Community</a></strong>
<br>
<br>			

<?php 
if( $_GET['action'] == 'edit' || $_GET['action'] == 'add' ){ 
if( $_GET['action'] == 'edit' ){
$sql = 'select img_name,link_url,image_title,target_window from '.MBP_AFF_IMAGETRACKER_TBL.' where image_id='.$_GET['id'].' ';
$editresult = mysql_query($sql);
$edit = mysql_fetch_array($editresult);
}
?>
<!--edit-->
<form action="" method="post" enctype="multipart/form-data" onSubmit="return __ValidateBannerForm()">
    <table border="0" width="100%" bgcolor="#f1f1f1" style="border:1px solid #e5e5e5">
     <tr >
   <td colspan="2" style="padding:4px 4px 4px 4px;background-color:#f1f1f1;"><strong>BACK >> <a href="?page=affiliate-image-tracker">Home Page</a></strong></td>
  </tr>
  </table>
<br>
    <table border="0" width="100%" bgcolor="#f1f1f1" style="border:1px solid #e5e5e5">
     <tr >
       <td colspan="2" style="padding:4px 4px 4px 4px;background-color:#f1f1f1; color:#0066CC"><strong><?php echo ( $_GET['action'] == 'add'?'Add':'Edit') ?> Image >> <font color="#CC3300"><?php if($msg){ echo $msg; } ?></font></strong></td>
      </tr>
     <tr >
       <td width="18%" style="padding:3px 3px 3px 3px; background-color:#f1f1f1  "><strong>Image Name:</strong></td>
       <td width="82%" style="padding:3px 3px 3px 3px; background-color:#f1f1f1  ">
	   <input type="text" name="alt" id="alt" value="<?php echo ($edit['img_name']?$edit['img_name']:$_POST['alt']); ?>" size="20" maxlength="100" /> 
        * </td>
     </tr>
	 <?php if( $_GET['action'] == 'add' ){ ?>
     <tr>
       <td style="padding:3px 3px 3px 3px; background-color:#fff"><strong>Image URL:</strong></td>
       <td style="padding:3px 3px 3px 3px; background-color:#fff"><span style="display:block">
         <input type="file" name="url_local" id="url_local" size="35" />
         *
       </span></td>
     </tr>
	 <?php } ?>
     <tr>
       <td style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><strong>Link:</strong></td>
       <td style="padding:3px 3px 3px 3px; background-color:#f1f1f1">
	   <input type="text" name="link" id="link" size="60" maxlength="250" 
	   value="<?php echo ($edit['link_url']?$edit['link_url']:$_POST['link']); ?>"
	   onfocus="this.value=(this.value=='http://') ? '' : this.value;"
	   onblur="this.value=(this.value=='') ? 'http://' : this.value;"
	   />
         *&nbsp;&nbsp;&nbsp;&nbsp;
         <input type="checkbox" name="in_new_win" id="in_new_win" value="1" <?php if($edit['target_window'] == '1'){
		 echo 'checked';
		 }elseif($_POST['in_new_win']){ echo 'checked'; } ?> /> 
        Open in new window<br>
		<em style="font-size:11px">Note: If no Affiliate link provide your own http host link<br>
		Your http host link is : http://<?php echo $_SERVER['HTTP_HOST']; ?>
		</em>
		</td>
     </tr>
     <tr>
       <td style="padding:3px 3px 3px 3px; background-color:#fff"><strong>Image title:</strong></td>
       <td style="padding:3px 3px 3px 3px; background-color:#fff">
	   <input type="text" name="title" id="title" size="60" value="<?php echo ($edit['image_title']?$edit['image_title']:$_POST['title']); ?>" maxlength="250" /> 
        * </td>
     </tr>
     <tr>
      <td colspan="2" style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><input type="submit" name="upload" value="Upload" class="button" /></td>
     </tr>
    </table>
</form>	
    <br>
					
<?php }else{ ?>
    <table border="0" width="100%" bgcolor="#f1f1f1" style="border:1px solid #e5e5e5">
     <tr >
       <td colspan="6" style="padding:3px 3px 3px 3px; background-color:#f1f1f1" align="right">
	   <input type="button" value="Add New Affiliate Image" onclick="window.location.href='?page=affiliate-image-tracker&action=add'" /></td>
      </tr>
	  <?php if($msg){ ?>
     <tr >
       <td colspan="6" style="padding:3px 3px 3px 3px; background-color:#f1f1f1" align="left"><?php echo $msg; ?></td>
     </tr>
	 <?php } ?>
     <tr >
		<td width="5%" style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><div align="center" class="style1">S.no</div></td>
	    <td width="17%" style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><span class="style1">Image</span></td>
	    <td width="27%" style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><span class="style1">Link URL</span></td>
	    <td width="23%" style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><span class="style1">Image Title </span></td>
	    <td width="7%" style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><div align="center" class="style1">Hits</div></td>
        <td width="21%" style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><div align="center" class="style1">Action</div></td>
     </tr>
	 <?php
	 $affimg_sql =  mysql_query('select * from '.MBP_AFF_IMAGETRACKER_TBL.' ');
	 $noofrows = mysql_num_rows($affimg_sql);
	 if($noofrows > 0 ){
	 $i = 1;
	 while( $affrecords = mysql_fetch_array($affimg_sql) ){
	 ?>
     <tr >
       <td style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><div align="center"><?php echo $i; ?></div></td>
       <td style="padding:3px 3px 3px 3px; background-color:#f1f1f1">
	   <img src="<?php echo MBP_AIT_SITEURL.'/wp-content/affiliate-images/'.$affrecords['image_url']; ?>" width="30px" height="30px" >	   </td>
       <td style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><?php echo $affrecords['link_url']; ?></td>
       <td style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><?php echo $affrecords['image_title']; ?></td>
       <td style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><div align="center"><?php echo $affrecords['aff_hits']; ?></div></td>
       <td style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><div align="center"><?php
				if($affrecords['flag'] == 1){
				echo "<a href='?page=affiliate-image-tracker&action=pub&id=".$affrecords['image_id']."' style='text-decoration:none'>Publish</a>";
				}else{
				echo "<a href='?page=affiliate-image-tracker&action=unpub&id=".$affrecords['image_id']."' style='text-decoration:none;color:#CC0000'>Unpublish</a>";
				}
				?> | <a href="?page=affiliate-image-tracker&action=edit&id=<?php echo $affrecords['image_id']; ?>"  style='text-decoration:none;color: #006600'>Edit</a> | <a href="?page=affiliate-image-tracker&action=1&id=<?php echo $affrecords['image_id']; ?>" onclick=" return ConfirmDelete()" style='text-decoration:none;color:#CC0000'>Delete</a> </div></td>
     </tr>
	 <?php $i++; } } else { ?>
     <tr >
       <td colspan="6" style="padding:3px 3px 3px 3px; background-color:#fff">
	   <div align="center">No Affiliate Image Yet</div>	   </td>
      </tr>
	 <?php } ?>
	 </table>		
<br>
<form action="" method="post">
<?php if($advmsg){ ?><p><?php echo $advmsg; ?></p><?php } ?>
			<b><img src="<?php echo MBP_AIT_LIBPATH?>image/plus.gif" id="rep_img" border="0" /><a style="cursor:hand;cursor:pointer" onclick="__ShowHide('div1','rep_img','<?php echo MBP_AIT_LIBPATH ?>')">Advance Image Option:</a></b><br> 
				<div id="div1" style="display:none" >
					<div style="border:1px #ccc dashed; padding:10px 0px 10px 10px; width:98%; background-color:#f1f1f1;" >
<input name="img_option" type="radio" value="orig" <?php echo $orig; ?> /> &nbsp;Leave the image as it is.<br>
<input name="img_option" type="radio" value="high" <?php echo $high; ?> /> &nbsp;Scale to a specific HEIGHT.<br>
<input name="img_option" type="radio" value="wide" <?php echo $wide; ?> /> &nbsp;Scale to a specific WIDTH.<br>
<input name="img_option" type="radio" value="spec" <?php echo $spec; ?> /> &nbsp;Constrain both height & width.<br>
<div id="w_h_show" style="padding:5px 5px 5px 5px;background-color:#FFFFFF">Width:&nbsp;&nbsp;<input type="text" name="width"  value="<?php echo $img_data['1']; ?>" /><br>Height:&nbsp;<input type="text" name="height" value="<?php echo $img_data['2']; ?>"></div>
<input name="submit" type="Submit" value="Submit"  class="button" />
					</div><br>
				</div>
  </form>
 <br> 

	<b><img src="<?php echo MBP_AIT_LIBPATH?>image/plus.gif" id="rep_img2" border="0" /><a style="cursor:hand;cursor:pointer" onclick="__ShowHide('div2','rep_img2','<?php echo MBP_AIT_LIBPATH ?>')">Instructions:</a></b><br> 

<div id="div2" style="display:none" >
<div style="background-color:#f1f1f1; padding:2px 0px 2px 5px; border:1px #0066CC dashed" >
<p><b>Post Tag:</b><br>
<input type="text" style="width:200px" value="<!--mbpaffiliateimage-->">
<p><b>Template Tag:</b><br>
&lt;?php<br>
     if (function_exists('__MBPR_Affiliate_Image_Tag'))<br>
     {<br>
       echo  __MBPR_Affiliate_Image_Tag();<br>
     }<br>
?&gt;
</p>
</div></div>				
<br>

	<b><img src="<?php echo MBP_AIT_LIBPATH?>image/plus.gif" id="rep_img3" border="0" /><a style="cursor:hand;cursor:pointer" onclick="__ShowHide('div3','rep_img3','<?php echo MBP_AIT_LIBPATH ?>')">Powered Option:</a></b><br> 

<div id="div3" style="display:none" >

<form action="" method="post">
    <table border="0" width="100%" bgcolor="#f1f1f1" style="border:1px solid #e5e5e5">
     <tr >
		<td style="padding:3px 3px 3px 3px; background-color:#fff">
<input name="pwdby" type="checkbox" value="pwdby" <?php echo $pwdby; ?> /> &nbsp;Remove "powered by <?php echo MBP_AIT_NAME; ?>"&nbsp;  <a href="" onMouseover="tooltip('<?php echo $msg_ri_txt; ?>',480)" onMouseout="hidetooltip()" style="border-bottom:none;"><img src="<?php echo MBP_AIT_LIBPATH."image/help.gif"; ?>" border="0"></a><br>
		</td>
	</tr>
	
<tr>
<td style="padding:3px 3px 3px 3px; background-color:#f1f1f1"><input name="submit" type="Submit" value="Remove"  class="button" /></td>
</tr>
	</table>
</form>
</div>
<br>
<?php } ?>
<!--end test-->

<div align="center" style="background-color:#f1f1f1; padding:5px 0px 5px 0px" >
<p align="center"><strong><?php echo MBP_AIT_NAME.' '.MBP_AIT_VERSION; ?> by <a href="http://www.maxblogpress.com" target="_blank">MaxBlogPress</a></strong></p>
<p align="center">This plugin is the result of <a href="http://www.maxblogpress.com/blog/219/maxblogpress-revived/" target="_blank">MaxBlogPress Revived</a> project.</p>
</div>
</div>
           <?php
}
}

	/****************
		DISPLAY IMAGE ON TEMPLATE
	****************/
function affimage_post($post_content){

	global $post;
	global $wp_version;
	
	$pwdby = get_option('mbp_affimagetracker_advance_option');
		$post_tag = '<!--mbpaffiliateimage-->';
		$search = "(<!--\s*mbpaffiliateimage\s*-->)";
		///echo stristr($post_content,$post_tag);
		if ( stristr($post_content,$post_tag) ) { 
			if (preg_match_all($search, $post_content, $matches)) { 
			  	if ( is_array( $matches )) { 
					foreach ( $matches as $key => $val ) { 
					        $randomImage    = __MBPR_Affiliate_Image_Tag(); 
							$post_content   = preg_replace($search, $randomImage, $post_content, 1);
						}
			 	 }
			}   
		}
		return $post_content;
}

/**********
MAIN SYSTEM HANDLER
**********/
function __MBPR_Affiliate_Image_Tag(){

	$db_sql = "select * from ".MBP_AFF_IMAGETRACKER_TBL." where flag='1'";
	$process_result = mysql_query($db_sql);
	$noofrows = mysql_num_rows($process_result);
	
	if( $noofrows > 0 ){
	$bannerPath = MBP_AIT_ABSPATH.'wp-content/affiliate-images/';
	$bannerUrl =  MBP_AIT_SITEURL.'/wp-content/affiliate-images';
	$get_randomImagearray = get_option('mbp_affimagetracker_advance_option');
	$pwdbyoption = get_option('mbp_affimagetracker_pwdby_option');
	$scaleOption = $get_randomImagearray[0];
	$scaleHeight = $get_randomImagearray[2];
	$scaleWidth = $get_randomImagearray[1];
	$pwdby = $pwdbyoption[0];
	
	$image_types = array('jpg','png','gif'); // Array of valid image types 
	
	while( $randomImg = mysql_fetch_array($process_result) ){
		 $image_array[] = $randomImg['image_id'];
		 sort($image_array);
		 reset ($image_array);
	}
	
	$ramdomimgID=$image_array[rand(1,count($image_array))-1];
	$sql = "select * from ".MBP_AFF_IMAGETRACKER_TBL." where image_id=".$ramdomimgID." and flag='1'";
	$affvalue = mysql_query($sql);
	$affdata = mysql_fetch_array($affvalue);
	$image_filename = $affdata['image_url'];
	$target = $affdata['target_window'];
	if($target == 1){
	$openin = '_blank';
	}
	
	$filename=$bannerUrl.'/'.$image_filename;
	$imageInfo = getimagesize($bannerPath.'/'.$image_filename);
	 $physHeight = $imageInfo[1];
	 $physWidth = $imageInfo[0];
	
	switch($scaleOption)
	{
	  case 'high':  
	$ratio = $physHeight / $scaleHeight;
		 $physWidth = $physWidth / $ratio;
		 $physHeight = $scaleHeight;
	break;
	  case 'wide':
	$ratio = $physWidth / $scaleWidth;
		$physHeight = $physHeight / $ratio;
		$physWidth = $scaleWidth;
	break;
	  case 'spec':
	$physHeight = $scaleHeight;
	$physWidth = $scaleWidth;
	break;
	  default:
	break;
	}
	
	if( $pwdby == '' ){ $pwdby = '<br><a href="http://wordpress.org/extend/plugins/affiliate-image-tracker/" style="font-size:9px;font-weight:normal;font-color:#0000FF;letter-spacing:-1px" target="_blank">Powered by Affiliate Image Tracker</a><br>';
	}else{
	$pwdby = '';
	}
	
	return '<a href="'.MBP_AIT_LIBPATH.'tracker.php?id='.$ramdomimgID.'" target='.$openin.' ><img src="'.$filename.'" title="'.$affdata['image_title'].'" alt="'.$affdata['img_name'].'" height="'.$physHeight.'" width="'.$physWidth.'" border="0" /></a>'.$pwdby;
	}	
}

/***************
	WIDGET
****************/

if( $wp_version < 2.8  ) {
	add_action('plugins_loaded', 'MBP_affimage_widget');
}

function MBP_affimage_widget(){

		if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control') ) { 
		return; 
		}
		
		function AffiliateImageWidgetController() {
		if ( isset($_POST["aff_image_submit"]) == 1 ) {
		$randomimg_sidebar_title   = $_POST['aff_random_image'];
		update_option('mbp_affiliate_image_widget_title', $randomimg_sidebar_title);
		}
		$random_imgtitle = get_option('mbp_affiliate_image_widget_title');
		?>
		<div><strong>Title:</strong></div>
		<div><input type="text" name="aff_random_image" value="<?php echo $random_imgtitle; ?>"  style="width:180px"  /></div>
		<input type="hidden" name="aff_image_submit" id="aff_image_submit" value="1" />
		<?php
		}
	
		function AffiliateImageWidgetSidebar($args) {  
		global $wp_version;
		
		extract($args);
		echo $before_widget;
		echo $before_title;
		echo $after_title;
		$title = get_option('mbp_affiliate_image_widget_title');
		echo "<h2>".$title."</h2>";
		echo $randomImg = __MBPR_Affiliate_Image_Tag();
		echo $after_widget;
		}
		
		if ( function_exists('wp_register_sidebar_widget') ) { // fix for wordpress 2.2
			wp_register_sidebar_widget(sanitize_title('Affiliate Image Tracker'), 'Affiliate Image Tracker', 'AffiliateImageWidgetSidebar');
		} else {
			register_sidebar_widget('Affiliate Image Tracker', 'AffiliateImageWidgetSidebar');
		}
		register_widget_control('Affiliate Image Tracker', 'AffiliateImageWidgetController', '', '210px');
}


/* WP greater then 2.8 */
if( $wp_version >= 2.8  ) {
add_action('widgets_init', create_function('', 'return register_widget("mbp_alt_widget");'));
class mbp_alt_widget extends WP_Widget {
	function mbp_alt_widget() {
		parent::WP_Widget(false, $name = 'Affiliate Image Tracker');	
	}
	function widget($args, $instance) {		
		global $wp_version;
		extract( $args );
		echo $before_widget
			  . $before_title
			  . $instance['title']
			  . $after_title
			  . __MBPR_Affiliate_Image_Tag()
			  . $after_widget; 
	}
	function update($new_instance, $old_instance) {				
		return $new_instance;
	}
	function form($instance) {				
		$title = esc_attr($instance['title']);
		?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></label></p>
		<?php 
	}
}
}

/***************
	EOF WIDGET
****************/


// Srart Registration.

/**
 * Plugin registration form
 */
function mbp_altRegistrationForm($form_name, $submit_btn_txt='Register', $name, $email, $hide=0, $submit_again='') {
	$wp_url = get_bloginfo('wpurl');
	$wp_url = (strpos($wp_url,'http://') === false) ? get_bloginfo('siteurl') : $wp_url;
	$plugin_pg    = 'options-general.php';
	$thankyou_url = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'];
	$onlist_url   = $wp_url.'/wp-admin/'.$plugin_pg.'?page='.$_GET['page'].'&amp;mbp_onlist=1';
	if ( $hide == 1 ) $align_tbl = 'left';
	else $align_tbl = 'center';
	?>
	
	<?php if ( $submit_again != 1 ) { ?>
	<script><!--
	function trim(str){
		var n = str;
		while ( n.length>0 && n.charAt(0)==' ' ) 
			n = n.substring(1,n.length);
		while( n.length>0 && n.charAt(n.length-1)==' ' )	
			n = n.substring(0,n.length-1);
		return n;
	}
	function mbp_altValidateForm_0() {
		var name = document.<?php echo $form_name;?>.name;
		var email = document.<?php echo $form_name;?>.from;
		var reg = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
		var err = ''
		if ( trim(name.value) == '' )
			err += '- Name Required\n';
		if ( reg.test(email.value) == false )
			err += '- Valid Email Required\n';
		if ( err != '' ) {
			alert(err);
			return false;
		}
		return true;
	}
	//-->
	</script>
	<?php } ?>
	<table align="<?php echo $align_tbl;?>">
	<form name="<?php echo $form_name;?>" method="post" action="http://www.aweber.com/scripts/addlead.pl" <?php if($submit_again!=1){;?>onsubmit="return mbp_altValidateForm_0()"<?php }?>>
	 <input type="hidden" name="unit" value="maxbp-activate">
	 <input type="hidden" name="redirect" value="<?php echo $thankyou_url;?>">
	 <input type="hidden" name="meta_redirect_onlist" value="<?php echo $onlist_url;?>">
	 <input type="hidden" name="meta_adtracking" value="mr-affiliate-image-tracker">
	 <input type="hidden" name="meta_message" value="1">
	 <input type="hidden" name="meta_required" value="from,name">
	 <input type="hidden" name="meta_forward_vars" value="1">	
	 <?php if ( $submit_again == 1 ) { ?> 	
	 <input type="hidden" name="submit_again" value="1">
	 <?php } ?>		 
	 <?php if ( $hide == 1 ) { ?> 
	 <input type="hidden" name="name" value="<?php echo $name;?>">
	 <input type="hidden" name="from" value="<?php echo $email;?>">
	 <?php } else { ?>
	 <tr><td>Name: </td><td><input type="text" name="name" value="<?php echo $name;?>" size="25" maxlength="150" /></td></tr>
	 <tr><td>Email: </td><td><input type="text" name="from" value="<?php echo $email;?>" size="25" maxlength="150" /></td></tr>
	 <?php } ?>
	 <tr><td>&nbsp;</td><td><input type="submit" name="submit" value="<?php echo $submit_btn_txt;?>" class="button" /></td></tr>
	 </form>
	</table>
	<?php
}

/**
 * Register Plugin - Step 2
 */
function mbp_altRegisterStep2($form_name='frm2',$name,$email) {
	$msg = 'You have not clicked on the confirmation link yet. A confirmation email has been sent to you again. Please check your email and click on the confirmation link to activate the plugin.';
	if ( trim($_GET['submit_again']) != '' && $msg != '' ) {
		echo '<div id="message" class="updated fade"><p><strong>'.$msg.'</strong></p></div>';
	}
	?>
	<style type="text/css">
	table, tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_AIT_NAME.' '.MBP_AIT_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff; text-align:left;">
	  <tr><td align="center"><h3>Almost Done....</h3></td></tr>
	  <tr><td><h3>Step 1:</h3></td></tr>
	  <tr><td>A confirmation email has been sent to your email "<?php echo $email;?>". You must click on the link inside the email to activate the plugin.</td></tr>
	  <tr><td><strong>The confirmation email will look like:</strong><br /><img src="http://www.maxblogpress.com/images/activate-plugin-email.jpg" vspace="4" border="0" /></td></tr>
	  <tr><td>&nbsp;</td></tr>
	  <tr><td><h3>Step 2:</h3></td></tr>
	  <tr><td>Click on the button below to Verify and Activate the plugin.</td></tr>
	  <tr><td><?php mbp_altRegistrationForm($form_name.'_0','Verify and Activate',$name,$email,$hide=1,$submit_again=1);?></td></tr>
	 </table>
	 </td></tr></table><br />
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:1px solid #e3e3e3; padding:8px; background-color:#f1f1f1;">
	 <tr><td align="center">
	 <table width="650" cellpadding="5" cellspacing="1" style="border:1px solid #e9e9e9; padding:8px; background-color:#ffffff; text-align:left;">
	   <tr><td><h3>Troubleshooting</h3></td></tr>
	   <tr><td><strong>The confirmation email is not there in my inbox!</strong></td></tr>
	   <tr><td>Dont panic! CHECK THE JUNK, spam or bulk folder of your email.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>It's not there in the junk folder either.</strong></td></tr>
	   <tr><td>Sometimes the confirmation email takes time to arrive. Please be patient. WAIT FOR 6 HOURS AT MOST. The confirmation email should be there by then.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>6 hours and yet no sign of a confirmation email!</strong></td></tr>
	   <tr><td>Please register again from below:</td></tr>
	   <tr><td><?php mbp_altRegistrationForm($form_name,'Register Again',$name,$email,$hide=0,$submit_again=2);?></td></tr>
	   <tr><td><strong>Help! Still no confirmation email and I have already registered twice</strong></td></tr>
	   <tr><td>Okay, please register again from the form above using a DIFFERENT EMAIL ADDRESS this time.</td></tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr>
		 <td><strong>Why am I receiving an error similar to the one shown below?</strong><br />
			 <img src="http://www.maxblogpress.com/images/no-verification-error.jpg" border="0" vspace="8" /><br />
		   You get that kind of error when you click on &quot;Verify and Activate&quot; button or try to register again.<br />
		   <br />
		   This error means that you have already subscribed but have not yet clicked on the link inside confirmation email. In order to  avoid any spam complain we don't send repeated confirmation emails. If you have not recieved the confirmation email then you need to wait for 12 hours at least before requesting another confirmation email. </td>
	   </tr>
	   <tr><td>&nbsp;</td></tr>
	   <tr><td><strong>But I've still got problems.</strong></td></tr>
	   <tr><td>Stay calm. <strong><a href="http://www.maxblogpress.com/contact-us/" target="_blank">Contact us</a></strong> about it and we will get to you ASAP.</td></tr>
	 </table>
	 </td></tr></table>
	 </center>		
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_AIT_NAME.' '.MBP_AIT_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}

/**
 * Register Plugin - Step 1
 */
function mbp_altRegisterStep1($form_name='frm1',$userdata) {
	$name  = trim($userdata->first_name.' '.$userdata->last_name);
	$email = trim($userdata->user_email);
	?>
	<style type="text/css">
	tabled , tbody, tfoot, thead {
		padding: 8px;
	}
	tr, th, td {
		padding: 0 8px 0 8px;
	}
	</style>
	<div class="wrap"><h2> <?php echo MBP_AIT_NAME.' '.MBP_AIT_VERSION; ?></h2>
	 <center>
	 <table width="100%" cellpadding="3" cellspacing="1" style="border:2px solid #e3e3e3; padding: 8px; background-color:#f1f1f1;">
	  <tr><td align="center">
		<table width="548" align="center" cellpadding="3" cellspacing="1" style="border:1px solid #e9e9e9; padding: 8px; background-color:#ffffff;">
		  <tr><td align="center"><h3>Please register the plugin to activate it. (Registration is free)</h3></td></tr>
		  <tr><td align="left">In addition you'll receive complimentary subscription to MaxBlogPress Newsletter which will give you many tips and tricks to attract lots of visitors to your blog.</td></tr>
		  <tr><td align="center"><strong>Fill the form below to register the plugin:</strong></td></tr>
		  <tr><td align="center"><?php mbp_altRegistrationForm($form_name,'Register',$name,$email);?></td></tr>
		  <tr><td align="center"><font size="1">[ Your contact information will be handled with the strictest confidence <br />and will never be sold or shared with third parties ]</font></td></tr>
		</table>
	  </td></tr></table>
	 </center>
	<p style="text-align:center;margin-top:3em;"><strong><?php echo MBP_AIT_NAME.' '.MBP_AIT_VERSION; ?> by <a href="http://www.maxblogpress.com/" target="_blank" >MaxBlogPress</a></strong></p>
	</div>
	<?php
}
?>
