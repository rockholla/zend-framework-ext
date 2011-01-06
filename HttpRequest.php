<?php

class ZendExt_HttpRequest extends Zend_Controller_Request_Http 
{

	protected $_method;
	protected $_debugLevel;
	protected $_urlParams;
	
	public function __construct() 
	{

		$this->_setDebugLevel();
		$this->_setMethod();
		$this->_urlParams = $_REQUEST;
		
	}
	
	public function getMethod() 
	{
		return $this->_method;
	}
	
	public function getDebugLevel() 
	{
		return $this->_debugLevel;
	}
	
	public function getRequestBody() 
	{
		return @file_get_contents('php://input');
	}
	
	public function getXmlRequestBody($schemaFileName = null) 
	{
		
		try 
		{
			
			$domXml = null;

    		if(!$schemaFileName == null) 
    		{
				$domXml = $this->validateXml($this->getRequestBody(), $schemaFileName, "Error validating posted XML: ");
			} 
			else 
			{
				$domXml = new DOMDocument;
				$domXml->loadXml($this->getRequestBody());
			}
			
			return simplexml_import_dom($domXml);
    		
		} 
		catch(Exception $exception) 
		{	
			throw new XmlRequestBodyException("Error parsing posted XML: " . $exception->getMessage());	
		}
		
	}
	
	public function getUrlParams() 
	{	
		return $this->_urlParams;	
	}
	
	public function validateXml($xmlString, $schemaFileName, $errorMessageIntro = "Error validating XML: ") 
	{

		$domXml = new DOMDocument;
    	$domXml->loadXml($xmlString);

		$schemaFilePath = realpath("../application/schema") . '/' . $schemaFileName;
        
        if(!$domXml->schemaValidate(realpath($schemaFilePath))) 
        {
        	throw new XmlRequestBodyException($errorMessageIntro . libxml_get_last_error()->message);
        }
        
        return $domXml;
		
	}
 	
	protected function _setMethod() 
	{
		
		if(isset($_SERVER['HTTP_X_METHOD'])) 
		{
			$this->_method = strtoupper($_SERVER['HTTP_X_METHOD']);
		} 
		else if(isset($_REQUEST['httpMethod'])) 
		{
			$this->_method = strtoupper($_REQUEST['httpMethod']);
		} 
		else if(isset($_SERVER['REQUEST_METHOD'])) 
		{
			$this->_method = $_SERVER['REQUEST_METHOD'];
		} 
		else 
		{
			// We have to just assume that it's a GET
			$this->_method = "GET";
		}
		
	}
	
	protected function _setDebugLevel() 
	{
		
		if(isset($_SERVER['HTTP_X_DEBUG_LEVEL'])) 
		{
			$this->_debugLevel = $_SERVER['HTTP_X_DEBUG_LEVEL'];
		} 
		else if(isset($_REQUEST['xDebugLevel'])) 
		{
			$this->_debugLevel = $_REQUEST['xDebugLevel'];
		} 
		else 
		{
			$this->_debugLevel = 0;
		}
		
	}
	
}