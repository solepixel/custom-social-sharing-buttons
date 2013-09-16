<?php
/**
 * Plugin Name: Custom Social Sharing Buttons
 * Plugin URI: https://github.com/solepixel/custom-social-sharing-buttons/
 * Description: Gives you the ability to add customizable social sharing buttons to your website.
 * Version: 1.0.0
 * Author: Brian DiChiara
 * Author URI: http://www.briandichiara.com
 * License: GPLv2
 */

define('CSSB_VERSION', '1.0.0');
define('CSSB_OPT_PREFIX', 'cssb_');
define('CSSB_PATH', plugin_dir_path( __FILE__ ));
define('CSSB_DIR', plugin_dir_url( __FILE__ ));

add_action( 'init', 'cssb_init' );

/**
 * cssb_init()
 * 
 * @return void
 */
function cssb_init(){
	add_shortcode( 'cssb', 'cssb_shortcode' );
	
	add_action( 'wp_enqueue_scripts', 'cssb_register_enqueues' );
	add_action( 'wp_ajax_cssb_get_share_counters', 'cssb_get_share_counters' );
	add_action( 'wp_ajax_nopriv_cssb_get_share_counters', 'cssb_get_share_counters' );
}


/**
 * cssb_register_enqueues()
 * 
 * @return void
 */
function cssb_register_enqueues(){
	wp_register_script( 'cssb-script', CSSB_DIR.'/cssb.js', array('jquery'), CSSB_VERSION );
	wp_localize_script( 'cssb-script', 'cssb_vars', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	) );
	wp_register_style( 'cssb-style', CSSB_DIR.'/cssb.css', array(), CSSB_VERSION );
}


/**
 * cssb_shortcode()
 * 
 * @param mixed $atts
 * @param mixed $content
 * @return
 */
function cssb_shortcode($atts=array(), $content=NULL){
	extract(shortcode_atts(array(
		'url' => 'permalink'
	), $atts));
	
	global $post;
	
	if($url == 'site'){
		$link = site_url();
	} else {
		//$link = get_permalink( $post->ID );
		$link = site_url().$_SERVER['REQUEST_URI'];
	}
	
	wp_localize_script( 'cssb-script', 'cssb_share_options', array(
		'url' => $link
	) );
	
	wp_enqueue_script( 'cssb-script' );
	wp_enqueue_style( 'cssb-style' );
	
	return '<div class="cssb-share-buttons"></div>';
}


/**
 * cssb_get_share_counters()
 * 
 * @return void
 */
function cssb_get_share_counters(){
	$options = $_GET['options'];
	$link = $options['url'];
	
	$fb_likes = cssb_get_fb_likes( $link );
	$fb_likes = cssb_trim_number( $fb_likes );
	
	$tweets = cssb_get_tweets( $link );
	$tweets = cssb_trim_number( $tweets );
	
	$pluses = cssb_get_pluses( $link );
	$pluses = cssb_trim_number( $pluses );
	
	ob_start();
	include( 'cssb-buttons.php' );
	$output = ob_get_contents();
	ob_end_clean();
	
	echo json_encode( array( 'html' => $output ) );
	exit();
}


/**
 * cssb_trim_number()
 * 
 * @param mixed $number
 * @return
 */
function cssb_trim_number($number){
	if(strlen((int)$number) > 9){ // over a billion
		$number = ( $number / 1000000000 ) .'.'. substr($number % 1000000000, 0, 1) .'B';
	} elseif(strlen((int)$number) > 6){ // over a million
		$number = ( $number / 1000000 ) .'.'. substr($number % 1000000, 0, 1) .'M';
	} elseif(strlen((int)$number) > 3){ // over a thousand
		$number = ( $number / 1000 ) .'.'. substr($number % 1000, 0, 1) .'K';
	}
	return $number;
}


/**
 * 
 * credit to: http://olegnax.com/927-counter-goole-plusone-twitter-facebook-buttons/
 * 
 */

/**
 * cssb_get_fb_likes()
 * 
 * @param mixed $url
 * @return
 */
function cssb_get_fb_likes( $url ){
	$json_string = wp_remote_get(
		'https://graph.facebook.com/'.$url,
		array(
			// disable checking SSL sertificates
			'sslverify'=>false
		)
	); 
	
	// retrives only body from previous HTTP GET request   
	$json_string = wp_remote_retrieve_body($json_string);
	
	// convert body data to JSON format
	$json = json_decode($json_string, true);   
	
	if (isset($json['shares'])) {
		// return count of Facebook shares for requested URL
		return intval( $json['shares'] );
	}
	
	// return zero if response is error or current URL not shared yet
	return '0';
}

/**
 * cssb_get_tweets()
 * 
 * @param mixed $url
 * @return
 */
function cssb_get_tweets( $url ){
	// retrieves data with HTTP GET method for current URL     
	$json_string = wp_remote_get(
		'https://urls.api.twitter.com/1/urls/count.json?url='.$url,
		array(
			// disable checking SSL sertificates
			'sslverify'=>false
		)
	);
	
	// retrives only body from previous HTTP GET request
	$json_string = wp_remote_retrieve_body($json_string);
	
	// convert body data to JSON format
	$json = json_decode($json_string, true);
	
	// return count of Tweets for requested URL        
	return intval( $json['count'] );
}

/**
 * cssb_get_pluses()
 * 
 * @param mixed $url
 * @return
 */
function cssb_get_pluses( $url ){
	$args = array(
		'method' => 'POST',
		'headers' => array(
			// setup content type to JSON
			'Content-Type' => 'application/json'
		),
		// setup POST options to Google API
		'body' => json_encode(array(
			'method' => 'pos.plusones.get',
			'id' => 'p',
			'method' => 'pos.plusones.get',
			'jsonrpc' => '2.0',
			'key' => 'p',
			'apiVersion' => 'v1',
			'params' => array(
				'nolog'=>true,
				'id'=> $url,
				'source'=>'widget',
				'userId'=>'@viewer',
				'groupId'=>'@self'
			)
		)),
		// disable checking SSL sertificates              
		'sslverify'=>false
	);
	
	// retrieves JSON with HTTP POST method for current URL 
	$json_string = wp_remote_post("https://clients6.google.com/rpc", $args);
	
	if (is_wp_error($json_string)){
		// return zero if response is error                            
		return '0';            
	} else {
		$json = json_decode($json_string['body'], true);                   
		// return count of Google +1 for requsted URL
		return intval( $json['result']['metadata']['globalCounts']['count'] );
	}
}