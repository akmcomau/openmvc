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

require_once(__DIR__.'/../../../composer/vendor/simogeo/Filemanager/connectors/php/inc/filemanager.inc.php');
require_once(__DIR__.'/../../../composer/vendor/simogeo/Filemanager/connectors/php/filemanager.class.php');

class FileManager extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
		'config' => ['administrator'],
	];

	public function index() {
		$site = $this->config->siteConfig();
		$path = 'sites'.DS.$site->namespace.DS.'themes'.DS;
		if (!is_dir($path)) {
			mkdir($path, 0775, TRUE);
		}

		$template = $this->getTemplate('pages/administrator/file_manager.php');
		$this->response->setContent($template->render());
	}

	public function config() {
		require(__DIR__.'/../../config/file_manager.php');
		$this->response->setJsonContent($this, json_encode($_FILE_MANAGER));
	}

	public function rpc() {
		$site = $this->config->siteConfig();
		$path = 'sites'.DS.$site->namespace.DS.'themes'.DS;
		$file_manager = new FileManagerRPC();
		$file_manager->setFileRoot($path);

		$response = '';

		if(!isset($_GET)) {
			$file_manager->error($file_manager->lang('INVALID_ACTION'));
		}
		else {
			if(isset($_GET['mode']) && $_GET['mode']!='') {

				switch($_GET['mode']) {

					default:
						$file_manager->error($file_manager->lang('MODE_ERROR'));
						break;

					case 'getinfo':
					if($file_manager->getvar('path')) {
						$response = $file_manager->getinfo();
					}
					break;

					case 'getfolder':
						if($file_manager->getvar('path')) {
							$response = $file_manager->getfolder();
						}
						break;

					case 'rename':
						if($file_manager->getvar('old') && $file_manager->getvar('new')) {
							$response = $file_manager->rename();
						}
						break;

					case 'delete':
						if($file_manager->getvar('path')) {
							$response = $file_manager->delete();
						}
						break;

					case 'addfolder':
						if($file_manager->getvar('path') && $file_manager->getvar('name')) {
							$response = $file_manager->addfolder();
						}
						break;

					case 'download':
						if($file_manager->getvar('path')) {
							$file_manager->download();
						}
						break;

					case 'preview':
						if($file_manager->getvar('path')) {
							if(isset($_GET['thumbnail'])) {
								$thumbnail = true;
							} else {
								$thumbnail = false;
							}
							$file_manager->preview($thumbnail);
						}
						break;

					case 'maxuploadfilesize':
						$file_manager->getMaxUploadFileSize();
							break;
				}

			}
			else if(isset($_POST['mode']) && $_POST['mode']!='') {
				switch($_POST['mode']) {
					default:
						$file_manager->error($file_manager->lang('MODE_ERROR'));
						break;

					case 'add':
						if($file_manager->postvar('currentpath')) {
							$file_manager->add();
						}
						break;
				}
			}
		}

		$this->response->setJsonContent($this, json_encode($response));
	}
}