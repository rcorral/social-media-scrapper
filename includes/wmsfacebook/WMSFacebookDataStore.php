<?php
require_once 'WMSFacebookObject.php';
require_once 'WMSFacebookDataStoreObject.php';
require_once 'WMSFacebookDataStoreAssociationLink.php';

/**
 * Represents a database built upon Facebook's Data Store API.
 *
 * This class facilitates the creation of objects (i.e., database tables)
 * and association links. It also contains helper methods for data
 * compression over HTTP (using bzip2 compression). Using compression can
 * significantly increase the amount of data that can be packed into
 * Facebook's per-column (of type BLOB) data limit.
 *
 * @example data-store.php Data Store Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookDataStore extends WMSFacebookObject {
  /**
   * Used to instantiate a new WMSFacebookDataStore instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   */
  public function WMSFacebookDataStore( $facebook ) {
    parent::WMSFacebookObject( $facebook );
  }

  /**
   * Creates an object type with the given name. This will fail silently
   * if the object creation failed because it already exists. Otherwise,
   * the error will be thrown to the calling code.
   *
   * @param string $objectName Name of the object type to create.
   *
   * @return WMSFacebookDataStoreObject
   */
  public function createObjectType( $objectName ) {
    $wmsDSObject = new WMSFacebookDataStoreObject(
      $this->getFacebook(), $objectName );

    $wmsDSObject->create();

    return $wmsDSObject;
  }

  /**
   * Creates an association link of a given name, with respect to the two
   * aliases. The aliases are names for the IDs. For example "user_id" would
   * be an alias that represents the Facebook user's unique identifier.
   *
   * @param string $associationName Name of the association link to create.
   * @param string $alias1 Name of the ID to associate with alias2.
   * @param string $alias2 Name of the ID to associate with alias1.
   *
   * @return WMSFacebookDataStoreAssociationLink
   */
  public function createAssociationLink(
    $associationName, $alias1, $alias2 ) {
    $wmsDSAssociation = new WMSFacebookDataStoreAssociationLink(
      $this->getFacebook(), $associationName );

    $wmsDSAssociation->create( $alias1, $alias2 );

    return $wmsDSAssociation;
  }

  /**
   * Compresses the given data and makes it suitable for transmission over
   * HTTP-based protocols. This uses bzip2 compression, which must have
   * been compiled into PHP.
   *
   * @param string $data Information to compress.
   * @param int $level How well to compress (9 = best/slow, 1 = poor/fast).
   *
   * @see decompress
   */
  public static function compress( $data, $level = 9 ) {
    return urlencode( bzcompress( $data, $level ) );
  }

  /**
   * Decompresses the given data that was previously compressed with a call
   * to compress.
   *
   * @param string $data Information to decompress.
   *
   * @see compress
   */
  public static function decompress( $data ) {
    return bzdecompress( urldecode( $data ) );
  }
}
?>

