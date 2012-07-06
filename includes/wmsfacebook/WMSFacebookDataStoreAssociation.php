<?php
require_once 'WMSFacebookObject.php';

/**
 * Represents two IDs that have been associated.
 *
 * This class contains details about an association of two objects, which
 * are represented by their IDs. Other details includes an arbitrary piece
 * of data and a timestamp. The timestamp can be used for any purpose, but
 * the most common is to track when the association was made.
 *
 * @example data-store.php Data Store Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookDataStoreAssociation extends WMSFacebookObject {
  private $associationName = null;

  private $id1 = null;
  private $id2 = null;

  private $dataValue = null;
  private $timestamp = null;

  /**
   * Creates a new instance of an association with a given name.
   *
   * @param Facebook $facebook Valid Facebook instance.
   * @param string $name Name of the association.
   */
  public function WMSFacebookDataStoreAssociation( $facebook, $name ) {
    parent::WMSFacebookObject( $facebook );

    $this->setAssociationName( $name );
  }

  /**
   * Removes this association from the data store. All other associations
   * remain valid.
   *
   * @return bool true - association was removed, or does not exist.
   */
  public function drop() {
    $result = true;

    try {
      $result = $this->getFacebookAPI()->data_removeAssociation(
        $this->getAssociationName(),
        $this->getId1(),
        $this->getId2() );
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_NOT_FOUND ) {
        throw $ex;
      }
    }

    return $result;
  }

  /**
   * Inserts this association from the data store.
   *
   * @see getData
   * @see getTimestamp
   *
   * @return boolean true - association was added.
   */
  public function create() {
    return $this->getFacebookAPI()->data_setAssociation(
      $this->getAssociationName(),
      $this->getId1(),
      $this->getId2(),
      $this->getData(),
      $this->getTimestamp() );
  }

  public function setId1( $id1 ) {
    $this->id1 = $id1;
  }

  private function getId1() {
    return $this->id1;
  }

  public function setId2( $id2 ) {
    $this->id2 = $id2;
  }

  private function getId2() {
    return $this->id2;
  }

  public function setData( $dataValue ) {
    $this->dataValue = $dataValue;
  }

  private function getData() {
    return $this->dataValue;
  }

  public function setTimestamp( $timestamp ) {
    $this->timestamp = $timestamp;
  }

  private function getTimestamp() {
    return $this->timestamp;
  }

  private function setAssociationName( $associationName ) {
    $this->associationName = $associationName;
  }

  private function getAssociationName() {
    return $this->associationName;
  }
}
?>

