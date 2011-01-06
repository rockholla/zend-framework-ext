<?php

class ZendExt_Controller extends Zend_Controller_Action 
{

	public static $userRecord;
	
	protected $_httpRequest;
	protected $_httpResponse;
	protected $_isFlexClient;
	
	public function defaultAction() 
	{
		
		error_reporting(E_ERROR);
		
		if(isset($_SERVER['HTTP_X_FLEX_CLIENT']) && $_SERVER['HTTP_X_FLEX_CLIENT'] == "true") 
		{
			$this->_isFlexClient = true;
		} 
		else 
		{
			$this->_isFlexClient = false;
		}
		
		require_once 'HttpRequest.php';
		$this->_httpRequest = new ZendExt_HttpRequest();
		
		if($this->getRequest()->getParam("protect")) 
		{
			$this->_httpProtect($this->getRequest()->getParam("protectExceptionMethods"));
		}
		
		// See if we need to clear the cache
		if(isset($_SERVER["HTTP_X_CLEARCACHE"]) || isset($_REQUEST["clearCache"])) 
		{
			Zend_Registry::getInstance()->cache->clean();
		}
		
		// call the appropriate method function
		$this->_runMethod($this->_httpRequest->getMethod());
		
	}
	
	public function getHttpRequest() 
	{
		return $this->_httpRequest;
	}
	
	public function getHttpResponse() 
	{
		
		if(!isset($this->_httpResponse)) 
		{
			$this->_setHttpResponse(200);
		}
		
		return $this->_httpResponse;
		
	}

	protected function _runMethod($methodName) 
	{
		
		if(!method_exists($this, "_" . strtolower($methodName))) 
		{
			$this->_setErrorMethodNotAllowed($methodName);
		} 
		else 
		{
			
			try 
			{
				
				switch($methodName) 
				{
					case "GET":
						$this->_get();
						break;
					case "POST":
						$this->_post();
						break;
					case "PUT":
						$this->_put();
						break;
					case "DELETE":
						$this->_delete();
						break;
					default:
						$this-_setErrorMethodNotAllow($methodName);
						break;
				}
				
			} 
			catch(Exception $exception) 
			{	
				throw new Exception($exception);	
			}
			
		}
		
	}

	protected function _setErrorMethodNotAllowed($methodName) 
	{
		
		$this->_setHttpErrorResponse
		(
			405, 
			"Method '" . $methodName . "' is not allowed"
		);
		
	}

	protected function _setHttpResponse($responseCode) 
	{
		
		$this->_httpResponse = new ZendExt_HttpResponse($responseCode, $this->_isFlexClient);
		$this->_httpResponse->setApplicationTitle
		(
			Zend_Registry::getInstance()->config->application->title
		);
		
	}
	
	protected function _setHttpStatusResponse($responseCode, $detailedMessage = "") 
	{
		
		$this->_setHttpResponse($responseCode);
		$this->_httpResponse->setContentType("text/html");
		$this->_httpResponse->setContentBody
		(
			'<div>' . $detailedMessage . '</div>'
		);
		$this->_httpResponse->render();
		exit;
		
	}
	
	protected function _setHttpErrorResponse($responseCode, $detailedErrorMessage = "") 
	{
		
		$this->_setHttpResponse($responseCode);
		$this->_httpResponse->setContentType("text/html");
		if(Zend_Registry::getInstance()->config->debug) 
		{
			$errors = $this->_getParam('error_handler');
			if($errors) 
			{
				$detailedErrorMessage .= Zend_Debug::dump($errors, null, false);	
			}
    	}
		$this->_httpResponse->setContentBody
		(
			'<div class="error">' . $detailedErrorMessage . '</div>'
		);

		if(Zend_Registry::getInstance()->config->email_errors_to != null) 
		{
			$to = Zend_Registry::getInstance()->config->email_errors_to;
			$mail = new Zend_Mail();
			$thisDetail = var_export($this->_httpResponse, true);
			$mail->setBodyText($detailedErrorMessage .= "Detail: " . "\n" . $thisDetail . "\n\n" . "User:" . "\n\n" . base64_decode(str_replace("Basic ", "", $_REQUEST["Authorization"])) . "\n\n" . "Server Variables:" . "\n\n" . var_export($_SERVER, true) . "\n\n" . "Request:" . "\n\n" . var_export($_REQUEST, true));
			$mail->setFrom('auto-mailer@' . $_SERVER["SERVER_NAME"], $_SERVER["HTTP_HOST"]);
			$mail->addTo($to, $to);
			$mail->setSubject('Error ' . date("Y-m-d H:i:s"));
			$mail->send();	
		}

		$this->_httpResponse->render();
		exit;
		
	}
	
    protected function _httpProtect($protectExceptionMethods = null) 
    {
    	
    	$cgiAuthUsername = null;
    	$cgiAuthPassword = null;
    	
		$methodList = explode(",", $protectExceptionMethods);
		for($i = 0; $i < count($methodList); $i++)
		{
			if($this->_httpRequest->getMethod() == $methodList[$i]) 
			{
    			return;
    		}
		}

		// Check to see if it's the Flex client and if it is the appropriate flex-specific header set
		if($this->_isFlexClient == true) 
		{
			
			if(isset($_SERVER['HTTP_X_AUTHORIZATION'])) 
			{
				
				if (preg_match('/Basic\s+(.*)$/i', $_SERVER['HTTP_X_AUTHORIZATION'], $auth)) 
				{
			        // Split the string, base64 decode it, and place the values into 
			        // the $authName and $authPassword variables
			        list($cgiAuthUsername, $cgiAuthPassword) = explode(':', base64_decode($auth[1]));
			    }
				
			} 
			else 
			{
				header('WWW-Authenticate: Basic realm="' . Zend_Registry::getInstance()->config->application->id . '"');
		    	header('HTTP/1.1 401 Unauthorized');
		    	echo '<h1>401 Unauthorized</h1>';
		    	exit;
			}
			
		}

    	if(isset($_GET['Authorization']) && $this->_isFlexClient != true) 
    	{
		    // Check for the HTTP authentication string in $_GET['Authorization'], 
		    // and put it in the $auth variable
		    if (preg_match('/Basic\s+(.*)$/i', $_GET['Authorization'], $auth)) 
		    {
		        // Split the string, base64 decode it, and place the values into 
		        // the $authName and $authPassword variables
		        list($cgiAuthUsername, $cgiAuthPassword) = explode(':', base64_decode($auth[1]));
		    }
		}
    	
    	
		if(!isset($_SERVER['PHP_AUTH_USER']) && !$cgiAuthUsername) 
		{
			
		    header('WWW-Authenticate: Basic realm="' . Zend_Registry::getInstance()->config->application->id . '"');
		    header('HTTP/1.1 401 Unauthorized');
		    echo '<h1>401 Unauthorized</h1>';
		    exit;
		    
		} 
		else 
		{
			
			if($cgiAuthUsername != null) 
			{
				$checkUsername = $cgiAuthUsername;
				$checkPassword = $cgiAuthPassword;
			} 
			else 
			{
				$checkUsername = $_SERVER['PHP_AUTH_USER'];
				$checkPassword = $_SERVER['PHP_AUTH_PW'];	
			}
			
		    $this->getHttpRequest()->setParam("httpUsername", $checkUsername);
		    $this->getHttpRequest()->setParam("httpPassword", $checkPassword);
		    
		    $userRecord = $this->_getValidUser($checkUsername, $checkPassword);
		    
		    if($userRecord == null) 
		    {
				$this->_setHttpErrorResponse(403, "Invalid login");
		    	exit;
			}
			
			// Authentication has passed, let's store necessary roles/rights in the request
			$this->getHttpRequest()->setParam("userRoles", $userRecord["Roles"]);
			$this->getHttpRequest()->setParam("permissionLevel", $userRecord["PermissionLevel"]);
			// Also a static reference to the entire user record in case it's needed in model
			self::$userRecord = $userRecord;
			
		}
		
    }
    
	protected function _protectResource($resource, $segment, $action) 
	{
		
		if(!$this->_userHasRight($resource, $segment, $action)) 
		{
			$this->_setHttpErrorResponse(403, "You do not have rights to view this resource.");
		}
		
	}    
    
    protected function _userHasRight($resource, $segment, $action) 
    {
    	
    	$roles = $this->getHttpRequest()->getParam("userRoles");
    	$applicationId = Zend_Registry::getInstance()->config->application->id;
    	$passed = false;
    	
    	if(!ZendExt_Model::isAssociativeArray($roles["Role"]) && $applicationId = "toco")
    	{
    		// means more than one role
    		$roles = $roles["Role"];
    	}
    	
    	foreach($roles as $role) 
    	{
    		
    		$rights = $role["Rights"];
    		if(!ZendExt_Model::isAssociativeArray($rights["Right"]) && $applicationId = "toco")
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
    
    protected function _getValidUser($userId, $password) 
    {
    	
		if($userId == null) 
		{
			header('WWW-Authenticate: Basic realm="' . Zend_Registry::getInstance()->config->application->id . '"');
		    header('HTTP/1.1 401 Unauthorized');
		    echo '<h1>401 Unauthorized</h1>';
		    exit;
		}
		
		require_once APPLICATION_PATH . '/controllers/AuthenticationController.php';
		return AuthenticationController::authenticate($userId, $password);
        
    }
    
}
