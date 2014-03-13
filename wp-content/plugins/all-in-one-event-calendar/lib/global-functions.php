<?php
//
//  global-functions.php
//  all-in-one-event-calendar
//
//  Created by The Seed Studio on 2012-02-28.
//

/**
 * Method to stop script execution
 *
 * @param int|string $code Exit value, expected int [optional=0]
 *
 * @return mixed Returns {$code}, unless `ai1ec_stop` filter returns false
 */
function ai1ec_stop( $code = 0 ) {
	if ( ! apply_filters( 'ai1ec_stop', true ) ) {
		echo $code;
		return $code;
	}
	exit( $code );
}

/**
 * Check if given post is Ai1EC event
 *
 * @param WP_Post $post Instance of WP_Post class
 *
 * @return bool True if it is Ai1EC
 */
function is_ai1ec_post( WP_Post $post ) {
	return ( AI1EC_POST_TYPE === $post->post_type );
}

/**
 * Perform HTTP redirect
 *
 * @param string $target Target URL to redirect to
 * @param int    $code   HTTP response code to use
 *
 * @return mixed Usually does not return - exit
 */
function ai1ec_redirect( $target, $code = 301 ) {
	if ( AI1EC_DEBUG > 1 && '127.0.0.1' === $_SERVER['SERVER_ADDR'] ) {
		printf(
			'<div class="timely"><p class="row">' .
				'<a href="%1$s">%1$s</a> (HTTP: %2$d)</p></div>',
			$target,
			$code
		);
	} else {
		header( 'Location: ' . $target, true, $code );
	}
	return ai1ec_stop();
}

if ( ! function_exists( 'pr' ) ):
/**
 * pr function
 *
 * Debug output of variable.
 * Print variable information (using var_dump for {@see empty()} values
 * and print_r otherwise) optionally preceeded by {$title}.
 *
 * @param mixed  $arg   Variable to output (print)
 * @param string $title Title to preceed the variable information
 *
 * @return void Method does not return
 */
function pr( $arg, $title = null )
{
	if ( WP_DEBUG ) {
		if ( $title ) {
			echo '<strong style="font-family:fixed;font-size:1.6em">',
				$title, '</strong>';
		}
		echo '<pre>';
		if ( empty( $arg ) ) {
			var_dump( $arg );
		} else {
			print_r( $arg );
		}
		echo '</pre>';
	}
}
endif;

/**
 * url_get_contents function
 *
 * @param string $url URL 
 *
 * @return string
 **/
function url_get_contents( $url ) {
	// holds the output
	$output = "";

	// To make a remote call in wordpress it's better to use the wrapper functions instead
	// of class methods. http://codex.wordpress.org/HTTP_API
	// SSL Verification was disabled in the cUrl call
	$result = wp_remote_get( $url, array( 'sslverify' => false, 'timeout' => 120 ) );
	// The wrapper functions return an WP_error if anything goes wrong.
	if( is_wp_error( $result ) ) {
		// We explicitly return false to notify an error. This is exactly the same behaviour we had before
		// because both curl_exec() and file_get_contents() returned false on error
		return FALSE;
	}

	$output = $result['body'];

	// check if data is utf-8
	if( ! SG_iCal_Parser::_ValidUtf8( $output ) ) {
		// Encode the data in utf-8
		$output = utf8_encode( $output );
	}

	return $output;
}

/**
 * is_curl_available function
 *
 * checks if cURL is enabled on the system
 *
 * @return bool
 **/
function is_curl_available() { 
	
	if( ! function_exists( "curl_init" )   && 
      ! function_exists( "curl_setopt" ) && 
      ! function_exists( "curl_exec" )   && 
      ! function_exists( "curl_close" ) ) {
			
			return false; 
	}
	
	return true;
}

/**
 * ai1ec_utf8 function
 *
 * Encode value as safe UTF8 - discarding unrecognized characters.
 * NOTE: objects will be cast as array.
 *
 * @uses iconv               To change encoding
 * @uses mb_convert_encoding To change encoding if `iconv` is not available
 *
 * @param mixed $input Value to encode
 *
 * @return mixed UTF8 encoded value
 *
 * @throws Exception If no trans-coding method is available
 */
function ai1ec_utf8( $input ) {
	if ( NULL === $input ) {
		return NULL;
	}
	if ( is_scalar( $input ) ) {
		if ( function_exists( 'iconv' ) ) {
			return iconv( 'UTF-8', 'UTF-8//IGNORE', $input );
		}
		if ( function_exists( 'mb_convert_encoding' ) ) {
			return mb_convert_encoding( $input, 'UTF-8' );
		}
		throw new Exception(
			'Either `iconv` or `mb_convert_encoding` must be available.'
		);
	}
	if ( ! is_array( $input ) ) {
		$input = (array)$input;
	}
	return array_map( 'ai1ec_utf8', $input );
}

/**
 * ai1ec_get_filter_menu function
 *
 * Template tag, to return contents for Ai1EC filter menu, in desired template
 * location.
 *
 * @return string Rendered HTML snippet
 */
function ai1ec_get_filter_menu() {
	return Ai1ec_Render_Entity_Utility::get_instance( 'Filter_Menu' )
		->get_content();
}

/**
 * ai1ec_filter_menu function
 *
 * Template tag, to return contents for Ai1EC filter menu, in desired template
 * location.
 * NOTICE: this is a template tag causing generated content to be outputted to
 * current buffer instantly.
 *
 * @return void Method does not return
 */
function ai1ec_filter_menu() {
	echo ai1ec_get_filter_menu();
}
