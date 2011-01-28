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
    
}
