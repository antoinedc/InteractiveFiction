<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Links extends CI_Model {

	private $_sdb;
	private $domain;
	
	private $itemName;
	var $origin;
	var $destination;
	var $sid;
	var $text;
	
	function __construct()
	{
		parent::__construct();
		$this->load->spark('amazon-sdk/0.1.8');
		$this->_sdb = $this->awslib->get_sdb();
		$this->domain = 'Links';
	}
	
	function insert()
	{
		$this->itemName = uniqid();
		$attributes = array(
								'origin' => $this->origin,
								'destination' => $this->destination,
								'sid' => $this->sid,
								'text' => $this->text
							);
							
		$putExists = array(	
								'origin' => array('exists', 'false'),
								'destination' => array('exists', 'false'),
								'sid' => array('exists', 'false'),
								'text' => array('exists', 'false')
		    				);
		
		return $this->_sdb->putAttributes($this->domain, $this->itemName, $attributes, $putExists);
	}
	
	function select($query)
	{
		return $this->_sdb->select($query);
	}
	
	function selectBySid($sid)
	{
		$res = $this->_sdb->select('SELECT * FROM Link WHERE sid = "' . $sid . '"');
		
		$links = array();
		if (!empty($res->body->SelectResult->Item))
		{
			foreach($res->body->SelectResult->Item as $item)
			{
				$newLink = array();
				foreach ($link->Attribute as $attribute)
					$newLink[(string)$attribute->Name] = (string)$attribute->Value;
				
				$links[] = $newLink;
			}
		}
		return $links;
	}
	
	function update()
	{
		$attributes = array(
								'origin' => $this->origin,
								'destination' => $this->destination,
								'sid' => $this->sid
							);
							
		$putExists = array(	'origin' => array('exists', 'false'),
							'destination' => array('exists', 'false'),
							'sid' => array('exists', 'false')
						   );
		
		return $this->_sdb->putAttributes($this->domain, $this->itemName, $attributes, $putExists);
	}
	
	function delete($conditions = array())
	{
		return $this->_sdb->delete($this->domain, $this->itemName, null, $conditions);
	}
	
	function getItemName()
	{
		return $this->itemName;
	}						
}