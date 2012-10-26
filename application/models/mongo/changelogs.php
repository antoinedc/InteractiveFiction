<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Changelogs extends CI_Model {

	private $collection;
	private $database;
	
	private $_id;
	var $author;
	var $date;
	var $text;
	
	function __construct()
	{
		parent::__construct();
		
		$this->load->spark('mongodb/0.5.2');
		
		$this->database = 'local';
		$this->collection = 'changelogs';
	}
	
	function insert()
	{
		$data = array(
		
			'_id' => new MongoId(),
			'author' => $this->author,
			'date' => $this->date,
			'text' => $this->text
		);
		
		return $this->mongo_db->select($this->database)->insert($this->collection, $data);
	}
	
	function select()
	{
		return $this->mongo_db->get($this->collection);
	}
}