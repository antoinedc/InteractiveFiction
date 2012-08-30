<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Users extends CI_Model {

	private $collection;
	private $database;
	
	private $_id;
	var $email;
	var $password;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->database = 'local';
		$this->collection = 'users';
	}
	
	function insert()
	{
		$isEmailPresent = count($this->mongo_db->where('email', $this->email)->get($this->collection));
		
		if ($isEmailPresent) return false;
		
		$data = array(
						'email' => $this->email,
						'password' => $this->password
					);
		
		return $this->mongo_db->select($this->database)->insert($this->collection, $data);
	}
	
	function select($filter)
	{
		//selectById
		if (isset($filter['_id']) && $filter['_id'] != '')
		{
			$filter['_id'] = new MongoId($filter['_id']);
			$res = $this->mongo_db->where('_id', $filter['_id'])->get($this->collection);
			if (count($res))
			{
				$res = $res[0];
				$this->_id = $res['_id'];
				$this->email = $res['email'];
				$this->password = $res['password'];
				return $this;
			}
			else
				return false;
		}
		
		//selectByEmail
		else if (isset($filter['email']) && $filter['email'] != '')
		{
			$res = $this->mongo_db->where('email', $filter['email'])->get($this->collection);
			if (count($res))
			{
				$res = $res[0];
				$this->_id = $res['_id'];
				$this->email = $res['email'];
				$this->password = $res['password'];
				return $this;
			}
			else
				return false;
		}
		else
			//No other choices for now
			return false;
	}
	
	function update()
	{
		$data = array(
						'email' => $this->email,
						'password' => $this->password
					);
		
		return $this->mongo_db->update($this->collection, $data);
	}
	
	function delete()
	{
		return $this->mongo_db->delete($this->collection, array('_id', $this->_id));
	}
	
	function getId()
	{
		return $this->_id;
	}
	
	function getOwner()
	{
		return $this->owner;
	}	
}