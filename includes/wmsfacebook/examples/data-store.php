<!--
 | Demonstrates the following tasks using Facebook's Data Store:
 |
 | * Create a WMSFacebook instance (wrapper for Facebook's $facebook class)
 | * Obtain a WMSFacebookApplication instance (for application-specific tasks)
 | * Create a WMSFacebookDataStore (framework for Facebook's data store API)
 | * Create a Facebook Object (i.e., "table")
 | * Create a Facebook Property (i.e., "column")
 | * Insert a Facebook Property (i.e., "row")
 | * Associate a user's ID with an inserted row.
 | * Select a Facebook Property (i.e., "row")
 | * Remove the Facebook Object and all its data
 | * Remove the Facebook Association and all its data
 |
 | The WMSFacebook API simplifies the exposed Data Store API.
 |
 | For this example to work, the constants $APP_API_KEY, $APP_SECRET,
 | and $APP_URL must be defined. This file must exist at the same
 | level as the "wmsfacebook" subdirectory.
 +-->
<p>
<?
  require_once 'constants.php';
  require_once 'wmsfacebook/WMSFacebook.php';

  $wmsFacebook = new WMSFacebook( $APP_API_KEY, $APP_SECRET );
  $wmsUser = $wmsFacebook->login( $APP_URL );
  $wmsApp = $wmsFacebook->getWMSFacebookApplication();

  // Create the "table" and a "column" for that table.
  //
  $wmsDataStore = $wmsFacebook->getWMSFacebookDataStore();
  $myTable = $wmsDataStore->createObjectType( 'my_table' );
  $property = $myTable->createPropertyString( 'my_column' );

  // Define the link for the user's ID and the "my_table" ID.
  //
  $userTableLink = $wmsDataStore->createAssociationLink(
   'user_table', 'user_id', 'table_id' );

  // Assign a value to the property that will be saved using Facebook's data
  // store.
  //
  $property->setValue( "Facebook Data Store API" );

  // Insert the property value into the data store.
  //
  $wmsRow = $myTable->insertProperties();

  // Set the association between this Facebook user and the row data
  // that was just inserted.
  //
  $association = $userTableLink->associate(
   $wmsUser->getId(), $wmsRow->getId() );

  // Get the IDs of the rows that have been associated with the user.
  //
  $wmsRows = $userTableLink->getAssociatedObjects( $wmsUser->getId() );

  // Iterate over the rows to get the properties (i.e., values) for each
  // row.
  //
  foreach( $wmsRows as $row ) {
    $properties = $row->select();

    // Display the row data.
    //
    print_r( $properties );
  }

  // Remove all references to this table from the Facebook Data Store
  // Normally you would not perform these steps as they will erase
  // all the data for the given table, as well as erasing all associated
  // links for this application.
  //
  // However, since this is example code, it behooves us to tidy up.
  //
  $myTable->drop();
  $userTableLink->drop();
?>
</p>

