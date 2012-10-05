<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Paragraphes extends CI_Model {

	private $collection;
	private $database;
	
	private $_id;
	var $text;
	var $sid;
	var $isStart;
	var $isEnd;
	var $links;
	var $idref;
	var $temp_links;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->Model('mongo/stories');
		
		$this->database = 'local';
		$this->collection = 'paragraphes';
	}
	
	function insert()
	{
		$data = array(
			'_id' => ($this->_id?$this->_id:new MongoId()),
			'text' => $this->text,
			'sid' => new MongoId($this->sid),
			'isStart' => $this->isStart,
			'isEnd' => $this->isEnd,
			'links' => $this->links ? $this->links : array()
		);
		
		$res = $this->mongo_db->where('_id', new MongoId($this->sid))->push('development.paragraphes', $data)->update('stories');
		
		if ($this->isStart == 'true')
			$res2 = $this->mongo_db->where('_id', new MongoId($this->sid))->set(array('development.start' => new MongoId($data['_id'])))->update('stories');

		if (!$res)
			return false;
			
		return $data['_id'];
	}
	
	function select($filter)
	{
		//selectById - 1 result
		if (isset($filter['_pid']) && $filter['_pid'] != '' && isset($filter['_sid']) && $filter['_sid'] != '')
		{
			$filter['_pid'] = new MongoId($filter['_pid']);
			$filter['_sid'] = new MongoId($filter['_sid']);
			
			$res = $this->mongo_db->where(array('development.paragraphes._id' => $filter['_pid'], '_id' => $filter['_sid']))->select(array('development.paragraphes'))->get('stories');
			
			if ($res)
			{
				foreach ($res[0]['development']['paragraphes'] as $p)
				{
					if ($p['_id']->{'$id'} == $filter['_pid']->{'$id'})
					{
						$res = $p;
						break;
					}
				}
			}
			else
				return false;
			
			if (count($res))
			{
				$this->_id = $res['_id'];
				$this->text = (isset($res['text'])?$res['text']:'');
				$this->sid = $res['sid'];
				$this->isStart = $res['isStart'];
				$this->isEnd = $res['isEnd'];
				$this->links = $res['links'];
				return $this;
			}
			else
				return false;
		}
		else
			//multiple results
			return $this->mongo_db->where($filter)->get($this->collection);
	}
	
	function update()
	{
		$data = array(
						'development.paragraphes.$.text' => $this->text,
						'development.paragraphes.$.sid' => $this->sid,
						'development.paragraphes.$.isStart' => $this->isStart,
						'development.paragraphes.$.isEnd' => $this->isEnd,
						'development.paragraphes.$.links' => $this->links
					);
		
		$paragraphes = $this->mongo_db->where(array('_id' => new MongoId($this->sid)))->select('development.paragraphes');
		
		return $this->mongo_db->where('development.paragraphes._id', new MongoId($this->_id))->set($data, array('false', 'true'))->update('stories');
	}
	
	function delete()
	{
		return $this->mongo_db->where('_id', new MongoId($this->_id))->delete($this->collection, array('_id', $this->_id));
	}
	
	function getId()
	{
		if (!$this->_id)
			$this->_id = new MongoId();
			
		return $this->_id->{'$id'};
	}
}