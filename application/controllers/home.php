<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('amazon-sdk/0.1.8');
		
		$this->load->helper('assets');
		$this->load->helper('url');
		
		$this->load->library('session');
		$this->load->library('parser');
		$this->load->library('encrypt');
		
		$this->load->model('users');
	}
	
	public function index()
	{
		$location = get_class($this);
		
		$visibility = ($this->session->userdata('email') ? 'private/' : 'public/');
		
		$data = array(
						'baseUrl' => base_url(),
						'location' => $location,
						'mainContent' => $this->parser->parse($visibility . 'home.html', array('baseUrl' => base_url()), TRUE)
					);
					
		$this->parser->parse($visibility . 'layout.html', $data);
	}
	
	public function signin()
	{
		if (!$this->session->userdata('email'))
		{
			$email = $this->input->post('email');
			$password = $this->input->post('pass');
			
			if  (empty($email) || empty($password))
				redirect('home/?error=blankFields');
			
			$user = $this->users->select('SELECT * FROM Users WHERE email = "' . $email . '" AND password = "' . $this->encrypt->sha1($password) . '"');
			
			if (!empty($user->body->SelectResult))
			{
				$attributes = array();
				
				$attributes['Name'] = (string)$user->body->SelectResult->Item->Name;
				foreach ($user->body->SelectResult->Item->Attribute as $result)
					$attributes[(string)$result->Name] = (string)$result->Value;
				
				$this->session->set_userdata(array(
														'email' => $attributes['email'],
														'oid' => $attributes['Name']
												));
			}
			else
				redirect('home/?error=wrongCreds');
		}
		
		redirect('home');
	}
	
	public function logout()
	{
		if ($this->session->userdata('email'))
			$this->session->sess_destroy();

		redirect('home');
	}
	
	public function register()
	{
		$email = $this->input->post('email');
		$password = $this->input->post('pass');
		$confpass = $this->input->post('confpass');
		
		if (empty($email) || empty($password) || empty($confpass))
			redirect('home/?error=blankFields');
	
		if ($password != $confpass)
			redirect('home/?error=wrongConf');
		
		$newUser= new Users;
		$newUser->email = $email;
		$newUser->password = $this->encrypt->sha1($password);
		$newUser->insert();
		
		$this->session->set_userdata(array('email' => $email));
		
		redirect('home');
	}
}