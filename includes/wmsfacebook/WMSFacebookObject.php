<?php
require_once 'facebook.php';

/**
 * Represents all WMSFacebook subclasses.
 *
 * All WMSFacebook subclasses require a handle to the global $facebook
 * instance. This class serves to abstract that handle as well as define
 * a number of constants.
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookObject {
  public $ERROR_OBJECT_NOT_FOUND = 803;
  public $ERROR_OBJECT_EXISTS = 804;

  /** 64-bit integer */
  public $OBJECT_TYPE_INTEGER = 1;
  /** 256 characters */
  public $OBJECT_TYPE_STRING  = 2;
  /** 64KB BLOB */
  public $OBJECT_TYPE_BINARY  = 3;

  /** One-way relationship. */
  public $ASSOCATION_TYPE_DIRECTED   = 1;
  /** Two-way, symmetrical relationship. */
  public $ASSOCATION_TYPE_SYMMETRIC  = 2;
  /** Two-way, asymmetrical relationship. */
  public $ASSOCATION_TYPE_ASYMMETRIC = 3;

  private $facebook;

  /**
   * Creates a new WMSFacebookObject instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   */
  public function WMSFacebookObject( $facebook ) {
    $this->setFacebook( $facebook );
  }

  /**
   * Sets the handle to the Facebook instance.
   *
   * @param $facebook Facebook instance.
   */
  private function setFacebook( $facebook ) {
    $this->facebook = $facebook;
  }

  /**
   * Returns the handle to the Facebook instance.
   *
   * @return Facebook A valid Facebook instance
   */
  protected function getFacebook() {
    return $this->facebook;
  }

  /**
   * Helper method to get Facebook's API.
   *
   * @return api_client
   */
  protected function getFacebookAPI() {
    return $this->getFacebook()->api_client;
  }
}
?>

