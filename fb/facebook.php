<?php
require_once( ROOT.INC.DS.'helper.php' );
//require_once( ROOT.INC.DS.'wmsfacebook'.DS.'facebook.php' );

class FaceBookClass extends RP
{
	/**
	 * Variable containing the object to
	 * facebooks API
	 * 
	 * @var Facebook Object
	 **/
	var $api;

	/**
	 * Facebook requires us to pass some 
	 * variables for every ajax request, 
	 * these values seem to change daily.
	 *
	 * @var Array
	 **/
	var $session_info = array();

	/**
	 * Login credentials
	 * 
	 * @var string
	 **/
	var $username;
	var $password;

	/**
	 * The userid of the current node
	 * 
	 * @var string
	 **/
	var $user_id;

	/**
	 * An array containing a list of
	 * all of the friends for the current node
	 * 
	 * @var array
	 **/
	var $friends = array();

	/**
	 * An array containing a list of
	 * all the friends of friends of the node
	 * 
	 * @var array
	 **/
	var $friends_friends = array();

	/**
	 * An array containing a list of
	 * all info of a friend organized by user_id
	 * 
	 * @var array
	 **/
	var $friend_info = array();

	/**
	 * Constructor function. Requires $username, $password
	 * Facebooks API is not implemented so it is not required at this point.
	 * This function will do a call to facebook to make sure we can reach the server
	 * and to get the session cookies. Then we do a login with our current credentials.
	 * 
	 * @param username String The facebook username
	 * @param password String The facebook password
	 * @param api_key String The facebook API key
	 * @param secret String The facebook secret key
	 */
	function __construct( $username, $password, $api_key = '', $secret = '' ) {
		if(!$username || !$password)
			$this->error( 'Please provide username/password credentials.' );

		// Set cache path
		$this->cache_path			= dirname( __FILE__ ).DS.'cache';
		// Set session cache path
		$this->session_cache_path	= dirname( __FILE__ ).DS.'session';
		// Set todo path
		$this->todo_path			= dirname( __FILE__ ).DS.'todo';

//      if(!$api_key || !$secret)
//          $this->error( 'Please provide a Facebook API and a secret key.' );

		$this->username = $username;
		$this->password = $password;

		$cache = true;
		$this->init( $cache );

		// Check that user is logged in only if IS_SEARCHER
		if(IS_SEARCHER){
			$id = $this->get_logged_user_id();
			while(!$id){
				$this->clear_session();
				$cache = false;
				$this->init( $cache );
				$id = $this->get_logged_user_id();
			}
			myPrint($id);
		}

		// Make sure that we are logged in
		if(!isset($this->session_info->post_form_id) || !$this->check_logged_in())
			$this->error( 'User is not logged in.' );

		// Facebook API
//      $this->fb = new Facebook( $api_key, $secret );
//      $this->set_facebook_app_session();
	}

	function init( $cache = true ){
		// Original request to get cookies
		$this->initial_request( $cache );

		// Login the user
		$this->login( $cache );

		// Facebook requires some information for us to do ajax requests
		$this->session_info = $this->get_session_info( $cache );
	}

	/**
	 * Function to get the facebook session key for a facebook application
	 * IMPORTANT: The rest of this "facebook application" functionality has
	 * not been implement in this version of the scrapper, but after this you can use
	 * $this->fb->api_client to make calls to facebooks API and retrieve whatever you want
	 */
	function set_facebook_app_session() {
		$res = $this->get_request( 'http://apps.facebook.com/fcbkapplication/' );
		$body = wp_remote_retrieve_body($res);

		preg_match( '/fb_sig_session_key=(.*?)&/im', $body, $matches );

		$this->fb->user = $this->get_user_id();
		$this->fb->api_client->session_key = array_pop( $matches ); // Example: 2.i9WQlFKJhDtG_eq29vTYQw__.86400.1258156800-1247490066
	}

	/**
	 * This is the initial contact to the server to get some
	 * of the basic cookies such as the session. It is possible
	 * to pass $scramble as true and the function will change to
	 * a random user agent and clear out all cookies.
	 * 
	 * @param $cache Bool To either cache the request or not
	 * @return void
	 */
	function initial_request( $cache = true ) {
		$request_uri = 'http://login.facebook.com/login.php?login_attempt=1';

		$args = array(
					'cache'			=> $cache,
					'is_session'	=> true
					);
		$res = $this->get_request( $request_uri, $args );

		// Recursively ensure that the cookie 'lsd' has been retrieved only if IS_SEARCHER
		if($this->get_cookie_val('lsd') == '' && IS_SEARCHER){
			$counter = 0;
			$args = array(
						'cache'			=> false,
						'is_session'	=> true
						);

			// Only up to 5 times no infinite loops
			while($this->get_cookie_val('lsd') == '' && $counter < 5){
				// Delete cookies and change useragent before doing a new request
				$this->_default_args['user-agent'] = $this->user_agents_list[array_rand($this->user_agents_list)];
				$this->clear_cookies();

				$res = $this->get_request( $request_uri, $args );
				$counter++;
			}
		}

		if($this->get_cookie_val('lsd') == '')
			$this->error( 'Unable to get initial request cookies.' );

		// Fist and only check to make sure we can get to the server
		if(wp_remote_retrieve_response_message($res) != 'OK')
			$this->error( 'Error connecting to server. Response code: '.print_r($res, true) );
	}

	/**
	 * Login function, simply logs user into facebook
	 * 
	 * @return $res Object Response object from request
	 */
	function login( $cache = true ) {
		/* Login attempt */
		$args = array(
					'body'			=> "locale=en_US&non_com_login=&email={$this->username}&pass={$this->password}&lsd=".$this->get_cookie_val('lsd'),
					'redirect'		=> false,
					'sslverify'		=> false,
					'cache'			=> $cache,
					'is_session'	=> true
					);
		$res = $this->post_request( 'https://login.facebook.com/login.php?login_attempt=1', $args );

		return $res;
	}

	/**
	 * Function will get the facebook values for the session
	 * these seem to change daily.
	 *
	 * @return void
	 **/
	function get_session_info( $cache = true ) {
		$args = array(
					'cache'			=> $cache,
					'is_session'	=> true
					);
		$res = $this->get_request( 'http://www.facebook.com/home.php?', $args );

		$body = wp_remote_retrieve_body($res);
		// <script type="text/javascript">
		// Env={user:1247490066,method:"GET",dev:0,start:(new Date()).getTime(),ps_limit:5,ps_ratio:4,svn_rev:202073,static_base:"http:\/\/static.ak.fbcdn.net\/",www_base:"http:\/\/www.facebook.com\/",tlds:["com"],rep_lag:2,pc:{"m":"0.1.5","l":"0.1.5"},post_form_id:"bfa62071e952a15b22a79fc712ef312a",fb_dtsg:"q5FOM",pagecache_whitelist_regex:"^\\\/ajax\\\/(presence\\\/|chat\\\/|proxy\\.php)",quickling_inactive_page_regex:"^\\\/(election08\\\/|username\\\/|ads\\\/manage\\\/|login\\.php|comments\\.php|logout\\.php|sorry\\.php|help\\.php|giftshop\\.php|ac\\.php|ae\\.php|ajax\\\/emu\\\/h\\.php|ext\\\/|ads\\\/create\\\/|feeds\\\/|.+(\\?|&)fb95_opt_(in|out)|intern\\\/(?!example\\\/page_cache)|sitetour\\\/homepage_tour\\.php|facebook-widgets\\\/|quikvote\\\/|syndication\\.php|identity_switch\\.php)"};
		// </script>

		preg_match( "/<script type=\"text\/javascript\">\nEnv=(.*?);\n<\/script>/", $body, $matches );
		$info		= array();
		// Must wrap this in quotes, there may be more in the future as facebook adds more
		$matches[1]	= str_replace( 'start:(new Date()).getTime()', 'start:"(new Date()).getTime()"', $matches[1] );
		// Parse the javascript object and make it readable in PHP
		parse_jsobj( $matches[1], $info );

		return (object) $info;
	}

	/**
	 * Function will check if the user is logged in
	 * 
	 * @return Bool Depending if user is logged in or not
	 */
	function check_logged_in( $user = null, $limit = 100, $page = 0 ) {
		echo 'Checking if user is logged in...<br />';

		$user_ids = array(
						'581153082'
						);

		if(!$user)
			$user = $user_ids[ array_rand($user_ids) ];

		$args = array(
					'body'      => "__a=1&class=FriendManager&edge_type=everyone&fb_dtsg={$this->session_info->fb_dtsg}&limit={$limit}&node_id={$user}&page={$page}&post_form_id={$this->session_info->post_form_id}&post_form_id_source=AsyncRequest",
					'cache'		=> false
					);
		$res = $this->post_request( 'http://www.facebook.com/ajax/social_graph/fetch.php', $args );

		$body = wp_remote_retrieve_body($res);

		$body		= str_replace( 'for (;;);', '', $body );
		$body		= json_decode($body);
		if(isset($body->error) && $body->error != 0)
			return false;

		return true;
	}

	/**
	 * Will get the set user_id, if none is set will return false
	 * 
	 * @return Int User_id that is currently set
	 */
	function get_user_id() {
		if(isset($this->user_id))
			return $this->user_id;

		return false;
	}

	/**
	 * Returns the user_id for the currently
	 * logged in user
	 * 
	 * @return Int user_id of logged in user
	 */
	function get_logged_user_id() {
		/* Request home */
		$res = $this->get_request( 'http://www.facebook.com/home.php?' );
		// Get the user_id
		$body = wp_remote_retrieve_body($res);
		// <input type="hidden" id="user" name="user" value="1247490066" />
		preg_match( '/<input type="hidden" id="user" name="user" value="([0-9]+)"/im', $body, $matches );
		$user_id = array_pop( $matches );

		return $user_id;
	}

	/**
	 * Will set the $user_id
	 * If no $user_id is passed to function,
	 * it will default to getting the user_id of the
	 * currently logged in user.
	 * 
	 * @param user_id Int The user_id for the current node
	 * @return user_id Int The user_id of the found or set user
	 */
	function set_user_id( $user_id = 0 ) {
		if(!$user_id)
			$user_id = $this->get_logged_user_id();

		$this->user_id = $user_id;

		return $this->user_id;
	}

	/**
	 * Function will load all of the friends for the current node
	 * 
	 * @return Object A list of user_ids of friends of node
	 */
	function load_my_friends() {
		$this->load_friends( $this->get_user_id() );
	}

	/**
	 * Loads the friends list. This function will call itself until
	 * there are no more friends to query.
	 * 
	 * @param user Int The user to get the friends of
	 * @param limit Int The limit of friends to get per query
	 * @param page Int The current page of friends to get
	 * @return Array A list of all friends for user
	 */
	function load_friends_list( $user, $limit = 100, $page = 0 ) {
		$url	= 'http://www.facebook.com/ajax/social_graph/fetch.php';
		$args	= array(
						'body'      => "__a=1&class=FriendManager&edge_type=everyone&fb_dtsg={$this->session_info->fb_dtsg}&limit={$limit}&node_id={$user}&page={$page}&post_form_id={$this->session_info->post_form_id}&post_form_id_source=AsyncRequest",
						'cache'		=> true
						);
		$res = $this->post_request( $url, $args );

		$body = wp_remote_retrieve_body($res);

		$body		= str_replace( 'for (;;);', '', $body );
		$body		= json_decode($body);
		$friends	= @$body->payload->user_info; // All of this nodes friends

		if(empty($friends)){
			// Delete cache as this request is useless
			$_body = (isset($args['body']))?$args['body']:'';
			$this->clear_cache( $url, $_body, $args );
			// Return empty
			return array();
		}

		// Recursive search for more friends
		if(count( (array) $friends) > ($limit - 5)) // Just in case
			$friends_friends = array_merge((array) $friends, $this->load_friends_list( $user, $limit, $page+1 ) );
		else
			$friends_friends = (array) $friends;

		return $friends_friends;
	}

	/**
	 * Loads the fan list. This function will call itself until
	 * there are no more fans to query.
	 * 
	 * @param user Int The fan page to get the fans of
	 * @param limit Int The limit of fans to get per query
	 * @param page Int The current page of fans to get
	 * @return Array A list of all fans for fan page
	 */
	function load_fan_list( $user, $limit = 100, $page = 0 ) {
		$url	= 'http://www.facebook.com/ajax/social_graph/fetch.php';
		$args	= array(
						'body'      => "__a=1&class=FanManager&edge_type=fan&fb_dtsg={$this->session_info->fb_dtsg}&limit={$limit}&node_id={$user}&page={$page}&post_form_id={$this->session_info->post_form_id}&post_form_id_source=AsyncRequest",
						'cache'		=> true
						);
		$res = $this->post_request( $url, $args );

		$body = wp_remote_retrieve_body($res);

		$body		= str_replace( 'for (;;);', '', $body );
		$body		= json_decode($body);
		$friends	= @$body->payload->user_info; // All of this nodes friends

		if(empty($friends)){
			// Delete cache as this request is useless
			$_body = (isset($args['body']))?$args['body']:'';
			$this->clear_cache( $url, $_body, $args );
			// Return empty
			return array();
		}

		// Recursive search for more friends
		if(count( (array) $friends) > ($limit - 5)) // Just in case
			$friends_friends = array_merge((array) $friends, $this->load_fan_list( $user, $limit, $page+1 ) );
		else
			$friends_friends = array_merge((array) $friends, $this->load_fan_list( $user, $limit, $page+1 ) );

		return $friends_friends;
	}

	/**
	 * Load all of the friends for a user. This function is recursive 
	 * so it will call itself until we have all of the nodes friends
	 * Loads all of the friends under $this->friends_friends[$user]
	 * 
	 * @param user String The user_id that we want to find the friends for
	 * @param depth Mixed Can be an int or array containing the path to the user
	 * @param limit Int The limit of friends to get per request
	 * @param page Int The page to start on, since the results are paginated
	 * @return void
	 */
	function load_friends( $user, $depth = 0, $limit = 100, $page = 0 ) {
		$user_friends	= array();

		if(IS_FAN_PAGE && $depth == 0)
			$friends = $this->load_fan_list( $user, $limit, $page );
		else
			$friends = $this->load_friends_list( $user, $limit, $page );

		foreach((array) $friends as $id => $info){
			$user_friends[] = $id;
		}

		// Store friend info
		$this->parse_user_info( $friends );

		// Add the friends to the corresponding place
		switch(gettype($depth)){
			case 'integer':
				// Depth == 0 - The friends of the main node
				if($depth == 0):
					$this->friends = $user_friends;
				// Depth == 1 - The friends friends of the main node
				elseif($depth == 1):
					$this->friends_friends[$user] = $user_friends;
				endif;
				break;
			case 'array':
				// The friends of whom ever friends starting at node
				
				break;
		}

	}

	/**
	 * Build the todo list of things to do.
	 * The workers will use this list to retrieve the needed data
	 * 
	 * @param force Bool If we want to force a new write of friends or not
	 * @return void
	 */
	function build_todo( $force = false ){
		$todo = array();
		$file_name = $this->todo_path .DS.$this->get_user_id().'.txt';

		if(!$force && file_exists( $file_name )){
			$_contents = file_get_contents($file_name);
//			$_contents = maybe_unserialize( $_contents );
			if(!empty( $_contents ))
				return;
		}

		// Setup the list of friends
		foreach((array) $this->friends as $friend){
			$todo['friends'][] = $friend;
			$todo['friend_info'][] = $friend;
		}

		$this->store_todo( $this->get_user_id(), $todo );
	}

	/**
	 * Load the todo list of things to do for the node.
	 * The workers will use this list to figure out what to do.
	 * 
	 * @return Array List of todos
	 */
	function load_todo(){
		$file_name = $this->todo_path .DS.$this->get_user_id().'.txt';
		if(!file_exists( $file_name ))
			$this->error( 'Todo not found, run the Searcher first for this user.' );
		
		$_contents = file_get_contents($file_name);
		$_contents = maybe_unserialize( $_contents );

		if(empty( $_contents )){
//			unlink($file_name);
//			$this->error( 'Todo empty, the work has probably been completed. If not then start by running the Searcher again.' );
		}

		return $_contents;
	}

	/**
	 * Write the todo list of things to do for the node.
	 * The worker will pass a list of pending todos for it or
	 * other workers to accomplish.
	 * 
	 * @param $todo Array The list of todos that are left todo
	 * @return void
	 */
	function write_todo( $todo ){
		$this->store_todo( $this->get_user_id(), $todo );
	}

	/**
	 * Function parses the user_info for a set of friends. This can be 
	 * any type of information, it will merge user_info if there is already 
	 * user info on record.
	 * 
	 * @param friends Array An array containing a list of user information organized by user_id
	 * @return void
	 */
	function parse_user_info( $friends ){
		foreach((array) $friends as $id => $user){
			// If we have never seen this user before
			if(!isset($this->friend_info[$id])){
				$this->friend_info[$id] = (object) $user;
			}else{ // Merge previous user info with new user info
				$this->friend_info[$id] = (object) array_merge((array)$this->friend_info[$id], (array)$user);
			}
		}
	}

	/**
	 * Will populate as much data as it can for a given user
	 *
	 * @param user Int The user to populate the data for
	 * @return void It stores all the data found under $this->friend_info[$user]
	 **/
	function populate_user_data( $user ) {
		$args = array(
					'cache'		=> true,
					'redirect'	=> false
					);
		$res = $this->get_request( "http://www.facebook.com/profile.php?id={$user}&v=info", $args );
		$body = wp_remote_retrieve_body($res);

		// Title
		if(!isset($this->friend_info[$user]->title) || !$this->friend_info[$user]->title){
			preg_match( '/<h1 id="profile_name">(.*?)<\/h1>/im', $body, $matches );
			if(isset($matches[1]))
				$this->friend_info[$user]->title = $matches[1];
		}

		// Title
		if(!isset($this->friend_info[$user]->href) || !$this->friend_info[$user]->href){
			$this->friend_info[$user]->href = "http://www.facebook.com/profile.php?id={$user}";
		}

		// Picture
		if(!isset($this->friend_info[$user]->pic) || !$this->friend_info[$user]->pic){
			preg_match( '/<img src="(.*?)" alt="(.*?)" id="profile_pic" \/>/im', $body, $matches );
			if(isset($matches[1]))
				$this->friend_info[$user]->pic = $matches[1];
		}

		// Sex
		$sex		= '';
		$matches	= '';
		preg_match( '/<dt>Sex:<\/dt><dd>([a-zA-Z]+)<\/dd>/im', $body, $matches );
		if(!isset($matches[1])){
			preg_match( '/<dt>Interested In:<\/dt><dd>(.*?)<\/dd>/im', $body, $matches );
			if(isset($matches[1])){
				$matches[1] = str_replace( '<br />', '', $matches[1] );
				if($matches[1] == 'Women')
					$sex = 'Male';
				elseif($matches[1] == 'Men')
					$sex = 'Female';
			}
		}else{
			$sex = $matches[1];
		}

		// Location
		$location	= '';
		$matches	= '';
		preg_match( '/<dt>Current City:<\/dt><dd><a href="([0-9a-zA-Z_!~*\'().;?:@&=+$,%#-\/]+)">(.*?)<\/a><\/dd>/im', $body, $matches );
		if(isset($matches[2])){
			$location = $matches[2];
		}else{
			// if subtitle is set, we can substitute that as the location
			if(isset($this->friend_info[$user]->subtitle))
				$location = $this->friend_info[$user]->subtitle;
		}

		// Age
		$age		= '';
		$matches	= '';
		preg_match( '/<dt>Birthday:<\/dt><dd>([0-9a-zA-Z, ]+)<\/dd>/im', $body, $matches );
		if(isset($matches[1])){
			$age = date( 'Y-m-d', strtotime($matches[1]) );
			$age = age( $age );
			if( (int) $age == 0 )
				$age = '';
		}

		// Political Views
		$politics	= '';
		$matches	= '';
		preg_match( '/<dt>Political Views:<\/dt><dd><a href="([0-9a-zA-Z_!~*\'().;?:@&=+$,%#-\/]+)">(.*?)<\/a><\/dd>/im', $body, $matches );
		if(isset($matches[2])){
			$politics = $matches[2];
		}

		// Religious Views
		$religion	= '';
		$matches	= '';
		preg_match( '/<dt>Religious Views:<\/dt><dd><a href="([0-9a-zA-Z_!~*\'().;?:@&=+$,%#-\/]+)">(.*?)<\/a><\/dd>/im', $body, $matches );
		if(isset($matches[2])){
			$religion = $matches[2];
		}

		// Networks
		$networks	= '';
		$matches	= '';
		preg_match( '/<dt>Networks:<\/dt><dd>(.*?)<\/dd>/im', $body, $matches );
		if(isset($matches[1])){
			$networks = preg_split( '/<br( ?)\/>/', $matches[1] );
		}

		// Groups
		$args = array(
					'cache'		=> true
					);
		$res = $this->get_request( "http://www.facebook.com/groups.php?id={$user}", $args );
		$body = wp_remote_retrieve_body($res);
		$groups		= array();
		$matches	= '';
		preg_match_all( '/<h3><a href="\/group.php\?gid=([0-9]+)">(.*?)<\/a><\/h3>/im', $body, $matches );
		if(isset($matches[2]) && !empty($matches[2])){
			for($i=0;$i<count($matches[0]);$i++){
				// Only add groups if you find them
				if($matches[1][$i] && $matches[2][$i])
					$groups[$i] = array(
										'id'	=> $matches[1][$i],
										'title'	=> $matches[2][$i]
										);
			}
		}

		// Now that we have all of our data, lets store
		$this->friend_info[$user]->sex			= $sex;
		$this->friend_info[$user]->location		= $location;
		$this->friend_info[$user]->age			= $age;
		$this->friend_info[$user]->politics		= $politics;
		$this->friend_info[$user]->religion		= $religion;
		$this->friend_info[$user]->networks		= $networks;
		$this->friend_info[$user]->groups		= $groups;
		$this->friend_info[$user]->populated	= true;
	}

	/**
	 * Function will get any extra user data that we need.
	 * Possible Keys:
	 * 				id			- userid
	 * 				title		- name
	 * 				href		- link to profile
	 * 				subtitle	- location
	 * 				pic			- link to profile picture
	 * 				sex			- sex
	 * 				location	- current residence same as subtitle?
	 * 				age			- age
	 * 				politics	- politic views
	 * 				religion	- religious views
	 * 				networks	- networks user is member of
	 * 				groups		- groups user is member of
	 * 
	 * @param user Int The user we want to retrieve data for
	 * @param key String The piece of data that is requested
	 * @return String Value of key if found
	 */
	function get_friend_data( $user, $key ) {
		$return = '';

		// Set user info for first time
		if(!isset($this->friend_info[$user])){
			$this->friend_info[$user] = (object) array(
													'id'	=> $user
													);
		}

		// If we haven't populated the user data and the request key doesn't exists then populate data
		if(!isset($this->friend_info[$user]->$key) && !isset($this->friend_info[$user]->populated))
			// Only populate the data for location/sex/age if we are doing a large search
			if(TYPE > 2 || $user == $this->get_user_id() || in_array( $user, $this->friends ))
				$this->populate_user_data( $user );

		if(isset($this->friend_info[$user]->$key))
			$return = $this->friend_info[$user]->$key;

		return $return;
	}

	/**
	 * Function will build the xls that is exported from all 
	 * of the info that we have.
	 * 
	 * @param count Int Limits the number of users to use
	 */
	function build_xls( $count ) {
		require_once('Spreadsheet/Excel/Writer.php');

		$dir = dirname(__FILE__) . DS . 'dumps' . DS;

		$filename = $dir . $this->get_user_id().'_'.date('Ymd').'.xls';

		// The file pointer is at the bottom of the file
		if (!$handle = fopen($filename, 'w')) {
			echo "Cannot open file ($filename)";
			exit;
		}

		// Write $somecontent to our opened file.
		if (fwrite($handle, '') === FALSE) {
			echo "Cannot write to file ($filename)";
			exit;
		}

		fclose($handle);
		unset($handle);

		// Creating a workbook
		$workbook = new Spreadsheet_Excel_Writer( $filename );

		/* Work Area Start */

		$node_id	= $this->get_user_id();

		// Set styles
		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();

		/* People Worksheet */
		$p_worksheet	=& $workbook->addWorksheet('People');

		// Add Column names
		$p_worksheet->write(0, 0, 'ID',					$format_bold);
		$p_worksheet->write(0, 1, 'Name',				$format_bold);
		$p_worksheet->write(0, 2, 'Image',				$format_bold);
		$p_worksheet->write(0, 3, 'Friend Count',		$format_bold);
		$p_worksheet->write(0, 4, 'Location',			$format_bold);
		$p_worksheet->write(0, 5, 'Gender',				$format_bold);
		$p_worksheet->write(0, 6, 'Age',				$format_bold);
		$p_worksheet->write(0, 7, 'Network Friends',	$format_bold);

		// The nodes data
		$this->add_people_row( $p_worksheet, $node_id, $this->friends );

		$_counter	= 0;
		// Loop through users and write data
		foreach($this->friends as $friend){
			$this->add_people_row( $p_worksheet, $friend, $this->friends_friends[$friend] );

			$_counter++;
			if($_counter == $count)
				break;
		}

		/* Friends Worksheet */
		$f_worksheet	=& $workbook->addWorksheet('Friends');

		$f_worksheet->write(0, 0, 'User ID',	$format_bold);
		$f_worksheet->write(0, 1, 'User',		$format_bold);
		$f_worksheet->write(0, 2, 'Friend ID',	$format_bold);
		$f_worksheet->write(0, 3, 'Friend',		$format_bold);

		$_counter	= 0;
		// Loop through all of the nodes friends
		foreach($this->friends as $friend){
			// Add friend to friends sheet
			$this->add_friends_row( $f_worksheet, $node_id, $friend );

			$_counter++;
			if($_counter == $count)
				break;
		}

		if( TYPE > 1 ):
		$_counter	= 0;
		// Loop through users and write data
		foreach($this->friends_friends as $friend => $friends_friends ){
			$_count = 0;
			foreach($friends_friends as $friends_friend){
				if( TYPE == 2 && !in_array( $friends_friend, $this->friends ) )
					continue;

				// Add friend to friends sheet
				$this->add_friends_row( $f_worksheet, $friend, $friends_friend );

				if( TYPE == 3 )
					// Add friends friend to people sheet only if large search
					$this->add_people_row( $p_worksheet, $friends_friend, array() );

				$_count++;
				if($_count == $count)
					break;
			}

			$_counter++;
			if($_counter == $count)
				break;
		}
		endif;

		/* Groups/Networks worksheet */
		$g_worksheet	=& $workbook->addWorksheet('Groups');
		$_count			= 1;

		$g_worksheet->write(0, 0, 'User ID',	$format_bold);
		$g_worksheet->write(0, 1, 'User',		$format_bold);
		$g_worksheet->write(0, 2, 'Group ID',	$format_bold);
		$g_worksheet->write(0, 3, 'Group',		$format_bold);

		// The nodes data
		$this->add_networks_row( $g_worksheet, $node_id, $_count );
		$this->add_groups_row( $g_worksheet, $node_id, $_count );

		if( TYPE > 1 ):
		$_counter	= 0;
		// Loop through all of the nodes groups/networks
		foreach($this->friends as $friend){
			// Add networks to group sheet
			$this->add_networks_row( $g_worksheet, $friend, $_count );
			// Add groups to group sheet
			$this->add_groups_row( $g_worksheet, $friend, $_count );

			$_counter++;
			if($_counter == $count)
				break;
		}
		endif;

		/* Work Area End */

		// Let's save the file
		$workbook->close();
	}

	/**
	 * Adds information about the passed user to the provided worksheet.
	 * Will add information in the following order:
	 * user_id, user_title, picture, friend_count, location, sex, age
	 * Function also makes sure that the same user is not added in twice
	 * 
	 * @param worksheet Object The worksheet to add the row to
	 * @param user Int The user_id that we are adding
	 * @param friends Array All of the friends for the user
	 * @return void
	 **/
	function add_people_row( &$worksheet, $user, $friends ) {
		static $counter;
		static $added;

		if(!$counter)
			$counter = 1;

		if(!$added)
			$added = array();

		// Make sure we are not adding the user twice
		if(isset($added[$user]))
			return;
		else
			$added[$user] = true;

		$worksheet->write($counter, 0, $user);
		$worksheet->write($counter, 1, $this->get_friend_data( $user, 'title' ));
		$worksheet->write($counter, 2, $this->get_friend_data( $user, 'pic' ));
		$worksheet->write($counter, 3, count($friends));
		$worksheet->write($counter, 4, $this->get_friend_data( $user, 'location' ));
		$worksheet->write($counter, 5, $this->get_friend_data( $user, 'sex' ));
		$worksheet->write($counter, 6, $this->get_friend_data( $user, 'age' ));

		if($user != $this->get_user_id()){
			$friends	= array();
			// Check to see if users friends are also friends of the node
			foreach( $this->friends_friends[$user] as $friend ){
				if(in_array($friend, $this->friends))
					$friends[] = $friend;
			}
			
			// Check through all of the nodes friends_friends to see if they are friends with the user
			foreach( $this->friends_friends as $friend => $friends_friends ){
				if(in_array($user, $friends_friends))
					$friends[] = $friend;
			}

			$_counter = count( array_unique( $friends ) );

			// Just to account for the friendship with the node
			if(in_array($user, $this->friends))
				$_counter++;
		}else
			$_counter = count($this->friends);
		$worksheet->write($counter, 7, $_counter);

		$counter++;
	}

	/**
	 * Adds user1 and user2 to the provided worksheet in this format:
	 * user1_id, user1_title, user2_id, user2_title
	 * We also make sure that we are not adding the user twice.
	 * 
	 * @param worksheet Object The worksheet to add the row to
	 * @param user1 Int The "node" user 
	 * @param user2 Int The friend of the "node" user
	 * @return void
	 **/
	function add_friends_row( &$worksheet, $user1, $user2 ) {
		static $counter;
		static $added;

		if(!$counter)
			$counter = 1;

		if(!$added)
			$added = array();

		// Make sure we are not adding the user twice
		if(isset($added[$user1]) && $added[$user1] == $user2)
			return;
		else
			$added[$user1] = $user2;

		$worksheet->write($counter, 0, $user1);
		$worksheet->write($counter, 1, $this->get_friend_data( $user1, 'title' ));
		$worksheet->write($counter, 2, $user2);
		$worksheet->write($counter, 3, $this->get_friend_data( $user2, 'title' ));

		$counter++;
	}

	/**
	 * Adds all of the users networks in this format:
	 * user_id, user_title, network_id, network_title
	 * to the provided worksheet.
	 * 
	 * @param worksheet Object The worksheet to add the row to
	 * @param user Int The user that we are getting the networks for
	 * @param counter Int Determines which row we are starting to add rows to
	 * @return void
	 **/
	function add_networks_row( $worksheet, $user, &$counter ) {
		$user_title = $this->get_friend_data( $user, 'title' );

		// Get users networks
		foreach((array) $this->get_friend_data( $user, 'networks' ) as $network){
			if( !$network ) continue;

			$worksheet->write($counter, 0, $user);
			$worksheet->write($counter, 1, $user_title);
			$worksheet->write($counter, 2, 0);
			$worksheet->write($counter, 3, $network);

			$counter++;
		}
	}

	/**
	 * Adds all of the users groups in this format:
	 * user_id, user_title, group_id, group_title
	 * to the provided worksheet.
	 * 
	 * @param worksheet Object The worksheet to add the row to
	 * @param user Int The user that we are getting the groups for
	 * @param counter Int Determines which row we are starting to add rows to
	 * @return void
	 **/
	function add_groups_row( $worksheet, $user, &$counter ) {
		$user_title = $this->get_friend_data( $user, 'title' );

		// Get users groups
		foreach((array) $this->get_friend_data( $user, 'groups' ) as $group){
			$group_id		= (isset($group['id']))?$group['id']:'';
			$group_title	= (isset($group['title']))?$group['title']:'';

			$worksheet->write($counter, 0, $user);
			$worksheet->write($counter, 1, $user_title);
			$worksheet->write($counter, 2, $group_id);
			$worksheet->write($counter, 3, $group_title);

			$counter++;
		}
	}

	/**
	 * Clean the body of a request from variables that can
	 * vary from session to session. These variables are useless to us.
	 * 
	 * @param body String The body of the request
	 */
	function clean_body( &$body ) {
		if(!$body) return;

		$patterns = array( '/fb_dtsg=(.*?)&/', '/post_form_id=([0-9a-zA-Z]+)&/', '/&lsd=([0-9a-zA-Z]+)/' );
		$body = preg_replace( $patterns, '', $body );
	}

	/**
	 * This function will check for errors coming from facebook
	 * 
	 * @param $res Array The response from a request
	 */
	function is_app_error( $res ){
		$body = wp_remote_retrieve_body($res);
		$body		= str_replace( 'for (;;);', '', $body );
		$body		= @json_decode($body);

		// If is error
		if(is_object($body) && isset($body->error) && $body->error != 0)
			return true;

		return false;
	}
}







?>