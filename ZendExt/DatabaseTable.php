<?php

class ZendExt_DatabaseTable extends Zend_Db_Table_Abstract 
{

	public static $totalListResults;
	
	public function setUtf8()
	{
		$this->_db->query("SET NAMES 'utf8'");
		$this->_db->query("SET CHARACTER SET 'utf8'");
	}
	
	protected function _executeSqlPaged($sql)
	{		
		
		$showAll = isset($_GET["showAll"]) && $_GET["showAll"] == "true";
		if(Zend_Registry::getInstance()->config->max_per_page && !$showAll) 
		{
			$sql_count = preg_replace("/SELECT(.*?)FROM/", "SELECT COUNT(*) as record_count FROM", $sql);
			$count_result = $this->_executeSql($sql_count);
			foreach($count_result as $count)
			{
				self::$totalListResults = $count["record_count"];	
			}
			
			$page = 1;
			if(isset($_REQUEST["page"]) && $_REQUEST["page"] > 0) $page = $_REQUEST["page"];
			$rows_per_page = Zend_Registry::getInstance()->config->max_per_page;
			$offset = ($page - 1) * $rows_per_page;
			$sql .= " LIMIT $rows_per_page OFFSET $offset";	
		}
		
		return $this->_executeSql($sql);
		
	}
	
	protected function _executeSql($sql) 
	{
		
		if($this->_isFetchQuery($sql)) 
		{
			$query = $this->_db->query($sql);
			$rows = $query->fetchAll();
    		return $rows;
		}
		else
		{
			//$this->setUtf8();
			$this->_db->query($sql);
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
