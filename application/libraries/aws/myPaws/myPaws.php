<?
/** 
 *  PHP Version 5
 *
 *  @category    Amazon Web Services Library
 *  @package     myPaws
 *  @copyright   Copyright (c) 2008 Jim Kalac, All Rights Reserved.
 *  @license     http://aws.amazon.com/apache2.0  Apache License, Version 2.0
 *  @version     1.0
 *  @requires    - PHP 5.2.1 or newer, http://www.php.net/
 *               - Amazon SimpleDB account, http://aws.amazon.com/simpledb
 *               - Amazon's SimpleDB library, http://developer.amazonwebservices.com/connect/entry.jspa?externalID=1135&categoryID=138
 *               - paws by Bruce E. Wampler, http://objectcentral.com/paws/
 *  @configure   via the paws configuration file: paws/pawsConfig.php
 */
/******************************************************************************* 
 *   __  _  _  _  _
 *  (my)(p)(a)(w)(s)
 *    '  '  '  '  '
 *   mySQL-lite PHP Amazon Web Services SimpleDB Library
 *   A mySQL-lite PHP oriented Library for AWS Simple DB.
 */

/******************************************************************************* 
 * CORE DEPENDENCIES
 *
 * Include the myPaws config file (via paws/pawsConfig.php)
 * Requires that chdir() into the folder that contains Amazon/, paws/ and myPaws/ subfolders
 * This is needed due to how the AWS library in Amazon/ is currently setup
 */

# add services base library path to the include path
$parts=explode('/',dirname(__FILE__));
unset($parts[count($parts)-1]);
set_include_path(get_include_path() . PATH_SEPARATOR . implode('/',$parts));

# include AWS SimpleDB libraries, presumes currently in the folder containing Amazon/
foreach(array('Exception','Model','Client','Util'/*,'Mock'*/) as $include) { 
	include_once("Amazon/SimpleDB/$include.php"); 
}

# include paws library and load configuration
include_once('paws/pawsSDB.php');



/******************************************************************************* 
 * CONSTANTS
 */

# Name of the software
define('MYPAWS_NAME', 'myPaws');

# Version of the software.
define('MYPAWS_VERSION', '1.1');

 # Build ID of the software
define('MYPAWS_BUILD', gmdate('YmdHis', strtotime(substr('$Date$', 7, 25)) ? strtotime(substr('$Date$', 7, 25)) : filemtime(__FILE__)));

# URL to learn more about the software.
define('MYPAWS_URL', 'http://code.google.com/p/mypaws/');

# User agent string used to identify Tarzan
# Ex: myPaws/0.9 (Amazon Web Services API; http://code.google.com/p/mypaws/) Build/20090122030000
define('MYPAWS_USERAGENT', MYPAWS_NAME . '/' . MYPAWS_VERSION . ' (Amazon Web Services API; ' . MYPAWS_URL . ') Build/' . MYPAWS_BUILD);



/******************************************************************************* 
 * CLASS myPawsSDB
 * This is the main class 
 */

class myPawsSDB {
	private $paws;
	private $domain;
	private $attributes;
	private $select_expression;
	private $select_item;
	private $limit;
	private $orderby;
	private $keyfield;
	private $internal_error;
	private $go_operation;
	private $key_field_value;
	private $insert_fields;
	
	public function __construct($keyfield='id') {
		$this->paws=new pawsSDB();
		$this->select();
		$this->where();
		$this->set_key_field($keyfield);
		$this->go_operation=false;
	}
	
	
	
	# set the table field that is used as the 'key' field. Default is 'id'
	public function set_key_field($keyfield='id') {
		$this->keyfield=trim($keyfield);
	}
	
	
	
	# get the table field that is used as the 'key' field. 
	public function get_key_field() {
		return $this->keyfield;
	}
	


	# set TABLE (domain in AWS terms)
	public function from($domain) {
		if ($this->domain<>$domain) {
			$this->paws->setDomain($domain);
			$this->domain=$domain;
		}
		return $this;
	}
	
	
	
	# set TABLE (domain in AWS terms)
	public function into($domain) { # alias of from(), used to help make insert more SQL familiar
		return $this->from($domain);
	}
	
	
	
	# setup WHERE clause
	public function where($select_expression='') {
		$select_expression=$this->prepSelectExpression($select_expression);
		$this->select_expression=$select_expression;
		$this->select_item=false;
		$this->limit();
		return $this;
	}
	
	
	# setup SELECT in AWS terms
	private function prepSelectExpression($select_expression) {
		$select_expression=trim($select_expression);
		if (($select_expression<>'') and (substr($select_expression,0,1)<>'[')) { $select_expression="[ $select_expression ]"; }
		return $select_expression;
	}



	# setup compound AND WHERE clause, use after initial where()	
	public function andWhere($select_expression='') {
		$select_expression=$this->prepSelectExpression($select_expression);
		$this->select_expression=substr($this->select_expression,0,-1).' AND '.substr($select_expression, 1);
		return $this;
	}



	# setup compound OR WHERE clause, use after initial where()	
	public function orWhere($select_expression='') {
		$select_expression=$this->prepSelectExpression($select_expression);
		$this->select_expression=substr($this->select_expression,0,-1).' OR '.substr($select_expression, 1);
		return $this;
	}
	
	
#	public function intersects($select_expression='') {
#		$select_expression=$this->prepSelectExpression($select_expression);
#		$this->select_expression=$this->select_expression.' intersection '.$select_expression;
#		return $this;
#	}
	
	
	# setup SELECT
	public function select($attributes='*') {
		$this->attributes=$attributes;
		$this->limit();
		$this->go_operation='_select';
		return $this;
	}
	

	
	# setup LIMIT, currently only used by SELECT
	public function limit($limit=false) { # default, don't limit
		$this->limit=$limit;
		return $this;
	}
	
	
	
	# setup ORDER BY 
	public function orderby($orderby=false, $order='DESC') { # default, don't order
		if ($orderby===false) {
			$this->orderby=$orderby;
		} else {
			$order=trim(strtoupper($order));
			$descending=(strtoupper($order)<>'ASC'); 
			$this->orderby=array('attribute'=>$orderby, 'descending'=>$descending);
		}
		return $this;
	}


	
	# execute the selected SELECT, INSERT or DELETE query
	public function go($get_data_with_keys=true) { # parameter only used with
		$this->internal_error=false;
		if ($this->go_operation) {
			$go_operation=$this->go_operation;
			$results=$this->$go_operation($get_data_with_keys);
			$this->where(); # reset where clause
			return $results;
		}
		$this->internal_error=array('error_code'=>'Missing operation', 'error_message'=>'Missing SELECT, INSERT or UPDATE operation');		return false;
	}
	
	
	
	# execute select query
	# output for get_data_with_keys=false:
	#    array of items with attributes with each item as the array key and the attributes as an associated array for each key
	# output for get_data_with_keys=true:
	#    array with values as the item keys
	private function _select($get_data_with_keys=true) {
		if ($this->select_item!==false) {
			if ($get_data_with_keys) {
				if ($this->attributes=='*') {
					$results=$this->paws->getAllAttributes($this->select_item);
				} else {
					$results=$this->paws->getAttributeValues($this->select_item, $attributes);
				}
				if ($results==NULL) { return false; }
				return $results;
			}
		}
		
		$sort='';
		if (($this->orderby!==false) and ($this->orderby['attribute']!=$this->keyfield)) {
			if ($this->select_expression=='') {
				$sort="[ '".$this->orderby['attribute']."' > '' ]";
			}
			$sort.=" SORT '".$this->orderby['attribute']."'";
			if ($this->orderby['descending']) { $sort.=' DESC'; }
		}
		$results=array();
#		echo "== $this->select_expression$sort ==<br>\n";
		if (!$this->paws->query($this->select_expression.$sort)) {	// make the query
			return false;
		} else {
			$rows=0;
			for (;;) {
				$item = $this->paws->getNextQueryItemName();
				if ($item != null) {
					if ($get_data_with_keys) {
						if ($this->attributes=='*') {
							$data=$this->paws->getAllAttributes($item);
						} else {
							$data=$this->paws->getAttributeValues($item, $attributes);
						}
						if ($data==NULL) { return false; }
						$results[$item]=$data;
					} else {
						$results[]=$item;
					}
				} else {
					break;
				}
				$rows++;
				if (($this->limit) and ($rows>=$this->limit)) { break; }
			}
		}
		if (($this->orderby!==false) and ($this->orderby['attribute']==$this->keyfield)) {
			if ($this->orderby['descending']) {
				ksort($results);
			} else {
				krsort($results);
			}
		}
		return $results;
	}


	# paws pass-thru functions
	public function getErrorCode() {
		if ($this->internal_error) {
			return $this->internal_error['error_code'];
		} else {
			return $this->paws->getErrorCode();
		}
	}
	
	public function getErrorMessage() {
		if ($this->internal_error) {
			return $this->internal_error['error_message'];
		} else {
			return $this->paws->getErrorMessage();
		}
	}
	
	public function getBoxUsage() {
		if ($this->internal_error) {
			return '0.0';
		} else {
			return $this->paws->getBoxUsage();
		}
	}
	
	public function getResponseID() {
		if ($this->internal_error) {
			return '0';
		} else {
			return $this->paws->getResponseID();
		}
	}
	
	public function createDomain($domain) {
		$this->internal_error=false;
		return $this->paws->createDomain($domain);
	}
	
	public function deleteDomain($domain) {
		$this->internal_error=false;
		return $this->paws->deleteDomain($domain);
	}
	
	public function showTables($max = 100) { # 100 is the max allowed by AWS SimpleDB
		$this->internal_error=false;
		return $this->paws->listDomains($max);
	}
	

	# mysql'ish aliases for pass-thru functions
	public function createTable($domain) {
		$this->internal_error=false;
		return $this->createDomain($domain);
	}
	
	public function dropTable($domain) {
		$this->internal_error=false;
		return $this->deleteDomain($domain);
	}



	# setup for inserting a record
	# in: key and array field values
	#     if the key_field_value is omitted, the array field that get_key_field() returns (default is 'id') will be used for the key
	public function insert($key_field_value=false, $insert_fields) {
		$this->key_field_value=$key_field_value;
		$this->insert_fields=$insert_fields;
		$this->go_operation='_insert';
		return $this;
	}
	
	
	
	# insert a record
	# in: key and array field values
	#     if the key is omitted, the array field that get_key_field() as it's value (default is 'id') will be used for the key
	private function _insert($update=false) {
		$key_field_value=$this->key_field_value;
		$insert_fields=$this->insert_fields;
		$this->insert_fields=false;
		$this->key_field_value=false;
		
		if (!is_array($insert_fields)) { 
			$this->internal_error=array('error_code'=>'Invalid field', 'error_message'=>'An array of fields is required');
			return false; 
		}
		if (count($insert_fields)<2) {
			
		}
		if (($key_field_value===false) and (isset($insert_fields[$this->keyfield]))) { 
			$key_field_value=$insert_fields[$this->keyfield];
			unset($insert_fields[$this->keyfield]);
		}
		if ($key_field_value===false) {
			$this->internal_error=array('error_code'=>'Missing key', 'error_message'=>'A key field is required');
			return false;
		}
		if ($update) {
			if (!$this->paws->replaceAttributes($key_field_value, $insert_fields)) {
				return false;
			}
		} else {
			if (!$this->paws->putAttributes($key_field_value, $insert_fields)) {
				return false;
			}
		}
		return true;
	}


	# setup for an UPDATE
	public function update($domain) {
		$this->from($domain);
		$this->go_operation='_update';
	}



	# preform the UPDATE, currently limited to single record updates
	private function _update() {
		return $this->_insert(true); # update instead of insert
	}



	# setup for DELETE
	public function delete($key_value=false) {
		if ($key_value) {
			return $this->paws->deleteAttributes($key_value);
		}
		$this->go_operation='_delete';
		return $this;
	}


	# preform DELETE
	private function _delete() {
		$keys=$this->_select(false); # get keys only
		if ($keys) {
			if (count($keys)>0) {
				foreach($keys as $key_value) {
					$ok=$this->delete($key_value);
					if (!$ok) {
						return false;
					}
				}
			}
			return true;
		}
		return false;
	}
	

	
	# perform SHOW COLUMNS
	public function showColumns($domain=false) {
		if ($domain!==false) {
			$this->from($domain);
		}
		$results=$this->paws->query();
		if ($results == null) {
			return false;
		}
		$item = $this->paws->getNextQueryItemName();
		$data=$this->paws->getAllAttributes($item);
		if ($data) {
			$results=array($this->get_key_field());
			foreach($data as $field=>$val) {
				$results[]=$field;
			}
			return $results;
		} else {
			return false;
		}
	}
}