<?php

use HobieCat\PHPBoxAPI\BoxAPI;

include( 'src/BoxAPI/BoxAPI.php' );

$client_id     = 'CLIENT ID';
$client_secret = 'CLIENT SECRET';
$redirect_uri  = 'REDIRECT URL';

$box = new HobieCat\PHPBoxAPI\BoxAPI( $client_id, $client_secret, $redirect_uri );

if ( ! $box->loadToken() ) {
	if ( isset( $_GET['code'] ) ) {
		$token = $box->getToken( $_GET['code'], true );
		if ( $box->writeToken( $token, 'file' ) ) {
			$box->loadToken();
		}
	} else {
		$box->getCode();
	}
}

// User details
$box->getUser();

// Get folder details
$box->getFolderDetails( 'FOLDER ID' );

// Get folder items list
$box->getFolderItems( 'FOLDER ID' );

// All folders in particular folder
$box->getFolders( 'FOLDER ID' );

// All Files in a particular folder
$box->getFiles( 'FOLDER ID' );

// All Web links in a particular folder
$box->getLinks( 'FOLDER ID' );

// Get folder collaborators list
$box->getFolderCollaborators( 'FOLDER ID' );

// Create folder
$box->createFolder( 'FOLDER NAME', 'PARENT FOLDER ID' );

// Update folder details
$details['name'] = 'NEW FOLDER NAME';
$box->updateFolder( 'FOLDER ID', $details );

// Share folder
$params['shared_link']['access'] = 'ACCESS TYPE'; //open|company|collaborators
print_r( $box->shareFolder( 'FOLDER ID', $params ) );

// Delete folder
$opts['recursive'] = 'true';
$box->deleteFolder( 'FOLDER ID', $opts );

// Get file details
$box->getFileDetails( 'FILE ID' );

// Upload file
$box->putFile( 'RELATIVE FILE URL', 'FILE NAME', 'FOLDER ID' );

// Update file details
$details['name']        = 'NEW FILE NAME';
$details['description'] = 'NEW DESCRIPTION FOR THE FILE';
$box->updateFile( 'FILE ID', $details );

// Share file
$params['shared_link']['access'] = 'ACCESS TYPE'; //open|company|collaborators
print_r( $box->shareFile( 'File ID', $params ) );

// Delete file
$box->deleteFile( 'FILE ID' );

if ( isset( $box->error ) ) {
	echo $box->error . "\n";
}