<?php

define( 'ROOT', dirname(__FILE__) );
define( 'INC', DS.'..'.DS.'includes' );

$defaults = array(
				'time_zone'			=> 'America/New_York', // Not in task.php
				'error_reporting'	=> E_ALL, // Not in task.php
				'set_time_limit'	=> (72 * 3600), // 72 hours // Not in task.php
				'memory_limit'		=> '8192M', // Not in task.php
				'cache'				=> true,
				'cache_max'			=> ( 5 * 86400 ), // Not in task.php
				'debug'				=> true,
				'time_to_sleep'		=> 1,
				'max_request_tries'	=> 2,
				'type'				=> 2,
				'is_fan_page'		=> false,
				'is_searcher'		=> (@$_GET['action']=='searcher'?true:false),
				'is_worker'			=> (@$_GET['action']=='worker'?true:false),
				'is_viewer'			=> (@$_GET['action']=='viewer'?true:false),
				'is_download'		=> (@$_GET['action']=='download'?true:false),
				'force_xls'			=> false,
				'force_todo'		=> false
				);

foreach($_GET as $key => $var)
	if(empty($var))
		unset($_GET[$key]);

$args = array_merge( $defaults, $_GET );

date_default_timezone_set($args['time_zone']);
error_reporting($args['error_reporting']);
set_time_limit( $args['set_time_limit'] );
ini_set('memory_limit', $args['memory_limit']);
define( 'CACHE', (($args['cache'])?true:false) );
define( 'CACHE_MAX', $args['cache_max'] );
define( 'DEBUG', (($args['debug'])?true:false) );
define( 'TIME_TO_SLEEP', $args['time_to_sleep'] );
define( 'MAX_REQUEST_TRIES', $args['max_request_tries'] );
define( 'TYPE', $args['type'] );

define( 'IS_FAN_PAGE', $args['is_fan_page'] );
define( 'IS_SEARCHER', $args['is_searcher'] );
define( 'IS_WORKER', $args['is_worker'] );
define( 'IS_VIEWER', $args['is_viewer'] );
define( 'IS_DOWNLOAD', $args['is_download'] );

define( 'FORCE_XLS', $args['force_xls'] );
define( 'FORCE_TODO', $args['force_todo'] );

unset($args);
