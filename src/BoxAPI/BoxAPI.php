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

namespace HobieCat\PHPBoxAPI;

class BoxAPI {

	public $clientId 		 = '';
	public $clientSecret 	 = '';
	public $redirectUri	     = '';
	public $accessToken	     = '';
	public $refreshToken	 = '';
	public $authorizeUrl 	 = 'https://www.box.com/api/oauth2/authorize';
	public $tokenUrl	 	 = 'https://www.box.com/api/oauth2/token';
	public $apiUrl 		     = 'https://api.box.com/2.0';
	public $uploadUrl 		 = 'https://upload.box.com/api/2.0';
	public $asUser			 = '';
	public $errorMessage     = '';
	public $reponseStatus    = '';
	public $tokenStoragePath = './';
	public $error;

	/**
	 * BoxAPI constructor.
	 *
	 * @param string $client_id
	 * @param string $client_secret
	 * @param string $redirect_uri
	 */
	public function __construct( $client_id = '', $client_secret = '', $redirect_uri = '' ) {
		if ( empty( $client_id ) || empty( $client_secret ) ) {
			throw new \Exception ( 'Invalid CLIENT_ID or CLIENT_SECRET or REDIRECT_URL. Please provide CLIENT_ID, CLIENT_SECRET and REDIRECT_URL when creating an instance of the class.' );
		} else {
			$this->clientId     = $client_id;
			$this->clientSecret = $client_secret;
			$this->redirectUri  = $redirect_uri;
		}
	}

	/**
	 * asUser setter
	 *
	 * @param string $userID
	 * @return self
	 */
	public function setAsUser( $userID ) {
		$this->asUser = $userID;

		return $this;
	}

	/**
	 * First step for authentication: gets the code.
	 */
	public function getCode() {
		if ( array_key_exists( 'refresh_token', $_REQUEST ) ) {
			$this->refreshToken = $_REQUEST['refresh_token'];
		} else {
			$url = $this->authorizeUrl . '?' . http_build_query( [
					'response_type' => 'code',
					'client_id'     => $this->clientId,
					'redirect_uri'  => $this->redirectUri
				] );
			header( 'location: ' . $url );
			exit();
		}
	}

	/**
	 * Second step for authentication: gets the accessToken and the refreshToken.
	 *
	 * @param string $code
	 * @param bool $json
	 *
	 * @return array|bool|mixed|object|string
	 */
	public function getToken( $code = '', $json = false ) {
		$url = $this->tokenUrl;
		if ( ! empty( $this->refreshToken ) ) {
			$params = [
				'grant_type'    => 'refresh_token',
				'refresh_token' => $this->refreshToken,
				'client_id'     => $this->clientId,
				'client_secret' => $this->clientSecret
			];
		} else {
			$params = [
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'client_id'     => $this->clientId,
				'client_secret' => $this->clientSecret
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
	public function getUser() {
		$url = $this->buildUrl( '/users/me' );

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
	public function getFolderDetails( $folder, $json = false ) {
		$url = $this->buildUrl( "/folders/$folder" );
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
	public function getFolderItems( $folder, $json = false ) {
		$url = $this->buildUrl( "/folders/$folder/items" );
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
	public function getFolderCollaborators( $folder, $json = false ) {
		$url = $this->buildUrl( "/folders/$folder/collaborations" );
		if ( $json ) {
			return $this->get( $url );
		} else {
			return json_decode( $this->get( $url ), true );
		}
	}

	/**
	 * Get shared items.
	 *
	 * @param stirng $link
	 *
	 * @return array
	 */
	public function getSharedItems( $link ) {
        $url = $this->buildUrl( '/shared_items' );

        $ch = curl_init();
        curl_setopt( $ch, CURLOPT_URL, $url);
        curl_setopt( $ch, CURLOPT_HTTPHEADER, [ "BoxApi: shared_link=".$link ] );
        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
        curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
        curl_setopt( $ch, CURLOPT_MAXREDIRS, 3 );

        $data = curl_exec( $ch );
        curl_close( $ch );

        return json_decode ($data );
    }

	/**
	 * Lists the folders in a folder.
	 *
	 * @param $folder
	 *
	 * @return array
	 */
	public function getFolders( $folder ) {
		$return = [];
		$data   = $this->getFolderItems( $folder );
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
	public function getFiles( $folder ) {
		$return = [];
		$data   = $this->getFolderItems( $folder );
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
	public function getLinks( $folder ) {
		$return = [];
		$data   = $this->getFolderItems( $folder );
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
	public function createFolder( $name, $parent_id ) {
		$url    = $this->buildUrl( "/folders" );
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
	public function updateFolder( $folder, array $params ) {
		$url = $this->buildUrl( "/folders/$folder" );

		return json_decode( $this->put( $url, $params ), true );
	}

	/**
	 * Deletes a folder.
	 *
	 * @param $folder
	 * @param array $opts
	 *
	 * @return boolean
	 */
	public function deleteFolder( $folder, array $opts ) {
		echo $url = $this->buildUrl( "/folders/$folder", $opts );
		$return = json_decode( $this->delete( $url ), true );
		if ( empty( $return ) ) {
			return true;
		} else {
			return false;
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
	public function shareFolder( $folder, array $params ) {
		$url = $this->buildUrl( "/folders/$folder" );

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
	public function shareFile( $file, array $params ) {
		$url = $this->buildUrl( "/files/$file" );

		return json_decode( $this->put( $url, $params ), true );
	}

	/**
	 * Copy a file.
	 *
	 * @param string $file
	 * @param array $params
	 * @param string $link
	 *
	 * @return array
	 */
    public function copyFile( $file, $params, $link ) {
		$url = $this->buildUrl( "/files/".$file."/copy" );

        return json_decode( $this->post( $url, json_encode( $params ),[ "BoxApi: shared_link=".$link ] ), true );
    }

	/**
	 * Get a file.
	 *
	 * @param  $file
	 *
	 * @return array|mixed|object
	 */
	public function getFile( $file ) {
		$url = $this->buildUrl( "/files/$file/content" );

		return $this->getViewer( $url );
	}

	/**
	 * Get the file preview link.
	 *
	 * @param string $fileId
	 *
	 * @return array
	 */
	public function getPreviewLink( $fileId ) {
        $url = $this->buildUrl( '/files/' . $fileId, ['fields' => 'expiring_embed_link'] );

        $result = json_decode( $this->get( $url ), true );

        if ( array_key_exists( 'expiring_embed_link', $result ) && array_key_exists( 'url', $result['expiring_embed_link'] ) ) {
            return $result['expiring_embed_link']['url'];
        } else {
            return '';
        }
	}

	/**
	 * Get the file thumbnail
	 *
     * @param $fileId
     * @param string $extension
     * @param int $minHeight
     * @param int $maxHeight
     * @param int $minWidth
     * @param int $maxWidth
	 *
     * @return mixed
     */
    public function getThumbnail( $fileId, $extension = 'png', $minHeight = null, $maxHeight = null, $minWidth = null, $maxWidth = null ) {
        $urlParams = [];
        if ( $minHeight !== null ) {
            $urlParams['min_height'] = $minHeight;
        }

        if ( $maxHeight !== null ) {
            $urlParams['max_height'] = $maxHeight;
        }

        if ( $minWidth !== null ) {
            $urlParams['min_width'] = $minWidth;
        }

        if ( $maxWidth !== null ) {
            $urlParams['max_width'] = $maxWidth;
        }

        $url = $this->buildUrl( '/files/' . $fileId . '/thumbnail.' . $extension, $urlParams );

        /**
         * - thumbnail is not yet available -> status code 202 and placeholder in Location-header
         * - can't generate thumbnail for this file type -> status code 302 and redirection to the placeholder
         * - thumbnail is available -> status 200
         */

        return $this->get( $url, true );
	}

	/**
	 * Get the details of a file.
	 *
	 * @param $file
	 * @param bool $json
	 *
	 * @return array|bool|mixed|object|string
	 */
	public function getFileDetails( $file, $json = false ) {
		$url = $this->buildUrl( "/files/$file" );
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
	public function getFileContent( $file ) {
		$url = $this->buildUrl( "/files/$file/content" );

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
	public function putFile( $filename, $name = '', $parent_id = '0' ) {
		$url = $this->buildUrl( '/files/content', [], $this->uploadUrl );
		if ( !isset( $name ) || empty ($name) ) {
			$name = basename( $filename );
		}
		$file   = new CURLFile( $filename );
		$params = [
			'file'         => $file,
			'name'         => $name,
			'parent_id'    => $parent_id,
			'access_token' => $this->accessToken
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
	public function updateFile( $file, array $params ) {
		$url = $this->buildUrl( "/files/$file" );

		return json_decode( $this->put( $url, $params ), true );
	}

	/**
	 * Deletes a file.
	 *
	 * @param $file
	 *
	 * @return array|mixed|object|string
	 */
	public function deleteFile( $file ) {
		$url    = $this->buildUrl( "/files/$file" );
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
	public function writeToken( $token, $type = 'file' ) {
		$array = json_decode( $token, true );
		if ( isset( $array['error'] ) ) {
			$this->error = $array['error_description'];

			return false;
		} else {
			$array['timestamp'] = time();
			if ( $type == 'file' ) {
				$fp = fopen( $this->getStoreTokenFilePath(), 'w' );
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
	public function readToken( $type = 'file', $json = false ) {
		$store_token_file_name = $this->getStoreTokenFilePath();
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
	public function loadToken() {
		$return = false;
		$array  = $this->readToken( 'file' );

		if ( ! $array ) {
			$return = false;
		} else {
			if ( isset( $array['error'] ) ) {
				$this->error = $array['error_description'];

				$return = false;
			} elseif ( $this->expired( $array['expires_in'], $array['timestamp'] ) ) {
				$this->refreshToken = $array['refresh_token'];
				$token              = $this->getToken( null, true );
				if ( $this->writeToken( $token, 'file' ) ) {
					$array              = json_decode( $token, true );
					$this->refreshToken = $array['refresh_token'];
					$this->accessToken  = $array['access_token'];

					$return = true;
				}
			} else {
				$this->refreshToken = $array['refresh_token'];
				$this->accessToken  = $array['access_token'];

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
	public function getComments( $file ) {
		$url = $this->buildUrl( "/files/$file/comments" );

		return json_decode( $this->get( $url ), true );
	}

	/**
	 * Get tasks
	 *
	 * @param string $file
	 *
	 * @return array
	 */
	public function getTasks( $file ) {
		$url = $this->buildUrl( "/files/$file/tasks" );

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
	public function createUser( $login, $name ) {
		$url = $this->buildUrl( "/users" );
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
	public function getUserByLogin( $login, $complete = false ) {
		$fields = '';
		if( $complete ) {
			$fields = '&fields=id,name,login,created_at,modified_at,language,space_amount,max_upload_size,status,avatar_url,space_used,can_see_managed_users,is_sync_enabled,is_external_collab_restricted,is_exempt_from_device_limits,is_exempt_from_login_verification';
		}
		$url = $this->buildUrl( "/users" )."&filter_term=$login".$fields;
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
	public function getUsers( $limit = 100, $offset = 0 ){
		$url = $this->buildUrl ("/users") ."&limit=$limit&offset=$offset";
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
	public function getUserById( $id ) {
		$url = $this->buildUrl( "/users/$id" );
		return json_decode( $this->get( $url ),true );
	}

	/**
	 * Get user ID by login
	 *
	 * @param object $user
	 *
	 * @return array
	 */
	public function getUserIDByLogin( $user ){
		$result = $this->getUserByLogin( $user->email );
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
	public function getEnterpriseEvents( $limit=0,$after="2015-06-10T00:00:00-08:00", $before="2015-12-12T10:53:43-08:00", $event='', $stream_position=0 ){
		$url = $this->buildUrl ("/events" )."&stream_type=admin_logs&limit=$limit&created_after=$after&created_before=$before&event_type=$event&stream_position=$stream_position";

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
	public function inviteUser( $login, $name ){
		$url = $this->buildUrl( "/invites" );
		$params = array( 'login' => $login, 'name' => $name );

		return json_decode( $this->post( $url, json_encode( $params ) ), true );
	}

	/**
	 * Get groups
	 *
	 * @return array
	 */
	private function getGroups() {
		$url = $this->buildUrl( "/groups" );

		return json_decode( $this->get($url) );
	}

	/**
	 * Get group id
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function getGroupId( $name ){
		$group_id = 0;
		$groups = $this->getGroups();
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
	public function createGroup( $name ) {
		$url = $this->buildUrl( "/groups" );
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
	public function addUserToGroup( $userId, $groupId ) {
		$url = $this->buildUrl( "/group_memberships" );
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
	public function shareFolderWithUser( $folderId, $userId ) {
		$url = $this->buildUrl( "/collaborations" );
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
	private function buildUrl( $api_func, $opts = [], $url = null ) {
		if (is_null($url)) $url = $this->apiUrl;
		$opts = $this->setOptions( $opts );
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
	private function setOptions( array $opts ) {
		if ( ! array_key_exists( 'access_token', $opts ) ) {
			$opts['access_token'] = $this->accessToken;
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
	 * sets self::response_message and self::errorMessage
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

		$this->reponseStatus = $code;
		$this->errorMessage  = $returnedCode[$code];
	}

	/**
	 * Gets an url response.
	 *
	 * @param $url
	 * @param boolean $followRedirects
	 *
	 * @return bool|string
	 */
	private function get( $url, $followRedirects = false ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

		if( !empty( $this->asUser ) ) {
			$headers = [];
			$headers[] = "as-user:".$this->asUser;
			curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		}

		if ($followRedirects) {
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
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
	 * @param array $headers
	 *
	 * @return bool|string
	 */
	private static function post( $url, $params, $headers = [] ) {
		$ch = curl_init();
		curl_setopt( $ch, CURLOPT_URL, $url );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
		curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
		curl_setopt( $ch, CURLOPT_POST, true );
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $params );
		curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, true );
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
				return $this->get( $matches[1] ) ;
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
	private function getStoreTokenFilePath() {
		return $this->getTokenStoragePath() . "/token.box";
	}

	/**
	 * Get the value of tokenStoragePath
	 */
	public function getTokenStoragePath() {
		return $this->tokenStoragePath;
	}

	/**
	 * Set the value of tokenStoragePath
	 *
	 * @return  self
	 */
	public function setTokenStoragePath( $tokenStoragePath ) {
		$this->tokenStoragePath = $tokenStoragePath;

		return $this;
	}
}