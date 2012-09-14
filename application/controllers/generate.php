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
		if (!$this->session->userdata('email'))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		$this->stories->goToProd(array('_id' => $sid));
		$return = array();
		
		$story = $this->stories->select(array('_id' => $sid));
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
		
		$data_layout_html = array(
			'title' => $story->title,
			'js_src' => '../js/loader.js',
			'css_src' => '../css/' . $sid . '.css',
			'notifications' => '',
			'paragraph' => $firstParagraph['text'],
			'links' => $firstParagraph['links']
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
							'filesStatus' => $return,
							'url' => $bucketName . '.s3-website-us-east-1.amazonaws.com/' . $html_base
						));
	}
	
	function mxit($sid)
	{
		$res = $this->stories->goToProd(array('_id' => $sid));
		echo json_encode(array('status' => 1));
	}
	
	function stateMachine($sid)
	{
		$story = $this->stories->select(array('_id' => $sid));
		require_once 'Image/GraphViz.php';
		$output = 
		'digraph finite_state_machine {
			rankdir=LR;
			size="8.5"
			node [shape = circle];';
			
		foreach ($story->paragraphes as $paragraph)
			foreach ($paragraph['links'] as $link)
				$output .= $link['origin'] . '->' . $link['destination'] . ';';
		
		$output .= '}';
		
		echo $output;
			
	}
	
}