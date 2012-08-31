<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Stories extends CI_Model {

	private $database;
	private $collectionName;
	private $_collection;
	
	private $_id;
	var $title;
	var $start;
	private $owner;
	var $paragraphes;
	
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->library('session');
		
		$this->database = 'local';
		$this->collectionName = 'stories';
	}
	
	function insert()
	{
		
		$isTitlePresent = count($this->mongo_db->where('title', $this->title)->get('stories'));
		
		if ($isTitlePresent) return false;
		
		$data = array(	
						'title' => $this->title ? $this->title : '',
						'start' => $this->start ? $this->start : -1,
						'owner' => new MongoId($this->session->userdata('uid')),
						'paragraphes' => $this->paragraphes ? $this->paragraphes : array()
					);
					
		return $this->mongo_db->select($this->database)->insert($this->collectionName, $data);
	}
	
	function select($filter)
	{
		//selectById - 1 result
		if (isset($filter['_id']) && $filter['_id'] != '')
		{
			$filter['_id'] = new MongoId($filter['_id']);
			
			$res = $this->mongo_db->where('_id', $filter['_id'])->get($this->collectionName);
			
			if (count($res))
			{
				$res = $res[0];
				$this->_id = $res['_id'];
				$this->title = $res['title'];
				$this->start = $res['start'];
				$this->owner = $res['owner'];
				$this->paragraphes = $res['paragraphes'];				
				
				return $this;
			}
			else
				return false;
		}
		 
		//selectByTitle - 1 result
		if (isset($filter['title']) && $filter['title'] != '')
		{
			$res = $this->mongo_db->where('title', $filter['title'])->get($this->collectionName);
			if (count($res))
			{
				$res = $res[0];
				$this->_id = $res['_id'];
				$this->title = $res['title'];
				$this->start = $res['start'];
				$this->owner = $res['owner'];
				$this->paragraphes = $res['paragraphes'];
				return $this;
			}
			else
				return false;
		}
		
		//Multiple results
		return $this->mongo_db->where($filter)->get($this->collectionName);
	}
	
	function selectAll()
	{
		return $this->mongo_db->get($this->collectionName);	
	}
	
	function update()
	{
		$data = array(
						'title' => $this->title ? $this->title : '',
						'start' => $this->start ? $this->start : '',
						'owner' => $this->owner,
						'paragraphes' => $this->paragraphes ? $this->paragraphes : array()
					);
					
		return $this->mongo_db->update($this->collectionName, $data);
	}
	
	function delete()
	{
		return $this->mongo_db->delete($this->collectionName, array('_id', $this->_id));
	}
	
	function getId()
	{
		return $this->_id->{'$id'};
	}
	
	function getOwner()
	{
		return $this->owner;
	}
}