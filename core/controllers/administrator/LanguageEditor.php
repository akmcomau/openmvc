<?php

namespace core\controllers\administrator;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;
use core\classes\Pagination;

class LanguageEditor extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
	];

	public function index() {
		$this->language->loadLanguageFile('administrator/language_editor.php');

		$params = [];
		if ($this->request->requestParam('search_file')) {
			$params['file'] = $this->request->requestParam('search_file');
		}

		$pagination = new Pagination($this->request, 'file');
		$pagination->setRecordCount(count($this->language->getLanguageFiles($params)));

		$language_files = $this->language->getLanguageFiles($params, $pagination->getOrdering(), $pagination->getLimitOffset());
		$form_search = $this->getLanguageSearchForm();

		$data = [
			'files' => $language_files,
			'form' => $form_search,
			'pagination' => $pagination,
		];

		$template = $this->getTemplate('pages/administrator/language_editor/list.php', $data);
		$this->response->setContent($template->render());
	}

	public function edit() {
		$this->language->loadLanguageFile('administrator/language_editor.php');

		$language_files = [];
		$files = '/'.join('/', func_get_args());
		if (preg_match_all('|/file/(.*?)/path/(.*?)/end|', $files, $matches)) {
			for ($i=0; $i<count($matches[0]); $i++) {
				$language_files[$matches[2][$i].'/'.$matches[1][$i]] = $this->language->getFile($matches[1][$i], $matches[2][$i]);
			}
		}

		$form = $this->getLanguageForm($language_files);

		if ($form->validate()) {
			$this->updateFromRequest($form, $language_files);
			foreach ($language_files as $file => $strings) {
				$this->language->updateFile($file, $strings);
			}
			$form->setNotification('success', $this->language->get('notification_update_success'));
		}
		elseif ($form->isSubmitted()) {
			$this->updateFromRequest($form, $language_files);
			$form->setNotification('error', $this->language->get('notification_update_error'));
		}

		$data = [
			'files' => $language_files,
			'form' => $form,
		];

		$template = $this->getTemplate('pages/administrator/language_editor/edit.php', $data);
		$this->response->setContent($template->render());
	}

	protected function updateFromRequest(FormValidator $form, array &$language_files) {
		$files = [];
		foreach ($language_files as $file => $strings) {
			$files[] = $file;
		}

		foreach ($form->getSubmittedValues() as $param => $value) {
			if (preg_match('/^(\d+)_(.*)$/', $param, $matches)) {
				$language_files[$files[$matches[1]]][$matches[2]] = $value;
			}
		}
	}

	protected function getLanguageSearchForm() {
		$inputs = [
			'search_file' => [
				'type' => 'string',
				'required' => FALSE,
				'max_length' => 256,
				'message' => $this->language->get('error_search_file'),
			],
		];

		return new FormValidator($this->request, 'form-language-search', $inputs);
	}

	protected function getLanguageForm($language_files) {
		$inputs = [];
		$counter = 0;
		foreach ($language_files as $file => $strings) {
			foreach ($strings as $tag => $string) {
				$inputs[$counter.'_'.$tag] = [
					'type' => 'string',
					'required' => FALSE,
				];
			}
			$counter++;
		}

		return new FormValidator($this->request, 'form-language', $inputs);
	}
}