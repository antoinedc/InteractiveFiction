<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Users extends CI_Model {

	private $_sdb;
	private $domain;
	
	private $itemName;
	var $email;
	var $password;
	
	function __construct()
	{
		parent::__construct();
		$this->load->spark('amazon-sdk/0.1.8');
		$this->_sdb = $this->awslib->get_sdb();
		$this->domain = 'Users';
	}
	
	function insert()
	{
		$this->itemName = uniqid();
		$attributes = array(	
								'email' => $this->email,
								'password' => $this->password
							);
		$putExists['email'] = array('exists' => 'false');
		
		return $this->_sdb->putAttributes($this->domain, $this->itemName, $attributes, $putExists);
	}
	
	function select($query)
	{
		return $this->_sdb->select($query);
	}
	
	function update()
	{
		$attributes = array(
								'email' => $this->email,
								'password' => $this->password
							);
		
		$putExists['email'] = array('exists' => 'false');
		
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