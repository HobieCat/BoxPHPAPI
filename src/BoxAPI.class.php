<?php
/**
 * Class Box_API
 * PHP API library for box.com.
 *
 * Features:
 *
 * Fetches the user details.
 * Folder:
 * * List the items
 * * List the collaborators
 * * List the folders/files
 * * Update folder details
 * * Create Folder
 * * Delete Folder & its content
 * * Share Folder
 * Files:
 * * Fetches the file details
 * * Upload file
 * * Update file details
 * * Delete file
 * * Share file
 */

namespace BoxAPI;

class Box_API {

	public $client_id = '';
	public $client_secret = '';
	public $redirect_uri = '';
	public $access_token = '';
	public $refresh_token = '';
	public $authorize_url = 'https://www.box.com/api/oauth2/authorize';
	public $token_url = 'https://www.box.com/api/oauth2/token';
	public $api_url = 'https://api.box.com/2.0';
	public $upload_url = 'https://upload.box.com/api/2.0';
	public $error;

	/**
	 * Box_API constructor.
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $redirect_uri
	 */
	public function __construct( $client_id = '', $client_secret = '', $redirect_uri = '' ) {
		if ( empty( $client_id ) || empty( $client_secret ) ) {
			die ( 'Invalid CLIENT_ID or CLIENT_SECRET or REDIRECT_URL. Please provide CLIENT_ID, CLIENT_SECRET and REDIRECT_URL when creating an instance of the class.' );
		} else {
			$this->client_id     = $client_id;
			$this->client_secret = $client_secret;
			$this->redirect_uri  = $redirect_uri;
		}
	}


	/**
	 * First step for authentication: gets the code.
	 */
	public function get_code() {
		if ( array_key_exists( 'refresh_token', $_REQUEST ) ) {
			$this->refresh_token = $_REQUEST['refresh_token'];
		} else {
			$url = $this->authorize_url . '?' . http_build_query( [
					'response_type' => 'code',
					'client_id'     => $this->client_id,
					'redirect_uri'  => $this->redirect_uri
				] );
			header( 'location: ' . $url );
			exit();
		}
	}


	/**
	 * Second step for authentication: gets the access_token and the refresh_token.
	 *
	 * @param string $code
	 * @param bool $json
	 *
	 * @return array|bool|mixed|object|string
	 */
	public function get_token( $code = '', $json = false ) {
		$url = $this->token_url;
		if ( ! empty( $this->refresh_token ) ) {
			$params = [
				'grant_type'    => 'refresh_token',
				'refresh_token' => $this->refresh_token,
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret
			];
		} else {
			$params = [
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'client_id'     => $this->client_id,
				'client_secret' => $this->client_secret
			];
		}
		if ( $json ) {
			return $this->post( $url, $params );
		} else {
			return json_decode( $this->post( $url, $params ), true );
		}
	}


	/**
	 * Gets the current user details.
	 *
	 * @return array|mixed|object
	 */
	public function get_user() {
		$url = $this->build_url( '/users/me' );

		return json_decode( $this->get( $url ), true );
	}


	/**
	 * Gets the details of a folder.
	 *
	 * @param $folder
	 * @param bool $json
	 *
	 * @return array|bool|mixed|object|string
	 */
	public function get_folder_details( $folder, $json = false ) {
		$url = $this->build_url( "/folders/$folder" );
		if ( $json ) {
			return $this->get( $url );
		} else {
			return json_decode( $this->get( $url ), true );
		}
	}


	/**
	 * Gets the list of items in a folder.
	 *
	 * @param $folder
	 * @param bool $json
	 *
	 * @return array|bool|mixed|object|string
	 */
	public function get_folder_items( $folder, $json = false ) {
		$url = $this->build_url( "/folders/$folder/items" );
		if ( $json ) {
			return $this->get( $url );
		} else {
			return json_decode( $this->get( $url ), true );
		}
	}


	/**
	 * Gets the list of collaborators in a folder.
	 *
	 * @param $folder
	 * @param bool $json
	 *
	 * @return array|bool|mixed|object|string
	 */
	public function get_folder_collaborators( $folder, $json = false ) {
		$url = $this->build_url( "/folders/$folder/collaborations" );
		if ( $json ) {
			return $this->get( $url );
		} else {
			return json_decode( $this->get( $url ), true );
		}
	}


	/**
	 * Lists the folders in a folder.
	 *
	 * @param $folder
	 *
	 * @return array
	 */
	public function get_folders( $folder ) {
		$return = [];
		$data   = $this->get_folder_items( $folder );
		foreach ( $data['entries'] as $item ) {
			$array = '';
			if ( $item['type'] == 'folder' ) {
				$array = $item;
			}
			$return[] = $array;
		}

		return array_filter( $return );
	}


	/**
	 * Lists the files in a folder.
	 *
	 * @param $folder
	 *
	 * @return array
	 */
	public function get_files( $folder ) {
		$return = [];
		$data   = $this->get_folder_items( $folder );
		foreach ( $data['entries'] as $item ) {
			$array = '';
			if ( $item['type'] == 'file' ) {
				$array = $item;
			}
			$return[] = $array;
		}

		return array_filter( $return );
	}


	/**
	 * Lists the files in a folder.
	 *
	 * @param $folder
	 *
	 * @return array
	 */
	public function get_links( $folder ) {
		$return = [];
		$data   = $this->get_folder_items( $folder );
		foreach ( $data['entries'] as $item ) {
			$array = '';
			if ( $item['type'] == 'web_link' ) {
				$array = $item;
			}
			$return[] = $array;
		}

		return array_filter( $return );
	}


	/**
	 * Creates a folder.
	 *
	 * @param $name
	 * @param $parent_id
	 *
	 * @return array|mixed|object
	 */
	public function create_folder( $name, $parent_id ) {
		$url    = $this->build_url( "/folders" );
		$params = [ 'name' => $name, 'parent' => [ 'id' => $parent_id ] ];

		return json_decode( $this->post( $url, json_encode( $params ) ), true );
	}


	/**
	 * Modifies the folder details as per the API.
	 *
	 * @param $folder
	 * @param array $params
	 *
	 * @return array|mixed|object
	 */
	public function update_folder( $folder, array $params ) {
		$url = $this->build_url( "/folders/$folder" );

		return json_decode( $this->put( $url, $params ), true );
	}


	/**
	 * Deletes a folder.
	 *
	 * @param $folder
	 * @param array $opts
	 *
	 * @return array|mixed|object|string
	 */
	public function delete_folder( $folder, array $opts ) {
		echo $url = $this->build_url( "/folders/$folder", $opts );
		$return = json_decode( $this->delete( $url ), true );
		if ( empty( $return ) ) {
			return 'The folder has been deleted.';
		} else {
			return $return;
		}
	}


	/**
	 * Shares a folder.
	 *
	 * @param $folder
	 * @param array $params
	 *
	 * @return array|mixed|object
	 */
	public function share_folder( $folder, array $params ) {
		$url = $this->build_url( "/folders/$folder" );

		return json_decode( $this->put( $url, $params ), true );
	}


	/**
	 * Shares a file.
	 *
	 * @param $file
	 * @param array $params
	 *
	 * @return array|mixed|object
	 */
	public function share_file( $file, array $params ) {
		$url = $this->build_url( "/files/$file" );

		return json_decode( $this->put( $url, $params ), true );
	}


	/**
	 * Get the details of a file.
	 *
	 * @param $file
	 * @param bool $json
	 *
	 * @return array|bool|mixed|object|string
	 */
	public function get_file_details( $file, $json = false ) {
		$url = $this->build_url( "/files/$file" );
		if ( $json ) {
			return $this->get( $url );
		} else {
			return json_decode( $this->get( $url ), true );
		}
	}


	/**
	 * Uploads a file.
	 *
	 * @param $filename
	 * @param $name
	 * @param $parent_id
	 *
	 * @return array|mixed|object
	 */
	public function put_file( $filename, $name, $parent_id ) {
		$url = $this->build_url( '/files/content', $this->upload_url );
		if ( isset( $name ) ) {
			$name = basename( $filename );
		}
		$file   = new CURLFile( $filename );
		$params = [
			'file'         => $file,
			'name'         => $name,
			'parent_id'    => $parent_id,
			'access_token' => $this->access_token
		];

		return json_decode( $this->post( $url, $params ), true );
	}


	/**
	 * Modifies the file details as per the API.
	 *
	 * @param $file
	 * @param array $params
	 *
	 * @return array|mixed|object
	 */
	public function update_file( $file, array $params ) {
		$url = $this->build_url( "/files/$file" );

		return json_decode( $this->put( $url, $params ), true );
	}


	/**
	 * Deletes a file.
	 *
	 * @param $file
	 *
	 * @return array|mixed|object|string
	 */
	public function delete_file( $file ) {
		$url    = $this->build_url( "/files/$file" );
		$return = json_decode( $this->delete( $url ), true );
		if ( empty( $return ) ) {
			return 'The file has been deleted.';
		} else {
			return $return;
		}
	}


	/**
	 * Saves the token.
	 *
	 * @param $token
	 * @param string $type
	 *
	 * @return bool
	 */
	public function write_token( $token, $type = 'file' ) {
		$array = json_decode( $token, true );
		if ( isset( $array['error'] ) ) {
			$this->error = $array['error_description'];

			return false;
		} else {
			$array['timestamp'] = time();
			if ( $type == 'file' ) {
				$fp = fopen( 'token.box', 'w' );
				fwrite( $fp, json_encode( $array ) );
				fclose( $fp );
			}

			return true;
		}
	}


	/**
	 * Reads the token.
	 *
	 * @param string $type
	 * @param bool $json
	 *
	 * @return array|bool|mixed|object|string
	 */
	public function read_token( $type = 'file', $json = false ) {
		if ( $type == 'file' && file_exists( 'token.box' ) ) {
			$fp      = fopen( 'token.box', 'r' );
			$content = fread( $fp, filesize( 'token.box' ) );
			fclose( $fp );
		} else {
			return false;
		}
		if ( $json ) {
			return $content;
		} else {
			return json_decode( $content, true );
		}
	}


	/**
	 * Loads the token.
	 *
	 * @return bool
	 */
	public function load_token() {
		$return = false;
		$array  = $this->read_token( 'file' );

		if ( ! $array ) {
			$return = false;
		} else {
			if ( isset( $array['error'] ) ) {
				$this->error = $array['error_description'];

				$return = false;
			} elseif ( $this->expired( $array['expires_in'], $array['timestamp'] ) ) {
				$this->refresh_token = $array['refresh_token'];
				$token               = $this->get_token( null, true );
				if ( $this->write_token( $token, 'file' ) ) {
					$array               = json_decode( $token, true );
					$this->refresh_token = $array['refresh_token'];
					$this->access_token  = $array['access_token'];

					$return = true;
				}
			} else {
				$this->refresh_token = $array['refresh_token'];
				$this->access_token  = $array['access_token'];

				$return = true;
			}
		}

		return $return;
	}


	/**
	 * Builds the URL for the call.
	 *
	 * @param $api_func
	 * @param array $opts
	 *
	 * @return string
	 */
	private function build_url( $api_func, $opts = [] ) {
		$opts = $this->set_opts( $opts );
		$base = $this->api_url . $api_func;

		if ( ! empty( $opts ) ) {
			$query_string = http_build_query( $opts );
			$base         = $base . '?' . $query_string;
		}

		return $base;
	}


	/**
	 * Sets the required before building the query.
	 *
	 * @param array $opts
	 *
	 * @return array
	 */
	private function set_opts( array $opts ) {
		if ( ! array_key_exists( 'access_token', $opts ) ) {
			$opts['access_token'] = $this->access_token;
		}

		return $opts;
	}

	/**
	 * Checks if the token is expired.
	 *
	 * @param $expires_in
	 * @param $timestamp
	 *
	 * @return bool
	 */
	private static function expired( $expires_in, $timestamp ) {
		$c_timestamp = time();
		if ( ( $c_timestamp - $timestamp ) >= $expires_in ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Gets an url response.
	 *
	 * @param $url
	 *
	 * @return bool|string
	 */
	private static function get( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		$data = curl_exec( $ch );
		curl_close( $ch );

		return $data;
	}

	/**
	 * Gets an url response with params.
	 *
	 * @param $url
	 * @param $params
	 *
	 * @return bool|string
	 */
	private static function post( $url, $params ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		$data = curl_exec( $ch );
		curl_close( $ch );

		return $data;
	}

	/**
	 * Puts a file.
	 *
	 * @param $url
	 * @param array $params
	 *
	 * @return bool|string
	 */
	private static function put( $url, array $params = [] ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'PUT' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $params ) );
		$data = curl_exec( $ch );
		curl_close( $ch );

		return $data;
	}

	/**
	 * Delete a file.
	 *
	 * @param $url
	 * @param string $params
	 *
	 * @return bool|string
	 */
	private static function delete( $url, $params = '' ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, 'DELETE' );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		$data = curl_exec( $ch );
		curl_close( $ch );

		return $data;
	}
}