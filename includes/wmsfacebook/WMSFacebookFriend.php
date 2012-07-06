<?php
require_once 'WMSFacebookObject.php';

/**
 * Represents a friend of the current user.
 *
 * This class is responsible for providing functionality that is specific
 * to a user's friends.
 *
 * @example friends.php Friends Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookFriend extends WMSFacebookObject {
  private $friend;

  /**
   * Used to instantiate a new WMSFacebookFriend instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   * @param string|int $friend Unique identifier for the friend.
   */
  public WMSFacebookFriend( $facebook, $friend ) {
    parent::WMSFacebookObject( $facebook );

    $this->setFriend( $friend );
  }

  private function setFriend( $friend ) {
    $this->friend = $friend;
  }

  private function getFriend() {
    return $this->friend;
  }
}
?>
