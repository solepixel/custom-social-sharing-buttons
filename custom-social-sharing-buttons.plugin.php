<?php
/**
 * Plugin Name: Custom Social Sharing Buttons
 * Plugin URI: https://github.com/solepixel/custom-social-sharing-buttons/
 * Description: Gives you the ability to add customizable social sharing buttons to your website.
 * Version: 1.1.0
 * Author: Brian DiChiara
 * Author URI: http://www.briandichiara.com
 * License: GPLv2
 */

define('CSSB_VERSION', '1.1.0');
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

	add_action( 'cssb_buttons', 'cssb_facebook_button', 10, 2 );
	add_action( 'cssb_buttons', 'cssb_twitter_button', 10, 2 );
	add_action( 'cssb_buttons', 'cssb_gplus_button', 10, 2 );

	add_action( 'cssb_admin_setting', 'cssb_facebook_setting' );
	add_action( 'cssb_admin_setting', 'cssb_twitter_setting' );
	add_action( 'cssb_admin_setting', 'cssb_gplus_setting' );

	add_action( 'admin_menu', 'cssb_options_menu' );
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
function cssb_shortcode( $atts=array(), $content=NULL ){
	extract( shortcode_atts( array(
		'url' => 'permalink',
		'post_id' => false
	), $atts ) );

	global $post;

	if( $url == 'site' ){
		$link = site_url();
	} else {
		# get's the current URL... not the best method.
		#$link = home_url( add_query_arg( array(), $wp->request ) );
		$post_id = $post_id ? $post_id : get_the_ID();
		# this works inside the loop.
		$link = get_permalink( $post_id );
	}

	$options = get_option( 'cssb_options', cssb_option_defaults() );

	wp_localize_script( 'cssb-script', 'cssb_share_options', apply_filters( 'cssb_share_options', array(
		'display_facebook' => $options['display_facebook'],
		'display_twitter' => $options['display_twitter'],
		'display_gplus' => $options['display_gplus']
	) ) );

	wp_enqueue_script( 'cssb-script' );
	wp_enqueue_style( 'cssb-style' );

	return '<div class="cssb-share-buttons" data-url="' . esc_attr( $link ) . '"></div>';
}

/**
 * Displays Facebook count
 * @param  string $url     URL to retrieve shares
 * @param  array $options  CSSB Options array
 * @return void
 */
function cssb_facebook_button( $url, $options ){
	if( $options['display_facebook'] ):
		$fb_likes = cssb_get_fb_likes( $url );
		$fb_likes = cssb_trim_number( $fb_likes );
		?>
		<li class="cssb-facebook-share"><a href="http://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode( $url ); ?>" target="_blank" title="Share on Facebook">
			<span class="cssb-counter"><?php echo $fb_likes; ?></span>
			<span class="cssb-button">Like</span>
		</a></li>
	<?php endif;
}

/**
 * Displays Twitter count
 * @param  string $url     URL to retrieve shares
 * @param  array $options  CSSB Options array
 * @return void
 */
function cssb_twitter_button( $url, $options ){
	if( $options['display_twitter'] ):
		$tweets = cssb_get_tweets( $url );
		$tweets = cssb_trim_number( $tweets );
		?>
		<li class="cssb-twitter-share"><a href="https://twitter.com/share?url=<?php echo urlencode( $url ); ?>" target="_blank" title="Tweet" data-dims="675x230">
			<span class="cssb-counter"><?php echo $tweets; ?></span>
			<span class="cssb-button">Tweet</span>
		</a></li>
	<?php endif;
}

/**
 * Displays G+ count
 * @param  string $url     URL to retrieve shares
 * @param  array $options  CSSB Options array
 * @return void
 */
function cssb_gplus_button( $url, $options ){
	if( $options['display_gplus'] ):
		$pluses = cssb_get_pluses( $url );
		$pluses = cssb_trim_number( $pluses );
		?>
		<li class="cssb-google-share"><a href="https://plus.google.com/share?url=<?php echo urlencode( $url ); ?>" target="_blank" title="Post on Google" data-dims="480x420">
			<span class="cssb-counter"><?php echo $pluses; ?></span>
			<span class="cssb-button">+1</span>
		</a></li>
	<?php endif;
}


/**
 * cssb_get_share_counters()
 *
 * @return void
 */
function cssb_get_share_counters(){
	$response = array(
		'html' => NULL
	);

	$options = isset( $_GET['options'] ) ? (array)$_GET['options'] : cssb_option_defaults();
	$url = isset( $_GET['url'] ) ? sanitize_text_field( $_GET['url'] ) : '';

	if( ! $url )
		wp_send_json( $response );

	ob_start();
	include( 'cssb-buttons.php' );
	$output = ob_get_contents();
	ob_end_clean();

	echo json_encode( array( 'html' => $output ) );
	exit();
}

function cssb_option_defaults(){
	return apply_filters( 'cssb_option_defaults', array(
		'display_facebook' => true,
		'display_twitter' => true,
		'display_gplus' => true
	));
}


/**
 * cssb_trim_number()
 *
 * @param mixed $number
 * @return
 */
function cssb_trim_number( $number ){
	$int = (int)$number;
	if( strlen( $int ) > 9 ){ // over a billion
		$number = (int)( $int / 1000000000 ) .'.'. substr( $number % 1000000000, 0, 1 ) .'B';
	} elseif( strlen( $int ) > 6 ){ // over a million
		$number = (int)( $int / 1000000 ) .'.'. substr( $number % 1000000, 0, 1 ) .'M';
	} elseif( strlen( $int ) > 3 ){ // over a thousand
		$number = (int)( $int / 1000 ) .'.'. substr( $number % 1000, 0, 1 ) .'K';
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
		'https://graph.facebook.com/' . $url,
		array(
			// disable checking SSL sertificates
			'sslverify' => false
		)
	);

	// retrives only body from previous HTTP GET request
	$json_string = wp_remote_retrieve_body( $json_string );

	// convert body data to JSON format
	$json = json_decode( $json_string, true );

	if ( isset( $json['shares'] ) ) {
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
		'https://urls.api.twitter.com/1/urls/count.json?url=' . $url,
		array(
			// disable checking SSL sertificates
			'sslverify' => false
		)
	);

	// retrives only body from previous HTTP GET request
	$json_string = wp_remote_retrieve_body( $json_string );

	// convert body data to JSON format
	$json = json_decode( $json_string, true );

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
		'sslverify' => false
	);

	// retrieves JSON with HTTP POST method for current URL
	$json_string = wp_remote_post( "https://clients6.google.com/rpc", $args );

	if ( is_wp_error( $json_string ) ){
		// return zero if response is error
		return '0';
	} else {
		$json = json_decode( $json_string['body'], true );
		// return count of Google +1 for requsted URL
		return intval( $json['result']['metadata']['globalCounts']['count'] );
	}
}


/**
 * Admin functions
 */


global $cssb_options_page;

/**
 * Add options page in Admin Menu
 * @return void
 */
function cssb_options_menu(){
	global $cssb_options_page;
	$cssb_options_page = add_options_page( __( 'Custom Social Sharing Buttons', 'cssb' ), __( 'CSSB', 'cssb' ), 'manage_options', 'cssb-options', 'cssb_options_page' );

	add_action( 'admin_init', 'cssb_register_settings' );
}

/**
 * Options Page content
 * @return void
 */
function cssb_options_page(){
	global $cssb_options_page;
	include_once( CSSB_PATH . 'options-page.php' );
}

/**
 * Register CSSB settings
 * @return void
 */
function cssb_register_settings(){
	global $cssb_options_page;

	add_settings_section( 'cssb-options', __( 'Visibility', 'cssb' ), 'cssb_options', $cssb_options_page );

	register_setting( 'cssb-options', 'cssb_options' );

	$values = array_merge( cssb_option_defaults(), get_option( 'cssb_options', cssb_option_defaults() ) );

	add_settings_field(
		'cssb_options',
		__( 'Select Visible Counters', 'cssb' ),
		'cssb_settings_checkboxex',
		$cssb_options_page,
		'cssb-options',
		$values
	);
}

/**
 * Output form option elements
 * @param  array $values Presets for each option
 * @return void
 */
function cssb_settings_checkboxex( $values ){
	do_action( 'cssb_admin_setting', $values );
}

/**
 * Admin Facebook setting
 * @param  array $values Set values
 * @return void
 */
function cssb_facebook_setting( $values ){
	echo '<p><label>' . __( 'Display Facebook?', 'cssb' ) . '</label> &nbsp;
		<label><input type="radio" name="cssb_options[display_facebook]" value="1"' . ( $values['display_facebook'] ? ' checked="checked"' : '' ) . ' /> ' . __( 'Yes', 'cssb' ) . '</label>
		<label><input type="radio" name="cssb_options[display_facebook]" value="0"' . ( ! $values['display_facebook'] ? ' checked="checked"' : '' ) . ' /> ' . __( 'No', 'cssb' ) . '</label>
	</p>';
}

/**
 * Admin Twitter setting
 * @param  array $values Set values
 * @return void
 */
function cssb_twitter_setting( $values ){
	echo '<p><label>' . __( 'Display Twitter?', 'cssb' ) . '</label> &nbsp;
		<label><input type="radio" name="cssb_options[display_twitter]" value="1"' . ( $values['display_twitter'] ? ' checked="checked"' : '' ) . ' /> ' . __( 'Yes', 'cssb' ) . '</label>
		<label><input type="radio" name="cssb_options[display_twitter]" value="0"' . ( ! $values['display_twitter'] ? ' checked="checked"' : '' ) . ' /> ' . __( 'No', 'cssb' ) . '</label>
	</p>';
}

/**
 * Admin G+ setting
 * @param  array $values Set values
 * @return void
 */
function cssb_gplus_setting( $values ){
	echo '<p><label>' . __( 'Display Google Plus?', 'cssb' ) . '</label> &nbsp;
		<label><input type="radio" name="cssb_options[display_gplus]" value="1"' . ( $values['display_gplus'] ? ' checked="checked"' : '' ) . ' /> ' . __( 'Yes', 'cssb' ) . '</label>
		<label><input type="radio" name="cssb_options[display_gplus]" value="0"' . ( ! $values['display_gplus'] ? ' checked="checked"' : '' ) . ' /> ' . __( 'No', 'cssb' ) . '</label>
	</p>';
}

/**
 * Display options fields
 * @return void
 */
function cssb_options(){
	settings_fields( 'cssb-options' );
}
