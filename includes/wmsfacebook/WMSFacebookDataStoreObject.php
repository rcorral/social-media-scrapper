<?php
require_once 'WMSFacebookObject.php';
require_once 'WMSFacebookDataStoreProperty.php';
require_once 'WMSFacebookDataStoreRow.php';

/**
 * Represents a Facebook Object to which properties may be added.
 *
 * This class glues together Facebook Data Store properties with their
 * corresponding Facebook Data Store object. Properties are name/value
 * pairs. This class abstracts the creation and insertion of properties
 * as well as the ability to drop the object entirely.
 *
 * @example data-store.php Data Store Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookDataStoreObject extends WMSFacebookObject {
  private $objectName;

  /** List of WMSFacebookDataStoreProperty instances. */
  private $properties = array();

  /**
   * Used to instantiate a new WMSFacebookDataStoreObject instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   * @param string $objectName Name of a data store object (database table).
   */
  public function WMSFacebookDataStoreObject( $facebook, $objectName ) {
    parent::WMSFacebookObject( $facebook );

    $this->setObjectName( $objectName );
  }

  /**
   * Appends the given property to the list of properties for this data
   * store object. When a property is created, this method is called.
   *
   * @param WMSFacebookDataStoreProperty $wmsProperty Add it to the list.
   *
   * @see createProperty
   */
  private function addProperty( $wmsProperty ) {
    $this->properties[] = $wmsProperty;
  }

  /**
   * Returns the list of properties belonging to this data store object.
   *
   * @return array WMSFacebookDataStoreProperty instances
   *
   * @see addProperty
   * @see createProperty
   */
  public function getProperties() {
    return $this->properties;
  }

  /**
   * Asks Facebook to create an object type based on the objectName for
   * this instance. This will fail silently if the object already exists.
   * Otherwise, the error will be thrown to the calling code.
   *
   * @return bool true - object type was created or already exists.
   */
  public function create() {
    $result = true;

    try {
      $result = $this->getFacebookAPI()->data_createObjectType(
        $this->getObjectName() );
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_EXISTS ) {
        throw $ex;
      }
    }

    return $result;
  }

  /**
   * Creates a map of name/value pairs for insertion into the Facebook
   * "table" associated with an instance of this class. It will return
   * an object that represents the row that was added.
   *
   * @return WMSFacebookDataStoreRow
   */
  public function insertProperties() {
    $wmsRow = null;

    try {
      $id = $this->getFacebookAPI()->data_createObject(
        $this->getObjectName(),
        $this->getPropertyMap() );

      $wmsRow = new WMSFacebookDataStoreRow( $this->getFacebook(), $id );
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_EXISTS ) {
        throw $ex;
      }
    }

    return $wmsRow;
  }

  /**
   * Asks Facebook to delete an object type with the given name. This will
   * fail silently if the object deletion failed because it does not exist.
   * Otherwise, the error will be thrown to the calling code.
   *
   * @return bool true - object type was deleted, or does not exist.
   */
  public function drop() {
    $result = true;

    try {
      $result = $this->getFacebookAPI()->data_dropObjectType(
        $this->getObjectName() );
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_NOT_FOUND ) {
        throw $ex;
      }
    }

    return $result;
  }

  /**
   * Creates a map of all the name/value pairs for all properties that are
   * part of this class. This is used primarily when the properties are
   * inserted into the data store.
   *
   * @return array Map of name/value pairs.
   *
   * @see addProperty
   * @see insertProperties
   */
  private function getPropertyMap() {
    // Create a map of the properties.
    //
    $properties = $this->getProperties();
    $map = array();

    // Append the properties to the end of the map.
    //
    foreach( $properties as $property ) {
      $property->append( $map );
    }

    // Return the map of name/value pairs.
    //
    return $map;
  }

  /**
   * Creates a new property for this data store object. A data store object
   * is like a database table and its properties are like the table's
   * columns. The propertyType must be one of the OBJECT_TYPE_ constants.
   * 
   * @param string $propertyName Name of property to create.
   * @param int $propertyType Type of property to create.
   *
   * @return WMSFacebookDataStoreProperty
   */
  private function createProperty( $propertyName, $propertyType ) {
    $wmsProperty = new WMSFacebookDataStoreProperty(
      $this->getFacebook(), $this, $propertyName, $propertyType );

    $this->addProperty( $wmsProperty );

    $wmsProperty->create();

    return $wmsProperty;
  }

  /**
   * Creates a data store property that contains an integer value.
   *
   * @param string $propertyName Name of the object property to create.
   *
   * @return WMSFacebookDataStoreProperty
   */
  public function createPropertyInteger( $propertyName ) {
    return $this->createProperty( $propertyName, $this->OBJECT_TYPE_INTEGER );
  }

  /**
   * Creates a data store property that contains a string value. This maximum
   * length of the string is 256 characters.
   *
   * @param string $propertyName Name of the object property to create.
   *
   * @return WMSFacebookDataStoreProperty
   */
  public function createPropertyString( $propertyName ) {
    return $this->createProperty( $propertyName, $this->OBJECT_TYPE_STRING );
  }

  /**
   * Creates a data store property that contains a binary value. The
   * maximum length of the value is 64K.
   *
   * @param string $propertyName Name of the object property to create.
   *
   * @return WMSFacebookDataStoreProperty
   */
  public function createPropertyBinary( $propertyName ) {
    return $this->createProperty( $propertyName, $this->OBJECT_TYPE_BINARY );
  }

  private function setObjectName( $objectName ) {
    $this->objectName = $objectName;
  }

  public function getObjectName() {
    return $this->objectName;
  }
}
?>

