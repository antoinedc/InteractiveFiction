<?php
die('failed');
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
session_start();			// need this to use $_SESSION
include ("pawsSDB.php");
chdir('..');
include_once('Amazon/SimpleDB/Exception.php'); # JK20090122 - Include Amazon library
include_once('Amazon/SimpleDB/Interface.php'); # JK20090122 - Include Amazon library
include_once('Amazon/SimpleDB/Client.php'); # JK20090122 - Include Amazon library

$mySDB = new pawsSDB();

// We will use $_SESSION to keep the state of the current domain across page generations

if (isset($_SESSION['curDomain'])) {
    $mySDB->setDomain($_SESSION['curDomain']);
}

    /**
     *	simple example of createDomain
     */
    function demoCreateDomain($domain){
		global $mySDB;
	if (!$mySDB->createDomain($domain)) {
	    echo("<p>Create failed, error code: "); echo($mySDB->getErrorCode()); echo("<p>");
	    return false;
	}
	return true;
    }
    
    /**
     *	simple example of deleteDomain
     */
    function demoDeleteDomain($domain) {
	global $mySDB;
	if (!$mySDB->deleteDomain($domain) ) {
	    echo("<p>Delete failed, error code: "); echo($mySDB->getErrorCode()); echo("<p>");
	    return false;
	}
	return true;
    }
    
    /**
     *	simple example of listDomains
     */    
    function demoListDomains() {
	global $mySDB;
	$domainNameList = $mySDB->listDomains(100);	// get array of domain names
	if ($domainNameList == null) {
	    echo("**** No domains defined ****\n");
	    return false;
	} else {
	    echo("<ul>");
	    foreach ($domainNameList as $domainName) {	// echo all names returned
		echo("<li>DomainName: ");
		echo($domainName . "</li>\n");
	    }
	    echo("</ul>\n");
	}
	return true;
    }
    
    /**
     *	simple example of putAttributes
     *
     *	calling example:
     *		$attrs = array('name1'=>'value1', 'name2'=>'value2',
     *			'name3' => array('val3a', 'val3b'));
     *		demoPutAttributes('testdomain','row01',$attrs);
     *
     *	Note: $replace is optional, and for replacement you can either use
     *		putAttributes with $replace paramater true, or use the alternate
     *		replaceAttributes function which conveys intent more clearly.
     *	
     */    
    function demoPutAttributes($itemName, $attributeRow, $replace = false) {
	global $mySDB;
	// $attributeRow is associative array of name/value(s) pairs
	if (!$mySDB->putAttributes($itemName, $attributeRow, $replace )) {
	    echo("<p>putAttributes failed, error code: "); echo($mySDB->getErrorCode()); echo("<p>");
	    return false;
	}
	return true;
    }
    
    /**
     *	example of getAllAttributes
     */
    function demoGetAllAttributes($itemName) {
	global $mySDB;
	$attrs = $mySDB->getAllAttributes($itemName);
	if ($attrs == null) {
	    echo("<p>getAllAttributes failed, error code: "); echo($mySDB->getErrorCode()); echo("<p>");
	    return false;
	} else {
	    echo("<ul>");
	    foreach ($attrs as $name => $value) {
		if (!is_array($value)) {			// distinguish single value from multiple value
		    echo("<li>$name = $value</li>\n"); 
		} else {
		    foreach ($value as $val) {
			echo("<li>$name = $val</li>\n");
		    }
		}
	    }
	    echo("</ul>\n");
	}
	return true;
    }
        /**
     *	example of getAttributeValues
     */
    function demoGetAttributes($itemName, $attributeName) {
	global $mySDB;
	if (is_array($attributeName)) {
	    $attrs = $mySDB->getAttributes($itemName, $attributeName);
	    if ($attrs == null) {
		echo("<p>getAttributes failed, error code: "); echo($mySDB->getErrorCode()); echo("<p>");
		return false;
	    } else {
		foreach ($attrs as $name => $value) {
		    if (!is_array($value)) {			// distinguish single value from multiple value
			echo("<li>$name = $value</li>\n"); 
		    } else {
			foreach ($value as $val) {
			    echo("<li>$name = $val</li>\n");
			}
		    }
		}
		echo("</ul>\n");
	    }	    
	} else {
	    $values = $mySDB->getAttributeValues($itemName, $attributeName);
	    if ($values == null) {
		echo("<p>getAttributesValues failed, error code: "); echo($mySDB->getErrorCode()); echo("<p>");
		return false;
	    } else {
		echo("<ul>");
		if (is_array($values)) {
		    foreach ($values as $value) {
			echo("<li>$attributeName = $value</li>\n");
		    }
    
		} else {
		    echo("<li>$attributeName = $values</li>\n");
		}
		echo("</ul>\n");
	    }
	}
	return true;
    }
    

    /**
     * example of using query and get attributes to show entire DB
     */
    function demoListDB() {
	global $mySDB;
	$domainNameList = $mySDB->listDomains(100);	// get array of domain names
	if ($domainNameList == null) {
	    echo("**** No domains defined ****\n");
	    return false;
	} else {
	    foreach ($domainNameList as $domainName) {	// echo all names returned
		echo "<h4>DomainName: " . $domainName . "</h4>\n";
		
		$mySDB->setDomain($domainName);
		$result = $mySDB->query();	// make null expression query return all rows
		if ($result == null) {
		    echo "<p>No items in this domain</p>\n";
		} else {
		    for (;;) {				// loop through all matching itemNames
			$nextItem = $mySDB->getNextQueryItemName();
			if ($nextItem != null) {
			    echo $nextItem . ": ";
			    $row = $mySDB->getAllAttributes($nextItem);
			    echo serialize($row) . "<br>\n";
			} else {
			    echo "<br>\n";
			    break;
			}
		    } 
		}
		
	    }
	}
	return true;
	
    }
    
       /**
     * example of using query and get attributes to show entire DB
     */
    function demoListCurDomain() {
	global $mySDB;
	$result = $mySDB->query();	// make null expression query return all rows
	if ($result == null) {
	    echo "<p>No items in this domain</p>\n";
	} else {
	    for (;;) {				// loop through all matching itemNames
		$nextItem = $mySDB->getNextQueryItemName();
		if ($nextItem != null) {
		     echo $nextItem . ": ";
		    $row = $mySDB->getAllAttributes($nextItem);
		     echo serialize($row) . "<br>\n";
		} else {
		    echo "<br>\n";
		    break;
		}
	    } 
	}
	return true;
    }
    
    /**
     * example of query
     */
    function demoQuery($queryExpression) {
	global $mySDB;
	if (!$mySDB->query($queryExpression)) {	// make the query
	    echo "<p><strong>!* Query failed. " .  $mySDB->getErrorCode() . "</strong></p>\n";
	} else {
	    echo "<p>Matching items: <p>\n";
	    for (;;) {				// loop through all matching itemNames
		$nextVal = $mySDB->getNextQueryItemName();
		if ($nextVal != null) {
		    echo "itemName: " . $nextVal . "<br>\n";
		} else {
		    echo "<br>\n";
		    break;
		}
	    } 
	}
    }
    
    /**
     * example of deleteAttributes
     */
    function demoDeleteAttributes($itemName, $attributes = null) {
	global $mySDB;
	if (!$mySDB->deleteAttributes($itemName,$attributes)) {
	    echo "deleteAttributes failed: " . $mySDB->getErrorCode() . "\n";
	}
    }
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN">
<html lang="en">
<head>
    <title>paws lib</title>
</head>
<body style='background-color:#ddddff; font-size:small'>
<div style='font-family:sans-serif; background-color:#ddddff; font-size:small' >
    <!-- Insert your content here -->
    <h2>paws Simple DB Library Demo v 2008-06-10</h2>
<?
/* Simple chain of if's to determine request from demo page.
 * You can't mess much with the $_POST index names unless you make corresponding
 * changes to the input fields on the HTML form.
 */

if (isset($_POST['setDomain'])) {
    echo("<h3> Set domain to:"); echo( $_POST['domainName']); echo(" </h3>\n");
    $mySDB->setDomain($_POST['domainName']);
    $_SESSION['curDomain'] = $mySDB->getDomain();
}
// check domain warning AFTER any setDomain operations
if (strlen($mySDB->getDomain()) < 1) {
    echo("<h2>!** Warning - set domian name before using put/set/query operations! **/</h2>\n");
}
if (isset($_POST["createTheDomain"])) {			//******* Create Domain
    $slen = strlen($_POST["createDomain"]);
    if ($slen > 0) {
	echo("<h3>Creating Domain: "); echo($_POST["createDomain"]); echo(" (takes up to 10 seconds)</h3>\n");
	demoCreateDomain($_POST["createDomain"]);
	$mySDB->setDomain($_POST['createDomain']);
	$_SESSION['curDomain'] = $mySDB->getDomain();
    } else {
	echo("<p><strong>!* No domain supplied to create</strong></p>\n");
    }
}

if (isset($_POST["deleteTheDomain"])) {			//******* Delete Domain
    $slen = strlen($_POST["deleteDomain"]);
    if ($slen > 0) {
	echo("<h3>Deleting Domain: "); echo($_POST["deleteDomain"]); echo("</h3>\n");
        demoDeleteDomain($_POST["deleteDomain"]);
    } else {
	echo("<p><strong>!* No domain supplied to delete</strong></p>\n");
    }
}

if (isset($_POST["listDomains"])) {			//******* List Domains
    echo("<h3>List Domains</h3>\n");
    demoListDomains();
}
if (isset($_POST["listCurDomain"])  || isset($_POST["listCurDomain1"])) {		//******* List Current Domain
    echo("<h3>List Current Domain contents</h3>\n");
    demoListCurDomain();
}

if (isset($_POST["putAttributes"]) || isset($_POST["replaceAttributes"])) {	//******* Put/replace Attributes
    if (isset($_POST["replaceAttributes"])) {
	echo("<h3>Replace Attributes</h3>\n");
	$replace = true;
    } else {
	echo("<h3>Put Attributes</h3>\n");
	$replace = false;
    }
    $err = false;

    if (strlen($_POST["itemPName"]) < 1) {
	echo("<p><strong>!* Item name not specified.</strong></p>\n");
	$err = true;	
    }
    if (strlen($_POST['n0']) < 1 || strlen($_POST["v0"]) < 1) {
	echo("<p><strong>!* No name/values given.</strong></p>\n");
	$err = true;	
    }
    if (!$err) {		// have values for everything
	$row[$_POST["n0"]] = $_POST["v0"];	// have at least one
	if (strlen($_POST["n1"]) > 0) {
	    $row[$_POST["n1"]] = $_POST["v1"];
	}
	if (strlen($_POST["n2"]) > 0) {
	    $row[$_POST["n2"]] = $_POST["v2"];
	}
	if (strlen($_POST["v3"]) > 0) {
	    $row[$_POST["n3"]] = $_POST["v3"];
	}
	if (demoPutAttributes($_POST["itemPName"], $row, $replace)) {
	    echo("<p>Put succeeded</p>");
	}
    }
}
    
if (isset($_POST["putDefaultAttributes"]) ) {	//******* Put default Attributes
    $err = false;

    if (strlen($_POST["itemPName"]) < 1) {
	echo("<p><strong>!* Item name not specified.</strong></p>\n");
	$err = true;	
    }
 
    if (!$err) {		// have values for everything
        $row = array("color" => array("red","green","blue"), "size"=>"medium");
	if (demoPutAttributes($_POST["itemPName"], $row)) {
	    echo("<p>Put succeeded</p>");
	}
	$r2 = array('password'=>'*', 'email'=>'*',
		'openID'=>'*', 'sessionid'=>'*', 'lognip'=>'*', 'createtime'=>'0',
		'logintime'=>'0', 'pagetime'=>'0', 'verified'=>'0', 'userlevel'=>'1','attr11'=>'eleven');
	if (!$mySDB->putAttributes('guest1',$r2)) {
	    echo("<p>Special put failed..</p>");
	}

    }
    
}

if (isset($_POST["getAttributes"]) || isset($_POST['getAttributeValues'])) {			//******* Get Attributes
    echo("<h3>Get Attributes/Values for "); echo($mySDB->getDomain()); echo(":"); echo($_POST['itemGName']); echo("</h3>\n");
    $err = false;

    if (strlen($_POST["itemGName"]) < 1) {
	echo("<p><strong>!* Item name not specified.</strong></p>\n");
	$err = true;	
    }
    if (isset($_POST['getAttributeValues']) && strlen($_POST["getName"]) < 1) {
	echo("<p><strong>!* Attribute name not specified.</strong></p>\n");
	$err = true;	
    }
    if (!$err) {		// have values for everything
	if (isset($_POST["getAttributes"])) {
	    demoGetAllAttributes($_POST["itemGName"]);
	}
	else {
	    if (strlen($_POST['gN1']) > 0) {
		$attrNames = array($_POST['getName'], $_POST['gN1']);
		if (strlen($_POST['gN2']) > 0) {
		    array_push($attrNames, $_POST['gN2']);
		}
		demoGetAttributes($_POST["itemGName"], $attrNames);
	    }
	    else {
		demoGetAttributes($_POST["itemGName"], $_POST['getName']);
	    }
	}
    }
}

if (isset($_POST["submitQuery"])) {
    echo "<h3>Query: " . stripslashes($_POST["queryExpression"]) . "</h3>\n";
    demoQuery(stripslashes($_POST["queryExpression"]));
}

if (isset($_POST['listDB'])) {
    echo "<h3>List Entire DB</h3>\n";
    demoListDB();
}

if (isset($_POST["deleteAttributes"])) {
    echo "<h3>Delete attributes </h3>\n";
    if (strlen($_POST["itemName"]) < 1) {
	echo("<p><strong>!* Item name not specified.</strong></p>\n");
    } else {
	$itemName = $_POST["itemName"];
	if (strlen($_POST['dn0']) > 0) {
	    if (strlen($_POST['dv0']) > 0) {
		$attr[$_POST['dn0']] = $_POST['dv0'];
		demoDeleteAttributes($itemName,$attr);
	    } else {
		$attr = array($_POST['dn0']=>NULL);
		$attr['test2'] = NULL;
		demoDeleteAttributes($itemName,$attr);
	    }
	} else {
	    demoDeleteAttributes($itemName);
	}
    }
}

echo("<p style='font-size:small'>Box Usage: " . $mySDB->getBoxUsage() . " </p>\n");

?>
    <form id="form1" action="pawsDemo.php" method="post" enctype="multipart/form-data">
	<fieldset style='background-color:#ccffcc'><legend>Domain Handling</legend>
	<table>
	    <tr><td>Current domain:<strong>[ <? echo($mySDB->getDomain()); ?> ]</strong>&nbsp;&nbsp;</td>
		<td>Set domain: <input name="domainName" type="text" maxlength=60 />
		    <input name="setDomain" type="submit" value="Set Domain" /></td>
		<td>&nbsp;&nbsp;List items in current Domain: <input name="listCurDomain1" type="submit" value="List Domain Items" /></td></tr>
	</table>
	<p><strong>Create and Delete domains can take up to 10 seconds - be patient!</strong></p>
	<table>
	<tr><td><label for="createDomain">Domain to create: </label><input name="createDomain" type="text" maxlength=60 /></td>
	    <td><input type="submit" name="createTheDomain" value="Create Domain" /></p></td>
	    <td>&nbsp;</td><td><label for="deleteDomain">Domain to delete: </label><input name="deleteDomain" type="text" maxlength=60 /></td>
	    <td><input type="submit" name="deleteTheDomain" value="Delete Domain" /></p></td></tr>
	<tr><td>List domains: <input type="submit" name="listDomains" value="List Domains" /></td></tr>
	</table>
	</fieldset>
    
        
	<fieldset style='background-color:#ffffcc'><legend>Get Attributes</legend>
	<table>
	    <tr><td><label for="itemGName">Item Name (row name): </label><input name="itemGName" type="text" maxlength=60 /></td></tr>
	    <tr><td><small>Gets all attributes for item </small><input type="submit" name="getAttributes" value="Get Attributes" /></td></tr>
	    <tr><td>Attribute name(s):<input name="getName" type="text" maxlength=60 />, <small>optional:</small></td>
		<td><input name="gN1" type="text" maxlength=60 />,</td><td><input name="gN2" type="text" maxlength=60 /></td>
		<td><input type="submit" name="getAttributeValues" value="Get Attribute Value(s)" /></td></tr>
	</table>
	</fieldset>
    
   	<fieldset style='background-color:#eeeeff'><legend>Delete Items/Attributes</legend>
	<table>
	    <tr><td><label for="itemName">Item Name (row name): </label><input name="itemName" type="text" maxlength=60 /></td></tr>

	    <tr><td>Attribute:<input name="dn0" type="text" maxlength=60 /> / <input name="dv0" type="text" maxlength=60 /></td>
	    <tr><td><input type="submit" name="deleteAttributes" value="Delete Attributes" /></td>
	        </tr>
	</table>
	</fieldset>
    
    
	<fieldset style='background-color:#ffccff'><legend>Put Attributes</legend>
	<p><small>Each time you use an existing attribute name with a new value, the new value will
	   get <em>added</em> on put, or <em>replaced</em> on replace operation.</small></p>
	<table>
	    <tr><td><label for="itemPName">Item Name (row name): </label><input name="itemPName" type="text" maxlength=60 /></td></tr>
	    <tr><td><small>Allows up to 4 name/value attributes</small></td><td><small>(Only one value per attribute so far)</small></td></tr>
	    <tr><td>0:<input name="n0" type="text" maxlength=60 /> / <input name="v0" type="text" maxlength=60 /></td>
	      <td>1:<input name="n1" type="text" maxlength=60 /> / <input name="v1" type="text" maxlength=60 /></td></tr>
	    <tr><td>2:<input name="n2" type="text" maxlength=60 /> / <input name="v2" type="text" maxlength=60 /></td>
	       <td>3:<input name="n3" type="text" maxlength=60 /> / <input name="v3" type="text" maxlength=60 /></td></tr>
	    <tr><td><input type="submit" name="putAttributes" value="Put Attributes" /></td>
	        <td><input type="submit" name="replaceAttributes" value="Replace Attributes" /></td></tr>
	    <tr><td><small>Default attributes: ("color"=>("red","green","blue"), "size"="medium"):</small></td><td>
			<input type="submit" name="putDefaultAttributes" value="put Default Attrubutes" /></td></tr>
	</table>
	</fieldset>
    

    
	<fieldset style='background-color:#ffff99'><legend>Query</legend>
	<table>
	    <tr><td>Query Expression: <input name="queryExpression" type="text" maxlength=30 />
		<input type="submit" name="submitQuery" value="Submit Query" /></td></tr>
	</table>
	</fieldset>
    
    
	<fieldset style='background-color:#ccffff'><legend>Utility</legend>
	<table>
	<tr><td>List current Domain: <input name="listCurDomain" type="submit" value="List Domain Items" /></td></tr>
	<tr><td>List entire DB: <input name="listDB" type="submit" value="List DB" /></td></tr></table>
	</fieldset>
	
	</form>
<p style='font-size:small'>&nbsp;</p>
<h5>Warning: This program is not secure - it could give the whole world access to your DB!</h5>
<p> If you have this program in a world accessible directory on your server, then it could
expose your whole SimpleDB database to anyone who accesses this demo. Be careful. It would
be best to set an .htaccess password for the directory this file is in.</p>

</div>
</body>
</html>
