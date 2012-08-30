<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Generate extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('amazon-sdk/0.1.8');
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->helper('url');
		$this->load->helper('file');
		
		$this->load->library('session');
		$this->load->library('mongo_db');
		
		$this->load->model('mongo/stories');
	}
	
	function index()
	{
	}
	
	function html($sid)
	{
		if (!$this->session->userdata('email'))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		$writeLocally = false;
		$writeOnS3 = true;
		$res = 0;
		$return = array();
		$story = $this->stories->select($sid);
		$story = $story[0];
		
		$path = 'stories/html/' . $story['title'] . '/';
		if (!file_exists($path))
			mkdir($path);
		
		$baseDir = $this->session->userdata('uid') . '/Stories/' . $sid . '/html/';
		var_dump($story);
		foreach ($story['paragraphes'] as $paragraph)
		{
			$content = $paragraph['text'] . '<br /><br />';
			$pid = $paragraph['_id']->{'$id'};
			$filename = $pid . '.html';
			if ($paragraph['isStart'] == 'true')
			{
				$filename = 'index.html';
				$start = $pid;
			}
			
			foreach($paragraph['links'] as $link)
			{
				if (!empty($link))
					$content .= '<a href="' . ($link['destination'] == $start ? 'index.html' : $link['destination'] . '.html') . '">' . $link['text']. '</a><br />';			
			}
			
			if ($paragraph['isEnd'] == 'true')
				$content .= '<br /><br />-------------------<br />End of the story !<br /><a href="index.html">Go back at the beginning</a>';
			
			//Writing locally
			if ($writeLocally)
				$res = write_file($path . $filename, $content);
			
			//Writing on S3
			if ($writeOnS3)
			{
				$s3 = $this->awslib->get_s3();
				$bucketName = 'interactivefiction';
				$dir = $baseDir . $filename;
				$res = $s3->create_object(
					$bucketName,
					$dir,
					array(
						'body' => $content,
						'contentType' => 'text/html',
						'meta' => array(
									'owner' => $this->session->userdata('uid'),
									'sid' => $sid
						)
					)
				);
							
			}
			
			$return[] = array($filename => $res);
		}
		echo json_encode(array(
							'filesStatus' => $return,
							'url' => $bucketName . '.s3-website-us-east-1.amazonaws.com/' . $baseDir . 'index.html'
						));
	}
	
	function s3()
	{
		$data = array('salut' => 'coucou', array(1 => 'test'));
		$obj = (object) $data;
		$s3 = $this->awslib->get_s3();
				$bucketName = 'interactivefiction';
				$dir = 'test';
				$s3->create_object(
					$bucketName,
					'test',
					array($this)
				);
	}
}