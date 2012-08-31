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
		$isTextPresent = count($this->mongo_db->where(
														array('paragraphes' => 
															array('$elemMatch' =>
																array('text' => $this->text)
															)
														,
														'_id' => new MongoId($this->sid)
														)
													)->get('stories')
												);
		
		if ($isTextPresent) return false;
		
     	$data = array(
						'_id' => new MongoId(),
						'text' => $this->text,
						'sid' => new MongoId($this->sid),
						'isStart' => $this->isStart,
						'isEnd' => $this->isEnd,
						'links' => $this->links ? $this->links : array()
				);
		
		$res = $this->mongo_db->where('_id', new MongoId($this->sid))->push('paragraphes', $data)->update('stories');
		
		if ($this->isStart)
			$res2 = $this->mongo_db->where('_id', new MongoId($this->sid))->set(array('start' => new MongoId($data['_id'])))->update('stories');

		if (!$res)
			return false;
			
		return $res;
	}
	
	function select($filter)
	{
		//selectById - 1 result
		if (isset($filter['_id']) && $filter['_id'] != '')
		{
			$filter['_id'] = new MongoId($filter['_id']);
			
			$res = $this->mongo_db->where(array('paragraphes._id' => $filter['_id']))->select(array('paragraphes'))->get('stories');
			if ($res)
			{
				foreach ($res[0]['paragraphes'] as $p)
				{
					if ($p['_id']->{'$id'} == $filter['_id']->{'$id'})
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
				$this->text = $res['text'];
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
						'text' => $this->text,
						'sid' => $this->sid,
						'isStart' => $this->isStart,
						'isEnd' => $this->isEnd,
						'links' => $this->links
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