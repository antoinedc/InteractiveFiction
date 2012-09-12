<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Read extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('amazon-sdk/0.1.8');
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->helper('url');
		$this->load->helper('file');
		
		$this->load->library('session');
		$this->load->library('mongo_db');
		$this->load->library('parser');
		
		$this->load->model('mongo/stories');
	}
	
	function index()
	{
	}
	
	function story($sid, $pid)
	{
		if (!isset($sid) || empty($sid) || !isset($pid) || empty($pid))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		$story = $this->story->select(array('_id' => $sid));
		$paragraph = false;
		
		foreach($story->paragraph as $p)
			if ($p['_id']->{'$id'} == $pid)
			{
				$paragraph = $p;
				break;
			}
		
		echo json_encode($paragraph);
	}
}