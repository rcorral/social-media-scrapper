<?php
/* Setup environment */
define( 'DS', DIRECTORY_SEPARATOR );
require( dirname(__FILE__).DS.'configuration.php' );

if(!isset($_GET['action'], $_GET['user_id']) || !$_GET['action'] || !$_GET['user_id'])
	require( ROOT.DS.'task.php' );

require_once( ROOT.DS.'framework.php' );


/* Work starts here */

$fb = new fb_framework();

if(IS_SEARCHER)
	$fb->do_searcher();

if(IS_WORKER)
	$fb->do_worker();

if(IS_VIEWER)
	$fb->do_viewer();

if(IS_DOWNLOAD)
	$fb->do_download();

