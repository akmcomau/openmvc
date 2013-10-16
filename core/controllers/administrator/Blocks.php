<?php

namespace core\controllers\administrator;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;
use core\classes\Page;
use core\classes\Pagination;

class Blocks extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
		'add' => ['administrator'],
		'edit' => ['administrator'],
	];

	public function index() {
		$this->language->loadLanguageFile('administrator/blocks.php');
		$form_search = $this->getBlockSearchForm();

		$pagination = new Pagination($this->request, 'tag');

		$params = [];
		if ($form_search->validate()) {
			$values = $form_search->getSubmittedValues();
			foreach ($values as $name => $value) {
				if (preg_match('/^search_(title|tag)$/', $name, $matches) && $value != '') {
					$value = strtolower($value);
					$params[$matches[1]] = ['type'=>'like', 'value'=>'%'.$value.'%'];
				}
				elseif ($name == 'search_category' && (int)$value != 0) {
					$params['block_category_id'] = (int)$value;
				}
			}
		}

		// get all the blocks
		$model  = new Model($this->config, $this->database);
		$block_type = $model->getModel('\core\classes\models\BlockType');
		$block_category = $model->getModel('\core\classes\models\BlockCategory');
		$block  = $model->getModel('\\core\\classes\\models\\Block');
		$blocks = $block->getMulti($params, $pagination->getOrdering(), $pagination->getLimitOffset());
		$pagination->setRecordCount(10);

		$data = [
			'form' => $form_search,
			'blocks' => $blocks,
			'pagination' => $pagination,
			'categories' => $block_category->getAsOptions(),
			'types' => $block_type->getAsOptions(),
		];

		$template = $this->getTemplate('pages/administrator/blocks/list.php', $data);
		$this->response->setContent($template->render());
	}

	public function add() {
		$this->language->loadLanguageFile('administrator/blocks.php');
		$form_block = $this->getBlockForm();

		$model = new Model($this->config, $this->database);
		$block = $model->getModel('\core\classes\models\Block');
		$block->site_id = $this->config->siteConfig()->site_id;
		$block_category = $model->getModel('\core\classes\models\BlockCategory');
		$data['categories'] = $block_category->getAsOptions();

		if ($form_block->validate()) {
			$this->updateFromRequest($form_block, $block);
			$block->insert();
		}
		elseif ($form_block->isSubmitted()) {
			$this->updateFromRequest($form_block, $block);
		}

		$data['is_add_page'] = TRUE;
		$data['form'] = $form_block;
		$data['block'] = $block;
		$template = $this->getTemplate('pages/administrator/blocks/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	public function edit($tag) {
		$this->language->loadLanguageFile('administrator/blocks.php');
		$form_block = $this->getBlockForm();

		$model = new Model($this->config, $this->database);
		$block_category = $model->getModel('\core\classes\models\BlockCategory');
		$data['categories'] = $block_category->getAsOptions();

		$block = $model->getModel('\core\classes\models\Block')->get([
			'tag' => $tag,
		]);

		if ($form_block->validate()) {
			$this->updateFromRequest($form_block, $block);
			$block->update();
		}
		elseif ($form_block->isSubmitted()) {
			$this->updateFromRequest($form_block, $block);
		}

		$data['is_add_page'] = FALSE;
		$data['form'] = $form_block;
		$data['block'] = $block;
		$template = $this->getTemplate('pages/administrator/blocks/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	protected function updateFromRequest(FormValidator $form_block, $block) {
		$block->title = $form_block->getValue('title');
		$block->tag = $form_block->getValue('tag');
		$block->content = $form_block->getValue('content');

		$block->setCategory(NULL);
		if ((int)$form_block->getValue('category')) {
			$block_category = $block->getModel('\core\classes\models\BlockCategory')->get([
				'id' => (int)$form_block->getValue('category'),
			]);
			if ($block_category) {
				$block->setCategory($block_category);
			}
		}
	}

	protected function getBlockSearchForm() {
		$inputs = [
			'search_title' => [
				'type' => 'string',
				'required' => FALSE,
				'max_length' => 256,
				'message' => $this->language->get('error_search_title'),
			],
			'search_tag' => [
				'type' => 'string',
				'required' => FALSE,
				'max_length' => 64,
				'message' => $this->language->get('error_search_title'),
			],
			'search_category' => [
				'type' => 'integer',
				'required' => FALSE,
			],
			'search_type' => [
				'type' => 'integer',
				'required' => FALSE,
			],
		];

		return new FormValidator($this->request, 'form-block-search', $inputs);
	}

	protected function getBlockForm() {
		$inputs = [
			'title' => [
				'type' => 'string',
				'required' => TRUE,
				'max_length' => 256,
				'message' => $this->language->get('error_title'),
			],
			'tag' => [
				'type' => 'string',
				'required' => TRUE,
				'max_length' => 64,
				'message' => $this->language->get('error_tag'),
			],
			'category' => [
				'type' => 'integer',
				'required' => FALSE,
			],
			'content' => [
				'type' => 'string',
				'required' => FALSE,
			],
		];

		return new FormValidator($this->request, 'form-block', $inputs);
	}
}