<?php
require_once 'WMSFacebookObject.php';

/**
 * Represents a message that can be sent by the current user.
 *
 * This class is responsible for providing functionality that is specific
 * to sending messages.
 *
 * @example message.php Message Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookMessage extends WMSFacebookObject {
  /**
   * Used to instantiate a new WMSFacebookMessage instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   */
  public function WMSFacebookMessage( $facebook ) {
    parent::WMSFacebookObject( $facebook );
  }
}
?>

