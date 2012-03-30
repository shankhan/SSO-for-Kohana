<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Broker Model base class.
 *
 * @package		Single Sign On (SSO) - Broker Model
 * @author		Zeeshan M. Khan
 * @link		http://zeeshanmkhan.com
 * @version		Version 0.01
 */
abstract class Kohana_Model_Brokers extends Model {
	
	public function get_details($key) 
	{
		try 
		{
			return DB::select()
							  ->from('brokers')
							  ->where('key', '=', $key)
							  ->where('is_active', '=', 1)
							  ->as_object()
							  ->execute()
					          ->current();
		}
		catch ( Kohana_Exception $e ) 
		{
			return FALSE;
		}
	}
	
} // End Model