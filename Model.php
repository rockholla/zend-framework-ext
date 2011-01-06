<?php

class ZendExt_Model 
{
	
	protected $_cachePrefix = "";
	protected $_subsetName;
	
	public function getCachedData($id) 
	{
		
		$cache = Zend_Registry::getInstance()->cache;
		if(!$cached = $cache->load($id)) 
		{
			return null;
		} 
		else 
		{
			return $cached;
		}
		
	}
	
	public function cacheData($data, $id) 
	{
		
		$cache = Zend_Registry::getInstance()->cache;
		$cache->save($data, $id);
		
	}
	
	public function clearCacheEntry($id) 
	{
		
		$cache = Zend_Registry::getInstance()->cache;
		$cache->remove(md5($this->_cachePrefix . "_" . $id));
		
	}
	
	public function load($id, $xmlOnly = false, $bypassCache = false) 
	{
		
		$cacheId = md5($this->_cachePrefix . "_" . $id);
		
		if(Zend_Registry::getInstance()->config->cache_enabled == false || $bypassCache == true || isset($_GET["bypassCache"])) 
		{
			$record = $this->getTable()->selectRecord($id);
		} 
		else if(!$cached = $this->getCachedData($cacheId)) 
		{
			if($this->_subsetName != null) 
			{
				$record = $this->getTable()->selectRecord($id, $this->_subsetName);	
			} 
			else 
			{	
				$record = $this->getTable()->selectRecord($id);
			}
			$this->cacheData($record, $cacheId);
		} 
		else 
		{
			$record = $cached;
		}
		
		if($xmlOnly) 
		{
			return $record["xml"];
		} 
		else 
		{
			return $record;
		}
		
	}
	
    public static function arrayToXml($array, $rootElementName) 
    {
    	$xml = '<' . $rootElementName . '>';
    	foreach ($array as $key => $value) 
    	{
    		if(is_array($value))
    		{
    			$newRootElementName = $key;
    			if(is_numeric($key))
    			{
    				// We want to name the element after it's parent, without the pluralization
    				$newRootElementName = ZendExt_StringUtils::getAsSingularEntityId($rootElementName);
    			}
    			$xml .= ZendExt_Model::arrayToXml($value, $newRootElementName);
    		} 
    		else 
    		{
    			//$xml .= '<' . $key . '>' . self::fixForXml($value) . '</' . $key . '>';
				$xml .= '<' . $key . '>' . $value . '</' . $key . '>';
    		}
    	}
    	$xml .= '</' . $rootElementName . '>';
    	
    	return $xml;
    }
	
	public static function xmlToArray($arrObjData, $arrSkipIndices = array())
	{
		
	    $arrData = array();
	   
	    // if input is object, convert into array
	    if (is_object($arrObjData)) 
	    {
	        $arrObjData = get_object_vars($arrObjData);
	    }
	   
	    if(is_array($arrObjData)) 
	    {
	        foreach($arrObjData as $index => $value) 
	        {
	            if(is_object($value) || is_array($value)) 
	            {
	                $value = self::xmlToArray($value, $arrSkipIndices); // recursive call
	            }
	            if(in_array($index, $arrSkipIndices)) 
	            {
	                continue;
	            }
	            $arrData[$index] = $value;
	        }
	    }
	    return $arrData;
	    
	}
    
	public static function fixForXml($string) 
	{

		// this one is for a floating '&'
		$string = preg_replace('/&(?![a-z]+[;]+)/', "&amp;", $string);
	
		// if it is html/xml itself, we need to just make it CDATA
		if(stristr($string, "<") || stristr($string, ">")) 
		{
			$string = "<![CDATA[" . $string . "]]>";
		}
	
		return $string;
		
	}
	
	function isAssociativeArray($var)
    {
        return (array_merge($var) !== $var || !is_numeric( implode( array_keys( $var ) ) ) );
    }
	
    
}
