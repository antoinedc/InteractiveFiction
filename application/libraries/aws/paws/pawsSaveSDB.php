<?php
/** 
 *  PHP Version 5
 *
 *  @category    Amazon Web Services Library
 *  @package     paws
 *  @copyright   Copyright (c) 2008 Bruce E. Wampler
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     2008-06-19
 */
/******************************************************************************* 
 *   _  _  _  _
 *  (p)(a)(w)(s)
 *   '  '  '  '
 *   PHP Amazon Web Services Library
 *   A PHP oriented Library for AWS Simple DB, S3, and EC2.
 *
 */
include ("pawsSDB.php");
$mySDB = new pawsSDB();

fwrite(STDERR, "pawsSaveSDB.php - save paws format to SDB definition file - this is NOT a web based application!\n\n");

fwrite(STDERR, "=============================================\nStart time: " . date("H:i:s") . "\n");
$saveName = date("Ymd-H:i:s");	// make up a tag
echo serialize(array('pawsBeginSDB'=>$saveName)) ."\n";
/* order of saved data:
$row['pawsBeginDomain']
$row['pawsItemName']
value of item
*/
	$domainNameList = $mySDB->listDomains(100);	// get array of domain names
	if ($domainNameList == null) {
	    fwrite(STDERR,"**** No domains defined ****\n");
	    exit(1);
	} else {
	    foreach ($domainNameList as $domainName) {	// echo all names returned
		fwrite(STDERR, "\nDomainName: " . $domainName . "\n");
		$row['pawsBeginDomain'] = $domainName;
		echo serialize($row) . "\n";
		unset($row);
		
		$mySDB->setDomain($domainName);
		$result = $mySDB->query();	// make null expression query return all rows
		if ($result == null) {
		    fwrite(STDERR, "No items in this domain\n");
		} else {
		    for (;;) {				// loop through all matching itemNames
			$nextItem = $mySDB->getNextQueryItemName();
			if ($nextItem != null) {
			    $row['pawsItemName'] = $nextItem;
			    echo serialize($row) . "\n";
			    unset($row);
			    $row = $mySDB->getAllAttributes($nextItem);
			    echo serialize($row) . "\n";
			    unset($row);
			    fwrite(STDERR,".");
			} else {
			    break;
			}
		    }
		    $row['pawsEndDomain'] = $domainName;
		    echo serialize($row) . "\n";
		    unset($row);
		}
	    }
	}
echo serialize(array('pawsEndSDB'=>$saveName)) ."\n";
fwrite(STDERR,"\n=============================================\nEnd time: " . date("H:i:s") . "\n\n");
exit(0);

?>
