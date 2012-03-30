<?php defined('SYSPATH') or die('No direct script access.');

/**
  * @requirement Kohana 3.2 or above
  * @module		Single Sign On (SSO) - Client Class
  * @author		Zeeshan M. Khan
  * @link		http://zeeshanmkhan.com
  * @version	Version 0.01 */

class SSOResponse
{
	public $response_code;
	public $data;

	public function __construct($response_code, $data=null)
	{
		$this->response_code = $response_code;
		$this->data          = $data;
	}
}	// END OF SSO RESPONSE CLASS