<?php

/**
 * Copy this controller in your Zend Framework application's controller folder and implement 
 * the authenticate method below
 * 
 * @author rockholla
 *
 */

class AuthenticationController extends ZendExt_Controller 
{
	
	/***** Write your own HTTP auth script ******/
	/***** only rule is it should return null if invalid, if not return non-null *******/
	public static function authenticate($userId, $password) 
	{
		   
        return null;
		
	}
	
}
