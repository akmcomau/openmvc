<?php

namespace core\controllers\administrator;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;
use core\classes\FileManager as FileManagerRPC;

class FileManager extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
		'config' => ['administrator'],
		'rpc' => ['administrator'],
	];

	public function index() {
		$this->language->loadLanguageFile('administrator/file_manager.php');

		$namespace = $this->config->siteConfig()->namespace;
		$glob = 'sites/'.$namespace.'/themes/default/images/';
		$path = __DIR__.'/../../..';
		chdir($path);

		for ($i=0; $i<$this->request->requestParam('num_images'); $i++) {
			if ($this->request->fileParam('image')['error'][$i] == 0) {
				$filename = $path.'/'.$glob.$this->request->fileParam('image')['name'][$i];
				copy($this->request->fileParam('image')['tmp_name'][$i], $filename);
			}
		}

		if (is_array($this->request->requestParam('delete_files'))) {
			foreach ($this->request->requestParam('delete_files') as $file) {
				if (preg_match('|^'.$glob.'|', $file)) {
					unlink($file);
				}
			}
		}

		$data = [
			'path' => $glob,
		];

		$template = $this->getTemplate('pages/administrator/file_manager.php', $data);
		$this->response->setContent($template->render());
	}
}
