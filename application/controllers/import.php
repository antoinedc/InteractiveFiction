<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Import extends CI_Controller {

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
		$data_import_lonewolf = array();
		
		$data_layout = array(
			'baseUrl' => base_url(),
			'location' => 'Import a Lonewolf story',
			'mainContent' => $this->parser->parse('private/import/lonewolf.html', $data_import_lonewolf, TRUE)
		);
		$this->parser->parse('private/layout.html', $data_layout);
	}
	
	function lonewolf()
	{
		require_once('simple_html_dom.php');
		if (!$this->session->userdata('uid'))
		{
			echo json_encode(array('status' => -1));
			return;
		}
		
		/*$proxy = "tcp://mib.dsp.sun.ac.za:3128";
		$context = array(
			'http' => array(
				'proxy' => $proxy,
				'request_fulluri' => true
			)
		);			
		
		$context = stream_context_create($context);*/
		
		$story = new Stories;
		
		
		$path = 'Stories/01fftd';
		$list = $this->scanFolder($path);
		
		$result = array();
		foreach ($list as $page)
		{
			$content = file_get_html($page);
			$newRes = array();
			$newRes['idref'] = substr(strrchr($page, '/'), 1);
			
			$choices = array();
			
			$choices_list = $content->find('div[class=maintext] p[class=choice]');
			
			foreach ($choices_list as $choice)
			{
				if (!empty($choice) && $choice->find('a'))
				{
					$res = array(
						'idref' => $choice->find('a')[0]->href,
						'text' =>  $choice->plaintext
					);
					$choices[] = $res;
				}
			}
			
			$nav_list = $content->find('div[class=frontmatter] div[class=navigation]');
			
			foreach ($content->find('div[class=frontmatter] div[class=navigation] a') as $nav_choice)
			{
				if (!empty($nav_choice))
				{
					//var_dump($nav_choice->children);
					$res = array(
						'idref' => $nav_choice->attr['href'],
						'text' => $nav_choice->children[0]->attr['alt']
					);
				}
				$choices[] = $res;
			}
			
			$res = $content->find('div[class=maintext]');
			if (!empty($res))
			{
				$res = $content->find('div[class=maintext]')[0];
				$newRes['text'] = $res->innertext;
				$newRes['text'] = preg_replace('#<p class="choice">(.*)</p>#', '', $newRes['text']);
			}
				
			$newRes['links'] = $choices;	
			$result[] = $newRes;
		}
		
		$paragraphes_story = array();
		foreach ($result as $page)
		{
			if (!empty($page['text']))
			{
				$paragraphes = new Paragraphes;
				$paragraphes->getId();
				$paragraphes->text = $page['text'];
				$paragraphes->isStart = ($page['idref'] == 'title.html');
				$paragraphes->isEnd = false;
				$paragraphes->idref = $page['idref'];
				$paragraphes->temp_links = $page['links'];
				$paragraphes_story[] = $paragraphes;
			}
		}
		
		foreach ($paragraphes_story as $paragraph)
		{
			$links = array();
			foreach ($paragraphes_story as $seek_dest)
			{
				foreach ($seek_dest->temp_links as $dest_link)
				{
					if ($dest_link['idref'] == $paragraph->idref)
					{
						$newLink = array();
						$newLink['origin'] = $paragraph->getId();
						$newLink['destination'] = $seek_dest->getId();
						$newLink['text'] = $dest_link['text'];
						$links[] = $newLink;
					}
				}
			}
			$paragraph->links = $links;
		}

		foreach ($paragraphes_story as $paragraph)
		{
			$links = array();
			foreach ($paragraph->temp_links as $temp_links)
			{
				foreach ($paragraphes_story as $seek_dest)
				{
					if ($seek_dest->idref == $temp_links['idref'])
					{
						$newLink = array();
						$newLink['origin'] = $paragraph->getId();
						$newLink['destination'] = $seek_dest->getId();
						$newLink['text'] = $temp_links['text'];
						$links[] = $newLink;
					}
				}
			}
			$paragraph->links = $links;
		}
		
		$story = new Stories;
		$story->title = "Flying from the dark";
		
		$id = $story->insert();
		
		foreach ($paragraphes_story as $paragraph)
		{
			$paragraph->sid = $id;
			var_dump($paragraph->insert());
		}
						
	}
	
	private function scanFolder($dir)
	{
		$allowedExtensions = array("htm");
		static $list = array();

		$myDir = opendir($dir) or die('Error while opening: ' . $dir);
		while($entry = @readdir($myDir))
				if(is_dir($dir.'/'.$entry)&& $entry != '.' && $entry != '..')
						scanFolder($dir.'/'.$entry);
				else
						if ($entry != '.' && $entry != '..')
						{                                                       
								$pathParts = pathinfo($dir.'/'.$entry);
								if (isset($pathParts["extension"]))
										if (in_array($pathParts["extension"], $allowedExtensions))
												$list[] = $dir . '/' . $entry;  
						}

		closedir($myDir);
		return $list;
	}   
}