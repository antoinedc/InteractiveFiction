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
		$this->load->library('parser');
		
		$this->load->model('mongo/stories');
	}
	
	function index()
	{
	}
	
	function html($sid)
	{
		try
		{
			if (!$this->session->userdata('email'))
			{
				echo json_encode(array('status' => -1));
				return;
			}
			
			$story = $this->stories->select(array('_id' => $sid));
			if (!count($story->paragraphes))
			{
				echo json_encode(array('status' => -2));
				return;
			}
			
			$this->stories->goToProd(array('_id' => $sid));
			$return = array();
			
			$firstParagraph = $story->getFirstParagraph();			
			$bucketName = 'interactivefiction';
			$s3 = $this->awslib->get_s3();
			
			$baseDir = 'Stories/' . $sid . '/';
			
			/**Generate the javascript**/
			$js_base = $baseDir . 'js/loader.js';
			$data_layout_js = array(
				'variables' => array(
					array(
						'key' => 'BASE_URL',
						'value' => base_url()
					),
					array(
						'key' => 'sid',
						'value' => $sid
					),
					array(
						'key' => 'firstPid',
						'value' => $firstParagraph['_id']->{'$id'}
					)
				),
			);
			
			$js_file = $this->parser->parse('generator/layout.js', $data_layout_js, TRUE);
		
			$res = $s3->create_object(
				$bucketName,
				$js_base,
				array(
					'body' => $js_file,
					'contentType' => 'text/javascript',
					'meta' => array(
						'owner' => $this->session->userdata('uid'),
						'sid' => $sid
					)
				)
			);
			/***************************/
			
			$result[] = array(
				'file' => $js_file,
				'status' => $res->status
			);
			
			/**Generate the HTML**/
			$html_base = $baseDir . 'html/index.html'; 
			$main = $story->getMainCharacter();
			$stats = array();
			
			if (isset($main['properties']))
				foreach ($main['properties'] as $key => $value)
					$stats[] = array('key' => $key, 'value' => $value);
					
			foreach ($firstParagraph['links'] as $i => $link)
				foreach ($link['condition'] as $condition)
				{
					$key = $condition['key'];
					if ($condition['operation'] == '0')
						if ( ($main['properties'][$key] < $condition['value'] && $condition['state'] == '0') || ( (!($main['properties'][$key] < $condition['value'])) && $condition['state'] == '1') )
							unset($firstParagraph['links'][$i]);
					if ($condition['operation'] == '1')
						if ( ($main['properties'][$key] <= $condition['value'] && $condition['state'] == '0') || ( (!($main['properties'][$key] <= $condition['value'])) && $condition['state'] == '1') )
							unset($firstParagraph['links'][$i]);
					if ($condition['operation'] == '2')
						if ( ($main['properties'][$key] == $condition['value'] && $condition['state'] == '0') || ( (!($main['properties'][$key] == $condition['value'])) && $condition['state'] == '1') )
							unset($firstParagraph['links'][$i]);
					if ($condition['operation'] == '3')
						if ( ($main['properties'][$key] >= $condition['value'] && $condition['state'] == '0') || ( (!($main['properties'][$key] >= $condition['value'])) && $condition['state'] == '1') )
							unset($firstParagraph['links'][$i]);
					if ($condition['operation'] == '4')
						if ( ($main['properties'][$key] > $condition['value'] && $condition['state'] == '0') || ( (!($main['properties'][$key] > $condition['value'])) && $condition['state'] == '1') )
							unset($firstParagraph['links'][$i]);				
					if ($condition['operation'] == '5')
						if ( ($main['properties'][$key] != $condition['value'] && $condition['state'] == '0') || ( (!($main['properties'][$key] != $condition['value'])) && $condition['state'] == '1') )
							unset($firstParagraph['links'][$i]);	
				}
				
			$data_layout_html = array(
				'title' => $story->title,
				'js_src' => '../js/loader.js',
				'css_src' => '../css/' . $sid . '.css',
				'notifications' => '',
				'paragraph' => $firstParagraph['text'],
				'links' => $firstParagraph['links'],
				'stats' => $stats
			);
			
			$html_file = $this->parser->parse('generator/layout.html', $data_layout_html, TRUE);
			
			$res = $s3->create_object(
				$bucketName,
				$html_base,
				array(
					'body' => $html_file,
					'contentType' => 'text/html',
					'meta' => array(
						'owner' => $this->session->userdata('uid'),
						'sid' => $sid
					)
				)
			);
			/*********************/
			
			$result[] = array(
				'file' => $html_file,
				'status' => $res->status
			);
			
			echo json_encode(array(
								'status' => $res->status == 200,
								'url' => $bucketName . '.s3-website-us-east-1.amazonaws.com/' . $html_base
							));
		} catch( Exception $e)
		{
			if (get_class($e) == "cURL_Exception")
				echo json_encode(array('status' => -3));
		}
	}
	
	function mxit($sid)
	{
		$story = $this->stories->select(array('_id' => $sid));
		if (!count($story->paragraphes))
		{
			echo json_encode(array('status' => -2));
			return;
		}
		$res = $this->stories->goToProd(array('_id' => $sid));
		echo json_encode(array('status' => 1));
	}
	
	function stateMachine($sid)
	{
		$story = $this->stories->select(array('_id' => $sid));
		require_once 'Image/GraphViz.php';
		
		$graph = new Image_GraphViz();
		$graph->addAttributes(array('rankdir' => 'LR')); 
		foreach ($story->paragraphes as $paragraph)
			$graph->addNode($paragraph['_id']->{'$id'}, array(
				'url' => 'url',
				'label' => 'label',
			));
		foreach ($story->paragraphes as $paragraph)
			foreach ($paragraph['links'] as $link)
				$graph->addEdge(array($link['origin'] => $link['destination']));
		
		$graph->image('svg');	
	}
	
}