<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Sessions extends CI_Model {

	private $database;
	private $_collection;
	
	private $_id;
	
	/**Sessions id**
	***0: user logged
	***1: user not logged (cookie)
	***2: mxit user
	*****************/
	var $sessionid;
	var $sid;
	var $pid;
	var $stats;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->library('session');
		
		$this->database = 'local';
		$this->collection = 'sessions';
	}
	
	function insert()
	{
		$data = array(
			'sessionid' => $this->sessionid,
			'sid' => $this->sid,
			'pid' => $this->pid,
			'stats' => $this->stats
		);
		
		return $this->mongo_db->select($this->database)->insert($this->collection, $data);
	}
	
	function select($sessionId)
	{
		$session = $this->mongo_db->where('sessionid', $sessionId)->get($this->collection);
		
		if (count($session))
		{
			$session = $session[0];
			$this->_id = new MongoId($session['_id']->{'$id'});
			$this->sessionid = $session['sessionid'];
			$this->sid = $session['sid'];
			$this->pid = $session['pid'];
			$this->stats = $session['stats'];
			
			return $this;
		}			
		else
			return false;
	}
	
	function update()
	{
		$data = array(
				'sessionid' => $this->sessionid,
				'sid' => $this->sid,
				'pid' => $this->pid,
				'stats' => $this->stats
			);

		return $this->mongo_db->where('_id', new MongoId($this->_id))->update($this->collection, $data);
	}
	
	function delete()
	{
		return $this->mongo_db->delete($this->collection, array('_id', $this->_id));
	}
}