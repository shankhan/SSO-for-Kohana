<?php defined('SYSPATH') or die('No direct script access.');

class Controller_User extends Controller {

	/** Login Sample action to login user through SSO */
	public function action_login()
	{
	    $view = View::factory('user/login');
		
		if ($_POST)
		{
		   $login = SSO::login(Arr::get($_POST, 'username'), Arr::get($_POST, 'password'));
		   		   
		   if ($login->response_code == SSO::SUCCESS)
		   {
			  // @@TODO: $login->data is the user object... You can have a local copy of user data for this session...
			  
			  Request::current()->redirect('home');
		   }
		   else
		   {
			   switch($login->response_code)
			   {
				   case SSO::ERROR_INCORRECT_USERNAME:
				   case SSO::ERROR_INCORRECT_PASSWORD:
					   $view->errors = 'Invalid userid/password';
					   break;
				   default:
					   $view->errors = 'An unknown error occured';
			   }
		   }
		}
		else 
		{
		   if (SSO::is_logged_in() != '') 
		   {
			  Request::current()->redirect('home');
		   }
		}

		$this->response->body($view);
	}
	
	/** Login Sample action to register user @ SSO server*/
	public function action_register()
	{
	    $view = View::factory('user/register');
		
		if ($_POST)
		{
		   $register = SSO::register($_POST);
		   		   
		   if ($register->response_code == SSO::SUCCESS)
		   {
			  // @@TODO: $register->data is the user object... You can have a local copy of user data for this session...
			  
			  Request::current()->redirect('home');
		   }
		   else
		   {
			   switch($login->response_code)
			   {
				   case SSO::ERROR_INCORRECT_USERNAME:
				   case SSO::ERROR_INCORRECT_PASSWORD:
					   $view->errors = 'Invalid userid/password';
				   break;
				   
				   case SSO::USER_ALREADY_REGISTERED:
					  $view->errors = 'User already registered';
				   break;
				   default:
					   $view->errors = 'An unknown error occured';
			   }
		   }
		}
		else 
		{
		   if (SSO::is_logged_in() != '') 
		   {
			  Request::current()->redirect('home');
		   }
		}

		$this->response->body($view);
	}
	
	public function action_logout()
	{
	   SSO::logout();
	   Request::current()->redirect('user/login');
	}
}
// End User Controller