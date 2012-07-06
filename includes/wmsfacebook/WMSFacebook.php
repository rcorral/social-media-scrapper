<?php
require_once 'WMSFacebookApplication.php';
require_once 'WMSFacebookDataStore.php';
require_once 'WMSFacebookObject.php';
require_once 'WMSFacebookUser.php';

/**
 * Abstracts a minimal subset of the functionality for $facebook.
 *
 * This class facilitates the creation of WMSFacebook subclasses. It is
 * also responsible for login (and redirection). The result of logging into
 * Facebook results in a valid WMSFacebookUser instance, upon success.
 *
 * @example data-store.php Data Store Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebook extends WMSFacebookObject {
  private $wmsFacebookApplication;
  private $wmsFacebookDataStore;
  private $wmsFacebookUser;

  /**
   * Creates a new WMSFacebook instance, which wraps the $facebook instance.
   *
   * @param string $key Unique identifier for this application.
   * @param string $secret Password for this application.
   */
  public function WMSFacebook( $key, $secret ) {
    parent::WMSFacebookObject( $this->initFacebook( $key, $secret ) );

    $this->setWMSFacebookDataStore( $this->initWMSFacebookDataStore() );
  }

  /**
   * Authenticates the user's credentials. If successful, this will return
   * a valid instance of WMSFacebookUser. If unsuccessful, the user will
   * be redirected to Facebook.
   *
   * @param string $url Application website.
   *
   * @return WMSFacebookUser
   */
  public function login( $url ) {
    $facebook = $this->getFacebook();
    $app = $this->initWMSFacebookApplication( $url );

    $this->setWMSFacebookUser(
      $this->initWMSFacebookUser( $facebook->require_login() ) );
    $this->setWMSFacebookApplication( $app );

    try {
      if( !$app->isAdded() ) {
        $app->redirect( $facebook->get_add_url() );
      }
    }
    catch( Exception $ex ) {
      // Clear cookies for the application and redirect to a login prompt.
      //
      $facebook->set_user( null, null );
      $app->redirect();

      $this->setWMSFacebookUser( null );
    }

    return $this->getWMSFacebookUser();
  }

  public function setWMSFacebookUser( $wmsFacebookUser ) {
    $this->wmsFacebookUser = $wmsFacebookUser;
  }

  public function getWMSFacebookUser() {
    return $this->wmsFacebookUser;
  }

  private function setWMSFacebookApplication( $wmsFacebookApplication ) {
    $this->wmsFacebookApplication = $wmsFacebookApplication;
  }

  public function getWMSFacebookApplication() {
    return $this->wmsFacebookApplication;
  }

  private function setWMSFacebookDataStore( $wmsFacebookDataStore ) {
    $this->wmsFacebookDataStore = $wmsFacebookDataStore;
  }

  public function getWMSFacebookDataStore() {
    return $this->wmsFacebookDataStore;
  }

  protected function initFacebook( $key, $secret ) {
    return new Facebook( $key, $secret );
  }

  protected function initWMSFacebookUser( $user ) {
    return new WMSFacebookUser( $this, $user );
  }

  protected function initWMSFacebookApplication( $url ) {
    return new WMSFacebookApplication( $this->getFacebook(), $url );
  }

  protected function initWMSFacebookDataStore() {
    return new WMSFacebookDataStore( $this->getFacebook() );
  }
}
?>

