<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Edit extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('amazon-sdk/0.1.8');
		
		$this->load->helper('assets');
		$this->load->helper('url');
		
		$this->load->library('session');
		$this->load->library('parser');
		$this->load->library('encrypt');
		
		$this->load->model('stories');
		$this->load->model('paragraphes');
		$this->load->model('links');
	}
	
	public function index()
	{
		if (!$this->session->userdata('email'))
			redirect('./home');
			
		$title = $this->input->post('title');
		
		if (empty($title))
			redirect('writer/start?error=blankFields');
		
		$newStory = new Stories;
		$newStory->title = $title;
		$newStory->insert();
		redirect('edit/story/' . $newStory->getItemName());
	}	
	
	public function story($sid)
	{
		if (!$this->session->userdata('email') || !$sid)
			redirect('./home');
			
		if (!$this->isOwnerOfTheStory($sid))
			redirect('./home/?error=notOwner');
			
		$story = $this->stories->select('SELECT * FROM Stories WHERE itemName() = "' . $sid . '"');
				
		if (!$story)
			redirect('./home/?error=emptyStory');
					
		$data_edit_story = array(
									'baseUrl' => base_url(),
									'title' => $story[0]['title'],
									'sid' => $sid,
									'paragraphes' => $story[0]['paragraphes'],
									'paragraphesToLink' => $story[0]['paragraphes']
							);
		
		$data_layout = array(
								'baseUrl' => base_url(),
								'location' => get_class($this),
								'mainContent' => $this->parser->parse('private/edit_story.html', $data_edit_story, TRUE)
							);
		
		$this->parser->parse('private/layout.html', $data_layout);
	}
	
	public function addParagraph()
	{
		$sid = $this->input->post('sid');
		if (!$this->isOwnerOfTheStory($sid))
		{
			echo json_encode(array('status' => -3));
			return;
		}
			
		$content = $this->input->post('content');
		$isStart = $this->input->post('isFirst');
		$isEnd = $this->input->post('isEnd');
		
		if (empty($sid) || empty($content) || empty($isStart) || empty($isEnd))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		$newParagraph = new Paragraphes;
		$newParagraph->text = nl2br($content);
		$newParagraph->sid = $sid;
		$newParagraph->isStart = $isStart;
		$newParagraph->isEnd = $isEnd;
		$res = $newParagraph->insert();	

		if ($res->status == 200)
		{
			$story = $this->stories->selectBySid($sid);

			if ($isStart)
			{
				$story = $this->stories->selectBySid($sid);
				
				if (!$story)
				{
					echo json_encode(array('status' => -2));
					return;
				}
				$story->start = $newParagraph->getItemName();
				$story->update();
			}
		}
		echo json_encode(array('status' => $res->status));		
	}
	
	function addLink()
	{
		$originId = $this->input->post('originid');
		$destId = $this->input->post('destid');
		$sid = $this->input->post('sid');
		$text = $this->input->post('text');
		
		if (empty($originId) || empty($destId) || empty($sid))
		{
			echo json_encode(array('status' => -1));
			return;
		}		
		
		$newLink = new Links;
		$newLink->origin = $originId;
		$newLink->destination= $destId;
		$newLink->sid = $sid;
		$newLink->text = $text;
		$res = $newLink->insert();
		
		echo json_encode(array('status' => $res->status));
	}
	
	private function isOwnerOfTheStory($sid)
	{
		/*$story = $this->stories->select('SELECT * FROM Stories WHERE itemName() = "' . $sid . '"');
		if (!$story)
			return false;
		if ($story->getOwner() != $this->session->userdata('oid'))
			return false;*/
		
		return true;
	}
}