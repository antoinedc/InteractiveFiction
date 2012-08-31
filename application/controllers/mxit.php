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
					echo '<li><a href="' . base_url() . 'index.php/mxit/read/?code=' . $_GET['code'] . '&sid=' . $story['_id']->{'$id'} . '">' . $story['title'] . '</a></li>';
				}
				echo "</ul>";
			}
			else
				$this->api->request_access(base_url() . 'index.php/mxit', 'graph/read');
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
				echo $_GET['sid'];
				$pid = $this->input->get('pid');
				$story = $this->stories->select($sid);
				$story = $story[0];
				
				$baseLink = base_url() . 'index.php/mxit/read/?code=' . $_GET['code'] . '&sid=' . $story['_id']->{'$id'};
				if ($pid == '')
					$paragraph = $this->paragraphes->select(array('_id' => $story['start']));
				else
					$paragraph = $this->paragraphes->select(array('_id' => $pid));
				
				echo $paragraph->text;
				echo "<br /><br />";
	
				foreach($paragraph->links as $link)
				{
					
					if (!empty($link))
						echo '<a href="' . $baseLink . ($link['destination'] == $paragraph->isStart ? '' : '&pid=' . $link['destination']) . '">' . $link['text']. '</a><br />';			
				}
				
				if ($paragraph->isEnd == 'true')
					echo '<br /><br />-------------------<br />End of the story !<br /><a href="' . $baseLink . '">Go back at the beginning</a>';
						
			}
			else
				$this->api->request_access(base_url() . 'index.php/mxit', 'profile/public');
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}
}
	