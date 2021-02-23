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
		'editor' => ['administrator'],
		'uploadImage' => ['administrator'],
		'config' => ['administrator'],
		'rpc' => ['administrator'],
	];

	public function index($message = NULL) {
		$this->language->loadLanguageFile('administrator/file_manager.php');
		$paths = get_object_vars($this->config->siteConfig()->filemanager_paths);

		$path_id = $this->request->requestParam('path') ? $this->request->requestParam('path') : 0;
		$sub_path = $this->request->requestParam('sub_path');
		if (strlen($sub_path) && $sub_path{0} == '/') {
			$sub_path = '/'.substr($sub_path, 1);
		}

		$namespace = $this->config->siteConfig()->namespace;
		$glob_path = 'sites/'.$namespace.array_keys($paths)[$path_id].$sub_path.'/';
		$root_path = __DIR__.'/../../..';
		chdir($root_path);

		$errors = "";
		$this->upload($root_path, $glob_path, $path_id, $sub_path, $errors);
		$this->delete($root_path, $glob_path, $path_id, $sub_path, $errors);
		$this->rename($root_path, $glob_path, $path_id, $sub_path, $errors);
		$this->move($root_path, $glob_path, $path_id, $sub_path, $errors);
		$this->newFolder($root_path, $glob_path, $path_id, $sub_path, $errors);

		$message_js = "";
		if ($errors) {
			$message_js = 'FormValidator.displayPageNotification("error", "'.trim(nl2br(htmlspecialchars($errors))).'");';
		}
		elseif ($message) {
			switch ($message) {
				case 'upload-success';
					$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_upload_success')).'");';
					break;

				case 'delete-success';
					$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_delete_success')).'");';
					break;

				case 'rename-success';
					$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_rename_success')).'");';
					break;
			}
		}

		$data = [
			'path_id' => $path_id,
			'folder_type' => $paths[array_keys($paths)[$path_id]],
			'paths' => $paths,
			'glob_path' => $glob_path,
			'message_js' => $message_js,
			'sub_path' => $sub_path,
		];

		$template = $this->getTemplate('pages/administrator/file_manager.php', $data);
		$this->response->setContent($template->render());
	}

	public function editor($message = NULL) {
		$this->language->loadLanguageFile('administrator/file_manager.php');
		$paths = get_object_vars($this->config->siteConfig()->filemanager_paths);

		$path_id = $this->request->requestParam('path') ? $this->request->requestParam('path') : 0;
		$sub_path = $this->request->requestParam('sub_path');
		if (strlen($sub_path) && $sub_path{0} == '/') {
			$sub_path = '/'.substr($sub_path, 1);
		}

		$namespace = $this->config->siteConfig()->namespace;
		$glob_path = 'sites/'.$namespace.array_keys($paths)[$path_id].$sub_path.'/';
		$root_path = __DIR__.'/../../..';
		chdir($root_path);

		$message_js = '';
		if (isset($_REQUEST['file_content'])) {
			$content = str_replace("\r", "", $_REQUEST['file_content']);
			file_put_contents($root_path.'/sites/'.$namespace.array_keys($paths)[$path_id].$sub_path, $content);
			$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_file_update_success')).'");';
		}

		$data = [
			'path_id' => $path_id,
			'paths' => $paths,
			'path' => $root_path.'/sites/'.$namespace.array_keys($paths)[$path_id],
			'message_js' => $message_js,
			'sub_path' => $sub_path,
		];

		$template = $this->getTemplate('pages/administrator/file_manager_editor.php', $data);
		$this->response->setContent($template->render());
	}

	public function uploadImage() {
		if (isset($_FILES['image'])) {
			$root_path = __DIR__.'/../../..';
			$namespace = $this->config->siteConfig()->namespace;
			$theme = $this->config->siteConfig()->theme;
			$image_path = '/sites/'.$namespace.'/themes/'.$theme.'/images/';
			$filename = $image_path.$_FILES['image']['name'];
			copy($_FILES['image']['tmp_name'], $root_path.$filename);
			$_SESSION['last_ct_image'] = [
				'size' => getimagesize($root_path.$filename),
				'url' => $filename,
			];
			$this->response->setJsonContent($this, json_encode($_SESSION['last_ct_image']));
			return;
		}

		$this->response->setJsonContent($this, json_encode($_SESSION['last_ct_image']));
	}

	protected function upload($root_path, $glob_path, $path_id, $sub_path, &$errors) {
		$uploaded = 0;
		for ($i=0; $i<$this->request->requestParam('num_images'); $i++) {
			if ($this->request->fileParam('image')['error'][$i] == 0) {
				$file = $this->request->fileParam('image')['name'][$i];
				$filename = $root_path.'/'.$glob_path.$file;
				try {
					copy($this->request->fileParam('image')['tmp_name'][$i], $filename);
					$uploaded++;
				}
				catch (\ErrorException $ex) {
					$errors .= $ex->getMessage()."\n";
				}
			}
		}
		if (!$errors && $uploaded) {
			throw new RedirectException($this->url->getUrl('administrator/FileManager', 'index', ['upload-success'], ['path' => $path_id, 'sub_path' => $sub_path]));
		}
	}

	protected function delete($root_path, $glob_path, $path_id, $sub_path, &$errors) {
		if ($this->request->requestParam('submit_type') == 'delete') {
			$deleted = 0;
			if (is_array($this->request->requestParam('select_files'))) {
				foreach ($this->request->requestParam('select_files') as $file) {
					if (preg_match('|^'.$glob_path.'|', $file)) {
						try {
							unlink($file);
							$deleted++;
						}
						catch (\ErrorException $ex) {
							$errors .= $ex->getMessage()."\n";
						}
					}
				}
			}

			if ($this->request->requestParam('select_folders')) {
				foreach ($this->request->requestParam('select_folders') as $folder) {
					if (preg_match('|^'.$glob_path.'|', $folder)) {
						try {
							rmdir($folder);
							$deleted++;
						}
						catch (\ErrorException $ex) {
							$errors .= $ex->getMessage()."\n";
						}
					}
				}
			}
			if (!$errors && $deleted) {
				throw new RedirectException($this->url->getUrl('administrator/FileManager', 'index', ['delete-success'], ['path' => $path_id, 'sub_path' => $sub_path]));
			}
		}
	}

	protected function rename($root_path, $glob_path, $path_id, $sub_path, &$errors) {
		if ($this->request->requestParam('submit_type') == 'rename') {
			if (is_array($this->request->requestParam('select_files')) || $this->request->requestParam('select_folders')) {
				foreach (array_merge(
					$this->request->requestParam('select_files') ? $this->request->requestParam('select_files') : [],
					$this->request->requestParam('select_folders') ? $this->request->requestParam('select_folders') : []
				) as $file) {
					try {
						if (preg_match('|^'.$glob_path.'|', $file)) {
							rename($root_path.'/'.$file, $root_path.'/'.$glob_path.$this->request->requestParam('submit_value'));
						}
					}
					catch (\ErrorException $ex) {
						$errors .= $ex->getMessage()."\n";
					}
					break;
				}
			}
			if (!$errors) {
				throw new RedirectException($this->url->getUrl('administrator/FileManager', 'index', ['rename-success'], ['path' => $path_id, 'sub_path' => $sub_path]));
			}
		}
	}

	protected function newFolder($root_path, $glob_path, $path_id, $sub_path, &$errors) {
		if ($this->request->requestParam('submit_type') == 'new_folder') {
			try {
				mkdir($root_path.'/'.$glob_path.$this->request->requestParam('submit_value'));
			}
			catch (\ErrorException $ex) {
				$errors .= $ex->getMessage()."\n";
			}
			if (!$errors) {
				throw new RedirectException($this->url->getUrl('administrator/FileManager', 'index', ['folder-success'], ['path' => $path_id, 'sub_path' => $sub_path]));
			}
		}
	}

	protected function move($root_path, $glob_path, $path_id, $sub_path, &$errors) {

	}
}
