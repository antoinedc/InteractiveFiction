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
 * List Domains  Sample
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
 * sample for List Domains Action
 ***********************************************************************/
 // @TODO: set action. Action can be passed as Amazon_SimpleDB_Model_ListDomains 
 // object or array of parameters
 // invokeListDomains($service, $action);

                            
/**
  * List Domains Action Sample
  * The ListDomains operaton lists all domains associated with the Access Key ID. It returns
  * domain names up to the limit set by MaxNumberOfDomains. A NextToken is returned if there are more
  * than MaxNumberOfDomains domains. Calling ListDomains successive times with the
  * NextToken returns up to MaxNumberOfDomains more domain names each time.
  *   
  * @param Amazon_SimpleDB_Interface $service instance of Amazon_SimpleDB_Interface
  * @param mixed $action Amazon_SimpleDB_Model_ListDomains or array of parameters
  */
  function invokeListDomains(Amazon_SimpleDB_Interface $service, $action) 
  {
      try {
              $response = $service->listDomains($action);
              
                echo ("Service Response\n");
                echo ("=============================================================================\n");

                echo("        ListDomainsResponse\n");
                if ($response->isSetListDomainsResult()) { 
                    echo("            ListDomainsResult\n");
                    $listDomainsResult = $response->getListDomainsResult();
                    $domainNameList  =  $listDomainsResult->getDomainName();
                    foreach ($domainNameList as $domainName) { 
                        echo("                DomainName\n");
                        echo("                    " . $domainName);
                    }	
                    if ($listDomainsResult->isSetNextToken()) 
                    {
                        echo("                NextToken\n");
                        echo("                    " . $listDomainsResult->getNextToken() . "\n");
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
                        