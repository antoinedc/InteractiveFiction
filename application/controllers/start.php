<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Start extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->helper('url');
		
		$this->load->library('session');
		$this->load->library('parser');
	}
	
	public function index()
	{
		$location = get_class($this);
		
		if (!$this->session->userdata('email'))
			redirect('./home');
		
		$data = array(
						'baseUrl' => base_url(),
						'location' => $location,
						'mainContent' => $this->parser->parse('private/start.html', array('baseUrl' => base_url()), TRUE)
					);
					
		$this->parser->parse('private/layout.html', $data);
	}
}