<?php
require_once 'WMSFacebookObject.php';

/**
 * Represents profile information for a Facebook user.
 *
 * This class is responsible for functionality related to profile information
 * of a Facebook user.
 *
 * @example profile.php Profile Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookProfile extends WMSFacebookObject {
  /**
   * Creates a new WMSFacebookProfile instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   */
  public function WMSFacebookProfile( $facebook ) {
    parent::WMSFacebookObject( $facebook );
  }
}
?>

