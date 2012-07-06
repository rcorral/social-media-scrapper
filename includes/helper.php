<?php

/**
 * Response parser class
 * This class will parse reponses
 **/
class RP
{
	/**
	 * Static variable containing all cookies
	 * 
	 * @var Array
	 **/
	static $cookies;

	/**
	 * This variable tells if we should store
	 * cookies or not. Cookies come from requests.
	 * 
	 * @var bool
	 **/
	var $save_cookies = true;

	/**
	 * The default arguments for a request
	 * 
	 * @var Array
	 **/
	var $_default_args = array(
				'timeout' => 30,
				'redirection' => 5,
				'httpversion' => '1.0',
				'user-agent' => 'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.2; Trident/4.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)',
				'blocking' => true,
				'headers' => array(),
				'cookies' => array(),
				'body' => null,
				'compress' => false,
				'decompress' => true,
				'sslverify' => true
				);

	/**
	 * A list of user agents that can be used to
	 * make a server think we are someone else
	 *
	 * @var Array
	 **/
	var $user_agents_list = array(
			'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.1.6) Gecko/20091201 Firefox/3.5.6',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0)',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 2.0.50727)',
			'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1; Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1) ; FDM; .NET C',
			'Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 5.1; Trident/4.0; GTB6.3; .NET CLR 1.1.4322; .NET CLR 2.0.50727; InfoPath.',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; de; rv:1.9.1.4) Gecko/20091016 Firefox/3.5.4 (.NET CLR 3.5.30729)',
			'Mozilla/5.0 (Windows; U; Windows NT 6.0; en-US) AppleWebKit/532.0 (KHTML, like Gecko) Chrome/3.0.195.27 Safari/532.0 EVE',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; Neostrada TP 6.1; .NET CLR 1.1.4322)',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/532.4 (KHTML, like Gecko) Chrome/4.0.237.0 Safari/532.4',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US) AppleWebKit/531.21.8 (KHTML, like Gecko) Version/4.0.4 Safari/531.21.10',
			'Mozilla/5.0 (compatible; Konqueror/4.3; Windows) KHTML/4.3.4 (like Gecko)',
			'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; GTB6.3; .NET CLR 2.0.50727; .NET CLR 3.0.4506.2152; .NET CLR 3.5'
							);

	/**
	 * The path to the cache folder, this should 
	 * be set on the constructor for the class that 
	 * extends to this one
	 *
	 * @var String
	 **/
	var $cache_path;

	/**
	 * The path to the session cache folder, this should 
	 * be set on the constructor for the class that 
	 * extends to this one
	 *
	 * @var String
	 **/
	var $session_cache_path;

	/**
	 * The path to the todo folder, this should 
	 * be set on the constructor for the class that 
	 * extends to this one
	 *
	 * @var string
	 **/
	var $todo_path;

	/**
	 * Saves WP_Http_Cookie objects to RP::$cookies
	 * 
	 * @param ncookies Array Contains WP_Http_Cookie objects
	 * @return Array Contains a list of all cookies set on RP::$cookies
	 **/
	function save_cookies( $ncookies ) {
		$_cookies	= $this->get_cookies();

		if( false == $this->save_cookies ){
			return array();
		}

		foreach((array) $ncookies as $nc){
			if ( !is_object($nc) || !is_a($nc, 'WP_Http_Cookie') || !isset($nc->name) )
				continue;

			// Loop through current cookies and find if this is
			// a new cookie or if we need to replace existing one
			$new = true;
			foreach($_cookies as &$_cookie){
				// If cookie exists, simply replace it here
				if($_cookie->name == $nc->name){
					$_cookie = $nc;
					$new = false;
				}
			}

			// If $new == true then this is a brand new cookie, insert
			if($new === true)
				$_cookies[] = $nc;
		}

		RP::$cookies = $_cookies;
		return RP::$cookies;
	}

	/**
	 * Get the value of a cookie
	 * 
	 * @param name String The name of a cookie
	 * @return String Returns the value of the cookie if found
	 **/
	function get_cookie_val( $name ) {
		$val = '';
		foreach($this->get_cookies() as $cookie){
			if($cookie->name == $name)
				$val = $cookie->value;
		}

		return $val;
	}

	/**
	 * Function used to retreive cookies
	 * 
	 * @return Array Contains all of the cookies from all requests
	 **/
	function get_cookies() {
		return (!RP::$cookies)?array():RP::$cookies;
	}

	/**
	 * Function used to clear out all cookies
	 * 
	 * @return void
	 **/
	function clear_cookies() {
		RP::$cookies = array();
	}

	/**
	 * Function used to clear out all sessions
	 * 
	 * @return void
	 **/
	function clear_session() {
		$folder = rtrim( $this->session_cache_path, DS ) . DS;

		$mydir = opendir($folder);
		while(false !== ($file = readdir($mydir))) {
			if(!in_array( $file, array('.', '..', '.svn') )) {
				chmod($folder.$file, 0777);

				if(is_dir($folder.$file)) {
//					chdir('.');
//					$this->clear_session($folder.$file);
//					rmdir($folder.$file) or $this->error("Couldn't delete {$folder}{$file}<br />");
				}else
					unlink($folder.$file) or $this->error("Couldn't delete {$folder}{$file}<br />");
			}
		}
		closedir($mydir);
	}

	/**
	 * Method to execute a GET request to a server
	 * 
	 * @param url String The full path URL to the server to make the request to
	 * @param args Array The arguments for the request
	 * @return Object Response object for request
	 */
	function get_request( $url, $_args = array(), $try = 0 ) {
		// Set this variable for caching purposes only
		$_body = (isset($_args['body']))?$_args['body']:'';

		// Get Cache?
		if( CACHE && isset($_args['cache']) && $_args['cache'] === true ){
			if( ($res = $this->get_cache( $url, $_body, $_args )) && !$this->is_app_error( $res ) ){
				// Record request
//				FB_Debug::record( $url, $_args, $res );

				// Save cookies
				$this->save_cookies( $res['cookies'] );

				return $res;
			}
		}

		// Set the cookies if there are none
		if(!isset($_args['cookies']))
			$_args['cookies'] = $this->get_cookies();

		// Merge arguments
		$args = wp_parse_args( $_args, $this->_default_args );

		// Do request
		$res = wp_remote_get( $url, $args );

		// We have an internal error...:(
		if ( is_object($res) && is_a($res, 'WP_Error') )
			if($try < MAX_REQUEST_TRIES) // Try again, maybe?
				return $this->get_request( $url, $_args, $try + 1 );
			else // Fail
				$this->error($res, $url, $args);

		// Response is error? If there is an error coming from server
		if($this->is_app_error( $res ))
			if(isset($_args['cache'])) // Don't cache if error
				unset($_args['cache']);

		// Store Cache? Always store cache as long as the constant is set
		if(CACHE && isset($_args['cache'])){
			$this->store_cache( $url, $_body, $res, $_args );
		}

		// Save cookies
		$this->save_cookies( $res['cookies'] );

		// Record request
		FB_Debug::record( $url, $_args, $res );

		// Make server sleep
		sleep(TIME_TO_SLEEP);

		return $res;
	}

	/**
	 * Method to execute a POST request to a server
	 * 
	 * @param url String The full path URL to the server to make the request to
	 * @param args Array The arguments for the request
	 * @return Object Response object for request
	 */
	function post_request( $url, $_args = array(), $try = 0 ) {
		// Set this variable for caching purposes only
		$_body = (isset($_args['body']))?$_args['body']:'';

		// Get Cache?
		if(CACHE && isset($_args['cache']) && $_args['cache'] === true){
			if(($res = $this->get_cache( $url, $_body, $_args )) && !$this->is_app_error( $res )){
				// Record request
//				FB_Debug::record( $url, $_args, $res );

				// Save cookies
				$this->save_cookies( $res['cookies'] );

				return $res;
			}
		}

		// Set the cookies if there are none
		if(!isset($_args['cookies']))
			$_args['cookies'] = $this->get_cookies();

		// Merge arguments
		$args = wp_parse_args( $_args, $this->_default_args );

		// Do request
		$res = wp_remote_post( $url, $args );

		// We have an internal error...:(
		if ( is_object($res) && is_a($res, 'WP_Error') )
			if($try < MAX_REQUEST_TRIES) // Try again, maybe?
				return $this->post_request( $url, $_args, $try + 1 );
			else // Fail
				$this->error($res, $url, $args);

		// Response is error? If there is an error coming from server
		if($this->is_app_error( $res ))
			if(isset($_args['cache'])) // Don't cache if error
				unset($_args['cache']);

		// Store Cache?
		if(CACHE && isset($_args['cache'])){
			$this->store_cache( $url, $_body, $res, $_args );
		}

		// Save cookies
		$this->save_cookies( $res['cookies'] );

		// Record request
		FB_Debug::record( $url, $_args, $res );

		// Make server sleep
		sleep(TIME_TO_SLEEP);

		return $res;
	}

	/**
	 * Gets a previously stored cache file if it exists and has
	 * not yet expired.
	 * 
	 * It uses $url and $_body to make file names unique
	 * 
	 * @param url String The URL that is being queried
	 * @param _body String The body of the request
	 * @param _args Array The "settings" for the request
	 * @return Mixed The contents of a cached request if it exists
	 */
	function get_cache( $url, $_body, $_args ) {
		// Clean the body to make it a generic string
		$this->clean_body( $_body );

		$cache_path = (isset($_args['is_session']) && $_args['is_session'] == true)?$this->session_cache_path:$this->cache_path;

		// Cache File
		$filename		= $cache_path .DS. md5( $url.$_body ).'.txt';
		// Cache Expiration File
		$filename_ext	= $cache_path .DS. md5( $url.$_body ).'_exp.txt';

		if(!file_exists( $filename ) || !file_exists( $filename_ext ))
			return false;

		// Check file expiration date
		$date = file_get_contents( $filename_ext );
		if( intval( strtotime('now') - (int) $date ) >= CACHE_MAX ){
			// Expired, need to delete old files
			$this->clear_cache( $url, $_body, $_args );
			return false;
		}

		$contents = file_get_contents( $filename );
		$contents = maybe_unserialize( $contents );

		return $contents;
	}

	/**
	 * Store a request to a cache file. Will create a .txt file
	 * with the contents of the $_body and an expiration file
	 * with a timestamp of when the cache was created.
	 * 
	 * It uses $url and $_body to make file names unique
	 * 
	 * @param url String The URL that is being queried
	 * @param _body String The body of the request
	 * @param res Mixed The string to be stored in the cache
	 * @param _args Array The "settings" for the request
	 * 
	 * @return void
	 */
	function store_cache( $url, $_body, $res, $_args ) {
		// Clean the body to make it a generic string
		$this->clean_body( $_body );

		$cache_path = ((isset($_args['is_session']) && $_args['is_session'] == true)?$this->session_cache_path:$this->cache_path);

		// Cache File
		$filename		= $cache_path .DS. md5( $url.$_body ).'.txt';
		// Cache Expiration File
		$filename_ext	= $cache_path .DS. md5( $url.$_body ).'_exp.txt';

		// Serialize response, maybe?
		if(is_array($res))
			$content = serialize($res);
		else
			$content = $res;

		/* Write to Cache File */
		// The file pointer is at the bottom of the file
		if (!$handle = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}

		// Write $somecontent to our opened file.
		if (fwrite($handle, $content) === FALSE) {
			echo "Cannot write to file ($filename)";
			exit;
		}

		fclose($handle);
		unset($handle);

		/* Write to Expiration File */
		// The file pointer is at the bottom of the file
		if (!$handle = fopen($filename_ext, 'w')) {
			echo "Cannot open file ($filename_ext)";
			exit;
		}

		// Write $somecontent to our opened file.
		if (fwrite($handle, strtotime('now')) === FALSE) {
			echo "Cannot write to file ($filename_ext)";
			exit;
		}

		fclose($handle);
	}

	/**
	 * Delets previously stored cache files.
	 * 
	 * It uses $url and $_body to make file names unique
	 * 
	 * @param url String The URL that is being queried
	 * @param _body String The body of the request
	 * @param _args Array The "settings" for the request
	 * @return Mixed The contents of a cached request if it exists
	 */
	function clear_cache( $url, $_body, $_args ) {
		// Clean the body to make it a generic string
		$this->clean_body( $_body );

		$cache_path = (isset($_args['is_session']) && $_args['is_session'] == true)?$this->session_cache_path:$this->cache_path;

		// Cache File
		$filename		= $cache_path .DS. md5( $url.$_body ).'.txt';
		// Cache Expiration File
		$filename_ext	= $cache_path .DS. md5( $url.$_body ).'_exp.txt';

		if(!file_exists( $filename ) || !file_exists( $filename_ext ))
			return false;

		// Delete files
		unlink( $filename );
		unlink( $filename_ext );

		return true;
	}

	/**
	 * Store a todo to a cache file. Will create a .txt file
	 * with the contents of the $todo and an expiration file
	 * with a timestamp of when the cache was created.
	 * 
	 * @param user Int The user that the todo is for
	 * @param res Mixed The string to be stored in the cache
	 * @return void
	 */
	function store_todo( $user, $todo ) {
		// Clean the body to make it a generic string
		$this->clean_body( $_body );

		// Cache File
		$filename		= $this->todo_path .DS. $user.'.txt';
		// Cache Expiration File
		$filename_ext	= $this->todo_path .DS. $user.'_exp.txt';

		// Serialize response, maybe?
		if(is_array($todo))
			$content = serialize($todo);
		else
			$content = $todo;

		/* Write to Cache File */
		// The file pointer is at the bottom of the file
		if (!$handle = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}

		// Write $somecontent to our opened file.
		if (fwrite($handle, $content) === FALSE) {
			echo "Cannot write to file ($filename)";
			exit;
		}

		fclose($handle);
		unset($handle);

		/* Write to Expiration File */
		// The file pointer is at the bottom of the file
		if (!$handle = fopen($filename_ext, 'w')) {
			echo "Cannot open file ($filename_ext)";
			exit;
		}

		// Write $somecontent to our opened file.
		if (fwrite($handle, strtotime('now')) === FALSE) {
			echo "Cannot write to file ($filename_ext)";
			exit;
		}

		fclose($handle);
	}

	/**
	 * Clean the body of a request from variables that can
	 * vary from session to session. These variables are useless to us.
	 * 
	 * @param body String The body of the request
	 */
	function clean_body( &$body ) {
		return;
	}

	/**
	 * This function will check for errors coming from facebook
	 * 
	 * @param $res Array The response from a request
	 */
	function is_app_error( $res ){
		return false;
	}

	/**
	 * Gracefully die
	 */
	function error($res, $args = array()){
		$xtras = func_get_args();
		array_shift( $xtras );
		array_shift( $xtras );
		myPrint( array(
						'response'			=> $res,
						'arguments'			=> $args,
						'extras'			=> $xtras,
					)
				);

		echo '<pre>';
		debug_print_backtrace();
		echo '</pre>';

		die( /*'<script type="text/javascript">setTimeout("window.location.reload();", 5000);</script>'*/ );
	}
}


/* Helper functions Start */

if ( !function_exists('myPrint') ) :
/**
 * Function for printing data
 * @return 
 */
function myPrint($var, $pre = true){
	if($pre)
		echo "<pre>";
	print_r($var);
	if($pre)
		echo "</pre>";
}
endif;

if ( !function_exists('myWrite') ) :
/**
 * Function for writing data to log file
 * @return 
 */
function myWrite($data, $file = 'log.txt', $mode = 'a'){
	// Make file safe
	$file = str_replace( array('../', '..\\', '..', '/', '\\'), '', $file );
	if(substr($file, -4) != '.txt' && substr($file, -4) != '.log')
		$file = 'log.txt';

	$filename	= dirname( __FILE__ ).DS.$file;

	$somecontent = "\n".print_r($data, true);

	// The mode determines where the pointer is put on the file
	if (!$handle = fopen($filename, $mode)) {
		echo "Cannot open file ($filename)";
		exit;
	}

	// Write $somecontent to our opened file.
	if (fwrite($handle, $somecontent) === FALSE) {
		echo "Cannot write to file ($filename)";
		exit;
	}

	fclose($handle);
}
endif;

/**
 * Gets the age from a birthday in this format:
 * 1987-12-19
 */
function age( $birthday ) {
	list( $Y, $m, $d )	= explode( '-', $birthday );
	$years				= date( 'Y' ) - $Y;

	if( date('md') < $m.$d && $years != 0 )
		$years--;

	return $years;
}

/**
 * Debuging class.
 * This class will store as much info as you pass to it
 * at script shutdown write() will be called which will write
 * all data to debug.txt in the same directory
 */
class FB_Debug
{
	/**
	 * Contains all of the debugging content
	 *
	 * @var Array
	 **/
	static $debug;

	/**
	 * Function will return all debug data if there
	 * is none set it will return an empty array
	 *
	 * @return Array Contains all debug data if any
	 **/
	function get_debug_info() {
		return (!FB_Debug::$debug)?array():FB_Debug::$debug;
	}

	/**
	 * Records any data that we want to store
	 * for debugging purposes
	 *
	 * @return debug data
	 **/
	function record($url, $args, $res) {
		// Debugging is turned off
		if( false == DEBUG ){
			return;
		}

		// Display requests on screen
		myPrint($url);
		if( isset( $args['body'] ) ){
			myPrint($args['body']);
		}
		flush();

		$_arr = FB_Debug::get_debug_info();

		$_arr['debug_record_'.count($_arr)] = array(
			'url'       => $url,
			'arguments' => $args,
			'response'  => $res
		);

		FB_Debug::$debug = $_arr;
		return FB_Debug::$debug;
	}

	/**
	 * Function will write all of the contents of
	 * debugging to a file
	 * 
	 * @return void
	 */
	function write() {
		// Debugging is turned off
		if(DEBUG === false) return;

		// Gather script information
		$info = array(
					'timer'		=> timer_stop(),
					'requests'	=> count( FB_Debug::$debug )
					);
		// Add script info to debug array
		$write = FB_Debug::get_debug_info();
		array_unshift( $write, $info );

		// Write data
		myWrite( $write, 'debug.txt', 'w' );
	}
}

/**
 * Functions used to parse JavaScript objects
 */
class JsParserException extends Exception {}
function parse_jsobj($str, &$data) {
    $str = trim($str);
    if(strlen($str) < 1) return;

    if($str{0} != '{') {
    	throw new JsParserException('The given string is not a JS object');
    }
    $str = substr($str, 1);

    /* While we have data, and it's not the end of this dict (the comma is needed for nested dicts) */
    while(strlen($str) && $str{0} != '}' && $str{0} != ',') { 
    	/* find the key */
    	if($str{0} == "'" || $str{0} == '"') {
    		/* quoted key */
    		list($str, $key) = parse_jsdata($str, ':');
    	} else {
    		$match = null;
    		/* unquoted key */
    		if(!preg_match('/^\s*[a-zA-z_][a-zA-Z_\d]*\s*:/', $str, $match)) {
    		throw new JsParserException('Invalid key ("'.$str.'")');
    		}	
    		$key = $match[0];
    		$str = substr($str, strlen($key));
    		$key = trim(substr($key, 0, -1)); /* discard the ':' */
    	}

    	list($str, $data[$key]) = parse_jsdata($str, '}');
    }
    "Finshed dict. Str: '$str'\n";
    return substr($str, 1);
}

function comma_or_term_pos($str, $term) {
    $cpos = strpos($str, ',');
    $tpos = strpos($str, $term);
    if($cpos === false && $tpos === false) {
    	throw new JsParserException('unterminated dict or array');
    } else if($cpos === false) {
    	return $tpos;
    } else if($tpos === false) {
    	return $cpos;
    }
    return min($tpos, $cpos);
}

function parse_jsdata($str, $term="}") {
    $str = trim($str);

    if(is_numeric($str{0}."0")) {
    	/* a number (int or float) */
    	$newpos = comma_or_term_pos($str, $term);
    	$num = trim(substr($str, 0, $newpos));
    	$str = substr($str, $newpos+1); /* discard num and comma */
    	if(!is_numeric($num)) {
    		throw new JsParserException('OOPSIE while parsing number: "'.$num.'"');
    	}
    	return array(trim($str), $num+0);
    } else if($str{0} == '"' || $str{0} == "'") {
    	/* string */
    	$q = $str{0};
    	$offset = 1;
    	do {
    		$pos = strpos($str, $q, $offset);
    		$offset = $pos;
    	} while($str{$pos-1} == '\\'); /* find un-escaped quote */
    	$data = substr($str, 1, $pos-1);
    	$str = substr($str, $pos);
    	$pos = comma_or_term_pos($str, $term);
    	$str = substr($str, $pos+1);		
    	return array(trim($str), $data);
    } else if($str{0} == '{') {
    	/* dict */
    	$data = array();
    	$str = parse_jsobj($str, $data);
    	return array($str, $data);
    } else if($str{0} == '[') {
    	/* array */
    	$arr = array();
    	$str = substr($str, 1);
    	while(strlen($str) && $str{0} != $term && $str{0} != ',') {
    		$val = null;
    		list($str, $val) = parse_jsdata($str, ']');
    		$arr[] = $val;
    		$str = trim($str);
    	}
    	$str = trim(substr($str, 1));
    	return array($str, $arr);
    } else if(stripos($str, 'true') === 0) {
    	/* true */
    	$pos = comma_or_term_pos($str, $term);
    	$str = substr($str, $pos+1); /* discard terminator */
    	return array(trim($str), true);
    } else if(stripos($str, 'false') === 0) {
    	/* false */
    	$pos = comma_or_term_pos($str, $term);
    	$str = substr($str, $pos+1); /* discard terminator */
    	return array(trim($str), false);
    } else if(stripos($str, 'null') === 0) {
    	/* null */
    	$pos = comma_or_term_pos($str, $term);
    	$str = substr($str, $pos+1); /* discard terminator */
    	return array(trim($str), null);
    } else if(strpos($str, 'undefined') === 0) {
    	/* null */
    	$pos = comma_or_term_pos($str, $term);
    	$str = substr($str, $pos+1); /* discard terminator */
    	return array(trim($str), null);
    } else {
    	throw new JsParserException('Cannot figure out how to parse "'.$str.'" (term is '.$term.')');
    }
}

/* Helper functions End */

/* Joomla functions Start */
require_once( dirname(__FILE__) .DS.'joomla'.DS.'uri.php' );
/* Joomla functions End */

/* WordPress functions Start */
require_once( dirname(__FILE__) .DS.'wordpress'.DS.'wp.php' );
require_once( dirname(__FILE__) .DS.'wordpress'.DS.'http.php' );
/* WordPress functions End */
?>