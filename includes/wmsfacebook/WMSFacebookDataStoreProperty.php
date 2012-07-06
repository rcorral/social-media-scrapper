<?php
require_once 'WMSFacebookObject.php';

/**
 * Represents a property, which is similar to a database column.
 *
 * This class is responsible for the creation and deletion of a database
 * column. It also contains a value that can be inserted into the column.
 * When setting a value, it is possible to ask that the data be compressed.
 * Compressing large amounts of data will speed up the application (presuming
 * network bandwidth is a bottleneck, not CPU time) as well as allow more
 * information to be packed within Facebook's data store limit.
 *
 * @example data-store.php Data Store Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookDataStoreProperty extends WMSFacebookObject {
  private $wmsFacebookDataStoreObject;
  private $propertyName;
  private $propertyType;

  private $propertyValue;

  /**
   * Used to instantiate a new WMSFacebookDataStoreProperty instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   * @param WMSFacebookDataStoreObject $wmsFacebookDataStoreObject Object
   * that will contain this property.
   * @param string $propertyName Unique identifier for this property.
   * @param int $propertyType Integer, String, or Binary.
   */
  public function WMSFacebookDataStoreProperty(
    $facebook, $wmsFacebookDataStoreObject, $propertyName, $propertyType ) {
    parent::WMSFacebookObject( $facebook );

    $this->setWMSFacebookDataStoreObject( $wmsFacebookDataStoreObject );
    $this->setPropertyName( $propertyName );
    $this->setPropertyType( $propertyType );
  }

  /**
   * Asks Facebook to create an object type based on the objectName for
   * this instance. This will fail silently if the object already exists.
   * Otherwise, the error will be thrown to the calling code.
   *
   * @return bool true - object was created, or already exists.
   */
  public function create() {
    $result = true;

    try {
      $result = $this->getFacebookAPI()->data_defineObjectProperty(
        $this->getObjectName(),
        $this->getPropertyName(),
        $this->getPropertyType() );
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_EXISTS ) {
        throw $ex;
      }
    }

    return $result;
  }

  /**
   * Asks Facebook to delete this property type. This will fail silently
   * if the deletion failed because the property does not exist. Otherwise,
   * the error will be thrown to the calling code.
   *
   * @return bool true - property type was deleted, or does not exist.
   */
  public function drop() {
    $result = true;

    try {
      $result = $this->getFacebookAPI()->data_undefineObjectProperty(
        $this->getObjectName(),
        $this->getPropertyName() );
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_NOT_FOUND ) {
        throw $ex;
      }
    }

    return $result;
  }

  private function setWMSFacebookDataStoreObject( $wmsFacebookDataStoreObject )
  {
    $this->wmsFacebookDataStoreObject = $wmsFacebookDataStoreObject;
  }

  private function getWMSFacebookDataStoreObject() {
    return $this->wmsFacebookDataStoreObject;
  }

  private function setPropertyName( $propertyName ) {
    $this->propertyName = $propertyName;
  }

  private function getPropertyName() {
    return $this->propertyName;
  }

  private function setPropertyType( $propertyType ) {
    $this->propertyType = $propertyType;
  }

  private function getPropertyType() {
    return $this->propertyType;
  }

  /**
   * Changes the value associated with this property. The value of the
   * property must be appropriate to the underlying data type. For example,
   * if this property was created as holding an integer value, then the
   * parameter value must be of type integer. Strings must match with strings
   * and binary data should match with binary content.
   *
   * Eventually the data will make its way into the permanent data store on
   * Facebook.
   *
   * @param string $propertyValue Value to store in the database.
   * @param bool $compress true - compress $propertyValue using bzip2.
   */
  public function setValue( $propertyValue, $compress = false ) {
    if( $compress ) {
      $propertyValue = WMSFacebookDataStore::compress( $propertyValue );
    }

    $this->propertyValue = $propertyValue;
  }

  /**
   * Returns the value associated with this property.
   *
   * @return Object An integer, string, or binary content depending on
   * the type of property this object represents.
   */
  public function getValue() {
    return $this->propertyValue;
  }

  /**
   * Returns the name of the data store object to which this property type
   * is bound.
   *
   * @return string Name of this property type's object.
   */
  private function getObjectName() {
    return $this->getWMSFacebookDataStoreObject()->getObjectName();
  }

  /**
   * Appends the name and value of this property to the end of the given
   * array.
   *
   * @param array $map Array into which property's name and value are appended.
   */
  public function append( &$map ) {
    $map[ $this->getPropertyName() ] = $this->getValue();
  }

  /**
   * Returns a preformatted string containing the name and value of this
   * property, separated by a full colon. The value itself will be in quotes
   * so that empty values can be "seen".
   *
   * @return string Name and value of this property.
   */
  public function __toString() {
    return $this->getPropertyName() . ": '" . $this->getValue() . "'";
  }
}
?>

