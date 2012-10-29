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
		
		$characters = array();
		$characters[] = array(
			
			'_id' => new MongoId(),
			'main' => "true"
		);
			
		$data = array(	
						'title' => $this->title ? $this->title : '',
						'start' => $this->start ? $this->start : -1,
						'owner' => new MongoId($this->session->userdata('uid')),
						'paragraphes' => $this->paragraphes ? $this->paragraphes : array(),
						'characters' => $this->characters ? $this->characters : $characters
					);
			
		$id = $this->mongo_db->select($this->database)->insert($this->collectionName, array('development' => $data, 'production' => array()));
		
		if ($data['start'])
			$this->mongo_db->where('_id', $this->_id)->set('start', $id)->update($this->collectionName);
		
		return $id;
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
		
		return $this->mongo_db->where('_id', $this->_id)->set('development', $data)->update($this->collectionName);
	}
	
	function getMainCharacter()
	{
		$characters = $this->getCharacters();
		$res = false;
		foreach ($characters as $character)
			if ($character['main'] == "true")
				$res = $character;
		return $res;
	}
	
	function updateCharStats($cid, $data)
	{
		if ($cid >= 0)
		{
			$data = array_merge(array('_id' => new MongoId($cid)), $data);
			if ($this->mongo_db->where(array(
				'_id' => $this->_id,
				'development.characters._id' => new MongoId($cid)
			))->set('development.characters.$', $data)->update('stories'))
				return $cid;
			else
				return false;
		}
		else
		{
			$id = new MongoId();
			$data = array_merge($data, array('_id' => $id));
			if ($this->mongo_db->where('_id', $this->_id)->push('development.characters', $data)->update('stories'))
				return $id;
			else 
				return false;
		}
	}
	
	function getCharacters($cid = -1)
	{
		$characters = $this->mongo_db->where('_id', $this->_id)->select(array('development.characters'))->get('stories');
		if ($cid < 0)
			return $characters[0]['development']['characters'];
		else
			return $characters[0]['development']['characters'][$cid];
	}
	
	function getFirstParagraph()
	{
		$paragraph = false;
		foreach($this->paragraphes as $p)
		{
			if (new MongoId($p['_id']) == new MongoId($this->start))
			{
				$paragraph = $p;
				break;
			}
		}
		
		return $paragraph;
	}
	
	function getParagraphById($pid)
	{
		if (!$pid) return false;
		foreach ($this->paragraphes as $p)
			if ($p['_id']->{'$id'} == $pid)
				return $p;
		return false;
	}
	
	function delete()
	{
		return $this->mongo_db->delete($this->collectionName, array('_id', $this->_id));
	}
	
	function getId()
	{
		if (!$this->_id)
			$this->_id = new MongoId();
		return $this->_id->{'$id'};
	}
	
	function getOwner()
	{
		return $this->owner;
	}
}