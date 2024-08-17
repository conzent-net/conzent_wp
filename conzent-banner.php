<?php
/**
Plugin Name: Conzent
Plugin URI: https://conzent.net/
Description: WordPress cookie banner and cookie policy help you comply with the major data protection laws (GDPR, ePrivacy, CCPA, LGPD, etc.).
Author: Conzent ApS
Version: 1.0.1
Author URI: https://conzent.net/
Text Domain: conzent-banner
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit;// Abort if this file is accessed directly.
}
require 'plugin-update-checker.php';
$conzentUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
    'https://conzent.net/updates/plugin/updateinfo.json',
    __FILE__, //Full path to the main plugin file or functions.php.
    'conzent-banner.php'
);
add_action('wp_enqueue_scripts', 'enqueue_cnzbannerfront');
add_action('admin_enqueue_scripts','enqueue_cnzbanneradmin');
add_action( 'init', 'conzent_banner_register_hooks' );
add_action( 'init', 'conzent_banner_add_shortcode' );
add_action( 'admin_menu', 'conzent_add_menu_items' );
add_action('plugins_loaded', 'conzent_update_check');
add_action('activated_plugin', 'conzent_save_activation_error');
add_action( 'wp_body_open','include_conzent_gtm_after_body');
function conzent_update_check() {
	if (!function_exists('get_plugin_data')) {
         require_once ABSPATH . 'wp-admin/includes/plugin.php';
     }
	$plugin_data = get_plugin_data( __FILE__ );
	$plugin_version = $plugin_data['Version'];
	$conzent_db_version = $plugin_version;

	if (get_site_option( 'conzent_db_version' ) != $conzent_db_version) {
		update_option('conzent_db_version', $conzent_db_version);
    }
}
function conzent_save_activation_error()
{
    $error = ob_get_contents();
    if (!empty($error)) {
        update_option('conzent_plugin_error', $error);
    }
}
function conzent_activate() {

  	if (get_option('conzent_db_version') === false) {
		//Install
		conzent_callback('install');
	} else {
		//Update
		conzent_callback('update');
	}
	
	$plugin_data = get_plugin_data( __FILE__ );
	$plugin_version = $plugin_data['Version'];
	$conzent_db_version = $plugin_version;
	update_option('conzent_db_version', $conzent_db_version);

  /* activation code here */
}
register_activation_hook( __FILE__, 'conzent_activate' );
function enqueue_cnzbannerfront()
{
	if (!is_admin()) {
		wp_enqueue_style('cnz-banner-css', plugins_url('assets/css/conzent-banner.css', __FILE__), false, false, 'screen');
		wp_enqueue_script('cnz-banner-js',plugins_url('assets/js/conzent-banner.js', __FILE__),false, false,true);
	}
}
function enqueue_cnzbanneradmin()
{	
	if (is_admin()) {
		wp_enqueue_style('cnz-banner-admin-css', plugins_url('assets/css/conzent-banner-admin.css', __FILE__), false, false, 'screen');
	}
}

function conzent_callback($action)
{
	if($action == 'install'){
		$site_info = conzent_phonehome();
		if(!empty($site_info) && array_key_exists("domain",$site_info)){
			add_option( 'conzent_website_key',$site_info['website_key'] );
			add_option( 'conzent_site_name', $site_info['site_name'] );
			add_option( 'conzent_site_domain', $site_info['domain'] );
			add_option( 'conzent_site_status', $site_info['status'] );
			add_option( 'conzent_site_id', $site_info['id'] );
		}
	}
	else if($action == 'update')
	{
        conzent_update_check();
	}
}
function conzent_banner_register_hooks(){
	if (!is_admin()) {
		add_action( 'wp_head','include_conzent_js', - 9998 );
		//add_action( 'wp_head','include_conzent_gcm_js', - 9997 );
		add_action( 'wp_head','include_conzent_gtm_js', - 9996 );
		
	}
}
function conzent_add_menu_items() {

	$hook = add_menu_page(
		__( 'Conzent Banner', 'conzent-banner' ), // Page title.
		__( 'Conzent Banner', 'conzent-banner' ),        // Menu title.
		'activate_plugins',                                         // Capability.
		'conzent-banner',                                             // Menu slug.
		'conzent_banner_setting',                                       // Callback function.
		plugin_dir_url(__FILE__) . 'assets/images/logo_icon.png',
		90
	);
	add_submenu_page( 'conzent-banner', __( 'Setting', 'conzent-banner' ), __( 'Setting', 'conzent-banner' ), 'manage_options', 'conzent_banner_setting', 'conzent_setting_actions',1 );
	
}
function conzent_banner_setting() {
	if(isset($_GET['refresh'])){
		conzent_get_siteinfo();
		echo '<script>window.location="'.admin_url('admin.php?page=conzent-banner').'";</script>';
	}
	?>
    <div class="opt_welcome"><h2><img src="<?php echo plugin_dir_url(__FILE__) . 'conzent-banner-logo.png';?>" height="35px" />&nbsp;Welcome to Conzent Banner</h2></div>
    <div class="opt_box_welcome">
    <div class="opt_item">
    	<div class="opt_key">Website Key :</div>
        <div class="opt_val"> <?php echo get_option( 'conzent_website_key');?></div>
    </div>
    <div class="opt_item">
    	<div class="opt_key">Website Id :</div>
        <div class="opt_val"> <?php echo get_option( 'conzent_site_id');?></div>
    </div>
    <div class="opt_item">
    	<div class="opt_key">Site Name:</div>
        <div class="opt_val"> <?php echo get_option( 'conzent_site_name');?></div>
     </div>
    <div class="opt_item">
    	<div class="opt_key">Domain:</div>
        <div class="opt_val"> <?php echo get_option( 'conzent_site_domain');?></div>
    </div>
    <div class="opt_item">
    	<div class="opt_key">Status:</div>
        <div class="opt_val"> <?php echo (get_option( 'conzent_site_status') == 1 ? 'Active':'Inactive');?></div>
    </div>
    <div style="margin:10px 0px;"><a href="<?php echo admin_url('admin.php?page=conzent-banner&refresh=1')?>" class="cnz-btn">Refresh</a></div>
    </div>
    
    <?php
}
function conzent_setting_actions()
{

$current_user = wp_get_current_user();
	$is_admin=false;
	if(in_array('administrator',$current_user->roles)){
		$is_admin=true;
	}
	if(!$is_admin){
	$redirect_url = admin_url('admin.php?page=conzent-banner');
		?>
<script>
			window.location = '<?php echo $redirect_url; ?>';
		</script>
<?php
	}
$msg ='';	
if(isset($_POST['action']) && $_POST['action']=='savesetting'){
	update_option('conzent-gtm-id',$_POST['conzent_gtm_id']);
	update_option('conzent-data-layer',$_POST['conzent_data_layer']);
	$msg = 'Setting saved successfully';
	
}
$conzent_gtm_id=get_option('conzent-gtm-id','');
$conzent_data_layer=get_option('conzent-data-layer','');
$conzent_site_id  = get_option( 'conzent_site_id');
$conzent_website_key  = get_option( 'conzent_website_key');


	?>
    <div class="opt_welcome"><h2><img src="<?php echo plugin_dir_url(__FILE__) . 'conzent-banner-logo.png';?>" height="35px" />&nbsp;Conzent Banner Setting</h2></div>
    <div class="opt_box_setting">
    <div><?php if($msg){ echo '<div class="cnz-success">'.$msg.'</div>';}?></div>
    	<form method="post" action="" name="frmsetting">
        <div class="opt_item">
          <div class="opt_key">Website ID : <span style="font-weight:normal;"><?php echo $conzent_site_id;?></span></div>
         </div>
         <div class="opt_item">
          <div class="opt_key">Website Key : <span style="font-weight:normal;"><?php echo $conzent_website_key;?></span></div>
          
         </div>
        <div class="opt_item">
          <div class="opt_key">Google Tag Manager ID</div>
           <div class="opt_val">
            <input type="text" name="conzent_gtm_id" id="conzent_gtm_id" value="<?php echo $conzent_gtm_id;?>" style="min-width:300px;"/>
          </div>
         </div>
         <div class="opt_item"> 
          <div class="opt_key">Google Tag Data layer</div>
           <div class="opt_val">
            <input type="text" name="conzent_data_layer" id="conzent_data_layer" value="<?php echo $conzent_data_layer;?>" placeholder="dataLayer" style="min-width:300px;"/>
          </div>
         </div>
          <div class="action_box">
            <input type="hidden" name="action" value="savesetting" />
            <input type="submit" name="savesett" value="Save" class="cnz-btn"/>
          </div>
        </form>
        <div style="margin-top:20px;margin-bottom:20x;">All settings is done in the conzent.net/app</div>
    </div>
<?php
}
function include_conzent_js(){
	echo '<script id="conzentbanner" type="text/javascript" src="https://conzent.net/app/sites_data/'.get_option( 'conzent_website_key' ).'/script.js"></script>';
}
function include_conzent_gcm_js(){
	return '';
}

function conzent_get_siteinfo(){
	$site_info = conzent_phonehome();
	  if(!empty($site_info) && array_key_exists("domain",$site_info)){
		update_option( 'conzent_website_key',$site_info['website_key'] );
		update_option( 'conzent_site_name', $site_info['site_name'] );
		update_option( 'conzent_site_domain', $site_info['domain'] );
		update_option( 'conzent_site_status', $site_info['status'] );
		update_option( 'conzent_site_id', $site_info['id'] );
	  }
}
function conzent_banner_add_shortcode(){
	add_shortcode('CONZENT_CONSENT_ID', 'get_conzent_consent_id');
	add_shortcode('conzent_consent_id', 'get_conzent_consent_id');
}
function get_conzent_consent_id($atts, $content) {
	 global $post;
  	$result_content = '';
	$default = array();
    $short_arr = shortcode_atts($default, $atts);
	//print_r($_COOKIE);
	$result_content = '
	<div class="cnz-tracking-box">
		<div class="tracking-inner">
			<div class="cnz-label">Conzent Consent ID</div>
			<div class="cnz-val"><span id="conzentId"></span></div>
		</div>
	</div>';
	return $result_content;
}
function conzent_phonehome() {
 	$items = array();
	$api_url = "https://conzent.net/app/api/v1/phonehome?domain=".$_SERVER['SERVER_NAME'];
	$curl = curl_init();			
	curl_setopt_array($curl, array(
	CURLOPT_URL => $api_url,
	CURLOPT_RETURNTRANSFER => true,
	CURLOPT_ENCODING => '',
	CURLOPT_MAXREDIRS => 10,
	CURLOPT_HTTPHEADER => array(
		'Content-Type: text/plain'
	),
	CURLOPT_SSL_VERIFYHOST=> 0,
	CURLOPT_SSL_VERIFYPEER=>0
	));
	
	$response_obj = curl_exec($curl);
	$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
	
	if($http_code == 200){
		$items = json_decode($response_obj,true);
	}
	curl_close($curl);
	return $items;
}
function include_conzent_gtm_after_body() { 
  if(get_option( 'conzent-gtm-id')){
  ?>
  <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_js( get_option( 'conzent-gtm-id' ) ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
  <?php
  }
}
function include_conzent_gtm_js(){

	if ( empty( get_option( 'conzent-data-layer' ) ) ) {
		$data_layer = 'dataLayer';
	} else {
		$data_layer = get_option( 'conzent-data-layer' );
	}
	if(get_option( 'conzent-gtm-id')){
?>
	<!-- Google Tag Manager -->
	<script>
        (function (w, d, s, l, i) {
            w[l] = w[l] || []; w[l].push({'gtm.start':new Date().getTime(), event: 'gtm.js'});
            var f = d.getElementsByTagName(s)[0],  j = d.createElement(s), dl = l !== 'dataLayer' ? '&l=' + l : '';
            j.async = true; j.src = 'https://www.googletagmanager.com/gtm.js?id=' + i + dl;
            f.parentNode.insertBefore(j, f);})(
            window,
            document,
            'script',
            '<?php echo esc_js( $data_layer ); ?>',
            '<?php echo esc_js( get_option( 'conzent-gtm-id' ) ); ?>'
        );
    </script>
    <!-- End Google Tag Manager -->
    <?php
    }
}

?>