<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mxit extends CI_Controller {

	var $key;
	var $secret;
	var $api;
	var $code;
	var $sessionId;
	var $mxit_user_id;
	var $sid;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('amazon-sdk/0.1.8');
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->helper('url');
		
		$this->load->library('session');
		$this->load->library('mongo_db');
		
		$this->load->model('mongo/stories');
		$this->load->model('mongo/sessions');
		$this->load->model('mongo/paragraphes');
		require_once('MxitAPI.php');
		$this->key = '99e8761245b4475399c118e0baac998e';
		$this->secret = '146a86c8efc245c39d9ba95952e735f0';
		if (!function_exists('apache_request_headers')) { 
			eval(' 
				function apache_request_headers() { 
					foreach($_SERVER as $key=>$value) { 
						if (substr($key,0,5)=="HTTP_") { 
							$key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5))))); 
							$out[$key]=$value; 
						} 
					} 
					return $out; 
				} 
			'); 
		} 
		$headers = apache_request_headers();
/*
To uncomment for prod, use to force to access the app through mxit			
		if (!isset($headers['X-Mxit-USERID-R']))
		{
			echo "Unauthorized access to the application.";
			return;
		}
*/						
		$this->mxit_user_id = isset($headers['X-Mxit-USERID-R']) ? $headers['X-Mxit-USERID-R'] : '123456' ;
	}
	
	function index()
	{
		try
		{
			$this->api = new MxitAPI($this->key, $this->secret);
			
			if (isset($_GET) && isset($_GET['code'])) 
			{
				echo "Choose a story: <br />";
				$this->session->set_userdata(array('code' => $_GET['code']));
				
				$this->code = $_GET['code'];
			
				$stories = $this->stories->selectAll();
				
				echo "<ul>";
				foreach ($stories as $story)
				{
					$id = $story['_id']->{'$id'};
					$story = $story['production'];
					if (!empty($story))
						echo '<li><a href="' . base_url() . 'index.php/mxit/read/?code=' . $this->code . '&sid=' . $id . '">' . $story['title'] . '</a></li>';
				}
				echo "</ul>";
				
			}
			else
				$this->api->request_access(base_url() . 'index.php/mxit', 'profile/public');
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
	
	function read()
	{
		try
		{
			if (isset($_GET) && isset($_GET['code']) && !empty($_GET['code'])) 
			{
				$sid = $_GET['sid'];
				$this->sid = $sid;
				$pid = $this->input->get('pid');
				
				$headers = apache_request_headers();
				
				if (isset($headers['X-Mxit-User-Input']))
				{
					$this->handle_user_input($headers['X-Mxit-User-Input']);
				}
				
				$story = $this->stories->select(array('_id' => $sid), true);
				
				$this->sessionId = $sid . '-3-' . $this->mxit_user_id;
				$session = $this->sessions->select($this->sessionId);
				
				if (empty($session))
				{
					$firstParagraph = $story->getFirstParagraph();
					$firstPid = $firstParagraph['_id']->{'$id'};
					$this->bookmark($sid, $firstPid, $this->sessionId);
					$session = $this->sessions->select($this->sessionId);
					
					if ($session)
						$currentParagraph = $story->getParagraphById($session->pid);
					else
					{
						echo 'Bookmarking failed';
						return;
					}
				}
				else
				{
					$session = $this->sessions->select($this->sessionId);
					if ($pid)
						$this->bookmark($sid, $pid, $this->sessionId);
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
					if ($p['_id']->{'$id'} == $session->pid)
					{
						$paragraph = $p;
						$status = 1;
						break;
					}
				
				$baseLink = base_url() . 'index.php/mxit/read/?code=' . $_GET['code'] . '&sid=' . $story->getId();
				
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
	
	private function handle_user_input($user_input)
	{
		if ($user_input == 'menu')
		{
			$this->return_to_menu();
		}
		
		else if ($user_input == 'stats')
		{
			$this->display_stats($this->sid . '-3-' . $this->mxit_user_id);
			die();
		}
		
		else if ($user_input == 'reset')
		{
			$this->restart($this->sid);
		}
		
		else if ($user_input == 'story')
		{
			return;
		}
		
		else if ($user_input == 'help')
		{
			die('List of available commands:<br />
				<ul>
					<li>menu: Go to the list of available stories</li>
					<li>reset: Restart the current story</li>
					<li>stats: Display stats of the current story</li>
				</ul>'
			);
		}
		
		else
		{
			die(
				'Uknown command, list of available commands:<br />
				<ul>
					<li>menu: Go to the list of available stories</li>
					<li>reset: Restart the current story</li>
					<li>stats: Display stats of the current story</li>
				</ul>'
			);
		}
	}
	
	private function return_to_menu()
	{
		redirect(base_url() . 'index.php/mxit/?code=' . $this->code);
	}
	
	private function display_stats($sessionId)
	{
		$session = $this->sessions->select($sessionId);
		$main_session_index = -1;
		
		for ($i = 0; $i < count($session->stats); $i++)
			if ($session->stats[$i]['main'] == true || $session->stats[$i]['main'] == 'true')
				$main_session_index = $i;
		
		$stats = array();
		while (list($key, $val) = each($session->stats[$main_session_index]['properties']))
			$stats[] = array('key' => $key, 'value' => $val);	
		
		echo '<table border="1">';
		echo '<tr><th>Your stats:</th></tr>';
		foreach ($stats as $el)
		{
			echo '<tr><td>' . $el['key'] . ':</td><td>' . $el['value'] . '</td></tr>';
		}
		echo '</table>';
	}
	
	private function bookmark($sid, $pid, $sessionId)
	{
		if ($this->sessionId)
		{
			//If the reader is logged
			$session = $this->sessions->select($this->sessionId);
			$story = $this->stories->select(array('_id' => $sid), true);
			
			if (empty($session))
			{
				//If the reader has just started the story
				$newSession = new Sessions;
				$newSession->sessionid = $this->sessionId;
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
	
	private function restart($sid)
	{	
		$session = $this->sessions->select($sid . '-3-' . $this->mxit_user_id);
		
		if ($session)
		{
			$session->delete();
			redirect(base_url() . 'index.php/mxit/read/?code=' . $this->session->userdata('code') . '&sid=' . $sid);
		}
		
	}
}
	