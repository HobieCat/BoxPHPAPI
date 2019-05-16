<?php

require_once __DIR__ . '/../vendor/autoload.php';

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

echo $box->getUser();