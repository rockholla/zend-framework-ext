<?php

class ZendExt_StringUtils 
{
	
	public static function removeSpaces($string) 
	{	
		return str_replace(" ", "", $string);	
	}
	
	public static function getAsLowercaseId($string) 
	{	
		return self::removeSpaces(strtolower($string));	
	}
	
	public static function getAsSingular($plural) 
	{
		
		$singular = $plural;
		// check to see if input value is plural
		if(strtolower(substr($plural, (strlen($plural) - 1), 1)) == "s") 
		{
			if(strtolower(substr($plural, (strlen($plural) - 3), 3)) == "ies") 
			{
				$singular  = self::removeLastCharacter($plural);
				$singular  = self::removeLastCharacter($singular);
				$singular  = self::removeLastCharacter($singular);
				$singular .= "y";
			} 
			else 
			{
				$singular = self::removeLastCharacter($plural);	
			}
		}
		return $singular;
		
	}
	
	public static function getAsSingularEntityId($pluralName) 
	{
		
		$singularName = self::getAsSingular($pluralName);
		return self::removeSpaces($singularName);
		
	}
	
	public static function removeLastCharacter($string) 
	{	
		return substr($string, 0, strlen($string) - 1);	
	}
    
}
