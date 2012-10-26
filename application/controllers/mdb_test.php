<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class mdb_test extends CI_Controller {

	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('amazon-sdk/0.1.8');
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->load->library('mongo_db');
		
		$this->load->model('mongo/users');
		
	}
	
	public function index()
	{		
		$s = new Users;
		$s->email = 'Antoine';
		$s->password = 'salut';
		
		$s = $this->users->select(array('_id' => '503ce8b57372166416000013'));
		$s->email = 'antoine2';
		$s->delete();
	}	
}