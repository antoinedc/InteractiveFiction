<?  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/************************************************************************
 *  PHP Version 5
 *
 *  @category    Amazon Web Services SimpleDB Library 
 *  @package     myPawsSDB for Code Igniter
 *  @copyright   Copyright (c) 2008 Jim Kalac, All Rights Reserved.
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     1.0
 *  @requires    - PHP 5.2.1 or newer, http://www.php.net/
 *               - Amazon SimpleDB account, http://aws.amazon.com/simpledb
 *               - Amazon's SimpleDB library, http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1135&categoryID=138
 *               - paws by Bruce E. Wampler, http://objectcentral.com/paws/
 *               - myPaws by Jim Kalac, http://code.google.com/p/mypaws/
 *  @install     - place the Mypaws.php file in your application/libraries/ folder
 *               - download the myPaws package from http://code.google.com/p/mypaws/downloads/list
 *               - untar the myPaws-x.x.tar file
 *               - place an 'aws' symlink to your myPaws-x.x/ folder (or rename myPaws-x.x/ to aws/) and place this in the application/libraries/ folder
 *  @configure   add $config[AWS_ACCESS_KEY_ID] and $config[AWS_SECRET_ACCESS_KEY] with you Amazon keys into application/config/config.php
 *
 ************************************************************************/

$CI =& get_instance();

define('AWS_ACCESS_KEY_ID', $CI->config->item('AWS_ACCESS_KEY_ID'));
define('AWS_SECRET_ACCESS_KEY', $CI->config->item('AWS_SECRET_ACCESS_KEY'));
 
include_once(dirname(__FILE__).'/aws/myPaws/myPaws.php');

class Mypaws extends myPawsSDB {

	public function status_summary() {
		$status=$db->getErrorCode();
		$results="Status: $status.&nbsp;&nbsp; ";
		if ($status!='OK') {
			$results.="Error: ".$db->getErrorMessage().".&nbsp;&nbsp; ";
		}
		$results.="Box Usage: ".$db->getBoxUsage().".&nbsp;&nbsp; Response ID: ".$db->getResponseID();
		return $results;	
	}
}
// END Mypaws Class

/* End of file Mypaws.php */
/* Location: ./application/libraries/Mypaws.php */