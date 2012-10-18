<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Tests extends CI_Controller {

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
	
	function index()
	{
		
		$data_import_tests = array(
		
		);
		
		$data_layout = array(
			'baseUrl' => base_url(),
			'location' => 'Import a Lonewolf story',
			'mainContent' => $this->parser->parse('private/tests.html', $data_import_tests, TRUE)
		);
		
		$this->parser->parse('private/layout.html', $data_layout);
	}
}