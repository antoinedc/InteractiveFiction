<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Edit extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->helper('url');
		
		$this->load->library('session');
		$this->load->library('parser');
		$this->load->library('mongo_db');
		
		$this->load->model('mongo/stories');
		$this->load->model('mongo/paragraphes');
		$this->load->model('mongo/links');
	}
	
	public function index()
	{
		if (!$this->session->userdata('email'))
			redirect('./home?error=notLogged');
			
		$title = $this->input->post('title');
		
		if (empty($title))
			redirect('writer/start?error=blankFields');
		
		$newStory = new Stories;
		$newStory->title = $title;
		$res = $newStory->insert();
		if (!$res)
			redirect('start/?error=existingTitle');
		else
			redirect('edit/story/' . $res);
	}	
	
	public function story($sid)
	{
		if (!$this->session->userdata('email'))
			redirect('./home?error=notLogged');
		
		if (!$sid)
			redirect('./home?error=noValidId');
			
		if (!$this->isOwnerOfTheStory($sid))
			redirect('./home/?error=notOwner');
			
		$story = $this->stories->select(array('_id' => $sid));
				
		if (!$story)
			redirect('./home/?error=emptyStory');
		
		$statsMain = array();
		$statsOthersChars = array();
		$temp = array();
		
		while (list($key, $val) = each($story->characters[0]))
			if ($key != '_id')
				$statsMain[] = array('key' => $key, 'value' => $val);
			
		for ($i = 1; $i < count($story->characters); $i++)
		{
			while (list($key, $val) = each($story->characters[$i]))
			{
				$temp = array_merge($temp, array($key => $val));
				$temp['_id'] = $i;
			}
			$statsOthersChars[] = $temp;
		}
		
		$data_edit_story = array(
									'baseUrl' => base_url(),
									'title' => $story->title,
									'sid' => $sid,
									'paragraphes' => $story->paragraphes,
									'paragraphesToLink' => $story->paragraphes,
									'mainCharStats' => array_reverse($statsMain),
									'statsOthersChars' => array_reverse($statsOthersChars)
								);
		
		$data_layout = array(
								'baseUrl' => base_url(),
								'location' => get_class($this),
								'mainContent' => $this->parser->parse('private/edit_story.html', $data_edit_story, TRUE)
							);
		
		$this->parser->parse('private/layout.html', $data_layout);
	}
	
	/**
	status codes:
		1: everything's good !
		-1: empty fields
		-2: unable to fetch the story to update (start value)
		-3: unable to update the start field of the story
		-4: text already present in this story
	**/
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

		if ($res)
		{
			$story = $this->stories->select(array('_id' => $sid));

			echo json_encode(array('status' => 1));	
			return;
		}
		else
			echo json_encode(array('status' => -4));	
	}
	
	/**
	status codes:
		1: everything's good !
		-1: empty fields
		-2: this link already exists
	**/
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
		
		if (!$res)
			echo json_encode(array('status' => -2));
		else
			echo json_encode(array('status' => 1));
	}
	
	function addCharProperties($cid = -1)
	{
		$properties = $this->input->post('properties');
		$sid = $this->input->post('sid');
		
		if (empty($sid) || empty($properties))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		$newProperties = array();
		foreach ($properties as $p)
			if (!empty($p['key']) && !empty($p['value']))
				$newProperties = array_merge(array($p['key'] => $p['value']), $newProperties);
		$story = $this->stories->select(array('_id' => $sid));
		echo $story->updateCharStats($cid, $newProperties);
	}
	
	function getCharProperties($cid)
	{
		$sid = $this->input->post('sid');
		$story = $this->stories->select(array('_id' => $sid));
		echo json_encode(array_merge(array('status'=> 1), $story->getCharacter($cid)));
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