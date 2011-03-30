<?php 

class ZendExt_ApiUtils
{
	
	public static function getRolesFromXml($xml)
	{
		return self::xmlToArray($xml->Roles);	
	}
	
	public static function userHasRole($roles, $roleName)
	{
		
		if(!self::isAssociativeArray($roles["Role"]))
    	{
    		// means more than one role
    		$roles = $roles["Role"];
    	}
    	
    	foreach($roles as $role) 
    	{
    		if($role["RoleId"] == $roleName)
    		{
    			return true;
    		} 
    	}
		return false;
    	
	}
	
	public static function userHasRight($roles, $applicationId, $resource, $segment, $action)
    {
    	
    	$passed = false;
    	
    	if(!self::isAssociativeArray($roles["Role"]))
    	{
    		// means more than one role
    		$roles = $roles["Role"];
    	}
    	
    	foreach($roles as $role) 
    	{
    		
    		$rights = $role["Rights"];
    		if(!self::isAssociativeArray($rights["Right"]))
    		{
    			// means more than one right
    			$rights = $rights["Right"];
    		}
    		
    		foreach($rights as $right) 
    		{
    			if($right["Application"] == $applicationId || $right["Application"] == "*")
    			{
    				if($right["Resource"] == $resource || $right["Resource"] == '*') 
	    			{
	    				if($right["Segment"] == $segment || $right["Segment"] == '*') 
	    				{
	    					if($right["Action"] == $action || $right["Action"] == '*') 
	    					{
	    						if($right["Access"] == 'grant') 
	    						{
	    							$passed = true;
	    						}
	    						elseif($right["Access"] == 'deny')
	    						{
	    							// explicit deny
	    							return false;
	    						}
	    					}
	    				}
	    			}	
    			}
    		}
    	}
    	
    	return $passed;
    	
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
    			$xml .= self::arrayToXml($value, $newRootElementName);
    		} 
    		else 
    		{
    			$value = trim($value);
    			if(!(substr($value, 0, 1) == "<"))
    			{
    				$value = htmlspecialchars($value);
    			}
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
	
	public static function makeXmlText($value, $makeXmlCdata = true)
	{
		
		// check if the $value itself is XML.  If so and our parameter $makeXmlCdata is true, then CDATA the text
		@$value_as_xml = simplexml_load_string($value);
		if(!($value_as_xml === false) && $makeXmlCdata == true) 
		{
			$value = "<![CDATA[" . $value . "]]>";
		}
		elseif($value_as_xml === false)
		{
			// We don't have XML, so let's espace html special characters
			$value = htmlspecialchars($value);
		}
	
		return $value;
		
	}
	
	public static function isAssociativeArray($var)
    {
        return (array_merge($var) !== $var || !is_numeric( implode( array_keys( $var ) ) ) );
    }
    
	function isValidMd5($md5)
	{
    	return !empty($md5) && preg_match('/^[a-f0-9]{32}$/', $md5);
	}
	
}