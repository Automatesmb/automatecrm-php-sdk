<?php
/***********************************************************************************************
** The contents of this file are subject to automateCRM License Version 1.0
 * ( "License" ); You may not use this file except in compliance with the License
 * The Original Code is:  Automate SMB Enterprises
 * The Initial Developer of the Original Code is Automate SMB Enterprises.
 * Portions created by Automate SMB Enterprises are Copyright ( C ) Automate SMB Enterprises
 * All Rights Reserved.
**
*************************************************************************************************/

namespace Automatecrm\Rest\Api;

use GuzzleHttp\Client as HTTPClient;


/**
 * automateCRM REST API Client
 */
class Client {
	// Webserice file
	var $servicebase = 'webservice.php';

	// HTTP Client instance
	var $client = false;
	// Service URL to which client connects to
	var $serviceurl = false;

	// Webservice user credentials
	var $serviceuser= false;
	var $servicekey = false;

	// Webservice login validity
	var $servertime = false;
	var $expiretime = false;
	var $servicetoken=false;

	// Webservice login credentials
	var $sessionid  = false;
	var $userid     = false;

	// Last operation error information
	var $lasterror  = false;

	/**
	 * Constructor.
	 */
	function __construct($url) {
		$this->serviceurl = $this->getWebServiceURL($url);
		$this->client = new HTTPClient(['base_uri' => $this->serviceurl]);
	}


	/**
	 * Get actual record id from the response id.
	 */
	private function getRecordId($id) {
		$ex = explode('x', $id);
		return $ex[1];
	}
	
	/**
	 * Get Result Column Names.
	 */
	private function getResultColumns($result) {
		$columns = Array();
		if(!empty($result)) {
			$firstrow= $result[0];
			foreach($firstrow as $key=>$value) $columns[] = $key;
		}
		return $columns;
	}	

	/**
	 * Get the URL for sending webservice request.
	 */
	public function getWebServiceURL($url) {
		if(stripos($url, $this->servicebase) === false) {
			if(strripos($url, '/') != (strlen($url)-1)) {
				$url .= '/';
			}
			$url .= $this->servicebase;
		}
		return $url;
	}


	/**
	 * Check if result has any error.
	 */
	public function hasError($result) {
		if(isset($result['success']) && $result['success'] === true) {
			$this->lasterror = false;
			return false;
		}
		$this->lasterror = $result['error'];
		return true;
	}

	/**
	 * Get last operation error
	 */
	public function lastError() {
		return $this->lasterror;
	}

	/**
	 * Perform the challenge
	 * @access private
	 */
	private function doChallenge($username) {
		$data = Array(
			'operation' => 'getchallenge',
			'username'  => $username
		);
		$resultdata = $this->client->request('GET','',['query' => $data]);

		if($this->hasError($resultdata)) {
			return false;
		}

		$this->servertime   = $resultdata['result']['serverTime'];
		$this->expiretime   = $resultdata['result']['expireTime'];
		$this->servicetoken = $resultdata['result']['token'];
		return true;
	}

	/**
	 * Do Login Operation
	 */
	public function doLogin($username, $userAccesskey) {
		// Do the challenge before login
		if($this->doChallenge($username) === false) return false;

		$data = Array(
			'operation' => 'login',
			'username'  => $username,
			'accessKey' => md5($this->servicetoken.$userAccesskey)
		);
		$resultdata = $this->client->request('POST','',['form_params' => $data]);

		if($this->hasError($resultdata)) {
			return false;
		}
		$this->serviceuser = $username;
		$this->servicekey  = $userAccesskey;

		$this->sessionid = $resultdata['result']['sessionName'];
		$this->userid    = $resultdata['result']['userId'];
		return true;
	}

	/**
	 * Do Query Operation.
	 */
	public function doQuery($query) {

		// Make sure the query ends with ;
		$query = trim($query);
		if(strripos($query, ';') != strlen($query)-1) $query .= ';';

		$data = Array(
			'operation' => 'query',
			'sessionName'  => $this->sessionid,
			'query'  => $query
		);
		$resultdata = $this->client->request('GET','',['query' => $data]);
		if($this->hasError($resultdata)) {
			return false;
		}
		return $resultdata['result'];
	}

	/**
	 * List types available Modules.
	 */
	public function doListTypes() {

		$data = Array(
			'operation' => 'listtypes',
			'sessionName'  => $this->sessionid
		);
		$resultdata = $this->client->request('GET','',['query' => $data]);
		if($this->hasError($resultdata)) {
			return false;
		}
		$modulenames = $resultdata['result']['types'];

		$returnvalue = Array();
		foreach($modulenames as $modulename) {
			$returnvalue[$modulename] =
				Array ( 'name' => $modulename );
		}
		return $returnvalue;
	}

	/**
	 * Describe Module Fields.
	 */
	public function doDescribe($module) {

		$data = Array(
			'operation' => 'describe',
			'sessionName'  => $this->sessionid,
			'elementType' => $module
		);
		$resultdata = $this->client->request('GET','',['query' => $data]);
		if($this->hasError($resultdata)) {
			return false;
		}
		return $resultdata['result'];
	}

	/**
	 * Retrieve details of record.
	 */
	public function doRetrieve($record) {

		$data = Array(
			'operation' => 'retrieve',
			'sessionName'  => $this->sessionid,
			'id' => $record
		);
		$resultdata = $this->client->request('GET','',['query' => $data]);
		if($this->hasError($resultdata)) {
			return false;
		}
		return $resultdata['result'];
	}

	/**
	 * Do Create Operation
	 */
	public function doCreate($module, $valuemap) {

		// Assign record to logged in user if not specified
		if(!isset($valuemap['assigned_user_id'])) {
			$valuemap['assigned_user_id'] = $this->userid;
		}

		$data = Array(
			'operation'   => 'create',
			'sessionName' => $this->sessionid,
			'elementType' => $module,
			'element'     => json_encode($valuemap)
		);
		$resultdata = $this->client->request('POST','',['form_params' => $data]);
		if($this->hasError($resultdata)) {
			return false;
		}
		return $resultdata['result'];
	}
	
	/**
	 * Do Update Operation
	 */
	public function doUpdate($module, $valuemap) {

		// Assign record to logged in user if not specified
		if(!isset($valuemap['assigned_user_id'])) {
			$valuemap['assigned_user_id'] = $this->userid;
		}

		$data = Array(
			'operation'   => 'update',
			'sessionName' => $this->sessionid,
			'elementType' => $module,
			'element'     => json_encode($valuemap)
		);
		$resultdata = $this->client->request('POST','',['form_params' => $data]);
		if($this->hasError($resultdata)) {
			return false;
		}
		return $resultdata['result'];
	}

	/**
	 * Do Revise Operation
	 */
	public function doRevise($module, $valuemap) {

		// Assign record to logged in user if not specified
		if(!isset($valuemap['assigned_user_id'])) {
			$valuemap['assigned_user_id'] = $this->userid;
		}

		$data = Array(
			'operation'   => 'revise',
			'sessionName' => $this->sessionid,
			'elementType' => $module,
			'element'     => json_encode($valuemap)
		);
		$resultdata = $this->client->request('POST','',['form_params' => $data]);
		if($this->hasError($resultdata)) {
			return false;
		}
		return $resultdata['result'];
	}	

	/**
	 * Invoke custom operation
	 *
	 * @param String $method Name of the webservice to invoke
	 * @param Object $type null or parameter values to method
	 * @param String $params optional (POST/GET)
	 */
	public function doInvoke($method, $params = null, $type = 'POST') {

		$senddata = Array(
			'operation' => $method,
			'sessionName' => $this->sessionid
		);
		if(!empty($params)) {
			foreach($params as $k=>$v) {
				if(!isset($senddata[$k])) {
					$senddata[$k] = $v;
				}
			}
		}

		$resultdata = false;
		if(strtoupper($type) == "POST") {
			$resultdata = $this->client->request('POST','',['form_params' => $data]);
		} else {
			$resultdata = $this->client->request('GET','',['query' => $data]);
		}

		if($this->hasError($resultdata)) {
			return false;
		}
		return $resultdata[result];
	}
}
