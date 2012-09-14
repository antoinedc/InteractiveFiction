<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mxit extends CI_Controller {

	var $key;
	var $secret;
	var $api;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('amazon-sdk/0.1.8');
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->helper('url');
		
		$this->load->library('session');
		$this->load->library('mongo_db');
		
		$this->load->model('mongo/stories');
		$this->load->model('mongo/paragraphes');
		require_once('MxitAPI.php');
		$this->key = '98df9cbc06234948bfc34d0069027859';
		$this->secret = 'a0c9ebd05bdb4815b33729703feb2d0c';
		
	}
	
	function index()
	{
		try
		{
			$this->api = new MxitAPI($this->key, $this->secret);
			
			if (isset($_GET) && isset($_GET['code'])) 
			{
				echo "Choose a story: <br />";
				
				$stories = $this->stories->selectAll();
				
				
				echo "<ul>";
				foreach ($stories as $story)
				{
					$id = $story['_id']->{'$id'};
					$story = $story['production'];
					if (!empty($story))
						echo '<li><a href="' . base_url() . 'index.php/mxit/read/?code=' . $_GET['code'] . '&sid=' . $id . '">' . $story['title'] . '</a></li>';
				}
				echo "</ul>";
			}
			else
				$this->api->request_access(base_url() . 'index.php/mxit', 'profile/public profile/private');
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	
	function read()
	{
		try
		{
			if (isset($_GET) && isset($_GET['code'])) 
			{
				$sid = $_GET['sid'];
				
				$pid = $this->input->get('pid');
				$story = $this->stories->select(array('_id' => $sid), true);
				
				$baseLink = base_url() . 'index.php/mxit/read/?code=' . $_GET['code'] . '&sid=' . $story->getId();
				
				if ($pid == '')
					$pid = $story->start;
				
				foreach ($story->paragraphes as $p)
				{
					if ($p['_id']->{'$id'} == $pid)
					{
						$paragraph = $p;
						break;
					}
				}
				
				echo $paragraph['text'];
				echo "<br /><br />";
	
				foreach($paragraph['links'] as $link)
				{
					
					if (!empty($link))
						echo '<a href="' . $baseLink . ($link['destination'] == $story->start ? '' : '&pid=' . $link['destination']) . '">' . $link['text']. '</a><br />';			
				}
				
				if ($paragraph['isEnd'] == 'true')
					echo '<br /><br />-------------------<br />End of the story !<br /><a href="' . $baseLink . '">Go back at the beginning</a>';
						
			}
			else
				$this->api->request_access(base_url() . 'index.php/mxit', 'profile/public');
		} catch (Exception $e) {
			echo $e->getMessage();
		}
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
			
		$session = $this->sessions->select($sid . '-2-' . $this->session->userdata('uid'));
		$session->delete();
		redirect('browse/story/' . $sid);
	}
}
	