<?php
require_once 'WMSFacebookObject.php';
require_once 'WMSFacebookDataStoreAssociation.php';
require_once 'WMSFacebookDataStoreRow.php';

/**
 * Represents a associative link between two IDs that will be associated.
 *
 * Associations are allow an application to retrieve stored data. A
 * common association is between the user's Facebook ID and an ID
 * belonging to a "row" of data that has been added to the Facebook
 * data store (i.e., the Facebook database).
 *
 * @example data-store.php Data Store Example
 *
 * @author     Dave Jarvis
 * @link       http://www.davidjarvis.ca
 * @copyright  Copyright (c) 2007 White Magic Software, Ltd.
 * @license    http://www.gnu.org/licenses/lgpl.html GNU LGPL Version 3
 * @version    0.0.1
 */
class WMSFacebookDataStoreAssociationLink extends WMSFacebookObject {
  private $associationName = null;
  private $associationType = null;

  /**
   * Creates a new instance of an association link with a given name.
   *
   * @param Facebook $facebook Valid Facebook instance.
   * @param string $name Name of the association link.
   */
  public function WMSFacebookDataStoreAssociationLink( $facebook, $name ) {
    parent::WMSFacebookObject( $facebook );

    $this->setAssociationName( $name );
  }

  /**
   * Defines a new association link.
   *
   * @param string $alias1 Name of the item to associate with alias2.
   * @param string $alias2 Name of the item to associate with alias1.
   */
  public function create( $alias1, $alias2 ) {
    $result = true;

    $association1 = $this->createComplexParams( $alias1 );
    $association2 = $this->createComplexParams( $alias2 );

    try {
      $result = $this->getFacebookAPI()->data_defineAssociation(
        $this->getAssociationName(),
        $this->getAssociationType(),
        $association1,
        $association2 );
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_EXISTS ) {
        throw $ex;
      }
    }

    return $result;
  }

  /**
   * Removes this association link and all associations from the data store.
   *
   * @return bool true - the association link was permanently deleted.
   */
  public function drop() {
    $result = true;

    try {
      $result = $this->getFacebookAPI()->data_undefineAssociation(
        $this->getAssociationName() );
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_NOT_FOUND ) {
        throw $ex;
      }
    }

    return $result;
  }

  /**
   * Stores an association between two Facebook identifiers ($id1, $id2) in
   * Facebook's Data Store. The optional timestamp parameter will be set to
   * the current date and time if not explicitly set.
   *
   * @param int|string $id1 Facebook ID to associate with id2.
   * @param int|string $id2 Facebook ID to associate with id1.
   * @param string $data (Optional) Arbitrary data to describe the association.
   * @param int $timestamp (Optional) Timestamp of the association.
   *
   * @return WMSFacebookDataStoreAssociation
   *
   * @see getAssociatedObjects
   */
  public function associate( $id1, $id2, $data = null, $timestamp = null ) {
    if( $timestamp == null ) {
      $timestamp = time();
    }

    $association = new WMSFacebookDataStoreAssociation(
      $this->getFacebook(),
      $this->getAssociationName() );

    $association->setId1( $id1 );
    $association->setId2( $id2 );
    $association->setData( $data );
    $association->setTimestamp( $timestamp );

    $association->create();

    return $association;
  }

  /**
   * Given a Facebook ID ($id), this method returns all the IDs that have
   * been associated with $id.
   *
   * @param int|string $id Facebook ID associated with another ID.
   *
   * @return WMSFacebookDataStoreRow
   *
   * @see associate
   */
  public function getAssociatedObjects( $id ) {
    $rows = array();

    try {
      $results = $this->getFacebookAPI()->data_getAssociatedObjects(
        $this->getAssociationName(),
        $id,
        false );

      if( $results ) {
        foreach( $results as $result ) {
          $row = new WMSFacebookDataStoreRow(
            $this->getFacebook(),
            $result["id2"] );

          $rows[] = $row;
        }
      }
    }
    catch( Exception $ex ) {
      if( $ex->getCode() != $this->ERROR_OBJECT_NOT_FOUND ) {
        throw $ex;
      }
    }

    return $rows;
  }

  /**
   * Creates an map of parameters used when defining the assoiative link.
   * The name/value pairs returned by this method are defined by the
   * Facebook API for "data_defineAssociation".
   *
   * @param string $alias Name of an ID to associate.
   *
   * @return array Map of name-value pairs.
   *
   * @see create
   */
  private function createComplexParams( $alias ) {
    return array( "alias" => $alias );
  }

  private function setAssociationName( $associationName ) {
    $this->associationName = $associationName;
  }

  private function getAssociationName() {
    return $this->associationName;
  }

  /**
   * Sets the type of association represented by this class. Valid
   * values include: ASSOCATION_TYPE_DIRECTED, ASSOCATION_TYPE_SYMMETRIC,
   * and ASSOCATION_TYPE_ASYMMETRIC.
   *
   * @param int $associationType The type of association.
   *
   * @see create
   */
  private function setAssociationType( $associationType ) {
    $this->associationType = $associationType;
  }

  /**
   * Returns the type of association represented by this class. If the
   * type has not been set, the type returned will indicate that this
   * association is one-way.
   *
   * @return int Type of association.
   *
   * @see create
   */
  private function getAssociationType() {
    if( $this->associationType == null ) {
      $this->associationType = $this->ASSOCATION_TYPE_DIRECTED;
    }

    return $this->associationType;
  }
}
?>

