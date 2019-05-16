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

namespace Xlthlx\PHPBoxAPI\API\BoxAPI;

class BoxAPI {

	public $client_id 		= '';
	public $client_secret 	= '';
	public $redirect_uri	= '';
	public $access_token	= '';
	public $refresh_token	= '';
	public $authorize_url 	= 'https://www.box.com/api/oauth2/authorize';
	public $token_url	 	= 'https://www.box.com/api/oauth2/token';
	public $api_url 		= 'https://api.box.com/2.0';
	public $upload_url 		= 'https://upload.box.com/api/2.0';
	public $asUser			= '';
	public $error_message   = '';
	public $reponse_status  = '';
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
	 * asUser setter
	 *
	 * @param string $userID
	 * @return self
	 */
	public function setAsUser($userID){
		$this->asUser = $userID;
		return $this;
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
	 * Get a file.
	 *
	 * @param  $file
	 *
	 * @return array|mixed|object
	 */
	public function get_file( $file ) {
		$url = $this->build_url( "/files/$file/content" );

		return $this->getViewer( $url );
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
	 * Get content of a file.
	 *
	 * @param $file
	 *
	 * @return mixed
	 */
	public function get_file_content( $file ) {
		$url = $this->build_url( "/files/$file/content" );

		return $this->download( $url );
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
		$url = $this->build_url( '/files/content', [], $this->upload_url );
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
				$fp = fopen( $this->get_store_token_file_path(), 'w' );
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
		$store_token_file_name = $this->get_store_token_file_path();
		if ( $type == 'file' && file_exists( $store_token_file_name ) ) {
			$fp      = fopen( $store_token_file_name, 'r' );
			$content = fread( $fp, filesize( $store_token_file_name ) );
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
	 * Get comments
	 *
	 * @param string $file
	 *
	 * @return array
	 */
	public function get_comments( $file ) {
		$url = $this->build_url( "/files/$file/comments" );

		return json_decode( $this->get( $url ), true );
	}

	/**
	 * Get tasks
	 *
	 * @param string $file
	 *
	 * @return array
	 */
	public function get_tasks( $file ) {
		$url = $this->build_url( "/files/$file/tasks" );

		return json_decode( $this->get( $url ), true );
	}

	/**
	 * Create user
	 *
	 * @param string $login
	 * @param string $name
	 *
	 * @return array
	 */
	public function create_user( $login, $name ) {
		$url = $this->build_url( "/users" );
		$params = array( 'login' =>$login, 'name' => $name );

		return json_decode( $this->post( $url, json_encode( $params ) ) , true );
	}

	/**
	 * Get user by login
	 *
	 * @param string $login
	 * @param boolean $complete
	 *
	 * @return array
	 */
	public function get_user_by_login( $login, $complete = false ) {
		$fields = '';
		if( $complete ) {
			$fields = '&fields=id,name,login,created_at,modified_at,language,space_amount,max_upload_size,status,avatar_url,space_used,can_see_managed_users,is_sync_enabled,is_external_collab_restricted,is_exempt_from_device_limits,is_exempt_from_login_verification';
		}
		$url = $this->build_url( "/users" )."&filter_term=$login".$fields;
		return json_decode( $this->get( $url ) );
	}

	/**
	 * Get users
	 *
	 * @param integer $limit
	 * @param integer $offset
	 *
	 * @return array
	 */
	public function get_users( $limit = 100, $offset = 0 ){
		$url = $this->build_url ("/users") ."&limit=$limit&offset=$offset";
		$params = array( 'limit' =>$limit );

		return json_decode( $this->get( $url ) );
	}

	/**
	 * Get users by ID
	 *
	 * @param string $id
	 *
	 * @return array
	 */
	public function get_user_by_id( $id ) {
		$url = $this->build_url( "/users/$id" );
		return json_decode( $this->get( $url ),true );
	}

	/**
	 * Get user ID by login
	 *
	 * @param object $user
	 *
	 * @return array
	 */
	public function get_userID_by_login( $user ){
		$result = $this->get_user_by_login( $user->email );
		if( isset( $result->entries ) ){
			if( count( $result->entries ) == 1 ){
				$user->id = $result->entries[0]->id;
			}
		}

		return $user;
	}

	/**
	 * Get enterprise events
	 *
	 * @param integer $limit
	 * @param string $after
	 * @param string $before
	 * @param string $event
	 * @param integer $stream_position
	 *
	 * @return array
	 */
	public function get_enterprise_events( $limit=0,$after="2015-06-10T00:00:00-08:00", $before="2015-12-12T10:53:43-08:00", $event='', $stream_position=0 ){
		$url = $this->build_url ("/events" )."&stream_type=admin_logs&limit=$limit&created_after=$after&created_before=$before&event_type=$event&stream_position=$stream_position";
		return json_decode( $this->get( $url ) );
	}

	/**
	 * Invite user
	 *
	 * @param string $login
	 * @param string $name
	 *
	 * @return array
	 */
	public function invite_user( $login, $name ){
		$url = $this->build_url( "/invites" );
		$params = array( 'login' => $login, 'name' => $name );

		return json_decode( $this->post( $url, json_encode( $params ) ), true );
	}

	/**
	 * Get groups
	 *
	 * @return array
	 */
	private function get_groups() {
		$url = $this->build_url( "/groups" );

		return json_decode( $this->get($url) );
	}

	/**
	 * Get group id
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function get_group_id( $name ){
		$group_id = 0;
		$groups = $this->get_groups();
		foreach( $groups->entries as $group ) {
			if( $group->name == $name ) {
				$group_id = $group->id;
			}
		}

		return $group_id;
	}

	/**
	 * Create group
	 *
	 * @param string $name
	 *
	 * @return array
	 */
	public function create_group( $name ) {
		$url = $this->build_url( "/groups" );
		$params = array( 'name' => $name ) ;
		return json_decode( $this->post( $url, json_encode( $params ) ), true );
	}

	/**
	 * Add user to group
	 *
	 * @param string $userId
	 * @param string $groupId
	 *
	 * @return array
	 */
	public function add_user_to_group( $userId, $groupId ) {
		$url = $this->build_url( "/group_memberships" );
		$params = array( 'user' => array('id' => $userId), 'group' => array('id' => $groupId) );

		return json_decode( $this->post( $url, json_encode( $params ) ), true );
	}

	/**
	 * Share folder with user
	 *
	 * @param string $folderId
	 * @param string $userId
	 *
	 * @return array
	 */
	public function share_folder_with_user( $folderId, $userId ) {
		$url = $this->build_url( "/collaborations" );
		$items = array( 'id' => $folderId, "type" => "folder" );
		$accessible_by = array( "id" => $userId, "type" => "user" );
		$params = array( "item" => $items, "accessible_by" => $accessible_by ,"role" => "viewer" );

		return json_decode( $this->post( $url, json_encode( $params ) ), true );
	}

	/**
	 * Builds the URL for the call.
	 *
	 * @param $api_func
	 * @param array $opts
	 * @param string $url
	 *
	 * @return string
	 */
	private function build_url( $api_func, $opts = [], $url = null ) {
		if (is_null($url)) $url = $this->api_url;
		$opts = $this->set_opts( $opts );
		$base = $url . $api_func;

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
	 * Get status from the code,
	 * sets self::response_message and self::error_message
	 *
	 * @param $code
	 *
	 * @return void
	 */
	private static function getStatus( $code ) {
		$returnedCode = [
			100 => "Continue", 101 => "Switching Protocols", 200 => "OK", 201 => "Created", 202 => "Accepted", 203 => "Non-Authoritative Information",
			204 => "No Content", 205 => "Reset Content", 206 => "Partial Content", 300 => "Multiple Choices", 301 => "Moved Permanently", 302 => "Found", 303 => "See Other",
			304 => "Not Modified", 305 => "Use Proxy", 306 => "(Unused)", 307 => "Temporary Redirect", 400 => "Bad Request", 401 => "Unauthorized", 402 => "Payment Required",
			403 => "Forbidden", 404 => "Not Found", 405 => "Method Not Allowed", 406 => "Not Acceptable", 407 => "Proxy Authentication Required", 408 => "Request Timeout",
			409 => "Conflict", 410 => "Gone", 411 => "Length Required", 412 => "Precondition Failed", 413 => "Request Entity Too Large", 414 => "Request-URI Too Long",
			415 => "Unsupported Media Type", 416 => "Requested Range Not Satisfiable", 417 => "Expectation Failed", 500 => "Internal Server Error", 501 => "Not Implemented",
			502 => "Bad Gateway", 503 => "Service Unavailable", 504 => "Gateway Timeout", 505 => "HTTP Version Not Supported"
		];

		$this->reponse_status = $code;
		$this->error_message  = $returnedCode[$code];
	}

	/**
	 * Gets an url response.
	 *
	 * @param $url
	 *
	 * @return bool|string
	 */
	private function get( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		if( !empty( $this->asUser ) ) {
			$headers = [];
			$headers[] = "as-user:".$this->asUser;
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		}

		$data = curl_exec( $ch );
		$this->getStatus( curl_getinfo( $ch, CURLINFO_HTTP_CODE ) );

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

	/**
	 * Get viewer for a file
	 *
	 * @param $url
	 *
	 * @return mixed
	 */
	private static function getViewer( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		$data = http_parse_headers( curl_exec( $ch ) )['Location'];
		curl_close( $ch );

		return $data;
	}

	/**
	 * Download a file
	 *
	 * @param $url
	 *
	 * @return mixed
	 */
	private function download( $url ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_HEADER, true );
		$data = curl_exec( $ch );
		curl_close( $ch) ;

		$headers = explode( "\r\n", $data );
		foreach ( $headers as $header ) {
			$matches = [];
			if ( preg_match( '/^Location:\s+(.*)/i', $header, $matches ) ) {
				return $this->get( $matches[1]) ;
			}
		}
		return $data;
	}

	/**
	 * Get the token file path
	 *
	 * @param string $tmpdir
	 *
	 * @return string
	 */
	private function get_store_token_file_path( $tmpdir = null ) {
		if ( is_null ( $tmpdir ) ) $tmpdir = sys_get_temp_dir();
		return $tmpdir . "/token.box";
	}
}