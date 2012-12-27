<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Layers extends CI_Model {

	private $collection;
	private $database;
	
	private $_id;
	var $sid;
	var $name;
	var $paragraphes;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->database = 'local';
		$this->collection = 'users';
	}
	
	function insert()
	{
		$data = array(
			'_id' => new MongoId(),
			'name' => $this->name,
			'paragraphes' => $this->paragraphes ? $this->paragraphes : array()
		);
		
		$res = $this->mongo_db->where('_id', new MongoId($this->sid))->push('development.layers', $data)->update('stories');
		$this->_id = $res;
		
		return $res;
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
				$this->name = $res['name'];
				$this->paragraphes = $res['paragraphes'];
				return $this;
			}
			else
				return false;
		}
		
		//selectByName
		else if (isset($filter['name']) && $filter['name'] != '')
		{
			$res = $this->mongo_db->where('name', $filter['name'])->get($this->collection);
			if (count($res))
			{
				$res = $res[0];
				$this->_id = $res['_id'];
				$this->name = $res['name'];
				$this->paragraphes = $res['paragraphes'];
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
			'name' => $this->name,
			'paragraphes' => $this->paragraphes ? $this->paragraphes : array()
		);
		
		return $this->mongo_db->update($this->collection, $data);
	}
	
	function delete()
	{
		return $this->mongo_db->delete($this->collection, array('_id', $this->_id));
	}
	
	function getId()
	{
		if ($this->_id)
			$this->_id = new MongoId();
			
		return $this->_id;
	}
}