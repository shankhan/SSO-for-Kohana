<?php  if (!defined('SYSPATH')) exit('No direct script access allowed');
/**
 * @requirement Kohana 3.2 or above
 * @package		Single Sign On (SSO) - Server Controller
 * @author		Zeeshan M. Khan
 * @link		http://zeeshanmkhan.com
 * @version		Version 0.01
 */

class Controller_SSO extends Controller {
	const SUCCESS = 0;

	const ERROR_DB_FAILURE         = 1;
	const USER_ALREADY_LOGGED_IN   = 2;
	const USER_ALREADY_LOGGED_OUT  = 3;
	const ERROR_INCORRECT_PASSWORD = 4;
	const ERROR_INCORRECT_USERNAME = 5;
	const USER_NOT_REGISTERED      = 6;
	const USER_ALREADY_REGISTERED  = 7;
	
	private $broker = null;
	private $session_id = null;
	private $error_code = null;
	
 	/** Attach a user session to a broker session */
    public function action_attach()
    {
    	$broker     = $this->request->param('broker', FALSE);
    	$token      = $this->request->param('token', FALSE);
    	$checksum   = $this->request->param('checksum', FALSE);
    	$return_url = $this->request->param('returnurl', FALSE);

		$is_invalid_data = (($broker === FALSE) OR ($token === FALSE) OR ($checksum === FALSE) OR 
		                    ($return_url === FALSE) OR $this->get_checksum($broker, $token) !== $checksum);
    	
		if ($is_invalid_data)
			throw new Kohana_Exception('Invalid broker information! Failed to attache broker client.', NULL, 406);
 		
		$this->start_session();
		
		$session_id = "SSO-{$broker}-{$token}-{$checksum}";
		$this->create_symlink($session_id);

		$this->request->redirect(base64_decode(urldecode($return_url)), 302);
	}

	/** Login user 
	  * @return user object
      */
	public function action_login()
    {
	    $username = $this->get_valid_username();
    	$password = $this->get_valid_password();
    	
    	if (($username === FALSE) OR ($password === FALSE)) return $this->return_reponse($this->error_code);
    	
		$this->start_session();
		
		$user = Model::factory('users')->get_information($username);
		if ( ! $user) return $this->return_reponse(self::USER_NOT_REGISTERED);
		if ($password != $user->password) return $this->return_reponse(self::ERROR_INCORRECT_PASSWORD);

		unset($user->password);
		
		$_SESSION['username'] = $user->username;
		$this->return_reponse(self::SUCCESS, $user);
	}

	/** Register new user */
	public function action_register()
	{
		$this->start_session();

		$data = $this->get_valid_user_data();
		if ($data === FALSE) return $this->return_reponse($this->error_code);
		
		$users_model = Model::factory('users');
		$user = $users_model->get_information($username);
		if ($user) return $this->return_reponse(self::USER_ALREADY_REGISTERED);
		
		if ($users_model->register($data) === FALSE) return $this->return_reponse(self::ERROR_DB_FAILURE);
		
		$this->return_reponse(self::SUCCESS);
	}

 	/** Validate username if registered or not */
	public function action_is_registered()
	{
		$this->start_session();

		$username = $this->get_valid_username();
		if ($username === FALSE) return $this->return_reponse(self::USER_NOT_REGISTERED);
		
		$user = Model::factory('users')->get_information($username);
		return $this->return_reponse(( ! $user) ? self::USER_NOT_REGISTERED : self::USER_ALREADY_REGISTERED);
	}

   	/** Get user information */
    public function action_info()
    {
    	$this->start_session();
		
		$username = $this->get_valid_username();
		if ($username === FALSE) return $this->return_reponse(self::USER_NOT_REGISTERED);
		
		$user = Model::factory('users')->get_information($username);
		if ( ! $user) return $this->return_reponse(self::USER_NOT_REGISTERED);
		
		unset($user->password);
		$this->return_reponse(self::SUCCESS, $user);
	}
	
	/** Check if user is logged in */
	public function action_is_logged_in()
	{
		$this->start_session();
		
		if ($this->is_authenticated() === FALSE) return $this->return_reponse(self::USER_ALREADY_LOGGED_OUT);
		
		$user = Model::factory('users')->get_information($_SESSION['username']);
		if ( ! $user) return $this->return_reponse(self::ERROR_DB_FAILURE);
		
		unset($user->password);
		$this->return_reponse(self::USER_ALREADY_LOGGED_IN, $user);
	}
	
	/** Log out sso session */
    public function action_logout()
    {
     	$this->start_session();
		if (Arr::get($_SESSION, 'username', FALSE) !== FALSE) 
		{
			unset($_SESSION['username']);
		}
		
        $this->return_reponse(self::SUCCESS);
	}

	/** Get broker check sum based on broker token and client ip */
	private function get_checksum($broker, $token, $clientip=null)
    {
    	$broker_record = Model::factory('brokers')->get_details($broker);
		if ( ! $broker_record) return null;

		if ( ! $clientip) 
		{
			$clientip = $_SERVER['REMOTE_ADDR'];
		}
		
		return sha1("{$token}{$clientip}{$broker_record->password}");
    }
    
	/** Start session, check session hijacking */
	private function start_session()
    {
		$sid =  Cookie::get('PHPSESSID', '');
		if (empty($sid)) 
		{
			$sid = session_id();
		}
		
		session_start();

    	$matches = null;
		$client_session_ip = Arr::get($_SESSION, 'client_addr', FALSE);
	
		// Check if request is from a valid broker, otherwise session hijacked destroy it! 
		if (preg_match('/^SSO-(\w*+)-(\w*+)-([a-z0-9]*+)$/', $sid, $matches)) 
		{
			if ($client_session_ip === FALSE) 
			{
                session_destroy();
                throw new Kohana_Exception('No broker is attached! Failed to start session.', NULL, 406);
            }
            
            $this->generate_session_id($matches[1], $matches[2], $matches[3], $client_session_ip);
            if (empty($this->session_id) OR $this->session_id != $sid) 
			{
                session_destroy();
                throw new Kohana_Exception('Invalid sessionid! Failed to start session.', NULL, 406);
            }
            
            $this->broker = $matches[1];
		 	return;
        }

		// User session
		if (($client_session_ip !== FALSE) AND $client_session_ip != $_SERVER['REMOTE_ADDR']) 
        {
			session_regenerate_id();
		}
		
        if ($client_session_ip === FALSE) 
        {
			$_SESSION['client_addr'] = $_SERVER['REMOTE_ADDR'];
		}
    }

	private function generate_session_id($broker, $token, $checksum, $clientip)
    {
		$this->session_id = ($checksum == $this->get_checksum($broker, $token, $clientip)) ? "SSO-{$broker}-{$token}-{$checksum}" : "";
	}
	
	private function is_authenticated()
	{
		return (Arr::get($_SESSION, 'username', FALSE) !== FALSE);
	}
	
	private function return_reponse($code, $data=null)
	{
		$this->response->status(200);
		$this->response->headers('Content-Type', 'application/json');
		$this->response->body(json_encode(new SSOResponse($code, $data)));
	}

	private function create_symlink($session_id, $flag = 0)
	{
		$session  = 'sess_' . session_id();
		$attached = false;
		$link     = (session_save_path() ? session_save_path() : sys_get_temp_dir()) . '/sess_' . $session_id;
		
		if ( ! file_exists($link)) 
		{
			if (PHP_OS == 'WINNT') 
			{
				switch ($flag) 
				{
					case 1: 
							$pswitch = '/d'; 
					break;
					
					case 2: 
							$pswitch = '/j'; 
					break;
					
					case 0:
					default: 
						$pswitch = ''; 
					break;
				}
				
				// Replace / to \
				$session  = str_replace('/', '\\', $session);
				$link     = str_replace('/', '\\', $link);
				$attached = exec('mklink ' . $pswitch . ' ' . escapeshellarg($link) . ' ' . escapeshellarg($session));
			}
			else 
			{
				$attached = symlink($session, $link);
			}
		}
		
		if ( ! $attached) throw new Kohana_Exception('Failed to attache broker client.', NULL, 406);
	}
	
	private function get_valid_user_data()
	{
		$data = array();
		
		$data['username'] = $this->get_valid_username();
		if ($data['username'] === FALSE) return FALSE;
		
		$data['password'] = $this->get_valid_password();
		if ($data['password'] === FALSE) return FALSE;
		
		//@@TODO: Check all other fields that you need to ask in user registration process
		
		return $data;
	}

	/** Validate Username */ 
	private function get_valid_username()
	{
		$username = Arr::get($_POST, 'username', FALSE);
		
		// @@TODO: Do any validation related to user name here (i.e. min lenght, alpha numeric only etc.)
		
		if ($username === FALSE)
		{
			$this->error_code = self::ERROR_INCORRECT_USERNAME;
			return FALSE;
		}
		
		return $username;
	}

	/** Validate Password */ 
	private function get_valid_password() 
	{
		$password = Arr::get($_POST, 'password', FALSE);
		
		// @@TODO: Do any validation related to password here (i.e. min lenght, alpha numeric only etc.)
		
		if ($password === FALSE)
		{
			$this->error_code = self::ERROR_INCORRECT_PASSWORD;
			return FALSE;
		}
		
		// @@TODO: Better to use some kind of encryption here as well
		return $password;
	}
}
/* End of file sso.php */
/* Location: /modules/sso/classes/controller/sso.php */