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
		$session = '';
		$endText = '<br /><br />-----------------------------<br />This is the end of the story !<br /><a href="#" class="restart">Restart</a>';
		if (empty($sessionId) && $this->session->userdata('uid'))
			$sessionId = $sid . '-0-' . $this->session->userdata('uid');
		
		$story = $this->stories->select(array('_id' => $sid));

		$session = $this->sessions->select($sessionId);
		if (empty($session))
		{
			$firstParagraph = $story->getFirstParagraph();
			$firstPId = $firstParagraph['_id']->{'$id'};
			$this->bookmark($sid, $firstPId, $sessionId);
			$session = $this->sessions->select($sessionId);
			$currentParagraph = $story->getParagraphById($session->pid);
		}
		else
		{
			$session = $this->sessions->select($sessionId);
			$currentParagraph = $story->getParagraphById($session->pid);
		}
		
		$main_session_index = -1;
		for ($i = 0; $i < count($session->stats); $i++)
			if ($session->stats[$i]['main'] == true || $session->stats[$i]['main'] == 'true')
				$main_session_index = $i;
		
		foreach ($currentParagraph['links'] as $link)
		{
			if ($link['destination'] == $pid)
			{
				if (count($link['action']))
				{
					for ($i = 0; $i < count($link['action']); $i++)
					{
						$action = $link['action'][$i];
						
						$operation = $action['operation'];
						$key = $action['key'];
						$value = $action['value'];
						
						if ($operation == '0')
							$session->stats[$main_session_index]['properties'][$key] += $value;
						else if ($operation == '1')
							$session->stats[$main_session_index]['properties'][$key] -= $value;
						else if ($operation == '2')
							$session->stats[$main_session_index]['properties'][$key] *= $value;
						else if ($operation == '3')
							$session->stats[$main_session_index]['properties'][$key] /= $value;
					
						$session->update();
					}
					break;
				}	
			}
		}
		
		$paragraph = false;
		$status = 0;
		
		foreach ($story->paragraphes as $p)
			if ($p['_id']->{'$id'} == $pid)
			{
				$paragraph = $p;
				$status = 1;
				break;
			}
		
	/*	if ($paragraph['isEnd'] == true || $paragraph['isEnd'] == "true")
			$paragraph['text'] .= $endText;*/
		
		foreach ($paragraph['links'] as $i => $link)
			foreach ($link['condition'] as $condition)
			{
				$key = $condition['key'];
				if ($condition['operation'] == '0')
					if ( ($session->stats[$main_session_index]['properties'][$key] < $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] < $condition['value'])) && $condition['state'] == '1') )
						unset($paragraph['links'][$i]);
				if ($condition['operation'] == '1')
					if ( ($session->stats[$main_session_index]['properties'][$key] <= $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] <= $condition['value'])) && $condition['state'] == '1') )
						unset($paragraph['links'][$i]);
				if ($condition['operation'] == '2')
					if ( ($session->stats[$main_session_index]['properties'][$key] == $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] == $condition['value'])) && $condition['state'] == '1') )
						unset($paragraph['links'][$i]);
				if ($condition['operation'] == '3')
					if ( ($session->stats[$main_session_index]['properties'][$key] >= $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] >= $condition['value'])) && $condition['state'] == '1') )
						unset($paragraph['links'][$i]);
				if ($condition['operation'] == '4')
					if ( ($session->stats[$main_session_index]['properties'][$key] > $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] > $condition['value'])) && $condition['state'] == '1') )
						unset($paragraph['links'][$i]);				
				if ($condition['operation'] == '5')
					if ( ($session->stats[$main_session_index]['properties'][$key] != $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] != $condition['value'])) && $condition['state'] == '1') )
						unset($paragraph['links'][$i]);	
			}
				
		
		if (isset($sessionId))
			$this->bookmark($sid, $pid, $sessionId);
		echo json_encode(array_merge($paragraph, array('stats' => $session->stats[0]), array('status' => $status)));
	}
	
	function load($sid, $sessionId = '')
	{
		/**Priority**
		user logged
		mxit user
		user not logged		
		/************/
		
		if (empty($sessionId))
		{
			$sessionId = $sid . '-0-' . $this->session->userdata('uid');
		}
			
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
						
						$main_session_index = -1;
						for ($i = 0; $i < count($session->stats); $i++)
							if ($session->stats[$i]['main'] == true || $session->stats[$i]['main'] == 'true')
								$main_session_index = $i;

						foreach ($session->stats[$main_session_index]['properties'] as $key => $value)
							$stats = array_merge($stats, array($key => $value));

						foreach ($paragraph['links'] as $i => $link)
							foreach ($link['condition'] as $condition)
							{
								$key = $condition['key'];
								if ($condition['operation'] == '0')
									if ( ($session->stats[$main_session_index]['properties'][$key] < $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] < $condition['value'])) && $condition['state'] == '1') )
										unset($paragraph['links'][$i]);
								if ($condition['operation'] == '1')
									if ( ($session->stats[$main_session_index]['properties'][$key] <= $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] <= $condition['value'])) && $condition['state'] == '1') )
										unset($paragraph['links'][$i]);
								if ($condition['operation'] == '2')
									if ( ($session->stats[$main_session_index]['properties'][$key] == $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] == $condition['value'])) && $condition['state'] == '1') )
										unset($paragraph['links'][$i]);
								if ($condition['operation'] == '3')
									if ( ($session->stats[$main_session_index]['properties'][$key] >= $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] >= $condition['value'])) && $condition['state'] == '1') )
										unset($paragraph['links'][$i]);
								if ($condition['operation'] == '4')
									if ( ($session->stats[$main_session_index]['properties'][$key] > $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] > $condition['value'])) && $condition['state'] == '1') )
										unset($paragraph['links'][$i]);				
								if ($condition['operation'] == '5')
									if ( ($session->stats[$main_session_index]['properties'][$key] != $condition['value'] && $condition['state'] == '0') || ( (!($session->stats[$main_session_index]['properties'][$key] != $condition['value'])) && $condition['state'] == '1') )
										unset($paragraph['links'][$i]);	
							}
							
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
	
	public function deleteSession($sessionId)
	{
		$session = $this->sessions->select($sessionId);
		if (!$session)
		{
			echo json_encode(array('status' => 0));
			return;
		}
		$res = $session->delete();
		echo json_encode(array('status' => $res));
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
				//var_dump($story->characters);
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