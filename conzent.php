<?php
/**
* @link https://conzent.net/
*
* @wordpress-plugin
* Plugin Name: Conzent - Cookie Banner - Conzent CMP - Google CMP & IAB TCF Certified
* Plugin URI: https://conzent.net/download/
* Description: Conzent CMP WordPress Cookie Banner and Cookie Policy generator. IAB/TCF and Google CMP Certified - Comply with the major data protection laws (GDPR, ePrivacy, CCPA, LGPD, etc.)
* Version: 1.0.9
* Requires at least: 5.8
* Requires PHP: 7.3
* Code Name: Conzent
* Author: Conzent ApS
* Author URI: https://conzent.net/about/
* License: GPLv3
* License URI: https://www.gnu.org/licenses/gpl-3.0.html
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
add_action('wp_body_open','cnz_add_gtm_after_body');
 
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

	
	
	if (!get_site_option('conzent_verified')) {
		if(get_site_option('conzent_website_key') && get_site_option('conzent_site_domain')){
			add_option( 'conzent_verified','yes');
		}
		else{
			add_option( 'conzent_verified','');
		}
    }
	if (!get_site_option( 'conzent_error' )) {
		add_option( 'conzent_error','');
    }
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
		add_option( 'conzent_website_key','');
		add_option( 'conzent_site_name','');
		add_option( 'conzent_site_domain','');
		add_option( 'conzent_site_status','');
		add_option( 'conzent_site_id','');
		add_option( 'conzent_verified','');
		add_option( 'conzent_error','');
	}
	else if($action == 'update')
	{
        cnz_update_check();
	}
}
function cnz_banner_register_hooks(){
	if (!is_admin()) {
		add_action( 'wp_head','cnz_js', - 9998 );
		add_action( 'wp_head','cnz_gtm_js', - 9996 );
	}
}
function cnz_js(){
	$is_verified = get_option( 'conzent_verified');
	if($is_verified == 'yes'){
	?>
	<script id='conzentbanner' data-consent='necessary' type='text/javascript' src='<?php echo esc_url(CNZ_APP_URL."/sites_data/".get_option( 'conzent_website_key' ));?>/script.js'></script>
	<?php 
	}
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
	$setting_url= esc_url(admin_url('admin.php?page=cnz_banner_setting'));
	$web_url = esc_url(CNZ_APP_URL);
	$logo_url = plugin_dir_url(__FILE__).'conzent-logo.png';
	?>
    <div class="opt_welcome"><h2><img src="<?php echo esc_attr($logo_url);?>" height="35px" />&nbsp;<?php echo esc_html__('Welcome to Conzent Banner','conzent');?></h2></div>
    <div class="opt_box_welcome">
    <div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Website Key :','conzent');?></div>
        <div class="opt_val"> <?php echo esc_attr(get_option( 'conzent_website_key'));?></div>
    </div>
    <!--<div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Website Id :','conzent');?></div>
        <div class="opt_val"> <?php echo esc_attr(get_option( 'conzent_site_id'));?></div>
    </div>
    <div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Site Name:','conzent');?></div>
        <div class="opt_val"> <?php echo esc_attr(get_option( 'conzent_site_name'));?></div>
     </div>
    <div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Domain:','conzent');?></div>
        <div class="opt_val"> <?php echo esc_attr(get_option( 'conzent_site_domain'));?></div>
    </div>
    <div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Status:','conzent');?></div>
        <div class="opt_val"> <?php echo esc_attr(get_option( 'conzent_site_status') == 1 ? 'Active':'Inactive');?></div>
    </div>-->
	<div class="opt_item">
    	<div class="opt_key"><?php echo esc_html__('Verified:','conzent');?></div>
        <div class="opt_val"> <?php echo esc_attr(get_option( 'conzent_verified'));?></div>
    </div>
    <div style="margin:10px 0px;"><a href="<?php echo $setting_url;?>" class="cnz-btn"><?php echo esc_html__('Change Setting','conzent');?></a></div>
	<div style="margin-top:20px;margin-bottom:20x;"><a href="<?php echo $web_url;?>" class="cnz-btn-normal"><?php echo esc_html__('All settings is done in the conzent.net/app','conzent');?></a></div>
    </div>
    
    <?php
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
		wp_redirect(esc_url($redirect_url));
		exit;
	}
$msg ='';
if ( isset( $_POST['savesetting_nonce'] ) && wp_verify_nonce( $_POST['savesetting_nonce'], 'savesetting' ) ) {	
	
	$site_info = cnz_verifyWebsite(sanitize_text_field($_POST['conzent_website_key']));
	$error_found = 0;
	  if(!empty($site_info) && array_key_exists("domain",$site_info)){
		update_option( 'conzent_website_key',$site_info['website_key'] );
		update_option( 'conzent_site_name', $site_info['site_name'] );
		update_option( 'conzent_site_domain', $site_info['domain'] );
		update_option( 'conzent_site_status', $site_info['status'] );
		update_option( 'conzent_site_id', $site_info['id'] );
		update_option( 'conzent_verified', 'yes');
		$error_found = 0;
	}
	else{
        update_option( 'conzent_website_key',$_POST['conzent_website_key'] );
		update_option( 'conzent_site_name', '');
		update_option( 'conzent_site_domain', '');
		update_option( 'conzent_site_status', '1' );
		update_option( 'conzent_site_id', '');
		update_option( 'conzent_verified', 'yes');
		update_option( 'conzent_error', '');
		$error_found = 0;
	}
	update_option('conzent-gtm-id',sanitize_text_field($_POST['conzent_gtm_id']));
	update_option('conzent-data-layer',sanitize_text_field($_POST['conzent_data_layer']));
	if($error_found){
		$msg = esc_html__('Website Key not found','conzent');
		$cnz_css_class = 'error';
	}
	else{
		$msg = esc_html__('Setting saved successfully','conzent');
		$cnz_css_class = 'success';
	}
	
}
$conzent_gtm_id=get_option('conzent-gtm-id','');
$conzent_data_layer=get_option('conzent-data-layer','');
$conzent_site_id  = get_option( 'conzent_site_id');
$conzent_website_key  = get_option( 'conzent_website_key');
$conzent_verified  = get_option( 'conzent_verified');
$conzent_error  = get_option( 'conzent_error','');


	?>
    <div class="opt_welcome"><h2><img src="<?php echo plugin_dir_url(__FILE__) . 'conzent-logo.png';?>" height="35px" />&nbsp;<?php echo esc_html__('Conzent Banner Setting','conzent');?></h2></div>
    <div class="opt_box_setting">
    <div><?php if($msg){ echo '<div class=\'cnz-'.$cnz_css_class.'\'>'.$msg.'</div>';}?></div>
    	<form method="post" action="" name="frmsetting">
        <!--<div class="opt_item">
          		<div class="opt_key"><?php echo esc_html__('Website ID :','conzent');?> <span style="font-weight:normal;"><?php echo esc_attr($conzent_site_id);?></span></div>
         	</div>
		--> 
         <div class="opt_item">
          <div class="opt_key"><?php echo esc_html__('Website Key :','conzent');?></div>
          <div class="opt_val">
            <input type="text" name="conzent_website_key" id="conzent_website_key" value="<?php echo esc_attr($conzent_website_key);?>" style="min-width:300px;"/>
          </div>
         </div>
        <div class="opt_item">
          <div class="opt_key"><?php echo esc_html__('Google Tag Manager ID :','conzent');?></div>
           <div class="opt_val">
            <input type="text" name="conzent_gtm_id" id="conzent_gtm_id" value="<?php echo esc_attr($conzent_gtm_id);?>" style="min-width:300px;"/>
          </div>
         </div>
         <div class="opt_item"> 
          <div class="opt_key"><?php echo esc_html__('Google Tag Data layer :','conzent');?></div>
           <div class="opt_val">
            <input type="text" name="conzent_data_layer" id="conzent_data_layer" value="<?php echo esc_attr($conzent_data_layer);?>" placeholder="dataLayer" style="min-width:300px;"/>
          </div>
         </div>
		 <?php if(get_option( 'conzent_verified')!=''){?>
		<div class="opt_item"> 
          <div class="opt_key"><?php echo esc_html__('Verified :','conzent');?> <?php echo esc_attr(get_option( 'conzent_verified'));?></div>
           
         </div>
		 
		<?php 
		}
		 ?>
          <div class="action_box">
            <input type="hidden" name="action" value="savesetting" />
			<?php wp_nonce_field( 'savesetting', 'savesetting_nonce' ); ?>
            <input type="submit" name="savesett" value="Save" class="cnz-btn"/>
          </div>
        </form>
        <div style="margin-top:20px;margin-bottom:20x;"><a href="<?php echo esc_url(CNZ_APP_URL);?>" class="cnz-btn-normal"><?php echo esc_html__('All settings is done in the conzent.net/app','conzent');?></a></div>
    </div>
<?php
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
			<div class="cnz-label">'.esc_html__('Conzent Consent ID','conzent').'</div>
			<div class="cnz-val"><span id="conzentId"></span></div>
		</div>
	</div>';
	return $result_content;
}
function cnz_verifyWebsite($website_id) {
 	$items = array();
	$api_url = esc_url(CNZ_APP_API_URL."/verify?website_id=".$website_id);
	$response_obj = wp_remote_get($api_url);
    
	$http_code = wp_remote_retrieve_response_code( $response_obj );

	if($http_code == 200){
		$items = json_decode(wp_remote_retrieve_body( $response_obj ),true);
	}
	return $items;
}
function cnz_add_gtm_after_body() { 
  if(get_option( 'conzent-gtm-id') && get_option( 'conzent_verified') == 'yes'){
  ?>
  <!-- Google Tag Manager (noscript) -->
<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_js( get_option( 'conzent-gtm-id' ) ); ?>"
height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->
  <?php
  }
}
function cnz_gtm_js(){

	if ( empty( get_option( 'conzent-data-layer' ) ) ) {
		$data_layer = 'dataLayer';
	} else {
		$data_layer = get_option( 'conzent-data-layer' );
	}
	if(get_option( 'conzent-gtm-id') && get_option( 'conzent_verified') == 'yes'){
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