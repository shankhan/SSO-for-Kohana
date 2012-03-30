<?php defined('SYSPATH') or die('No direct script access.');

/**
  * @requirement Kohana 3.2 or above
  * @module		Single Sign On (SSO) - Client Class
  * @author		Zeeshan M. Khan
  * @link		http://zeeshanmkhan.com
  * @version	Version 0.01 */

class Kohana_SSO
{
	const SUCCESS = 0;

	const ERROR_DB_FAILURE         = 1;
	const USER_ALREADY_LOGGED_IN   = 2;
	const USER_ALREADY_LOGGED_OUT  = 3;
	const ERROR_INCORRECT_PASSWORD = 4;
	const ERROR_INCORRECT_USERNAME = 5;
	const USER_NOT_REGISTERED      = 6;
	const USER_ALREADY_REGISTERED  = 7;
	
	// Failed to connect to server or server return response other than 200 (OK)
	const ERROR_INVALID_RESPONSE   = 8;

	private static $sso = NULL;
	
    private $sso_server_url;     // URL of the SSO server
    private $key;                // Key assigned by the SSO server (to Broker)
    private $password;           // Password assigned by the SSO server (to Broker)
    private $cookie_expiry_time;

    /** Attach broker with SSO Server and load required variables */
 	public static function init()
    {
    	if (self::$sso !== NULL) return;

		self::$sso = new self;
		
    	self::$sso->sso_server_url     = Kohana::$config->load('sso.url');
    	self::$sso->key                = Kohana::$config->load('sso.key');
    	self::$sso->password           = Kohana::$config->load('sso.password');
    	self::$sso->cookie_expiry_time = Kohana::$config->load('sso.cookie_expiry_time');
	}
	
    /** Attach session at SSO server */
    public static function attach()
	{
		if (self::is_attached()) return;

		$url        = self::$sso->sso_server_url;
		$key        = self::$sso->key;

		$token      = md5(uniqid(rand(), true));
		$expiration = time() + self::$sso->cookie_expiry_time;

		$current_url= self::self_url();
		$return_url = urlencode(base64_encode($current_url));

		$checksum   = self::get_checksum($token);
		$location   = "{$url}attach/{$key}/{$token}/{$checksum}/{$return_url}";

		setcookie('sso_token', $token, $expiration, '/');
		header("Location: {$location}", true, 302);
		exit(0);
	}
	
	/** Register User
	 * @param $data array with keys { username, password }
	 * @return SSOResponse object
	 */
	public static function register($data)
	{
		$user_fields = self::get_user_fields();
    	$data        = array_intersect_key($data, $user_fields);
    	if ( ! self::validate_username(Arr::get($data, 'username', '')))
    		return self::error(self::ERROR_INCORRECT_USERNAME);
    	
		if ( ! self::validate_password(Arr::get($data, 'password', '')))
    		return self::error(self::ERROR_INCORRECT_PASSWORD);
    	
    	return self::server_call(__FUNCTION__, $data);
	}

	/** Authenticate user
     * @param string $username
     * @param string $password
	 * @return SSOResponse object;
	 */
    public static function login($username, $password)
    {
		if ( ! self::validate_username($username)) 
			return self::error(self::ERROR_INCORRECT_USERNAME);
    	
		if ( ! self::validate_username($password)) 
			return self::error(self::ERROR_INCORRECT_PASSWORD);
    	
    	return self::server_call(__FUNCTION__, array('username' => $username, 'password' => $password));
    }

	/** Validate if a username is already registered with SSO Server
	 * @param string $username
	 * @return SSOResponse object;
	 */
	public static function is_registered($username)
	{
	    self::attach();
		
	    if ( ! self::validate_username($username))
    		return self::error(self::ERROR_INCORRECT_USERNAME);
    	
		return self::server_call(__FUNCTION__, array('username' => $username));
	}

	/** Validate if user is logged in at SSO
	 * @return string (User name if logged in else '' )
	 */
	public static function is_logged_in()
	{
		self::attach();
		$object = self::server_call(__FUNCTION__);
		if ($object->response_code == self::USER_ALREADY_LOGGED_IN) return $object->data->username;

      return '';
	}
	
	/** Get user information by username
	 * @param string $username
	 * @return SSOResponse object
	 */
	public static function info($username)
	{
	    self::attach();
		
		if ( ! self::validate_username($username))
    		return self::error(self::ERROR_INCORRECT_USERNAME);
    	
		return self::server_call(__FUNCTION__, array('username' => $username));
	}

	/** Logout */
    public static function logout()
    {
        return self::server_call(__FUNCTION__);
    }

    /**
     * Send request to SSO server.
     *
     * @param string $command - Action (function) to be executed on remote server
     * @param array $data - Variables posted to remote server
     * @return SSOResponse object
     */
    private static function server_call($command, $data=null)
    {
    	$token       = Arr::get($_COOKIE, 'sso_token', '');
    	$key         = self::$sso->key;
    	$checksum    = self::get_checksum($token);
		$password    = self::$sso->password;
		$session_id  = "SSO-{$key}-{$token}-{$checksum}";
		$request_url = self::$sso->sso_server_url . $command;

		$curl = curl_init($request_url);
    	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_COOKIE, 'PHPSESSID=' . $session_id);	// Name must be PHPSESSID

		if ($data) 
		{
			curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }

        $json = curl_exec($curl);
        $HTTP_RESPONSE_CODE = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		
		
		//print $HTTP_RESPONSE_CODE;
		/*
		print '<pre>';
		 print_r(json_decode($json));
		print '</pre>';
		//*/
		if ($HTTP_RESPONSE_CODE != 200) 
        	return new SSOResponse(self::ERROR_INVALID_RESPONSE);
		
        return json_decode($json);
    }

	private static function is_attached()
	{
	    return (Arr::get($_COOKIE, 'sso_token', '') != '');
	}

	private static function get_checksum($token)
	{
		$clientip = Arr::get($_SERVER, 'REMOTE_ADDR');
		$password = self::$sso->password;

		return sha1("{$token}{$clientip}{$password}");
	}

	private static function self_url()
	{
		$protocol    = (Arr::get($_SERVER, 'HTTPS', 'off') == 'on') ? 'https://' : 'http://';
		$server_name = Arr::get($_SERVER, 'SERVER_NAME');
		$port        = (Arr::get($_SERVER, 'SERVER_PORT') == '80') ? '' : (':'.Arr::get($_SERVER, 'SERVER_PORT'));
		$uri         = Arr::get($_SERVER, 'REQUEST_URI');

		return "{$protocol}{$server_name}{$port}{$uri}";
	}

	private static function validate_username($username)
	{
		//@@TODO: impliment any validation (or rules) for username
		
		return TRUE;
	}

	private static function validate_password($password)
	{
		//@@TODO: impliment any validation (or rules) for password
		
		return TRUE;
	}

	private static function get_user_fields()
	{
		//@@TODO: Add add fields name here from users table
		return array('username'=>'', 'password'=>'');
	}
}	// END OF SSO CLASS