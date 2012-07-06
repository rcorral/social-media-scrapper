<?php
require_once( ROOT.INC.DS.'helper.php' );

class TwitterClass extends RP
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
	 * The format requests are returned in from twitter
	 *
	 * @var string
	 **/
	var $format = 'json';

	/**
	 * If we can send a new request to twitter
	 * as an authenticated user
	 *
	 * @var int
	 **/
	var $_auth_can_send = 0;

	/**
	 * If we can send a new request to twitter
	 * as a non authenticated user
	 *
	 * @var int
	 **/
	var $_noauth_can_send = 0;

	/**
	 * Constructor function. Requires $username, $password
	 * Facebooks API is not implemented so it is not required at this point.
	 * This function will do a call to facebook to make sure we can reach the server
	 * and to get the session cookies. Then we do a login with our current credentials.
	 * 
	 * @param username String The facebook username
	 * @param password String The facebook password
	 * @return TwitterClass
	 */
	function __construct( $username, $password ) {
		if(!$username || !$password)
			$this->error( 'Please provide username/password credentials.' );

		// Set default user-agent, in case that twitter blocks us
		$this->_default_args['user-agent'] = 'corePHP/SNA; support@corephp.com';

		// We don't want to be saving the cookies for twitter
		$this->save_cookies = false;

		// Set cache path
		$this->cache_path         = dirname( __FILE__ ).DS.'cache';
		// Set session cache path
		$this->session_cache_path = dirname( __FILE__ ).DS.'session';
		// Set todo path
		$this->todo_path          = dirname( __FILE__ ).DS.'todo';

		$this->username = $username;
		$this->password = $password;

		$cache = true;
		$this->init( $cache );
	}

	function init( $cache = true ){
		// Original request to get cookies
		$this->initial_request( $cache );

		// Login the user
		$this->check_credentials( $cache );
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
		$request_uri = 'http://twitter.com/';

		$args = array(
					'cache'			=> $cache,
					'is_session'	=> true
					);
		$res = $this->get_request( $request_uri, $args );

		// Fist and only check to make sure we can get to the server
		if( wp_remote_retrieve_response_message($res) != 'OK' )
			$this->error( 'Error connecting to server. Response code: '.print_r($res, true) );
	}

	/**
	 * Function will check if the user for which we have
	 * credentials for can login
	 * 
	 * @since 1.0
	 * 
	 * @return Bool Depending if user is logged in or not
	 */
	function check_credentials( $cache = true ) {
		echo 'Checking if user can login...<br />';

		$args = array(
					'cache'      => $cache,
					'is_session' => true,
					'auth'       => $this->get_credentials()
					);
		$res = $this->get_request(
			"http://twitter.com/account/verify_credentials.{$this->format}", $args
		);

		if( wp_remote_retrieve_response_code($res) != '200' ){
			$this->error(
				'Error logging in. Response code: ' . wp_remote_retrieve_response_code( $res )
			);
		}

		return true;
	}

	/**
	 * Function will return username and password for http login
	 * 
	 * The format that is returned will always be username:password
	 * this will allow http authentication
	 * 
	 * @since 1.0
	 * 
	 * @return string Username:Password
	 **/
	function get_credentials()
	{
		return "{$this->username}:{$this->password}";
	}

	/**
	 * Will get the set user_id, if none is set will return false
	 * 
	 * @return Int User_id that is currently set
	 */
	function get_user_id()
	{
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
	function get_logged_user_id()
	{
		$args = array(
					'cache'      => false,
					'is_session' => true,
					'auth'       => $this->get_credentials()
					);
		$res = $this->get_request(
			"http://twitter.com/account/verify_credentials.{$this->format}", $args
		);

		$body = wp_remote_retrieve_body($res);
		$body = json_decode( $body );

		$user_id = $body->screen_name;

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
	function set_user_id( $user_id = 0 )
	{
		if(!$user_id){
			$user_id = $this->get_logged_user_id();
		}

		$this->user_id = $user_id;

		return $this->user_id;
	}

	/**
	 * Function will load all of the friends for the current node
	 * 
	 * @return Object A list of user_ids of friends of node
	 */
	function load_my_friends()
	{
		$this->load_friends( $this->get_user_id() );
	}

	/**
	 * Loads the friends list. This function will call itself until
	 * there are no more friends to query.
	 * 
	 * @param user Int The user to get the friends of
	 * @param page Int The current page of friends to get
	 * @return Array A list of all friends for user
	 */
	function load_friends_list( $user, $page = -1 )
	{
		$url	= "http://twitter.com/statuses/friends/{$user}.{$this->format}?cursor={$page}";
		$args	= array(
						'cache'		=> true
						);
		$res = $this->_try_get_request( $url, $args );

		$body    = wp_remote_retrieve_body($res);
		$body    = json_decode($body);
		$friends = @$body->users;

		if( empty( $friends ) && !@$body->next_cursor ){
			// Delete cache as this request is useless
			// $_body = (isset($args['body']))?$args['body']:'';
			// $this->clear_cache( $url, $_body, $args );

			return array();
		}

		// Recursive search for more friends
		if( $body->next_cursor ) // If there is a next page
			$friends_friends = array_merge(
				(array) $friends, $this->load_friends_list( $user, $body->next_cursor )
			);
		else
			$friends_friends = (array) $friends;

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
	function load_friends( $user, $depth = 0, $page = -1 )
	{
		$user_friends	= array();

		$friends = $this->load_friends_list( $user, $page );

		foreach((array) $friends as $friend){
			$user_friends[] = $friend->screen_name;
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
	function build_todo( $force = false )
	{
		$todo = array();
		$file_name = $this->todo_path .DS.$this->get_user_id().'.txt';

		if( $force && file_exists( $file_name ) ){
			$_contents = file_get_contents( $file_name );
//			$_contents = maybe_unserialize( $_contents );
			if( !empty( $_contents ) )
				return;
		}

		// Setup the list of friends
		foreach( (array) $this->friends as $friend ){
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
	function load_todo()
	{
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
		foreach((array) $friends as $friend){
			$id = $friend->screen_name;
			// If we have never seen this user before
			if( !isset( $this->friend_info[$id] ) ){
				$this->friend_info[$id] = (object) $friend;
			} else { // Merge previous user info with new user info
				$this->friend_info[$id] = (object) array_merge(
					(array) $this->friend_info[$id], 
					(array) $friend
				);
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
					'cache'    => true,
					'redirect' => false
					);
		$res = $this->get_request( "http://twitter.com/users/show/{$user}.{$this->format}", $args );
		$body = wp_remote_retrieve_body($res);

		$body = array( (object) json_decode( $body ) );
		$this->parse_user_info( $body );

		$this->friend_info[$user]->populated	= true;
	}

	/**
	 * Function will get any extra user data that we need.
	 * Possible Keys:
	 * 				id                - userid
	 * 				name              - name
	 * 				screen_name       - link to profile
	 * 				location          - Don't rely on this as user may not reside here
	 * 				description       - self description
	 * 				profile_image_url - link to profile picture
	 * 				url               - Personal webiste
	 * 				protected         - Is profile protected?
	 * 				friends_count     - Friends count (following)
	 * 				followers_count   - The count of followers
	 * 				statuses_count    - Amount of tweets
	 * 				created_at        - Time user signed up?
	 * 				favourites_count  - Favourite tweets count
	 * 				profile_background_color - Profile background color
	 * 				profile_text_color - Profile text color
	 * 				profile_link_color - Profile link color
	 * 				profile_sidebar_fill_color - Profile sidebar color
	 * 				profile_sidebar_border_color - Profile sidebar border color
	 * 				utc_offset - ???
	 * 				time_zone - Time zone
	 * 				profile_background_image_url - Profile background image url
	 * 				profile_background_tile - Profile title
	 * 				notifications - ???
	 * 				verified - Account is verified
	 * 				following - ???
	 * 				status - Current status, this is an array and contains more options
			@see {{http://apiwiki.twitter.com/Twitter-REST-API-Method%3A-statuses%C2%A0friends}}}
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
		if( !isset($this->friend_info[$user]->$key) && !isset($this->friend_info[$user]->populated) ){
			// No automatic data population
			// if( TYPE > 2 || $user == $this->get_user_id() || in_array( $user, $this->friends ) ){
			// 	$this->populate_user_data( $user );
			// }
			$this->friend_info[$user]->$key = '';
		}

		if( isset( $this->friend_info[$user]->$key ) )
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

		$node_id = $this->get_user_id();

		// Set styles
		$format_bold =& $workbook->addFormat();
		$format_bold->setBold();

		/* People Worksheet */
		$p_worksheet =& $workbook->addWorksheet('People');

		// Add Column names
		$p_worksheet->write(0, 0, 'ID',              $format_bold);
		$p_worksheet->write(0, 1, 'Name',            $format_bold);
		$p_worksheet->write(0, 2, 'Image',           $format_bold);
		$p_worksheet->write(0, 3, 'Following',       $format_bold);
		$p_worksheet->write(0, 4, 'Followers',       $format_bold);
		$p_worksheet->write(0, 5, 'Tweets',          $format_bold);
		$p_worksheet->write(0, 6, 'Location',        $format_bold);
		$p_worksheet->write(0, 7, 'Network Friends', $format_bold);

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
		$f_worksheet =& $workbook->addWorksheet('Friends');

		$f_worksheet->write( 0, 0, 'User ID',   $format_bold );
		$f_worksheet->write( 0, 1, 'User',      $format_bold );
		$f_worksheet->write( 0, 2, 'Friend ID', $format_bold );
		$f_worksheet->write( 0, 3, 'Friend',    $format_bold );

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
				if(
					TYPE == 2 && !in_array( $friends_friend, $this->friends )
					&& $friends_friend != $node_id
				)
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

		$worksheet->write( $counter, 0, $user );
		$worksheet->write( $counter, 1, $this->get_friend_data( $user, 'name' ) );
		$worksheet->write( $counter, 2, $this->get_friend_data( $user, 'profile_image_url' ) );
		$worksheet->write( $counter, 3, count( $friends ));
		// $worksheet->write( $counter, 4, $this->get_friend_data( $user, 'friends_count' ) );
		$worksheet->write( $counter, 4, $this->get_friend_data( $user, 'followers_count' ) );
		$worksheet->write( $counter, 5, $this->get_friend_data( $user, 'statuses_count' ) );
		$worksheet->write( $counter, 6, $this->get_friend_data( $user, 'location' ) );

		if( $user != $this->get_user_id() ){
			$friends	= array();
			// Check to see if users friends are also friends of the node
			foreach( $this->friends_friends[$user] as $friend ){
				if( in_array( $friend, $this->friends ) )
					$friends[] = $friend;
			}
			
			// Check through all of the nodes friends_friends to see if they are friends with the user
			foreach( $this->friends_friends as $friend => $friends_friends ){
				if( in_array( $user, $friends_friends ) )
					$friends[] = $friend;
			}

			$_counter = count( array_unique( $friends ) );

			// Just to account for the friendship with the node
			if( in_array( $user, $this->friends ) )
				$_counter++;
		}else
			$_counter = count( $this->friends );
		$worksheet->write( $counter, 7, $_counter );

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

		$worksheet->write( $counter, 0, $user1 );
		$worksheet->write( $counter, 1, $this->get_friend_data( $user1, 'name' ) );
		$worksheet->write( $counter, 2, $user2 );
		$worksheet->write( $counter, 3, $this->get_friend_data( $user2, 'name' ) );

		$counter++;
	}

	/**
	 * Function will get the amount of requests left
	 * 
	 * We can only do so many requests to twitter in an hour.
	 * 
	 * @since 1.0
	 * 
	 * @param auth mixed Tell if we are doing an authenticated request or not
	 * @return requests left
	 **/
	function requests_left( $auth )
	{
		$url	= "http://twitter.com/account/rate_limit_status.{$this->format}";
		$args	= array(
						'cache' => false,
						'auth'  => $auth
						);
		$res = $this->get_request( $url, $args );

		$body = wp_remote_retrieve_body($res);
		$body = json_decode($body);

		if( $auth ){
			$this->_auth_can_send = $body->remaining_hits;
		} else {
			$this->_noauth_can_send = $body->remaining_hits;
		}

		return array( $body->remaining_hits, $body->reset_time_in_seconds );
	}

	/**
	 * Function will try and make a get request if we have
	 * enough request left in the hour
	 * 
	 * Twitter only allows a username or IP address to do so
	 * many requests per hour, therefore we need to check
	 * ourselves if we are able to send another request as
	 * we don't want to get banned.
	 * The variable $max_requests tells the function how many
	 * requests we can do till we check how many we have remaning.
	 * 
	 * @since 1.0
	 * 
	 * @param url string The URL for the request
	 * @param args array The parameters/arguments for the request
	 * @return Mixed Request response
	 **/
	function _try_get_request( $url, $args )
	{
		static $remaining_requests = 0;
		$max_requests = 2;
		$have_request = false;

		$auth = ( isset( $args['auth'] ) )?$args['auth']:null;

		// This is here to stop us from checking requests left when we don't need to
		if( CACHE && isset($args['cache']) && $args['cache'] === true ){
			$_body = (isset($args['body']))?$args['body']:'';
			if( ($res = $this->get_cache( $url, $_body, $args )) && !$this->is_app_error( $res ) ){
				$have_request = true;
			}
		}

		if( false == $have_request && $remaining_requests < 1 ){
			list( $left, $expire ) = $this->requests_left( $auth );
			if( $left > 20 ){
				// Reset the request count
				$remaining_requests = $max_requests;
			}
			if( $left < 5 ){
				$time = intval( $expire - strtotime('now') );
				if( false != DEBUG ){
					myPrint( "{$time} seconds left till next request" );
					flush();
				}
				// If we have no requests left then sleep for the remaining and try again
				sleep( $time );
				$this->_try_get_request( $url, $args );
			}
		}

		// Do request
		$res = $this->get_request( $url, $args );

		if( false == $have_request ){
			$remaining_requests--;
		}

		return $res;
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
		return false;
	}
}







?>