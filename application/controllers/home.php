<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->helper('assets');
		$this->load->helper('url');
		$this->load->helper('cookie');
		
		$this->load->library('session');
		$this->load->library('parser');
		$this->load->library('encrypt');
		$this->load->library('mongo_db');
		
		$this->load->model('mongo/users');
		$this->load->model('mongo/stories');
		$this->load->model('mongo/changelogs');
	}
	
	public function index()
	{
		$location = get_class($this);
		date_default_timezone_set('Europe/Paris');
		$visibility = ($this->session->userdata('email') ? 'private/' : 'public/');
		
		$changeLog = $this->input->post('changelog');
		if (!empty($changeLog))
		{
			$newChangelog = new Changelogs;
			$newChangelog->author = 'Antoine';
			$newChangelog->date = date('D/M/Y, H:i:s');
			$newChangelog->text = $changeLog;
			$newChangelog->insert();
		}
		
		$data_home = array(
			
			'baseUrl' => base_url(),
			'changelogs' => array_reverse($this->changelogs->select()),
			'write' => ''
		);
		$write = '';
		if ($this->session->userdata('email') == 'adechevigne@gmail.com')
		{
			$data_home['write'] = '<form method="post">
					<textarea class="ckeditor" id="changelog" name="changelog"></textarea>
					<br />
					<input type="submit" class="btn" value="Envoyer" />
				</form>';
		}
		
		$data = array(
						'baseUrl' => base_url(),
						'location' => $location,
						'mainContent' => $this->parser->parse($visibility . 'home.html', $data_home, TRUE)
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
			
			$user = $this->users->select(array('email' => $email));
			
			if ($user && $user->password == $this->encrypt->sha1($password))
			{
				$this->session->set_userdata(array(
												'email' => $user->email,
												'uid' => $user->getId()->{'$id'}
												)
											);
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
	
	public function getUserInfos()
	{
		if (!$this->session->userdata('email'))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		echo json_encode(array(
			'status' => 1,
			'email' => $this->session->userdata('email'),
			'id' => $this->session->userdata('uid')
		));
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
		
		$newUser = new Users;
		$newUser->email = $email;
		$newUser->password = $this->encrypt->sha1($password);
		$uid = $newUser->insert();
		
		$this->session->set_userdata(array(
												'email' => $email,
												'uid' => $uid->{'$id'}
										)
									);
									
		//Add the sample story
		$sample = $this->stories->select(array('_id' => '508fe8fad72ee9da2d000002'));
		$new_story = $sample;
		$new_story->owner = $uid;
		$new_story->exportable = false;
		$new_story->insert();
		
		redirect('home');
	}
}