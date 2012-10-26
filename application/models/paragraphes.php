<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Paragraphes extends CI_Model {

	private $_sdb;
	private $domain;
	
	private $itemName;
	var $text;
	var $sid;
	var $isStart;
	var $isEnd;
	
	function __construct()
	{
		parent::__construct();
		$this->load->spark('amazon-sdk/0.1.8');
		$this->_sdb = $this->awslib->get_sdb();
		$this->domain = 'Paragraphes';
	}
	
	function insert()
	{
		$this->itemName = uniqid();
		$attributes = array(
								'text' => $this->text,
								'sid' => $this->sid,
								'isStart' => $this->isStart,
								'isEnd' => $this->isEnd
							);
							
		$putExists['text'] = array('exists' => 'false');
		
		return $this->_sdb->putAttributes($this->domain, $this->itemName, $attributes, $putExists);
	}
	
	function select($query)
	{	
		return $this->_sdb->select($query);
	}
	
	function update()
	{
		$attributes = array(
								'text' => $this->text,
								'sid' => $this->sid,
								'isStart' => $this->isStart,
								'isEnd' => $this->isEnd
							);
							
		$putExists['text'] = array('exists' => 'false');
		
		return $this->_sdb->putAttributs($this->domain, $this->itemName, $attributes, $putExists);
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