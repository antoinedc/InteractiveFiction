<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mystories extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->helper('url');
		
		$this->load->library('session');
		$this->load->library('parser');
		$this->load->library('mongo_db');
		
		$this->load->model('mongo/stories');
	}
	
	function index()
	{
		if (!$this->session->userdata('email'))
			redirect('./home');
		
		$uid = $this->session->userdata('oid');
		
		$stories = $this->stories->select(array());
		
		$data_my_stories = array(
									'baseUrl' => base_url(),
									'stories' => $stories
								);
		
		$data_layout = array(
								'baseUrl' => base_url(),
								'location' => 'My stories',
								'mainContent' => $this->parser->parse('private/my_stories.html', $data_my_stories, TRUE)
							);
		
		$this->parser->parse('private/layout.html', $data_layout);		
	
	}
}