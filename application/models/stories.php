<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class Stories extends CI_Model {

	private $_sdb;
	
	private $itemName;
	var $title;
	var $start;
	private $owner;
	var $paragraphes;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->library('session');
		
		$this->load->spark('amazon-sdk/0.1.8');
		
		$this->load->model('paragraphes');
		
		$this->_sdb = $this->awslib->get_sdb();
		$this->domain = 'Stories';
	}
	
	function insert()
	{
		$this->itemName = uniqid();
		$attributes = array(
								'title' => $this->title,
								'start' => $this->start,
								'owner' => $this->session->userdata('oid')
							);
		$putExists['title'] = array('exists' => 'false');
		
		return $this->_sdb->putAttributes($this->domain, $this->itemName, $attributes, $putExists);
	}
	
	function select($query)
	{
		$res = $this->_sdb->select($query);
		if (empty($res->body->SelectResult->Item))
			return false;
		
		$stories = array();
		
		foreach ($res->body->SelectResult->Item as $story)
		{
			$newStory = array();
			$newStory['itemName'] = $story->Name;
			$newStory['title'] = $story->Attribute[0]->Value;
			$newStory['start'] = $story->Attribute[1]->Value;
			$newStory['owner'] = $story->Attribute[2]->Value;
			
			$paragraphes = $this->_sdb->select('SELECT * FROM Paragraphes WHERE sid = "' . $story->Name . '"');	
			
			$newStory['paragraphes'] = array();
			if (!empty($paragraphes->body->SelectResult->Item))
			{
				foreach ($paragraphes->body->SelectResult->Item as $item)
				{
					$newParagraph = array();
					$newParagraph['Name'] = (string)$item->Name;
					foreach($item->Attribute as $attribute)
						$newParagraph[(string)$attribute->Name] = (string)$attribute->Value;
					
					$newStory['paragraphes'][] = $newParagraph;
				}
			}
			$stories[] = $newStory;
		}
			
		return $stories;
	}
	
	function selectBySid($sid)
	{
		if (!$sid)
			return false;
			
		$res = $this->_sdb->select('SELECT * FROM Stories WHERE itemName() = "' . $sid . '"');
		
		if (empty($res->body->SelectResult->Item))
			return false;
			
		$this->itemName = $res->body->SelectResult->Item->Name;
		$this->title = $res->body->SelectResult->Item->Attribute[0]->Value;
		$this->start = $res->body->SelectResult->Item->Attribute[1]->Value;
		$this->owner = $res->body->SelectResult->Item->Attribute[2]->Value;
		
		$paragraphes = $this->_sdb->select('SELECT * FROM Paragraphes WHERE sid = "' . $this->itemName . '"');	
		$links = $this->_sdb->select('SELECT * FROM Links WHERE sid = "' . $this->itemName . '"');
		
		$this->paragraphes = array();
		if (!empty($paragraphes->body->SelectResult->Item))
		{
			foreach ($paragraphes->body->SelectResult->Item as $item)
			{
				$newParagraph = array();
				$newParagraph['Name'] = (string)$item->Name;
				$newParagraph['link'] = array();
				foreach($item->Attribute as $attribute)
					$newParagraph[(string)$attribute->Name] = (string)$attribute->Value;
				if (!empty($links->body->SelectResult->Item))
				{
					foreach($links->body->SelectResult->Item as $link)
					{
						$newLink = array();
						if ((string)$link->Attribute[1]->Value == (string)$item->Name)
						{
							foreach($link->Attribute as $attribute)
								$newLink[(string)$attribute->Name] = (string)$attribute->Value;
							$newParagraph['link'][] = $newLink;
						}
					}					
				}
				$this->paragraphes[] = $newParagraph;
			}
		}
			
		return $this;
	}
	
	function update()
	{
		$attributes = array(
								'title' => $this->title,
								'start' => $this->start
							);
		$putExists['title'] = array('exists' => 'false');
		
		return $this->_sdb->putAttributes($this->domain, $this->itemName, $attributes, $putExists);
	}
	
	function delete($conditions = array())
	{
		return $this->_sdb->delete($this->domain, $this->itemName, null, $conditions);
	}
	
	function getItemName()
	{
		return $this->itemName;
	}
	
	function getOwner()
	{
		return $this->owner;
	}
}