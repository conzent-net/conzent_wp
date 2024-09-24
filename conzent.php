<?php
/**
* @link            https://conzent.net/
*
* @wordpress-plugin
* Plugin Name: Conzent - Cookie Banner - Conzent CMP - Google CMP & IAB TCF Certified
* Plugin URI: https://conzent.net/
* Description: Conzent CMP WordPress Cookie Banner and Cookie Policy generator. IAB/TCF and Google CMP Certified - Comply with the major data protection laws (GDPR, ePrivacy, CCPA, LGPD, etc.)
* Author: Conzent ApS
* Version: 1.0.3
* Author URI: https://conzent.net/
* License:           GPLv3
* License URI:       https://www.gnu.org/licenses/gpl-3.0.html
* Text Domain: conzent
**/

if ( ! defined( 'ABSPATH' ) ) {
	exit;// Abort if this file is accessed directly.
}

add_action('wp_enqueue_scripts', 'cnzbannerfront');
add_action('admin_enqueue_scripts','cnzbanneradmin');
add_action('init', 'cnz_banner_register_hooks');
add_action('init', 'cnz_banner_add_shortcode');
add_action('admin_menu', 'cnz_add_menu_items');
add_action('plugins_loaded', 'cnz_update_check');
add_action('activated_plugin', 'cnz_save_activation_error');
add_action('wp_body_open','add_cnz_gtm_after_body');
/** Conzent web app URL */
if ( ! defined( 'CNZ_APP_URL' ) ) {
	define( 'CNZ_APP_URL', 'https://conzent.net/app' );
}

/** Conzent web app script  URL. */
if ( ! defined( 'CNZ_APP_API_URL' ) ) {
	define( 'CNZ_APP_API_URL', 'https://conzent.net/app/api/v1' );
}
function cnz_update_check() {
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
function cnz_save_activation_error()
{
    $error = ob_get_contents();
    if (!empty($error)) {
        update_option('conzent_plugin_error', $error);
    }
}
function cnz_activate() {

  	if (get_option('conzent_db_version') === false) {
		//Install
		cnz_callback('install');
	} else {
		//Update
		cnz_callback('update');
	}
	
	$plugin_data = get_plugin_data( __FILE__ );
	$plugin_version = $plugin_data['Version'];
	$conzent_db_version = $plugin_version;
	update_option('conzent_db_version', $conzent_db_version);

  /* activation code here */
}
register_activation_hook( __FILE__, 'cnz_activate' );
function cnzbannerfront()
{
	if (!is_admin()) {
		wp_enqueue_style('cnz-banner-css', plugins_url('assets/css/conzent-banner.css', __FILE__), false, false, 'screen');
		wp_enqueue_script('cnz-banner-js',plugins_url('assets/js/conzent-banner.js', __FILE__),false, false,true);
	}
}
function cnzbanneradmin()
{	
	if (is_admin()) {
		wp_enqueue_style('cnz-banner-admin-css', plugins_url('assets/css/conzent-banner-admin.css', __FILE__), false, false, 'screen');
	}
}

function cnz_callback($action)
{
	if($action == 'install'){
		$site_info = cnz_verifyDomain();
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
        cnz_update_check();
	}
}
function cnz_banner_register_hooks(){
	if (!is_admin()) {
		add_action( 'wp_head','add_cnz_js', - 9998 );
		add_action( 'wp_head','add_cnz_gtm_js', - 9996 );
	}
}
function add_cnz_js(){
	echo __('<script id="conzentbanner" type="text/javascript" src="'.CNZ_APP_URL.'/app/sites_data/'.get_option( 'conzent_website_key' ).'/script.js"></script>');
}
function cnz_add_menu_items() {

	$hook = add_menu_page(
		__( 'Conzent Banner', 'conzent' ), // Page title.
		__( 'Conzent Banner', 'conzent' ),        // Menu title.
		'activate_plugins',                                         // Capability.
		'conzent',                                             // Menu slug.
		'cnz_banner_setting',                                       // Callback function.
		plugin_dir_url(__FILE__) . 'assets/images/logo_icon.png',
		90
	);
	add_submenu_page( 'conzent', __( 'Setting', 'conzent' ), __( 'Setting', 'conzent' ), 'manage_options', 'cnz_banner_setting', 'cnz_setting_actions',1 );
	
}
function cnz_banner_setting() {
	if(isset($_GET['refresh'])){
		cnz_get_siteinfo();
		echo __('<script>window.location = "'.admin_url('admin.php?page=conzent').'"</script>');
		exit;
	}
	else{
	?>
    <div class="opt_welcome"><h2><img src="<?php echo plugin_dir_url(__FILE__) . 'conzent-logo.png';?>" height="35px" />&nbsp;<?php echo esc_html__('Welcome to Conzent Banner','conzent');?></h2></div>
    <div class="opt_box_welcome">
    <div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Website Key :','conzent');?></div>
        <div class="opt_val"> <?php echo get_option( 'conzent_website_key');?></div>
    </div>
    <!--<div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Website Id :','conzent');?></div>
        <div class="opt_val"> <?php echo get_option( 'conzent_site_id');?></div>
    </div>-->
    <div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Site Name:','conzent');?></div>
        <div class="opt_val"> <?php echo get_option( 'conzent_site_name');?></div>
     </div>
    <div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Domain:','conzent');?></div>
        <div class="opt_val"> <?php echo get_option( 'conzent_site_domain');?></div>
    </div>
    <div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Status:','conzent');?></div>
        <div class="opt_val"> <?php echo (get_option( 'conzent_site_status') == 1 ? 'Active':'Inactive');?></div>
    </div>
    <div style="margin:10px 0px;"><a href="<?php echo admin_url('admin.php?page=conzent&refresh=1')?>" class="cnz-btn"><?php echo esc_html__('Refresh','conzent');?></a></div>
	<div style="margin-top:20px;margin-bottom:20x;"><a href="<?php echo CNZ_APP_URL;?>" class="cnz-btn-normal"><?php echo esc_html__('All settings is done in the conzent.net/app','conzent');?></a></div>
    </div>
    
    <?php
	}
}
function cnz_setting_actions()
{

$current_user = wp_get_current_user();
	$is_admin=false;
	if(in_array('administrator',$current_user->roles)){
		$is_admin=true;
	}
	if(!$is_admin){
		$redirect_url = admin_url('admin.php?page=conzent');
		echo __('<script>window.location = "'.esc_url($redirect_url).'"</script>');
		exit;
	}
$msg ='';	
if(isset($_POST['action']) && $_POST['action']=='savesetting'){
	update_option('conzent-gtm-id',$_POST['conzent_gtm_id']);
	update_option('conzent-data-layer',$_POST['conzent_data_layer']);
	$msg = esc_html__('Setting saved successfully','conzent');
	
}
$conzent_gtm_id=get_option('conzent-gtm-id','');
$conzent_data_layer=get_option('conzent-data-layer','');
$conzent_site_id  = get_option( 'conzent_site_id');
$conzent_website_key  = get_option( 'conzent_website_key');


	?>
    <div class="opt_welcome"><h2><img src="<?php echo plugin_dir_url(__FILE__) . 'conzent-logo.png';?>" height="35px" />&nbsp;<?php echo esc_html__('Conzent Banner Setting','conzent');?></h2></div>
    <div class="opt_box_setting">
    <div><?php if($msg){ echo '<div class="cnz-success">'.$msg.'</div>';}?></div>
    	<form method="post" action="" name="frmsetting">
        <!--<div class="opt_item">
          		<div class="opt_key"><?php echo esc_html__('Website ID :','conzent');?> <span style="font-weight:normal;"><?php echo $conzent_site_id;?></span></div>
         	</div>
		--> 
         <div class="opt_item">
          <div class="opt_key"><?php echo esc_html__('Website Key :','conzent');?> <span style="font-weight:normal;"><?php echo $conzent_website_key;?></span></div>
          
         </div>
        <div class="opt_item">
          <div class="opt_key"><?php echo esc_html__('Google Tag Manager ID :','conzent');?></div>
           <div class="opt_val">
            <input type="text" name="conzent_gtm_id" id="conzent_gtm_id" value="<?php echo $conzent_gtm_id;?>" style="min-width:300px;"/>
          </div>
         </div>
         <div class="opt_item"> 
          <div class="opt_key"><?php echo esc_html__('Google Tag Data layer :','conzent');?></div>
           <div class="opt_val">
            <input type="text" name="conzent_data_layer" id="conzent_data_layer" value="<?php echo $conzent_data_layer;?>" placeholder="dataLayer" style="min-width:300px;"/>
          </div>
         </div>
          <div class="action_box">
            <input type="hidden" name="action" value="savesetting" />
            <input type="submit" name="savesett" value="Save" class="cnz-btn"/>
          </div>
        </form>
        <div style="margin-top:20px;margin-bottom:20x;"><a href="<?php echo CNZ_APP_URL;?>" class="cnz-btn-normal"><?php echo esc_html__('All settings is done in the conzent.net/app','conzent');?></a></div>
    </div>
<?php
}
function cnz_get_siteinfo(){
	$site_info = cnz_verifyDomain();
	  if(!empty($site_info) && array_key_exists("domain",$site_info)){
		update_option( 'conzent_website_key',$site_info['website_key'] );
		update_option( 'conzent_site_name', $site_info['site_name'] );
		update_option( 'conzent_site_domain', $site_info['domain'] );
		update_option( 'conzent_site_status', $site_info['status'] );
		update_option( 'conzent_site_id', $site_info['id'] );
	  }
}
function cnz_banner_add_shortcode(){
	add_shortcode('CONZENT_CONSENT_ID', 'cnz_consent_id');
	add_shortcode('conzent_consent_id', 'cnz_consent_id');
}
function cnz_consent_id($atts, $content) {
	 global $post;
  	$result_content = '';
	$default = array();
    $short_arr = shortcode_atts($default, $atts);
	$result_content = '
	<div class="cnz-tracking-box">
		<div class="tracking-inner">
			<div class="cnz-label">Conzent Consent ID</div>
			<div class="cnz-val"><span id="conzentId"></span></div>
		</div>
	</div>';
	return $result_content;
}
function cnz_verifyDomain() {
 	$items = array();
	$api_url = CNZ_APP_API_URL."/verify?domain=".sanitize_url($_SERVER['SERVER_NAME']);
	$response_obj = wp_remote_get($api_url);
	
	$http_code = wp_remote_retrieve_response_code( $response_obj );
	
	if($http_code == 200){
		$items = json_decode(wp_remote_retrieve_body( $response_obj ),true);
	}
	return $items;
}
function add_cnz_gtm_after_body() { 
  if(get_option( 'conzent-gtm-id')){
  ?>
  <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_js( get_option( 'conzent-gtm-id' ) ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
  <?php
  }
}
function add_cnz_gtm_js(){

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