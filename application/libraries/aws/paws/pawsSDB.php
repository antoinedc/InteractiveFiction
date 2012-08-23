<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon Web Services Library
 *  @package     paws
 *  @copyright   Copyright (c) 2008 Bruce E. Wampler
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2008-06-05
 */
/******************************************************************************* 
 *   _  _  _  _
 *  (p)(a)(w)(s)
 *   '  '  '  '
 *   PHP Amazon Web Services Library
 *   A PHP oriented Library for AWS Simple DB, S3, and EC2.
 *
 */
include_once ('pawsConfig.php');

/**
 * pawsSDB - Easy PHP access to AWS SimpleDB
 */
class pawsSDB
{
    private	$domain;		// current domain
    private	$errorCode;		// paws error message - parallels AWS errors
    private	$errorMessage;		// Long message that accompanies Code
    private	$itemStack;		// stack of items found by query
    private	$lastCalled;		// last function called to help interpretation of other vars
    private	$lastResponse;		// the last response from a SimpleDB lib - use to retrieve additional information
    private	$lastException;		// last excpetion handled
    private	$nextLToken;		// nextToken returned from AWS on list domain
    private	$nextQToken;		// nextToken returned from AWS on queries
    private	$queryExp;		// remember expression
    private	$sdbService;		// the service connection to SDB
    
    /**
     * Construct new SDB class
     *   
     */
    public function __construct($data = null)
    {
	$this->sdbService = new Amazon_SimpleDB_Client(AWS_ACCESS_KEY_ID, 
                                       AWS_SECRET_ACCESS_KEY);
	$this->lastCalled = 'SDB';
	$this->lastResponse = null;
	$this->domain = null;
	$this->errorCode = "none";
	$this->errorMessage = "none";
    }
    
    /**
     * Returns error code from previous paws SDB operation.
     * Error codes are those defined in AWS SimpleDB documentation, plus
     * a few others relevant to paws handling of SDB.
     *
     * @return string error_code 
     */
    public function getErrorCode() {
	return $this->errorCode;
    }
    
    private function setErrorCode($ec) {
	$this->errorCode = $ec;
	$this->errorMessage = 'Error in ' . $lastCalled;
    }
    
    public function getErrorMessage() {
	return $this->errorMessage;
    }
    private function setErrorMessage($em) {
	$this->errorMessage = $em;
    }
    
    public function getDomain() {
	return $this->domain;
    }
    
    public function setDomain($dom) {
	$this->domain = $dom;
    }
    
    /**
     * get the Box Usage value returned by AWS
     *
     * @return string Box usage
     */
    public function getBoxUsage() {
	// return box usage for last operation
	if ($this->lastResponse == null)
	    return "Not Available";
	$responseMetadata = $this->lastResponse->getResponseMetadata();
	if ($responseMetadata->isSetBoxUsage())
	    return $responseMetadata->getBoxUsage();
	else
	    return "Not Available";
    }
    
    /**
     * get the response ID value returned by AWS
     *
     * @return string ResponseID
     */
    public function getResponseID() {
	// return box usage for last operation
	if ($this->lastResponse == null)
	    return "Not Available";
	$responseMetadata = $this->lastResponse->getResponseMetadata();
        if ($responseMetadata->isSetRequestId()) 
	    return $responseMetadata->getRequestId();
	else
	    return "Not Available";
    }
    
    /**
     * build an attribute array for Amazon SimpleDB Libraray calls
     */
    private function buildAttributesArray($attributes, $replace=false) {
	$attrList = array();				// build list of name/value pairs to put
	foreach ($attributes as $name => $value) {
	    if (is_array($value)) {			// multiple values supplied
		foreach( $value as $val) {
		    if ($replace)
			array_push($attrList, array("Name" => $name, "Value" => $val, "Replace" => true));
		    else
			array_push($attrList, array("Name" => $name, "Value" => $val));  
		}
	    } elseif ($value != null) {	// value supplied
		if ($replace)
		    array_push($attrList, array("Name" => $name, "Value" => $value, "Replace" => true));
		else
		    array_push($attrList, array("Name" => $name, "Value" => $value));
	    }
	    else
		array_push($attrList, array("Name" => $name));	// just a name supplied
	}
	return $attrList;
    }
    
     /**
     * Create a new domain
     *
     * @param string $domain Name of domain to create
     * @return boolean True if OK, false if error (use getErrorCode to retrieve)
     */
    public function createDomain($domain) {
	$this->lastCalled = "createDomain";
	$this->setErrorCode('OK');

	if (strlen($domain) < 1)		// simple error check
	  {
	    $this->setErrorCode('DomainNameNotSet');
	    return false;
	  }
	$request = array ("DomainName" => $domain);
	try {
	    $this->lastResponse = $this->sdbService->createDomain($request);
	} catch (Amazon_SimpleDB_Exception $ex) {
	    $this->lastException = $ex;
	    $this->errorCode = $ex->getErrorCode();
	    $this->setErrorMessage($ex->getMessage());
	    return false;
	}
	return true;
    }
    
    /**
     * Delete attributes.
     * 
     * If $attributes is null, then entire $itemName row is deleted.
     * $attributes is an associative array, and if value part is not null, then
     * the attribute name with provided values will be deleted. If the value part
     * is null, then the named attribute will be deleted.
     *
     * @param string $itemName The itemName (row)
     * @param array $attributes Associative array of attributes
     * @return boolean True if OK, false on error (use getErroCode)
     */    
    public function deleteAttributes($itemName, $attributes = null) {
	$this->lastCalled = "deleteAttributes";
	if (strlen($this->domain) < 1) {
	    $this->setErrorCode('DomainNameNotSet');
	    return false; 
	}
	if (strlen($itemName) < 1) {	// simple error check
	    $this->setErrorCode('InvalidParameterValue');
	    return false;
	}
 
	if ($attributes != null) {	// build list if supplied	
	    $attrList = $this->buildAttributesArray($attributes);	// build array for request
	    $request = 
		array ("DomainName" => $this->getDomain(),
		    "ItemName" => $itemName,
		    "Attribute" => $attrList);
	} else {			// no list - delete whole row
	    $request = 
		array ("DomainName" => $this->getDomain(),
		    "ItemName" => $itemName);
	}
        try {
            $this->lastResponse = $this->sdbService->deleteAttributes($request);
        } catch (Amazon_SimpleDB_Exception $ex) {
	    $this->lastException = $ex;
	    $this->errorCode = $ex->getErrorCode();
	    $this->setErrorMessage($ex->getMessage());
	    return false;
	}
	return true;
    }
    
    /**
     * Delete a domain.
     * 
     * Although the error handling code is here, in fact AWS does not return anything
     * if you try do delete a non-existent domain. So be sure to get it right!
     *
     * @param string $domain Domain name to delete
     * @return boolean True if OK, false otherwise.
     */    
    public function deleteDomain($domain) {
	$this->lastCalled = "deleteDomain";
	$this->setErrorCode('OK');

	if (strlen($domain) < 1)		// simple error check
	  {
	    $this->setErrorCode('DomainNameNotSet');
	    return false;
	  }
	$request = array ("DomainName" => $domain);
  	try {
	    $this->lastResponse = $this->sdbService->deleteDomain($request);
	} catch (Amazon_SimpleDB_Exception $ex) {
	    $this->lastException = $ex;
	    $this->errorCode = $ex->getErrorCode();
	    $this->setErrorMessage($ex->getMessage());
	    return false;
	}
	return true;
    }

    /**
     * Get all attributes of an item, return as associative array or null.
     * 
     * The associative array will have form array("name" => "value") for single values,
     * or array("name" => array("value0", "value1", "etc")) for multiple valued attribute.
     * Use isarray() to determine if multiple values returned for one name.
     *
     * If the $itemName row does not exist, the operation does not fail but returns
     * an empty list (Error Code = "NoAttributes"). This is a result of SDB "eventual consistency".
     *
     *	The order the attributes are returned seems to have no relationship to anything:
     *	not the order they were created in, not sorted, just some random order, although
     *	it seems to be the same order each time.
     *
     * @param string $itemName The itemName of the row of attributes to fetch
     * @return array Return associative array of name/value pairs, or null on failure
     */
     public function getAllAttributes($itemName) {
	$result = $this->getAttributes($itemName);
	$this->lastCalled = "getAllAttributes";
	return $result;
     }
     
    /**
     * GetAttributes - returns array of attribute name/value of attribute names passed
     * in in simple list of attribute names - $attributeNames
     * If $attributeNames is NULL, then it returns all attributes
     */
    public function getAttributes($itemName, $attributeNames = NULL) {
	$this->lastCalled = "getAttributes";
	$this->setErrorCode('OK');
	if (strlen($this->domain) < 1) {
	    $this->setErrorCode('DomainNameNotSet');
	    return null; 
	}
	if (strlen($itemName) < 1) {	// simple error check
	    $this->setErrorCode('InvalidParameterValue');
	    return null;
	}

	$request = array ("DomainName" => $this->getDomain(),
			  "ItemName" => $itemName);
	
	if ($attributeNames != NULL) { 	// get all attributes
	    $request['AttributeName'] = $attributeNames;	// just one name
	}	
	
        try {
            $this->lastResponse = $this->sdbService->getAttributes($request);
        } catch (Amazon_SimpleDB_Exception $ex) {
	    $this->lastException = $ex;
	    $this->errorCode = $ex->getErrorCode();
	    $this->setErrorMessage($ex->getMessage());
	    return null;
	}
	if ($this->lastResponse->isSetGetAttributesResult()) { 
            $getAttributesResult = $this->lastResponse->getGetAttributesResult();
            $attributeList = $getAttributesResult->getAttribute();
	    // now loop through the results returned by transaction with SDB
	    $attributes = array();
            foreach ($attributeList as $attribute) {
		if ($attribute->isSetName()) {		// get the names
		    $name = $attribute->getName();
		    if ($attribute->isSetValue()) {
			$value = $attribute->getValue();
		    } else {
			$value = null;
		    }
		} else {
		    $this->setErrorCode('NoAttributes');
		    return null;	    
		}
		// Have to handle multiple values - convert to our value array
		if ($attributes[$name] != null) {		// name already has at least one value
		    if (is_array($attributes[$name])) {		// already multiple values
			array_push($attributes[$name], $value);	// add to array
		    } else {					// has only one previous value
			$firstval = $attributes[$name];		// preserve first value
			$attributes[$name] = array($firstval, $value); // and build value array
		    }
		} else {					// first time value for name
		    $attributes[$name] = $value;
		}
	    }
	} else {
	    $this->setErrorCode('NoAttributes');
	    return null;
	}
	if ($attributes == null) {
	    $this->setErrorCode('NoAttributes');	// row probably doesn't exist
	    return null;   
	}
	return $attributes;
    }        
     /**
     * Get value(s) of a specific attribute of a specific item
     *
     * Will return either the single value or an array of values
     * 
     * If the $itemName or attribute does not exist, the operation does not fail but returns
     * an empty list (Error Code = "NoAttributes"). This is a result of SDB "eventual consistency".
     *
     * @param string $itemName The itemName of the row of attributes to fetch
     * @param string $attributeName The name of the attribute to get values for
     * @return array Return associative array of name/value pairs, or null on failure
     */
    public function getAttributeValues($itemName, $attributeName) {
	$this->lastCalled = "getAttributeValues";
	$this->setErrorCode('OK');
	if (strlen($this->domain) < 1) {
	    $this->setErrorCode('DomainNameNotSet');
	    return null; 
	}
	if (strlen($itemName) < 1) {	// simple error check
	    $this->setErrorCode('InvalidParameterValue');
	    return null;
	}

	$request = array ('DomainName' => $this->domain,
			  'ItemName' => $itemName,
			  'AttributeName' => $attributeName);
        try {
            $this->lastResponse = $this->sdbService->getAttributes($request);
        } catch (Amazon_SimpleDB_Exception $ex) {
	    $this->lastException = $ex;
	    $this->errorCode = $ex->getErrorCode();
	    $this->setErrorMessage($ex->getMessage());
	    return null;
	}
	if ($this->lastResponse->isSetGetAttributesResult()) { 
            $getAttributesResult = $this->lastResponse->getGetAttributesResult();
            $attributeList = $getAttributesResult->getAttribute();
	    $retVal = null;
            foreach ($attributeList as $attribute) {	// get one for each value
		if ($attribute->isSetName()) {
		    $name = $attribute->getName();
		    if ($attribute->isSetValue()) { 
			$value = $attribute->getValue();
		    } else {
			$value = null;
		    }
		} else {
		    $this->setErrorCode('NoAttributes');
		    return null;	    
		}
		// Have to handle multiple values 
		if ($retVal != null) {		// name already has at least one value
		    if (is_array($retVal)) {		// already multiple values
			array_push($retVal, $value);
		    } else {					// has only one previous value
			$firstval = $retVal;
			$retVal = array($firstval, $value);
		    }
		} else {					// first time value for name
		    $retVal = $value;
		}
	    }
	} else {
	    $this->setErrorCode('NoAttributes');
	    return null;
	}
	if ($retVal == null) {
	    $this->setErrorCode('NoAttributes');	// row probably doesn't exist
	    return null;   
	}
	return $retVal;
    }        
 
    /**
     * Returns list of domains or null if error.
     *
     * @param int $max Maximum number of domains to fetch (100 default)
     * @return array $domainNames A list of all domains
     *
     * Note: since current maximum is 100 domains, this will work as is for now.
     * If the maximum is significantly raised, then this may have to be modified
     * to work on a 'getFirstDomain' and 'getNextDomain' basis.
     */
    public function listDomains($max = 100) {
	$this->lastCalled = 'listDomains';
	$this->setErrorCode('OK');
	$request = array ("MaxNumberOfDomains" => $max);
 
	$this->lastException = null;
	$domainNameList = null;
 	try { 
            $this->lastResponse = $this->sdbService->listDomains($request);
	    if ($this->lastResponse->isSetListDomainsResult()) {
		$listDomainsResult = $this->lastResponse->getListDomainsResult();
                $domainNameList  =  $listDomainsResult->getDomainName();
		if ($listDomainsResult->isSetNextToken())
		    $this->nextLToken = $listDomainsResult->getNextToken();
		else
		    $this->nextLToken = null;
	    }
              
	} catch (Amazon_SimpleDB_Exception $ex) {
	    $this->lastException = $ex;
	    $this->errorCode = $ex->getErrorCode();
	    $this->setErrorMessage($ex->getMessage());
	    return null;
	}
	return $domainNameList;
    }
    
    /**
     * Put attributes to supplied domain on itemName.
     * Attributes are in an associative array with name/value pairs:
     * $attr = array("name1" = "val1", "name2" => "val2", "name3" => array("vala", "valb"));
     *
     * This does NOT need to be called with all attributes of an item, just the ones
     * you need to add or update.
     * 
     * @param string $itemName The itemName (row)
     * @param array $attributes Associative array of attributes
     * @param boolean $replace Optional: if true, then replace on put
     * @return boolean True if OK, false on error (use getErroCode)
     */
    public function putAttributes($itemName, $attributes, $replace = false) {
	$this->lastCalled = "putAttributes";
	$this->setErrorCode('OK');
	if (strlen($this->domain) < 1) {
	    $this->setErrorCode('DomainNameNotSet');
	    return false; 
	}
	if (strlen($itemName) < 1) {	// simple error check
	    $this->setErrorCode('InvalidParameterValue');
	    return false;
	}
	
	$attrList = $this->buildAttributesArray($attributes, $replace);	// build array for request
	$putAttributesRequest = 
	    array ("DomainName" => $this->domain,
                "ItemName" => $itemName,
                "Attribute" => $attrList);
        try {
            $this->lastResponse = $this->sdbService->putAttributes($putAttributesRequest);
        } catch (Amazon_SimpleDB_Exception $ex) {
	    $this->lastException = $ex;
	    $this->errorCode = $ex->getErrorCode();
	    $this->setErrorMessage($ex->getMessage());
	    return false;
	}
	return true;
    }
    
    /** query: send a query to SDB.
     *
     * query is on current domain using standard AWS query format.
     *
     * If you need to retain the results of a query, but need to make
     * another query with a different domain or different expression,
     * then you need to instantiate another instance of a pawSDB object.
     *
     * @param string $queryExpression The query using AWS notation
     * @param bool $followNext Used internally by getNextQueryItemName
     * @return bool success True if some matches, false otherwise
     */
    public function query($queryExpression = null, $followNext = false) {
	define('maxQueryItems',100);	// up to 100 items at a time
	$maxQuery = maxQueryItems;
	$this->lastCalled = 'query';
	$this->setErrorCode('OK');
	if (strlen($this->domain) < 1) {
	    $this->setErrorCode('DomainNameNotSet');
	    return false; 
	}

	$request = array ('DomainName' => $this->domain,	// required settings
		    'MaxNumberOfItems' => $maxQuery);
	if ($queryExpression != null)
	    $request['QueryExpression'] = $queryExpression;	// expression is optional
	$this->queryExp = $queryExpression;			// save query string for more calls
	    
	if ($followNext && $this->nextQToken != null) // called from getNextQueryItemName
	    $request['NextToken'] = $this->nextQToken;
 
 	try {
	    $this->lastResponse = $this->sdbService->query($request);
	    
            if ($this->lastResponse->isSetQueryResult()) { 
                $queryResult = $this->lastResponse->getQueryResult();
                $this->itemStack = $queryResult->getItemName();	// this sets the itemStack
                if ($queryResult->isSetNextToken()) {		// > maxQueryItem results?
		    $this->nextQToken = $queryResult->getNextToken();
                } else {
		    $this->nextQToken = null;			// be sure know when at end
                }
		return true;
            }
	} catch (Amazon_SimpleDB_Exception $ex) {
 	    $this->lastException = $ex;
	    $this->errorCode = $ex->getErrorCode();
	    $this->setErrorMessage($ex->getMessage());
	    return false;
	}
	return false;		// shouldn't get here usually
    }
    
    /**
     * Returns the next itemName on the itemStack. When the stack is
     * empty, it calls query to with nextToken to continue the query
     */
    public function getNextQueryItemName() {
	$nextName = array_pop($this->itemStack);
	if ($nextName == null) {
	    if ($this->nextQToken != null) {		// more results available
		if ($this->query($this->queryExp, true)) {	// do another query
		} else {
		    return null;			// nothing more...
		}
		return $nextName = array_pop($this->itemStack);
	    }
	    return null;
	}
	return $nextName;
    }
    
    /** Just like PutAttributes, but will replace values
    */      
    public function replaceAttributes($itemName, $attributes) {
	return $this->PutAttributes($itemName, $attributes, true);
    }
}
?>
