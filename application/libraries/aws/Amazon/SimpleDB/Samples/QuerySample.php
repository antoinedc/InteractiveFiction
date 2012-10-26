<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon
 *  @package     Amazon_SimpleDB
 *  @copyright   Copyright 2007 Amazon Technologies, Inc.
 *  @link        http://aws.amazon.com
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2007-11-07
 */
/******************************************************************************* 
 *    __  _    _  ___ 
 *   (  )( \/\/ )/ __)
 *   /__\ \    / \__ \
 *  (_)(_) \/\/  (___/
 * 
 *  Amazon Simple DB PHP5 Library
 *  Generated: Wed Jan 02 01:46:17 PST 2008
 * 
 */

/**
 * Query  Sample
 */

include_once ('.config.inc.php'); 

/************************************************************************
 * Instantiate Implementation of Amazon SimpleDB
 * 
 * AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY constants 
 * are defined in the .config.inc.php located in the same 
 * directory as this sample
 ***********************************************************************/
 $service = new Amazon_SimpleDB_Client(AWS_ACCESS_KEY_ID, 
                                       AWS_SECRET_ACCESS_KEY);

/************************************************************************
 * Uncomment to try out Mock Service that simulates Amazon_SimpleDB
 * responses without calling Amazon_SimpleDB service.
 *
 * Responses are loaded from local XML files. You can tweak XML files to
 * experiment with various outputs during development
 *
 * XML files available under Amazon/SimpleDB/Mock tree
 *
 ***********************************************************************/
 // $service = new Amazon_SimpleDB_Mock();

/************************************************************************
 * Setup action parameters and uncomment invoke to try out 
 * sample for Query Action
 ***********************************************************************/
 // @TODO: set action. Action can be passed as Amazon_SimpleDB_Model_Query 
 // object or array of parameters
 // invokeQuery($service, $action);

                                                
/**
  * Query Action Sample
  * The Query operation returns a set of ItemNames that match the query expression. Query operations that
  * run longer than 5 seconds will likely time-out and return a time-out error response.
  * A Query with no QueryExpression matches all items in the domain.
  *   
  * @param Amazon_SimpleDB_Interface $service instance of Amazon_SimpleDB_Interface
  * @param mixed $action Amazon_SimpleDB_Model_Query or array of parameters
  */
  function invokeQuery(Amazon_SimpleDB_Interface $service, $action) 
  {
      try {
              $response = $service->query($action);
              
                echo ("Service Response\n");
                echo ("=============================================================================\n");

                echo("        QueryResponse\n");
                if ($response->isSetQueryResult()) { 
                    echo("            QueryResult\n");
                    $queryResult = $response->getQueryResult();
                    $itemNameList  =  $queryResult->getItemName();
                    foreach ($itemNameList as $itemName) { 
                        echo("                ItemName\n");
                        echo("                    " . $itemName);
                    }	
                    if ($queryResult->isSetNextToken()) 
                    {
                        echo("                NextToken\n");
                        echo("                    " . $queryResult->getNextToken() . "\n");
                    }
                } 
                if ($response->isSetResponseMetadata()) { 
                    echo("            ResponseMetadata\n");
                    $responseMetadata = $response->getResponseMetadata();
                    if ($responseMetadata->isSetRequestId()) 
                    {
                        echo("                RequestId\n");
                        echo("                    " . $responseMetadata->getRequestId() . "\n");
                    }
                    if ($responseMetadata->isSetBoxUsage()) 
                    {
                        echo("                BoxUsage\n");
                        echo("                    " . $responseMetadata->getBoxUsage() . "\n");
                    }
                } 

     } catch (Amazon_SimpleDB_Exception $ex) {
            
         echo("Caught Exception: " . $ex->getMessage() . "\n");
         echo("Response Status Code: " . $ex->getStatusCode() . "\n");
         echo("Error Code: " . $ex->getErrorCode() . "\n");
         echo("Error Type: " . $ex->getErrorType() . "\n");
         echo("Box Usage: " . $ex->getBoxUsage() . "\n");
         echo("Request ID: " . $ex->getRequestId() . "\n");
         echo("XML: " . $ex->getXML() . "\n");
     }
 }
    