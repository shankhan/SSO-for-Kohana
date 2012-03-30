<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Home extends Controller {

	public function action_index()
	{
     $username = SSO::is_logged_in();
	   if ($username != '')
	   {
        $this->response->body('hello, ' . $username . '! <a href="/user/logout" >Logout</a>'); 
	   }
	   else 
	   {
		  $this->response->body('hello, world!');
	   }
	}

} // End Welcome
