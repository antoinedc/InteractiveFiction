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
	var $characters;	
	
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
						'paragraphes' => $this->paragraphes ? $this->paragraphes : array(),
						'characters' => $this->characters ? $this->characters : array()
					);
					
		return $this->mongo_db->select($this->database)->insert($this->collectionName, array('development' => $data, 'production' => array()));
	}
	
	function select($filter, $prod = false)
	{
		//selectById - 1 result
		if (isset($filter['_id']) && $filter['_id'] != '')
		{
			$filter['_id'] = new MongoId($filter['_id']);
			$version = ($prod?'production':'development');
			$res = $this->mongo_db->select(array($version))->where('_id', $filter['_id'])->get($this->collectionName);
			
			if (count($res))
			{
				
				$this->_id = $res[0]['_id'];
				$res = $res[0][$version];
				$this->title = $res['title'];
				$this->start = $res['start'];
				$this->owner = $res['owner'];
				$this->paragraphes = $res['paragraphes'];			
				$this->characters = $res['characters'];
				
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
				$this->_id = $res[0]['_id'];
				$res = $res[0]['development'];
				$this->title = $res['title'];
				$this->start = $res['start'];
				$this->owner = $res['owner'];
				$this->paragraphes = $res['paragraphes'];
				$this->characters = $res['characters'];
				
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
	
	function goToProd($filter)
	{
		$dev = $this->mongo_db->select(array('development'))->where('_id', new MongoId($filter['_id']))->get($this->collectionName);
		$dev = $dev[0]['development'];
		$res = $this->mongo_db->where('_id', new MongoId($filter['_id']))->set('production', $dev)->update('stories');
		return $res;
	}
	
	function update()
	{
		$data = array(
						'title' => $this->title ? $this->title : '',
						'start' => $this->start ? $this->start : '',
						'owner' => $this->owner,
						'paragraphes' => $this->paragraphes ? $this->paragraphes : array(),
						'characters' => $this->characters
					);
					
		return $this->mongo_db->update($this->collectionName, $data);
	}
	
	function updateCharStats($cid, $data)
	{
		$data = array_merge($data, array('_id' => new MongoId()));
		if ($cid >= 0)
			return $this->mongo_db->where('_id', $this->_id)->set('development.characters.' . $cid, $data)->update('stories');
		else
			return $this->mongo_db->where('_id', $this->_id)->push('development.characters', $data)->update('stories');
	}
	
	function getCharacter($cid)
	{
		$characters = $this->mongo_db->where('_id', $this->_id)->select(array('development.characters'))->get('stories');
		return $characters[0]['development']['characters'][$cid];
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