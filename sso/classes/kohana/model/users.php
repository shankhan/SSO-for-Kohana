<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Users Model base class.
 *
 * @package		Single Sign On (SSO) - Users Model
 * @author		Zeeshan M. Khan
 * @link		http://zeeshanmkhan.com
 * @version		Version 0.01
 */
abstract class Kohana_Model_Users extends Model {
	
	public function get_information($username) 
	{
		try 
		{
			return DB::select('*')->from("users")->where('username', '=', $username)->as_object()->execute()->current();
		}
		catch (Kohana_Exception $e) 
		{
			return FALSE;
		}
	}
	
	public function register($data) 
	{
		$user_fields = $this->get_user_fields();
		$data = array_intersect_key($data, $user_fields);
		
		try 
		{
			list($insert_id, $total_rows) = DB::insert('users', array_keys($data))->values($data)->execute();
				
			return ($total_rows == 1);
		}
		catch (Database_Exception $e)
		{
			return FALSE;
		}
	}
	
	private function get_user_fields()
	{
		//@@TODO: Add add fields name here from users table
		return array('username'=>'', 'password'=>'');
	}
	
} // End Model
