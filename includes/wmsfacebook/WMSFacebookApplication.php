<?php
require_once 'WMSFacebookObject.php';

/**
 * Represents a Facebook application.
 *
 * This class contains functionality that is application-specific. Typically
 * the functionality is centred around the URL for the application.
 *
 * @example data-store.php Data Store Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookApplication extends WMSFacebookObject {
  private $url;
  private $imagePath = "images/";

  /**
   * Used to instantiate a new WMSFacebookApplication instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   * @param string $url Application's fully qualified website address.
   */
  public function WMSFacebookApplication( $facebook, $url ) {
    parent::WMSFacebookObject( $facebook );

    $this->setURL( $url );
  }

  /**
   * Sets the URL for this application.
   *
   * @param string $url Application's fully qualified website address.
   */
  private function setURL( $url ) {
    $this->url = $url;
  }

  /**
   * Returns the URL for this application.
   *
   * @return string Application's fully qualified website address.
   */
  private function getURL() {
    return $this->url;
  }

  /**
   * Asks Facebook to redirect the user's browser to the given address.
   *
   * @param string $url New URL for the browser to visit and render.
   */
  public function redirect( $url ) {
    if( !$url ) {
      $url = $this->getURL();
    }

    $this->getFacebook()->redirect( $url );
  }

  /**
   * Returns a list of users that have this application installed.
   *
   * @return array List of WMSFacebookFriend instances.
   */
  public function getUsers() {
    $facebook = $this->getFacebook();
    $result = new ArrayObject();

    $users = $this->getFacebookAPI()->friends_getAppUsers();

    if( $users ) {
      foreach( $users as $user ) {
        $result->append( new WMSFacebookFriend( $facebook, $user ) );
      }
    }

    return $result;
  }

  /**
   * Indicates whether the current user has added this application.
   *
   * @return bool true - application is in the user's list.
   */
  public function isAdded() {
    return $this->getFacebookAPI()->users_isAppAdded();
  }

  /**
   * Sets the subdirectory in which images for the application can be found.
   *
   * @param string $imagePath Subdirectory name with trailing slash, without
   * leading slash.
   */
  public function setImagePath( $imagePath ) {
    $this->imagePath = $imagePath;
  }

  /**
   * Returns the subdirectory in which the images for the application can
   * be found. If the image subdirectory structure is more complicated, then
   * subclasses should be programmed to provide the proper behaviour.
   *
   * The default value is "images/".
   *
   * @return string Subdirectory name with trailing, but no leading slash.
   */
  protected function getImagePath() {
    return $this->imagePath;
  }

  /**
   * Echos an <img> tag to the document; includes width and height tags.
   *
   * @param string $filename Name of the image file to embed in the web page.
   * @param string $alt Short description of the image.
   */
  public function image( $filename, $alt ) {
    $filename = $this->getImagePath() . $filename;

    list( $width, $height, $type, $attr ) = getimagesize( $filename );

    echo
      '<img ' .
      'src="' . $this->getURL() . $filename . '" ' .
      $attr .
      ' alt="' . $alt . '"' .
      '/>';
  }
}
?>

