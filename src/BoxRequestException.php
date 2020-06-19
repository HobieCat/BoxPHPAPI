<?php

namespace HobieCat\PHPBoxAPI;

class BoxRequestException extends \Exception {

	/**
	 * @var string
	 */
	private $responseData;

	/**
	 * @var string
	 */
	private $curlError;

	/**
	 * @var int
	 */
	private $curlErrorCode;

	/**
	 * @var string
	 */
	private $requestMethod;

	/**
	 * @var string
	 */
	private $requestUrl;

	/**
	 * @var array
	 */
	private $requestHeaders;

	/**
	 * BoxRequestException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|NULL $previous
	 */
	public function __construct($message, $code, $responseData, $curlError = '', $curlErrorCode = 0, $requestMethod = '', $requestUrl = '', $requestHeaders = [], Throwable $previous = null) {
		parent::__construct($message, $code, $previous);
		$this->responseData = $responseData;
		$this->curlError = $curlError;
		$this->curlErrorCode = $curlErrorCode;
		$this->requestMethod = $requestMethod;
		$this->requestUrl = $requestUrl;
		$this->requestHeaders = $requestHeaders;
	}

	/**
	 * @return string
	 */
	public function getReponseData() {
		return $this->responseData;
	}

	/**
	 * @return string
	 */
	public function getCurlError() {
		return $this->curlError;
	}

	/**
	 * @return int
	 */
	public function getCurlErrorCode() {
		return $this->curlErrorCode;
	}

	/**
	 * @return string
	 */
	public function getRequestMethod() {
		$this->requestMethod;
	}

	/**
	 * @return string
	 */
	public function getRequestUrl() {
		$this->requestUrl;
	}

	/**
	 * @return int
	 */
	public function getRequestHeaders() {
		$this->requestHeaders;
	}

}
