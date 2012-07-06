<?php
// Load application
require_once( ROOT.DS.'twitter.php' );

class tw_framework
{
	/**
	 * The facebook application variable
	 *
	 * @var Object
	 **/
	var $parser;

	/**
	 * The username to use to login
	 *
	 * @var string
	 **/
	var $username;

	/**
	 * The password to use to login
	 *
	 * @var string
	 **/
	var $password;

	/**
	 * The user_id of the user that is being mapped
	 *
	 * @var mixed
	 **/
	var $user_id;

	function __construct() {
		// User credentials
		$this->username	= 'erastus.plunck@gmail.com';
		$this->password	= 'imawesome';

		// Set $user_id
		$this->user_id = $_GET['user_id'];
	}

	function init() {
		header("Cache-Control: no-cache, must-revalidate");
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
		@ob_end_flush();
		flush();

		// Just preventative measures in case we do something wrong there is time to stop.
		sleep(2);

		if(IS_VIEWER)
			ignore_user_abort(false);
		else
			ignore_user_abort(false); // Should be true if no development is being done

		// Start timer
		timer_start();

		if(IS_SEARCHER)
			register_shutdown_function( array('FB_Debug', 'write') );

		echo 'Initializing...<br />';
		flush();

		// Request parser and other misc functions object
		// Will login the user and load the user_id
		$this->parser	= new TwitterClass( $this->username, $this->password );
		$this->parser->set_user_id( $this->user_id );
	}

	function do_searcher() {
		$this->init();
		$this->parser->load_my_friends();
		$this->parser->build_todo( FORCE_TODO );

		echo 'Todo built...<br />';
		flush();

		if(!FORCE_XLS){
			$_counter	= 0;
			$todo = $this->parser->load_todo();
			while(!empty($todo) && $_counter < 144){
				sleep(5 * 60);
				$todo = $this->parser->load_todo();
				$_counter++;
			}

			if($_counter == 144)
				$this->parser->error( 'Unable to build. Try again.' );
		}

		echo 'Todo done...<br />';
		flush();

		$count		= -1;
		$_counter	= 0;

		// Load all of nodes friends
		foreach($this->parser->friends as $friend){
			$this->parser->load_friends( $friend, 1 );

			$_counter++;
			if($_counter == $count)
				break;
		}

		echo 'Building excel file...<br />';
		flush();

		$this->parser->build_xls( $count );

//		myPrint($this->parser);

		echo 0;
	}

	function do_worker() {
		$this->init();
		$todo = $this->parser->load_todo();
		while(!empty($todo)){
			if(isset($todo['friends'])){
				$friend_key	= array_rand($todo['friends']);
				$friend		= $todo['friends'][$friend_key];

				$do_friend_info = false;
				// Remove from file and do friend_info if exists
				if(isset($todo['friend_info'][$friend_key]) && $todo['friend_info'][$friend_key] == $todo['friends'][$friend_key]){
					$do_friend_info = true;
					unset($todo['friend_info'][$friend_key]);
					if(empty($todo['friend_info']))
						unset($todo['friend_info']);
				}
				// Unset and save
				unset($todo['friends'][$friend_key]);
				if(empty($todo['friends'])) // Remove the main key if there are no more childs
					unset($todo['friends']);
				$this->parser->write_todo( $todo );

				// Do queries?
				$this->parser->load_friends( $friend, 1 );
				if($do_friend_info)
					$this->parser->populate_user_data( $friend );

			}elseif(isset($todo['friend_info'])){
				$friend_key	= array_rand($todo['friend_info']);
				$friend		= $todo['friend_info'][$friend_key];

				// Unset and save
				unset($todo['friend_info'][$friend_key]);
				if(empty($todo['friend_info'])) // Remove the main key if there are no more childs
					unset($todo['friend_info']);
				$this->parser->write_todo( $todo );

				// Do query
				$this->parser->populate_user_data( $friend );
			}

			// Reset todo
			$todo = $this->parser->load_todo();
		}

		echo 0;
	}

	function do_viewer() {
		$this->init();
		$todo = $this->parser->load_todo();

		while(!empty($todo)){
			$friends		= 0;
			$friend_info	= 0;
			if(isset($todo['friends'])){
				$friends = count($todo['friends']);
				echo "Friends left: {$friends}<br />";
			}
			if(isset($todo['friend_info'])){
				$friend_info = count($todo['friends']);
				echo "Friend info left: {$friend_info}<br />";
			}

			$time_left = number_format( ((($friends * 2.5) + ($friend_info * 1)) / 60), 2 );
			echo "Time remaining: {$time_left} minutes<br /><br />";

			flush();
			sleep(10);
			$todo = $this->parser->load_todo();
		}

		echo 'Todo is empty.';
	}

	function do_download() {
		$dumps_dir	= 'dumps';
		$folder		= dirname(__FILE__) . DS . $dumps_dir . DS;
		$files		= array();

		$mydir = opendir($folder);
		while(false !== ($file = readdir($mydir))) {
			if(strpos($file, $this->user_id.'_') !== false) {
				$_file = str_replace(array('.xls', '.xlsx'), '', $file);
				$_file = explode( '_', $_file );
				$files[$_file[1]] = $file;
			}
		}

		closedir($mydir);
		if(empty($files)){
			echo 'There are no files for download.<br />The search may not be done yet.';
		}elseif(count($files) == 1){
			$url = JURI::root().$dumps_dir.'/'.array_shift($files);
			header("Location: {$url}");
		}else{
			krsort($files);
			echo '<ul>';
			foreach($files as $file){
				$url = JURI::root().$dumps_dir.'/'.$file;
				echo "<li><a href='{$url}'>{$file}</a></li>";
			}
			echo '</ul>';
		}

		die();
	}
}

?>