<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Stories extends CI_Model {

	private $database;
	private $collectionName;
	private $_collection;
	
	private $_id;
	var $type;
	var $sid;
	var $pid;
	var $stats;	
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->library('session');
		
		$this->database = 'local';
		$this->collectionName = 'sessions';
	}
	
	function insert($id = '')
	{
		$data = array(
			'_id' => (empty($id) ? new MongoId() : $id),
			'type' => $this->type,
			'sid' => $this->sid,
			'pid' => $this->pid,
			'stats' => $this->stats
		);
		
		return $this->mongo_db->select($this->database)->insert($this->collectionName, $data);
	}
	
	function select($sessionId)
	{
		$session = $this->mongo_db->where('_id', new MongoId($sessionId))->get($this->collectionName);
		
		if (count($session))
		{
			$session = $session[0];
			$this->_id = new MongoId($session['_id']);
			$this->type = $session['type'];
			$this->sid = $session['sid'];
			$this->pid = $session['pid'];
			$this->stats = $session['stats'];
			
			return $this;
		}			
		else
			return false;
	}
	
	function delete()
	{
		return $this->mongo_db->delete($this->collectionName, array('_id', $this->_id));
	}
}