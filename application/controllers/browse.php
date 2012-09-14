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
	
	function story($sid, $pid = 0)
	{
		if (!$this->session->userdata('uid'))
			redirect('home?error=notLogged');
		if (!$sid)
			redirect('browse?error=noStory');
			
		$sessionId = $sid . '-1-' . $this->session->userdata('uid');
		
		$story = $this->stories->select(array('_id' => $sid), TRUE);
		
		$session = $this->sessions->select($sessionId);
		if ($session && !$pid)
			$pid = $session->pid;	
			
		if ($pid)
			$paragraph = $story->getParagraphById($pid);
		else
			$paragraph = $story->getFirstParagraph();
			
		if (!$paragraph)
			redirect(base_url() . 'index.php/browse/story/' . $sid . '/?error=noPid');
		
		if ($pid && $sessionId)
			$this->bookmark($sid, $pid, $sessionId);
			
		$endHtml = '<br /><br />----------------------<br />
					End of the story<br />
					<a href="' . base_url() . 'index.php/browse/restart/'. $sid . '">Go back at the begginning</a>';
		
		$restartHtml = '';
		
		if ($paragraph['isEnd'] == 'true') 
			$paragraph['text'] .= $endHtml;
		else
			$restartHtml = '<br /><br />----------------------<br />
							<a href="' . base_url() . 'index.php/browse/restart/'. $sid . '">Restart the story</a>';
		
		$data_story = array(
			'notifications' => '',
			'title' => $story->title,
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