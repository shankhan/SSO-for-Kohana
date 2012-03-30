<?php defined('SYSPATH') or die('No direct script access.');

/**
 * @requirement Kohana 3.2 or above
 * @module		Single Sign On (SSO) - Initialization file
 * @author		Zeeshan M. Khan
 * @link		http://zeeshanmkhan.com
 * @version		Version 0.01
 */

// Catch-all route for SSO Server ( If not running server comment out the following route value )
Route::set('ssoserver', 'sso/attach/<broker>/<token>/<checksum>/<returnurl>')
	->defaults(array(
		'controller' => 'sso',
		'action' => 'attach'));

Route::set('ssoserver_1', 'sso/<action>')
	->defaults(array(
		'controller' => 'sso'
	));

SSO::init();