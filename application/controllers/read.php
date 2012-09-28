<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Read extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('amazon-sdk/0.1.8');
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->helper('url');
		$this->load->helper('file');
		$this->load->helper('cookie');
		
		$this->load->library('session');
		$this->load->library('mongo_db');
		$this->load->library('parser');
		
		$this->load->model('mongo/stories');
		$this->load->model('mongo/sessions');
	}
	
	function index()
	{
	}
	
	function story($sid, $pid, $sessionId = '')
	{
		if (!isset($sid) || empty($sid) || !isset($pid) || empty($pid))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		if (empty($sessionId) && $this->session->userdata('uid'))
			$sessionId = $sid . '-0-' . $this->session->userdata('uid');
			
		$story = $this->stories->select(array('_id' => $sid));
		$paragraph = false;
		$status = 0;
		
		foreach($story->paragraphes as $p)
			if ($p['_id']->{'$id'} == $pid)
			{
				$paragraph = $p;
				$status = 1;
				break;
			}
		
		
		if (isset($sessionId))
			$this->bookmark($sid, $pid, $sessionId);
		echo json_encode(array_merge($paragraph, array('status' => $status)));
	}
	
	function load($sid, $sessionId = '')
	{
		/**Priority**
		user logged
		mxit user
		user not logged		
		/************/
		
		if (empty($sessionId))
			$sessionId = $sid . '-0-' . $this->session->userdata('uid');
			
		if ($sessionId)
		{
			$session = $this->sessions->select($sessionId);
			if (!empty($session))
			{
				$story = $this->stories->select(array('_id' => $session->sid), true);
				if (!empty($story))
				{
					$paragraph = false;
					foreach ($story->paragraphes as $p)
						if ($p['_id']->{'$id'} == $session->pid)
						{
							$paragraph = $p;
							$status = 1;
							break;
						}
					
					if ($paragraph)
					{
						$stats = array();
						
						foreach ($session->stats[0] as $key => $value)
							$stats[] = array('key' => $key, 'value' => $value);
							
						echo json_encode(array(
							'status' => 1,						
							'session' => array(
								'text' => $paragraph['text'],
								'links' => $paragraph['links'],
								'stats' => $stats
							)
						));
					}
					else
						echo json_encode(array('status' => -1));
				}
				else
					echo json_encode(array('status' => -1));
			}
			else
				echo json_encode(array('status' => 0));
		}
		else
			echo json_encode(array('status' => -1));
	}
	
	private function bookmark($sid, $pid, $sessionId)
	{
		if ($sessionId)
		{
			//If the reader is logged
			$session = $this->sessions->select($sessionId);
			$story = $this->stories->select(array('_id' => $sid), true);
			
			if (empty($session))
			{
				//If the reader has just started the story
				$newSession = new Sessions;
				$newSession->sessionid = $sessionId;
				$newSession->sid = $sid;
				$newSession->pid = $pid;
				$newSession->stats = $story->characters;
				$newSession->insert();
			}
			else
			{
				//If the reader has already started this story
				$session->pid = $pid;
				$session->update();
			}
		}
	}
}