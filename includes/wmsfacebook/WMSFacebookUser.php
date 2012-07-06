<?php
require_once 'WMSFacebookObject.php';

/**
 * Represents a Facebook user.
 *
 * This class is a simple wrapper class for a Facebook user.
 *
 * @example data-store.php Data Store Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookUser extends WMSFacebookObject {
  private $facebookUser;

  /**
   * Creates a new WMSFacebookUser instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   * @param mixed $facebookUser Facebook's $user variable.
   */
  public function WMSFacebookUser( $facebook, $facebookUser ) {
    parent::WMSFacebookObject( $facebook );

    $this->setFacebookUser( $facebookUser );
  }

  private function setFacebookUser( $facebookUser ) {
    $this->facebookUser = $facebookUser;
  }

  /**
   * Returns the unique identifier for the Facebook user represented by this
   * instance.
   *
   * @return int Value of the Facebook user's ID.
   */
  public function getId() {
    return $this->facebookUser;
  }
}
?>

