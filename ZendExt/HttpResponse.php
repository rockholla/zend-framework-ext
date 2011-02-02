<?php

class ZendExt_HttpResponse extends Zend_Http_Response 
{
	
	protected $_applicationTitle;
	protected $_applicationBaseUrl;
	
	protected $_statusCode;
	protected $_realStatusCode;
	protected $_statusMessage;
	
	protected $_contentType;
	protected $_contentBody;
	
	protected $_isFlexClient;
	
	public function __construct($statusCode, $isFlexClient = false) 
	{
		
		$this->_isFlexClient = $isFlexClient;
		$this->_realStatusCode = $statusCode;
		
		if($this->_isFlexClient == true) 
		{
			$this->_statusCode = 200;
			$this->_statusMessage = Zend_Http_Response::responseCodeAsText($this->_realStatusCode);
			parent::__construct(200, $_SERVER);
			header("HTTP/1.1 200 OK");	
		} 
		else 
		{
			$this->_statusCode = $statusCode;
			$this->_statusMessage = Zend_Http_Response::responseCodeAsText($statusCode);
			parent::__construct($statusCode, $_SERVER);
			header("HTTP/1.1 $statusCode " . $this->_statusMessage);
		}
		
	}
	
	public function setHeader($name, $value) 
	{		
		header($name . ': ' . $value);	
	}
	
	public function getRequestHeaders() 
	{
		
		$headers = array();
		foreach($_SERVER as $name => $value) {
			if(strpos($name, 'HTTP_') === 0) {
				$headers[str_replace('HTTP_', '', $name)] = $value;
			}
		}
		return $headers;
		
	}
	
	public function getRequestHeader($name) 
	{	
		return $_SERVER[$name];	
	}
	
	public function setApplicationTitle($applicationTitle) 
	{	
		$this->_applicationTitle = $applicationTitle;	
	}
	
	public function getApplicationTitle() 
	{	
		return $this->_applicationTitle;	
	}
	
	public function setContentType($contentType) 
	{
		
		$this->_contentType = $contentType;
		$this->setHeader("Content-type", $contentType);
		
	}
	
	public function setContentBody($contentBodyString) 
	{	
		$this->_contentBody = $contentBodyString;	
	}
	
	public function getContentBody() 
	{	
		return $this->_contentBody;	
	}
	
	public function render($headersOnly = false) 
	{
		
		// We really should be doing it like the commented-out code below
		// but Flex's HTTPService limitation means we have to return everything in status code 200
		// Additionally, we're returning headers in the body since Flex can't access response headers either
		
		if($this->_isFlexClient) 
		{
			$this->setHeader("X_STATUSCODE", $this->_realStatusCode);
			$this->setHeader("X_STATUSMESSAGE", $this->_statusMessage);
		}
		if($headersOnly == true) return;
		
		if($this->_realStatusCode >= 400 || $this->_realStatusCode == 201) 
		{
			// Client error case, we will display this in body
			$tempContentBody = $this->_contentBody . "";
			$headersXhtml = "";
			if($this->_isFlexClient == true) 
			{
				$headersXhtml .= '<headers>';
				foreach(headers_list() as $value) 
				{
					$nameValue = explode(": ", $value);
					$headersXhtml .= '<header name="' . $nameValue[0] . '" value="' . $nameValue[1] . '" />';
				}
				$headersXhtml .= '</headers>';
			}
			$this->_contentBody = $headersXhtml . '<h1>' . $this->_realStatusCode . ' ' .
					       $this->_statusMessage . '</h1>' . $tempContentBody;
			include APPLICATION_PATH . '/templates/xhtml.php';
		} 
		else 
		{
			if(stristr($this->_contentType, "html")) 
			{
				$headersXhtml = "";
				if($this->_isFlexClient == true) 
				{
					$headersXhtml .= '<headers>';
					foreach(headers_list() as $value) 
					{
						$nameValue = explode(": ", $value);
						$headersXhtml .= '<header name="' . $nameValue[0] . '" value="' . $nameValue[1] . '" />';
					}
					$headersXhtml .= '</headers>';
				}
				$this->_contentBody = $headersXhtml . '<h1>' . $this->_applicationTitle . '</h1>' . $this->_contentBody;
				include APPLICATION_PATH . '/templates/xhtml.php';
			} 
			else if(stristr($this->_contentType, "xml")) 
			{
				if($this->_isFlexClient == true) 
				{
					
					$wrapperXml = '<FlexResult><Headers>';
					foreach(headers_list() as $value) 
					{
						$nameValue = explode(": ", $value);
						$wrapperXml .= '<Header name="' . $nameValue[0] . '" value="' . $nameValue[1] . '" />';
					}
					$wrapperXml .= '</Headers><ResultBody>' . $this->_contentBody . '</ResultBody>';
					$wrapperXml .= '</FlexResult>';
					echo '<?xml version="1.0" encoding="UTF-8"?>' . $wrapperXml;
					
				} 
				else 
				{
					echo '<?xml version="1.0" encoding="UTF-8"?>' . $this->_contentBody;	
				}
			}
			
		}
		
		/*if($this->_statusCode >= 400 || $this->_statusCode == 201) {
			// Client error case, we will display this in body
			$this->_contentBody = '<h1>' . $this->_statusCode . ' ' .
					       $this->_statusMessage . '</h1>' . $this->_contentBody;
			include APPLICATION_PATH . '/templates/xhtml.php';
		} else {

			if(stristr($this->_contentType, "html")) {
				$this->_contentBody = '<h1>' . $this->_applicationTitle . '</h1>' . $this->_contentBody;
				include APPLICATION_PATH . '/templates/xhtml.php';
			} else if(stristr($this->_contentType, "xml")) {
				echo '<?xml version="1.0" encoding="UTF-8"?>' . $this->_contentBody;
			}
			
		}*/
		
	}
	
	public function renderLinkableList($linkableList, $listTitle = null) 
	{
		$this->renderList($linkableList, $listTitle, true);	
	}
	
	public function renderList($list, $listTitle = null, $isLinkable = false) 
	{

		if(Zend_Registry::getInstance()->config->buffer_list_trigger_size) 
		{
			if(count($list) > Zend_Registry::getInstance()->config->buffer_list_trigger_size) 
			{
				$this->_renderListBuffered($list, $listTitle, $isLinkable);
				return;
			}	
		}

		if($listTitle) 
		{
			$this->_contentBody .= '<h3>' . $listTitle . '</h3>';	
		}
		
		
		if(Zend_Registry::getInstance()->config->max_per_page && ZendExt_DatabaseTable::$totalListResults != null && ZendExt_DatabaseTable::$totalListResults > 0)
		{
			$page = 1;
			if(isset($_REQUEST["page"]) && $_REQUEST["page"] > 1) $page = $_REQUEST["page"];
			$rows_per_page = Zend_Registry::getInstance()->config->max_per_page;
			$offset = ($page - 1) * $rows_per_page;
			$total = $offset + $rows_per_page;
			if($total > ZendExt_DatabaseTable::$totalListResults) $total = ZendExt_DatabaseTable::$totalListResults;
			$displayingText = '<b id="list-showing">' . ($offset + 1) . ' - ' . $total . '</b> of <b id="list-total">' . ZendExt_DatabaseTable::$totalListResults . '</b>';
			if(($offset + 1) > ZendExt_DatabaseTable::$totalListResults)
			{
				$displayingText = '<b id="list-showing">an invalid page</b> of <b id="list-total">' . ZendExt_DatabaseTable::$totalListResults . '<b/>';
			}
		}
		else
		{
			$displayingText = '<b id="list-showing">' . count($list) . '</b> of <b id="list-total">' . count($list) . '</b>';
		}
		
		$this->_contentBody .= '<p>Displaying ' . $displayingText . ' records</p>';

		// Go through each list item
		$this->_contentBody .= "\n" . '<ul>' . "\n";
		foreach($list as $listItem) 
		{
			$this->_contentBody .= '<li';
			
			if($isLinkable) 
			{
				$this->_contentBody .= '><a';
			}
			
			// get the attributes for the anchor tag
			foreach($listItem as $key => $value) 
			{
				if($key != 'htmlText') 
				{
					if($key == 'href') 
					{
						$this->_contentBody .= ' ' . $key . '="' . Zend_Controller_Front::getInstance()->getBaseUrl() . $value . '"';
					} 
					else 
					{
						$this->_contentBody .= ' ' . $key . '="' . $value . '"';
					}
				}
			}
			
			if($isLinkable) 
			{
				$this->_contentBody .= '>' . $listItem['htmlText'] . '</a></li>' . "\n";
			} 
			else 
			{
				$this->_contentBody .= '>' . $listItem['htmlText'] . '</li>' . "\n";
			}
			
		}
		$this->_contentBody .= '</ul>' . "\n";

		$this->setContentType("text/html");
		$this->render();
		
	}
	
	protected function _renderListBuffered($list, $listTitle = null, $isLinkable = false) 
	{
		
		$this->setContentType("text/html");
		ob_start();
		ob_clean();
		
		include APPLICATION_PATH . '/templates/xhtml_header.php';
		
		echo '<h1>' . $this->_applicationTitle . '</h1>';
		
		if($listTitle) 
		{
			echo '<h3>' . $listTitle . '</h3>';	
		}
		
		echo '<p>Displaying <b>' . count($list) . ' records</b></p>';

		// Go through each list item
		echo "\n" . '<ul>' . "\n";
		foreach($list as $listItem) 
		{
			echo '<li';
			
			if($isLinkable) 
			{
				echo '><a';
			}
			
			// get the attributes for the anchor tag
			foreach($listItem as $key => $value) 
			{
				if($key != 'htmlText') 
				{
					if($key == 'href') 
					{
						echo ' ' . $key . '="' . Zend_Controller_Front::getInstance()->getBaseUrl() . $value . '"';
					} 
					else 
					{
						echo ' ' . $key . '="' . $value . '"';
					}
				}
			}
			
			if($isLinkable) 
			{
				echo '>' . $listItem['htmlText'] . '</a></li>' . "\n";
			} 
			else 
			{
				echo '>' . $listItem['htmlText'] . '</li>' . "\n";
			}
			
			ob_flush();
		}
		echo '</ul>' . "\n";
		
		include APPLICATION_PATH . '/templates/xhtml_header.php';
		ob_flush();
		ob_end_clean();
		
	}
	
}