<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Links extends CI_Model {

	private $collection;
	private $database;
	
	private $_id;
	var $origin;
	var $destination;
	var $sid;
	var $text;
	var $action;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->Model('mongo/stories');
		$this->load->Model('mongo/paragraphes');
		
		$this->database = 'local';
		$this->collection = 'links';
	}
	
	function insert()
	{
		//if there is already a link with the same text from the same origin with the same destination in the story
		if (count($this->mongo_db->where(array(
													'origin' => $this->origin,
													'destination' => $this->destination,
													'text' => $this->text,
													'sid' => $this->sid,
													'action' => $this->action,
													'condition' => $this->condition
											)
										)->get('stories')))
			return false;
			
		$data = array(
						'_id' => new MongoId(),
						'origin'=> $this->origin,
						'destination' => $this->destination,
						'text' => $this->text,
						'sid' => $this->sid,
						'condition' => $this->condition,
						'action' => $this->action
					);
		
		$res = $this->mongo_db->where(array('development.paragraphes._id' => new MongoId($this->origin))
														)->push('development.paragraphes.$.links', $data)->update('stories');
														
		if ($res) return $data['_id']->{'$id'};
		else return $res;
	}
	
	function select($filter, $prod = false)
	{
		//selectById
		if (isset($filter['_id']) && $filter['_id'] != '')
		{
			$filter['_id'] = new MongoId($filter['_id']);
			
			$version = ($prod?'production':'development');
			
			$res = $this->mongo_db->select(array($version))->where('_id', $filter['_id'])->get('stories');
			var_dump($res);
			if (count($res))
			{
				
				$res = $res[0];
				$this->_id = $res['_id'];
				$res = $res[0][$version];
				$this->origin = $res['origin'];
				$this->destination = $res['destination'];
				$this->text = $res['text'];
				$this->sid = $res['sid'];
				$this->action = $res['action'];
				$this->condition = $res['condition'];
				return $this;
			}
			else
				return false;
		}
		else
			//Multiple result
			return $this->mongo_db->where($filter)->get($this->collection);
	}
	
	function update()
	{
		$data = array(
						'origin' => $this->origin,
						'destination' => $this->destination,
						'text' => $this->text,
						'action' => $this->action,
						'condition' => $this->condition,
						'sid' => $this->sid
					);
		
		return $this->mongo_db->update($this->collection, $data);
	}
	
	function delete()
	{
		return $this->mongo_db->delete($this->collection, array('_id', $this->_id));
	}
	
	function getId()
	{
		return $this->_id->{'$id'};
	}
}