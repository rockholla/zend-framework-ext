<?php

class ZendExt_DatabaseTable extends Zend_Db_Table_Abstract 
{

	protected $_db;
    
	public function init() 
	{	
		$this->_db = Zend_Registry::getInstance()->dbAdapter;		
	}
	
	protected function _executeSql($sql) 
	{
		
		if(Zend_Registry::getInstance()->config->max_list_rows) 
		{
			if(!stristr(strtolower($sql), " limit ")) 
			{
				$sql .= " LIMIT " . Zend_Registry::getInstance()->config->max_list_rows;
			}	
		}
		
		$query = $this->_db->query($sql);
		if($this->_isFetchQuery($sql)) 
		{
			$rows = $query->fetchAll();
    		return $rows;
		}
		
		return null;
		
	}
	
	protected function _isFetchQuery($sql) 
	{
		
		if(stristr($sql, "DELETE FROM ") || stristr($sql, "UPDATE ") || stristr($sql, "INSERT ")) 
		{
			return false;
		}
		
		return true;
		
	}
	
	protected function _getRecordsetForXml($sql) 
	{
		
		$recordset = array();
		$query = $this->_db->query($sql);
		$rows = $query->fetchAll();
		
		$i = 0;
		foreach($rows as $row) 
		{
			$recordset[$i] = $row;	
			$i++;
		}
		
		return $recordset;
		
	}

}
