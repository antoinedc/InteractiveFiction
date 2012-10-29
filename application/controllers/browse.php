<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Browse extends CI_Controller {

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
		$this->load->model('mongo/links');
		$this->load->model('mongo/sessions');
	}
	
	function index()
	{
		$stories = $this->stories->selectAll();
		$s = array();
		foreach ($stories as $story)
			$s[] = array_merge($story['development'], array('_id' => $story['_id']->{'$id'}));
		
		$s = array_reverse($s);
		
		$data_browse = array(
			'baseUrl' => base_url(),
			'stories' => $s
		);
		
		$data_layout = array(
			'baseUrl' => base_url(),
			'location' => 'Browse',
			'mainContent' => $this->parser->parse('private/browse.html', $data_browse, TRUE)
		);
		
		$this->parser->parse('private/layout.html', $data_layout);
	}
	
	function story($sid, $pid = 0, $lid = 0)
	{
		if (!$this->session->userdata('uid'))
			redirect('home?error=notLogged');
		if (!$sid)
			redirect('browse?error=noStory');
			
		$sessionId = $sid . '-1-' . $this->session->userdata('uid');
		
		$story = $this->stories->select(array('_id' => $sid), TRUE);
		
		$mainCharacter = $story->getMainCharacter();
		
		$session = $this->sessions->select($sessionId);
		if ($session && !$lid && !$pid)
		{
			$pid = $session->pid;
			$paragraph = $story->getParagraphById($pid);
		}
		else if (!$session && !$lid && !$pid)
		{
			$paragraph = $story->getFirstParagraph();
			$pid = $paragraph['_id']->{'$id'};
			$this->bookmark($sid, $pid, $sessionId);
		}
		else if ($session && $pid && $lid)
		{
			$paragraph = $story->getParagraphById($pid);
			$linkToGo = false;
			foreach ($paragraph['links'] as $el)
			{
				if ($el['_id']->{'$id'} == $lid)
				{
					$linkToGo = $el;
					$this->bookmark($sid, $linkToGo['destination'], $sessionId);
					break;
				}
			}
			
			$main_session_index = -1;
			for ($i = 0; $i < count($session->stats); $i++)
				if ($session->stats[$i]['main'] == true || $session->stats[$i]['main'] == 'true')
					$main_session_index = $i;
			
			foreach ($paragraph['links'] as $link)
			{
				if ($link['destination'] == $linkToGo['destination'])
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
			
			$paragraph = $story->getParagraphById($linkToGo['destination']);
		}
		else
			redirect(base_url() . 'index.php/browse/?error=noPid');
		
		$session = $this->sessions->select($sessionId);
		$main_session_index = -1;
			for ($i = 0; $i < count($session->stats); $i++)
				if ($session->stats[$i]['main'] == true || $session->stats[$i]['main'] == 'true')
					$main_session_index = $i;
					
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

			
		$endHtml = '<br /><br />----------------------<br />
					End of the story<br />
					<a href="' . base_url() . 'index.php/browse/restart/'. $sid . '">Go back at the begginning</a>';
		
		$restartHtml = '';
		
		if ($paragraph['isEnd'] == 'true') 
			$paragraph['text'] .= $endHtml;
		else
			$restartHtml = '<br /><br />----------------------<br />
							<a href="' . base_url() . 'index.php/browse/restart/'. $sid . '">Restart the story</a>';
		
		$session = $this->sessions->select($sessionId);
		$main_session_index = -1;
		
		for ($i = 0; $i < count($session->stats); $i++)
			if ($session->stats[$i]['main'] == true || $session->stats[$i]['main'] == 'true')
				$main_session_index = $i;
				
		$stats = array();
		while (list($key, $val) = each($session->stats[$main_session_index]['properties']))
			$stats[] = array('key' => $key, 'value' => $val);	
			
		$data_story = array(
			'notifications' => '',
			'title' => $story->title,
			'stats' => $stats,
			'baseUrl' => base_url(),
			'paragraph' => $paragraph['text'],
			'sid' => $sid,
			'links' => $paragraph['links'],
			'restart' => $restartHtml
		);
		
		$data_layout = array(
			'baseUrl' => base_url(),
			'location' => $story->title,
			'mainContent' => $this->parser->parse('private/reader.html', $data_story, TRUE)
		);
		
		$this->parser->parse('private/layout.html', $data_layout);
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
	
	function restart($sid)
	{
		if (!$this->session->userdata('uid'))
			redirect('home?error=notLogged');
		if (!$sid)
			redirect('browse?error=noStory');
			
		$session = $this->sessions->select($sid . '-1-' . $this->session->userdata('uid'));
		$session->delete();
		redirect('browse/story/' . $sid);
	}
}