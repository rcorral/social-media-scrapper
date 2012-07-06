<?php
require_once 'WMSFacebookObject.php';
require_once 'WMSFacebookDataStoreObject.php';
require_once 'WMSFacebookDataStoreProperty.php';

/**
 * Represents a row inserted into Facebook's data store, tracked by its ID.
 *
 * This class is responsible for obtaining information from the data store
 * provided a unique identifier. Typically rows are returned from associations,
 * which, in turn, can be used to obtain object data (i.e., the properties
 * associated with an object).
 *
 * @example data-store.php Data Store Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookDataStoreRow extends WMSFacebookObject {
  /** Represents the numeric identifier (FaceBook ID: FBID) for this row. */
  private $fbid;

  /**
   * Used to instantiate a new WMSFacebookDataStoreRow instance.
   *
   * @param Facebook $facebook Valid Facebook instance.
   * @param string|int $id Unique identifier (a Facebook ID) for this row.
   */
  public function WMSFacebookDataStoreRow( $facebook, $id ) {
    parent::WMSFacebookObject( $facebook );

    $this->setId( $id );
  }

  /**
   * Retrieves a list of properties for a set of objects that have an ID
   * matching the ID for this row.
   *
   * @return array List of property values.
   */
  public function select() {
    $result = null;

    try {
      $result = $this->getFacebookAPI()->data_getObject( $this->getId() );
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_NOT_FOUND ) {
        throw $ex;
      }
    }

    return $result;
  }

  private function setId( $fbid ) {
    $this->fbid = $fbid;
  }

  public function getId() {
    return $this->fbid;
  }

  public function __toString() {
    return $this->getId();
  }
}
?>

