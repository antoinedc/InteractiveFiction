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
		
		if (count($story->characters) > 0)
		{	
			$main = $story->getMainCharacter();
			
			if (isset($main['properties']))
			{
				while (list($key, $val) = each($main['properties']))
					$statsMain[] = array('key' => $key, 'value' => $val);
			}
				
			for ($i = 0; $i < count($story->characters); $i++)
			{	
				if (isset($story->characters[$i]['properties']))
				{
					while (list($key, $val) = each($story->characters[$i]['properties']))
					{
						$temp = array_merge($temp, array($key => $val));
						$temp = array_merge($temp, array('_id' => $story->characters[$i]['_id']->{'$id'}));
					}
					
					if ($story->characters[$i]['main'] == 'false' || $story->characters[$i]['main'] == false)
						$statsOthersChars[] = $temp;
				}
			}
		}
		
		foreach ($story->paragraphes as $paragraph)
		{
			if (!isset($paragraph['title']))
				$paragraph['title'] = '';
		}
		
		$data_edit_story = array(
									'baseUrl' => base_url(),
									'srory_title' => $story->title,
									'sid' => $sid,
									'paragraphes' => $story->paragraphes,
									'paragraphesToLink' => $story->paragraphes,
									'mainCharStats' => array_reverse($statsMain),
									'propList' => array_reverse($statsMain),
									'statsOthersChars' => array_reverse($statsOthersChars)
								);
		
		$data_layout = array(
								'baseUrl' => base_url(),
								'location' => get_class($this),
								'mainContent' => $this->parser->parse('private/edit_story.html', $data_edit_story, TRUE)
							);
		
		$this->parser->parse('private/layout.html', $data_layout);
	}
	
	public function getLink($sid, $pid, $lid)
	{
		if (!$this->session->userdata('uid') || !$sid || !$pid || !$lid)
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		$story = $this->stories->select(array('_id' => $sid));
		$paragraph = $story->getParagraphById($pid);
		
		$res = array();
		
		foreach($paragraph['links'] as $link)
			if ($link['_id']->{'$id'} == $lid)
			{
				$res = $link;
				break;
			}
			
		echo json_encode(array('status' => 1, 'link' => $res));
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
		
		$story = $this->stories->select(array('_id' => $sid));
		
		if (!count($story->paragraphes)) 
			$isStart = true;
		
		$newParagraph = new Paragraphes;
		$newParagraph->text = nl2br($content);
		$newParagraph->title = '';
		$newParagraph->sid = $sid;
		$newParagraph->isStart = $isStart;
		$newParagraph->isEnd = $isEnd;
		$res = $newParagraph->insert();	

		if ($res)		
			echo json_encode(array('status' => 1, 'id' => $res));
		else
			echo json_encode(array('status' => -4));	
	}
	
	public function updateParagraph($sid)
	{
		if (!$this->session->userdata('uid'))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		$pid = $this->input->post('pid');
		$text = $this->input->post('text');
		$isFirst = $this->input->post('isFirst');
		$isEnd = $this->input->post('isEnd');
		
		if (!$sid || empty($text) || empty($pid))
		{
			echo json_encode(array('status' => 0));
			return;
		}
		
		$paragraph = $this->paragraphes->select(array('_sid' => $sid, '_pid' => $pid));
		$paragraph->text = $text;
		$paragraph->isStart = $isFirst;
		$paragraph->isEnd = $isEnd;
		$res = $paragraph->update();
		
		if ($res == 'true') $res = 1;
		else $res = 0;
		echo json_encode(array('status' => $res));
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
		$action = $this->input->post('action');
		$condition = $this->input->post('condition');
		$lid = $this->input->post('lid');
		
		if (empty($originId) || empty($destId) || empty($sid))
		{
			echo json_encode(array('status' => -1));
			return;
		}		
		
		if (!$lid)
		{
			$newLink = new Links;
			$newLink->origin = $originId;
			$newLink->destination = $destId;
			$newLink->sid = $sid;
			$newLink->text = $text;
			$newLink->action = (count($action) && $action ? $action : array());
			$newLink->condition = (count($condition) && $action ? $condition : array());
			$res = $newLink->insert();
		}
		else
		{
			$paragraph = $this->paragraphes->select(array('_sid' => $sid, '_pid' => $originId));
			$i = 0;
			foreach ($paragraph->links as $link)
			{
				if ($link['_id']->{'$id'} == $lid)
				{
					$paragraph->links[$i]['origin'] = $originId;
					$paragraph->links[$i]['destination'] = $destId;
					$paragraph->links[$i]['sid'] = $sid;
					$paragraph->links[$i]['text'] = $text;
					$paragraph->links[$i]['action'] = $action;
					$paragraph->links[$i]['condition'] = $condition;
					
					$paragraph->update();
					$res = $lid;
					break;
				}
				else
					$i++;
			}
		}
		
		if (!$res)
			echo json_encode(array('status' => -2));
		else
			echo json_encode(array('status' => 1, 'lid' => $res));
	}

	function addCharProperties($cid = -1)
	{
		$properties = $this->input->post('properties');
		$main = $this->input->post('main');
		$sid = $this->input->post('sid');
		
		if (empty($sid))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		$newProperties = array();
		if ($properties == "") $properties = array();
		
		foreach ($properties as $p)
			if (!empty($p['key']))
				$newProperties = array_merge(array($p['key'] => $p['value']), $newProperties);
		
		$story = $this->stories->select(array('_id' => $sid));
		
		$id = $story->updateCharStats($cid, array('main' => $main, 'properties' => $newProperties));
		
		if ($id)
			echo json_encode(array('status' => true, 'id' => $id));
		else
			echo json_encode(array('status' => false));
	}
	
	function getCharProperties($cid)
	{
		$sid = $this->input->post('sid');
		$story = $this->stories->select(array('_id' => $sid));
		echo json_encode(array_merge(array('status'=> 1), $story->getCharacter($cid)));
	}
	
	function removeLink($sid, $pid, $lid)
	{
		if (!$this->session->userdata('uid') || empty($lid) || empty($pid) || empty($sid))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		$story = $this->stories->select(array('_id' => $sid));
		
		for ($j = 0; $j < count($story->paragraphes); $j++)
			if ($story->paragraphes[$j]['_id']->{'$id'} == $pid)
				for ($i = 0; $i < count($story->paragraphes[$j]['links']); $i++)
					if ($story->paragraphes[$j]['links'][$i]['_id']->{'$id'} == $lid)
					{
						unset($story->paragraphes[$j]['links'][$i]);
						break;
					}
					
		$res = $story->update();
		echo json_encode(array('status' => 1));
	}
	
	function update()
	{
		$new_story = $this->input->post('story');
		
		if (!$this->session->userdata('uid') || !$new_story)
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		$new_story = json_decode($new_story);
		$new_story = $new_story->{'0'};
		
		foreach ($new_story->paragraphes as $paragraph)
		{
			$paragraph->_id = new MongoId($paragraph->id);
			unset($paragraph->id);
			foreach ($paragraph->links as $link)
			{
				$link->_id = new MongoId($link->id);
				unset($link->id);
			}
		}
		
		foreach ($new_story->characters as $character)
		{
			$character->_id = new MongoId($character->id);
			unset($character->id);
		}
		
		$sid = $new_story->id;
		$story = $this->stories->select(array('_id' => $sid));
		$story->title = $new_story->title;
		$story->start = $new_story->start;
		$story->paragraphes = $new_story->paragraphes;
		$story->characters = $new_story->characters;
		$res = $story->update();
		
		echo json_encode(array('status' => $res));
	}
	
	function getStory($sid)
	{
		if (!$this->isOwnerOfTheStory($sid))
		{
			echo json_encode(array('status' => -2));
			return;
		}
	
		if (!$this->session->userdata('uid'))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		echo json_encode(array('status' => 1, 'story' => $this->stories->select(array('_id' => $sid))));
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