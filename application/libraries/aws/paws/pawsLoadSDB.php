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
/**
 * This php script is used to upload a paws format SDB save file from
 * a terminal window.
 * 
 * See createSDBexample.php for an example on how to manually create a save file.
 *
 * A paws SDB save file consists of serialized PHP arrays. Each section of the
 * save file is bracketed by one pawsSDB name ('pawsBeginSDB'/'pawsEndSDB') pair.
 * Each Domain is brackedt by a 'pawsBeginDomain'/'pawsEndDomain' pair. Then
 * each item in the data base is defined by two lines: the 'pawsItemName' line
 * followed by the attribute value line.
 *
 * pawsSaveSDB.php can be used to download a save file of your entire database.
 *
 * Usage:
 *     php pawsLoadSDB.php < filename.sdb
 *
 **/

include ("pawsSDB.php");
$mySDB = new pawsSDB();

echo "pawsLoadSDB.php - upload paws format SDB definition file - this is NOT a web based application!\n\n";

echo "=============================================\nStart time: " . date("H:i:s") . "\n\n";

    if (!feof(STDIN)) {
	$begin = unserialize(fgets(STDIN));
	if (!isset($begin['pawsBeginSDB'])) {
	    echo "***** ERROR: Invalid paws sdb file - missing 'pawsBeginSDB' line *****\n\n";
	    exit(1);
	}
	$dbname = $begin['pawsBeginSDB'];
	echo "##### Beginning SDB: ". $dbname . "\n";
	
    }
    while (!feof(STDIN)) {
	$row = unserialize(fgets(STDIN));
	if (isset($row['pawsEndSDB'])) {		// look for pawsEndSDB to end upload
	    echo "##### End SDB: ". $dbname . "\n\n";
	    if ($row['pawsEndSDB'] != $dbname)
	        echo "***** WARNING: SDB names don't match (" . $dbname . "!=" . $row['pawsEndDB'] . ") *****\n\n";
	    break;
	} else if (isset($row['pawsComment'])) {	// just ignore comments
	    continue;
	} else if (isset($row['pawsBeginDomain'])) {		// starting a new domain
	    $curDomain = $row['pawsBeginDomain'];
	    echo "<<<<< Begin Domain: " . $curDomain . " >>>>>\n";
	    // first, delete old copy if it exists
	    echo "     ---- First, deleting existing domain, wait...\n";
	    if ( !$mySDB->deleteDomain($curDomain)) {
		echo "***** ERROR: failed on deleteDomain\n";
		echo "      errorcode: " . $mySDB->getErrorCode() . "\n";
		break;
	    }
	    // now, create the new domain
	    echo "     ++++ Now creating domain, wait...\n";
	    if ( !$mySDB->createDomain($curDomain)) {
		echo "***** ERROR: failed on createDomain\n";
		echo "      errorcode: " . $mySDB->getErrorCode() . "\n";
		break;
	    }
	    echo "     Loading items";
	    $mySDB->setDomain($curDomain);	// set domain for loading items
	} else if (isset($row['pawsEndDomain'])) {
	    if ($row['pawsEndDomain'] != $curDomain) {
		echo "\n***** ERROR: BeginDomain/EndDomain don't match! Exiting. *****\n\n";
		break;
	    }
	    else {
		echo "\n<<<<< End Domain: " . $curDomain . " >>>>>\n";
	    }
	} else if (isset($row['pawsItemName'])) {		// only legal option remaining!
	    $itemName = $row['pawsItemName'];
	    // temp
	    echo ".";
	    $attributes = unserialize(fgets(STDIN));		// assume line there - skip eof check.
	    // now save attributes to DB
	    if (!$mySDB->putAttributes($itemName, $attributes)) {
		echo "***** ERROR: putAttributes failed! Exiting. *****\n";
		echo "      errorcode: " . $mySDB->getErrorCode() . "\n";
		break;
	    }
	    
	} else {			// Something wrong!!!
	   echo "***** ERROR: SDB file in incorrect format!\n\n";
	   break;
	}
    }

echo "=============================================\nEnd time: " . date("H:i:s") . "\n\n";
if (!isset($row['pawsEndSDB'])) {
    echo "***** ERROR: SDB file did not end with 'pawsEndSDB' line!\n      Data Base may have been only partially created!\n\n";
    exit(3);
}
exit(0);
?>
