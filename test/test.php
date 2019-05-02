<?php

require_once __DIR__ . '/../vendor/autoload.php';

$client_id     = 'CLIENT ID';
$client_secret = 'CLIENT SECRET';
$redirect_uri  = 'REDIRECT URL';

$box = new Xlthlx\PHPBoxAPI\API\BoxAPI\BoxAPI( $client_id, $client_secret, $redirect_uri );

if ( ! $box->load_token() ) {
	if ( isset( $_GET['code'] ) ) {
		$token = $box->get_token( $_GET['code'], true );
		if ( $box->write_token( $token, 'file' ) ) {
			$box->load_token();
		}
	} else {
		$box->get_code();
	}
}

echo $box->get_user();