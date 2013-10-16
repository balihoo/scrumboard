<?php
/*
	Copyright (c) 2012 Balihoo, Inc.

	Permission is hereby granted, free of charge, to any person
	obtaining a copy of this software and associated documentation
	files (the "Software"), to deal in the Software without
	restriction, including without limitation the rights to use,
	copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the
	Software is furnished to do so, subject to the following
	conditions:

	The above copyright notice and this permission notice shall be
	included in all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
	EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
	OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
	NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
	HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
	WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
	FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
	OTHER DEALINGS IN THE SOFTWARE.
	*/

// ========================================================================

// ensure Curl is installed
if (!extension_loaded("curl")) {
	throw(new Exception(
		"Curl extension is required"));
}

/*
if(!class_exists("RemoteService", false)) {
	interface RemoteService
	{
		public function init($url);
		public function set($name, $value);
		public function execute();
		public function getInfo($name);
		public function lastError();
		public function close();
	}
}
  */
class CurlRemoteService /*implements RemoteService*/
{
    private $handle = null;

	public function init($url) {
		$this->handle = curl_init($url);
	}

    public function set($name, $value) {
        curl_setopt($this->handle, $name, $value);
    }

    public function execute() {
        return curl_exec($this->handle);
    }

    public function getInfo($name) {
        return curl_getinfo($this->handle, $name);
    }

	public function lastError() {
		return curl_error($this->handle);
	}

    public function close() {
        curl_close($this->handle);
    }
}

/*
	 * RestResponse holds all the REST response data
	 * Before using the reponse, check IsError to see if an exception
	 * occurred with the data sent to Balihoo
	 * ResponseJSON will contain a JSON text
	 * ResponseText contains the raw string response
	 * Url and QueryString are from the request
	 * HttpStatus is the response code of the request
	 */
class RestResponse
{

	public $ResponseText;
	public $ResponseJSON;
	public $HttpStatus;
	public $Url;
	public $QueryString;
	public $IsError;
	public $ErrorMessage;

	public function __construct($url, $text, $status)
	{
		preg_match('/([^?]+)\??(.*)/', $url, $matches);
		$this->Url = $matches[1];
		$this->QueryString = $matches[2];
		$this->ResponseText = $text;
		$this->HttpStatus = $status;
		if ($this->HttpStatus != 204) {
//			$this->ResponseJSON = json_decode($text);
		}

		if (($this->IsError = ($status >= 400)) && is_object($this->ResponseJSON)) {
			$this->ResponseJSON = json_decode($text);
			$this->ErrorMessage =
				(string)$this->ResponseJSON->Message;
		}

	}

}

/*  throws RestException on error
	 * Useful to catch this exception separately from general PHP
	 * exceptions, if you want
	 */
class RestException extends Exception
{
}

class RestClient
{

	protected $endpoint;
	protected $user;
	protected $password;
	protected $serviceClass;

	/** @var  CurlRemoteService */
	protected $service;

	public function __construct($username, $password, $restUrl, $service = "CurlRemoteService")
	{
		$this->user = $username;
		$this->password = $password;
		$this->endpoint = "https://$restUrl";

		$this->serviceClass = $service;
	}

	public function getFullUrl($path)
	{
		return "{$this->endpoint}/$path";
	}

	public function post($path, $params = array())
	{
		return $this->request($path, 'POST', $params);
	}

	public function get($path, $params = array())
	{
		return $this->request($path, 'GET', $params);
	}

	public function request($path, $method = 'GET', $vars = array())
	{
		return $this->requestInner($path, $method, $vars);
	}

	private function encodeVars(&$vars)
	{
		$encoded = "";
		foreach ($vars as $key => $value)
		{
			$encoded .= "$key=" . urlencode($value) . "&";
		}
		return substr($encoded, 0, -1);
	}

	private function joinToken($path)
	{
		return FALSE === strpos($path, '?') ? "?" : "&";
	}

	private function makeUrl($path, $method, $encodedVars)
	{
		// construct full url
		$url = "{$this->endpoint}/$path";

		// if GET and vars, append them
		if (strtoupper($method) == "GET") {
			$url .= $this->joinToken($path) . $encodedVars;
		}

		return $url;
	}

	public function requestInner($path, $method = "GET", $vars = array())
	{
		$fp = null;
		$tmpfile = "";
		$encoded = $this->encodeVars($vars);
		// construct full url
		$url = $this->makeUrl($path, $method, $encoded);

		// initialize a new curl object
		$this->service = new $this->serviceClass();
		$this->service->init($url);

		$this->service->set(CURLOPT_SSL_VERIFYPEER, false);
		$this->service->set(CURLOPT_RETURNTRANSFER, true);
		switch (strtoupper($method)) {
			case "GET":
				$this->service->set(CURLOPT_HTTPGET, true);
				break;
			case "POST":
				$this->service->set(CURLOPT_POST, true);
				$this->service->set(CURLOPT_POSTFIELDS, $encoded);
				break;
			case "PUT":
				$this->service->set(CURLOPT_POSTFIELDS, $encoded);
				$this->service->set(CURLOPT_CUSTOMREQUEST, "PUT");
				file_put_contents(
					$tmpfile = tempnam("/tmp", "put_"),
					$encoded);
				$this->service->set(CURLOPT_INFILE, $fp = fopen(
					$tmpfile,
					'r'));
				$this->service->set(CURLOPT_INFILESIZE, filesize($tmpfile));
				break;
			case "DELETE":
				$this->service->set(CURLOPT_CUSTOMREQUEST, "DELETE");
				break;
			default:
				throw(new RestException("Unknown method $method"));
				break;
		}

		// send credentials
		if(isset($this->user))
		{
			$this->service->set(CURLOPT_USERPWD, "{$this->user}:{$this->password}");
		}

		if (FALSE === ($result = $this->service->execute())) {
			throw(new RestException(
				"Curl failed with error " . $this->service->lastError()));
		}

		// get result code
		$responseCode = $this->service->getInfo(CURLINFO_HTTP_CODE);

		// unlink tmpfiles
		if ($fp) {
			fclose($fp);
		}
		if (strlen($tmpfile)) {
			unlink($tmpfile);
		}

		return new RestResponse($url, $result, $responseCode);
	}

}
