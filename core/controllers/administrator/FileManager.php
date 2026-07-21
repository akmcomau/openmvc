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

	/**
	 * File extensions that are safe to upload. Deliberately excludes anything
	 * the web server could ever execute (.php, .phtml, .php5, .phar, .cgi,
	 * .pl, .py, .rb, .sh, .htaccess, etc). Extend this list as needed but
	 * never allow executable/script extensions.
	 */
	protected $allowed_upload_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'];

	/**
	 * Reject a sub_path containing any '..' traversal segment.
	 * @return string|NULL The sub_path if safe, NULL if traversal was detected.
	 */
	protected function sanitizeSubPath($sub_path) {
		if (!strlen($sub_path ?? '')) return $sub_path;
		$normalized = str_replace('\\', '/', $sub_path);
		foreach (explode('/', $normalized) as $part) {
			if ($part === '..') {
				return NULL;
			}
		}
		return $sub_path;
	}

	/**
	 * Resolve $target_path and confirm it is actually contained within
	 * $base_dir (using realpath so that '..' segments and symlinks can't be
	 * used to escape the intended directory). Works for paths that don't
	 * exist yet (uploads, renames, new files) by resolving the parent
	 * directory instead.
	 * @return string|NULL The resolved absolute path, or NULL if it escapes $base_dir.
	 */
	protected function containedRealPath($base_dir, $target_path) {
		$real_base = realpath($base_dir);
		if ($real_base === FALSE) return NULL;

		if (file_exists($target_path)) {
			$real_target = realpath($target_path);
		}
		else {
			$real_parent = realpath(dirname($target_path));
			if ($real_parent === FALSE) return NULL;
			$real_target = $real_parent.DIRECTORY_SEPARATOR.basename($target_path);
		}
		if ($real_target === FALSE) return NULL;

		if ($real_target !== $real_base && strpos($real_target, $real_base.DIRECTORY_SEPARATOR) !== 0) {
			return NULL;
		}
		return $real_target;
	}

	/**
	 * Validate an uploaded filename: strip any directory component and
	 * reject anything not on the extension whitelist.
	 * @return string|NULL The safe basename, or NULL if the extension is not allowed.
	 */
	protected function sanitizeUploadFilename($filename) {
		$filename = basename($filename);
		$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
		if (!in_array($ext, $this->allowed_upload_extensions, TRUE)) {
			return NULL;
		}
		return $filename;
	}

	public function index($message = NULL) {
		$this->language->loadLanguageFile('administrator/file_manager.php');
		$paths = get_object_vars($this->config->siteConfig()->filemanager_paths);

		$path_id = $this->request->requestParam('path') ? $this->request->requestParam('path') : 0;
		$sub_path = $this->sanitizeSubPath($this->request->requestParam('sub_path'));
		if ($sub_path === NULL) {
			throw new SoftRedirectException($this->url->getControllerClass('Root'), 'error401');
		}
		if (strlen($sub_path ?? '') && $sub_path[0] == '/') {
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
		$sub_path = $this->sanitizeSubPath($this->request->requestParam('sub_path'));
		if ($sub_path === NULL) {
			throw new SoftRedirectException($this->url->getControllerClass('Root'), 'error401');
		}
		if (strlen($sub_path) && $sub_path[0] == '/') {
			$sub_path = '/'.substr($sub_path, 1);
		}

		$namespace = $this->config->siteConfig()->namespace;
		$glob_path = 'sites/'.$namespace.array_keys($paths)[$path_id].$sub_path.'/';
		$root_path = __DIR__.'/../../..';
		chdir($root_path);

		$message_js = '';
		if (isset($_REQUEST['file_content'])) {
			$base_dir = $root_path.'/sites/'.$namespace.array_keys($paths)[$path_id];
			$target = $root_path.'/sites/'.$namespace.array_keys($paths)[$path_id].$sub_path;

			// Confirm the resolved target is still inside the configured base
			// directory for this path_id (belt-and-suspenders on top of the
			// sub_path traversal check above).
			$safe_target = $this->containedRealPath($base_dir, $target);
			if ($safe_target === NULL) {
				throw new SoftRedirectException($this->url->getControllerClass('Root'), 'error401');
			}

			$content = str_replace("\r", "", $_REQUEST['file_content']);
			file_put_contents($safe_target, $content);
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

	// Only genuine image extensions are ever acceptable for this endpoint,
	// regardless of the general upload whitelist.
	protected $allowed_image_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

	public function uploadImage() {
		if (isset($_FILES['image'])) {
			$root_path = __DIR__.'/../../..';
			$namespace = $this->config->siteConfig()->namespace;
			$theme = $this->config->siteConfig()->theme;
			$image_path = '/sites/'.$namespace.'/themes/'.$theme.'/images/';

			$safe_filename = basename($_FILES['image']['name']);
			$ext = strtolower(pathinfo($safe_filename, PATHINFO_EXTENSION));
			if (!in_array($ext, $this->allowed_image_extensions, TRUE)) {
				$this->response->setStatusCode(400);
				$this->response->setJsonContent($this, json_encode(['error' => 'Unsupported file type']));
				return;
			}

			// Make the on-disk name unpredictable and collision-free instead of
			// trusting the client-supplied filename outright.
			$safe_filename = bin2hex(random_bytes(8)).'.'.$ext;
			$filename = $image_path.$safe_filename;
			$destination = $root_path.$filename;

			// Verify it's really an image (getimagesize returns FALSE for non-images,
			// which also blocks polyglot files that are valid PHP + valid image).
			if (!@getimagesize($_FILES['image']['tmp_name'])) {
				$this->response->setStatusCode(400);
				$this->response->setJsonContent($this, json_encode(['error' => 'Invalid image']));
				return;
			}

			move_uploaded_file($_FILES['image']['tmp_name'], $destination);
			$_SESSION['last_ct_image'] = [
				'size' => getimagesize($destination),
				'url' => $filename,
			];
			$this->response->setJsonContent($this, json_encode($_SESSION['last_ct_image']));
			return;
		}

		$this->response->setJsonContent($this, json_encode($_SESSION['last_ct_image']));
	}

	protected function upload($root_path, $glob_path, $path_id, $sub_path, &$errors) {
		$uploaded = 0;
		$base_dir = $root_path.'/'.$glob_path;
		for ($i=0; $i<$this->request->requestParam('num_images'); $i++) {
			if ($this->request->fileParam('image')['error'][$i] == 0) {
				$file = $this->request->fileParam('image')['name'][$i];

				$safe_file = $this->sanitizeUploadFilename($file);
				if ($safe_file === NULL) {
					$errors .= "File type not allowed: ".htmlspecialchars($file)."\n";
					continue;
				}

				$filename = $root_path.'/'.$glob_path.$safe_file;
				$safe_filename = $this->containedRealPath($base_dir, $filename);
				if ($safe_filename === NULL) {
					$errors .= "Invalid upload path\n";
					continue;
				}

				try {
					move_uploaded_file($this->request->fileParam('image')['tmp_name'][$i], $safe_filename);
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
			// The base directory a selected file/folder must actually resolve
			// inside of. preg_match('|^'.$glob_path.'|', $file) alone is NOT
			// sufficient here: $file can still contain '../' after a
			// legitimate-looking prefix (e.g. "sites/ns/uploads/../../../etc/x"
			// literally starts with "sites/ns/uploads/"). realpath()-based
			// containment closes that gap.
			$base_dir = $root_path.'/'.$glob_path;

			$deleted = 0;
			if (is_array($this->request->requestParam('select_files'))) {
				foreach ($this->request->requestParam('select_files') as $file) {
					$safe_file = $this->containedRealPath($base_dir, $root_path.'/'.$file);
					if ($safe_file !== NULL) {
						try {
							unlink($safe_file);
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
					$safe_folder = $this->containedRealPath($base_dir, $root_path.'/'.$folder);
					if ($safe_folder !== NULL) {
						try {
							rmdir($safe_folder);
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
			$base_dir = $root_path.'/'.$glob_path;

			// The new name must be a plain filename, not a path (blocks
			// "../../etc/cron.d/x" being smuggled in via submit_value).
			$new_name = basename($this->request->requestParam('submit_value'));

			if (is_array($this->request->requestParam('select_files')) || $this->request->requestParam('select_folders')) {
				foreach (array_merge(
					$this->request->requestParam('select_files') ? $this->request->requestParam('select_files') : [],
					$this->request->requestParam('select_folders') ? $this->request->requestParam('select_folders') : []
				) as $file) {
					try {
						$safe_source = $this->containedRealPath($base_dir, $root_path.'/'.$file);
						$safe_dest   = $this->containedRealPath($base_dir, $root_path.'/'.$glob_path.$new_name);
						if ($safe_source !== NULL && $safe_dest !== NULL) {
							rename($safe_source, $safe_dest);
						}
						else {
							$errors .= "Invalid rename path\n";
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
			// Plain folder name only, no path components.
			$new_folder_name = basename($this->request->requestParam('submit_value'));
			try {
				mkdir($root_path.'/'.$glob_path.$new_folder_name);
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
