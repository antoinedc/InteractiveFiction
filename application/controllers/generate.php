<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Generate extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('amazon-sdk/0.1.8');
		
		$this->load->helper('assets');
		$this->load->helper('url');
		$this->load->helper('file');
		
		$this->load->library('session');
		$this->load->library('parser');
		
		$this->load->model('stories');
	}
	
	function index()
	{
	}
	
	function html($sid)
	{
		$story = $this->stories->selectBySid($sid);
		
		$path = 'stories/html/' . $story->title . '/';
		if (!file_exists($path))
			mkdir($path);
		
		foreach ($story->paragraphes as $paragraph)
		{
			$content = $paragraph['text'] . '<br /><br />';
			
			$filename = $paragraph['Name'] . '.html';
			if ($paragraph['isStart'] == 'true')
			{
				$filename = 'index.html';
				$start = $paragraph['Name'];
			}
			
			foreach($paragraph['link'] as $link)
			{
				if (!empty($link))
					$content .= '<a href="' . ($link['destination'] == $start ? 'index.html' : $link['destination'] . '.html') . '">' . $link['text']. '</a><br />';			
			}
			
			if ($paragraph['isEnd'] == 'true')
				$content .= '<br /><br />-------------------<br />End of the story !<br /><a href="index.html">Go back at the beginning</a>';
			
			$res = write_file($path . $filename, $content);
			if ($res)
				echo $filename . " generated!<br />";
		}
	}
}