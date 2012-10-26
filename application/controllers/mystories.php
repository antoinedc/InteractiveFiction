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
		if (!$this->session->userdata('uid'))
			redirect('./home?error=notLogged');
		
		$stories = $this->stories->select(array());
		
		$s = array();
		
		foreach ($stories as $story)
			$s[] = array_merge($story['development'], array('_id' => $story['_id']->{'$id'}));
		
		$s = array_reverse($s);
		
		$data_my_stories = array(
									'baseUrl' => base_url(),
									'stories' => $s
								);
		
		$data_layout = array(
								'baseUrl' => base_url(),
								'location' => 'My stories',
								'mainContent' => $this->parser->parse('private/my_stories.html', $data_my_stories, TRUE)
							);
		
		$this->parser->parse('private/layout.html', $data_layout);		
	
	}
}