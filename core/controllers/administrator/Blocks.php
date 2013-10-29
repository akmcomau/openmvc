<?php

namespace core\controllers\administrator;

use core\classes\exceptions\RedirectException;
use core\classes\exceptions\SoftRedirectException;
use core\classes\Encryption;
use core\classes\Template;
use core\classes\FormValidator;
use core\classes\renderable\Controller;
use core\classes\Model;
use core\classes\models\Block;
use core\classes\Page;
use core\classes\Pagination;
use core\classes\Module;

class Blocks extends Controller {

	protected $show_admin_layout = TRUE;

	protected $permissions = [
		'index' => ['administrator'],
		'add' => ['administrator'],
		'edit' => ['administrator'],
		'delete' => ['administrator'],
	];

	public function index($message = NULL) {
		$this->language->loadLanguageFile('administrator/blocks.php');
		$form_search = $this->getBlockSearchForm();

		$pagination = new Pagination($this->request, 'tag');

		$params = ['site_id' => ['type'=>'in', 'value'=>$this->allowedSiteIDs()]];
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
				elseif ($name == 'search_type' && (int)$value != 0) {
					$params['block_type_id'] = (int)$value;
				}
			}
		}

		// get all the blocks
		$model  = new Model($this->config, $this->database);
		$block_type = $model->getModel('\core\classes\models\BlockType');
		$block_category = $model->getModel('\core\classes\models\BlockCategory');
		$block  = $model->getModel('\\core\\classes\\models\\Block');
		$blocks = $block->getMulti($params, $pagination->getOrdering(), $pagination->getLimitOffset());
		$pagination->setRecordCount($block->getCount($params));

		$message_js = NULL;
		switch($message) {
			case 'delete-success':
				$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_delete_success')).'");';
				break;

			case 'add-success':
				$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_add_success')).'");';
				break;

			case 'update-success':
				$message_js = 'FormValidator.displayPageNotification("success", "'.htmlspecialchars($this->language->get('notification_update_success')).'");';
				break;
		}

		$data = [
			'form' => $form_search,
			'blocks' => $blocks,
			'pagination' => $pagination,
			'categories' => $block_category->getAsOptions($this->allowedSiteIDs()),
			'types' => $block_type->getAsOptions(),
			'message_js' => $message_js,
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
		$data['categories'] = $block_category->getAsOptions($this->allowedSiteIDs());
		$block_type = $model->getModel('\core\classes\models\BlockType');
		$data['types'] = $block_type->getAsOptions();

		if ($form_block->validate()) {
			$this->updateFromRequest($form_block, $block);
			$block->insert();
			$this->callHook('block_add', [$block]);
			throw new RedirectException($this->url->getURL('administrator/Blocks', 'index', ['add-success']));
		}
		elseif ($form_block->isSubmitted()) {
			$this->updateFromRequest($form_block, $block);
			$form_block->setNotification('error', $this->language->get('notification_add_error'));
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
		$data['categories'] = $block_category->getAsOptions($this->allowedSiteIDs());
		$block_type = $model->getModel('\core\classes\models\BlockType');
		$data['types'] = $block_type->getAsOptions();

		$block = $model->getModel('\core\classes\models\Block')->get([
			'tag' => $tag,
		]);
		$this->siteProtection($block);

		if ($form_block->validate()) {
			$this->updateFromRequest($form_block, $block);
			$block->update();
			$this->callHook('block_update', [$block]);
			throw new RedirectException($this->url->getURL('administrator/Blocks', 'index', ['update-success']));
		}
		elseif ($form_block->isSubmitted()) {
			$this->updateFromRequest($form_block, $block);
			$form_block->setNotification('error', $this->language->get('notification_update_error'));
		}

		$data['is_add_page'] = FALSE;
		$data['form'] = $form_block;
		$data['block'] = $block;
		$template = $this->getTemplate('pages/administrator/blocks/add_edit.php', $data);
		$this->response->setContent($template->render());
	}

	public function delete() {
		if ($this->request->requestParam('selected')) {
			$model = new Model($this->config, $this->database);
			$block_model = $model->getModel('\core\classes\models\Block');
			foreach ($this->request->requestParam('selected') as $id) {
				$block = $block_model->get(['id' => $id]);
				$this->siteProtection($block);
				$block->delete();
			}

			throw new RedirectException($this->url->getURL('administrator/Blocks', 'index', ['delete-success']));
		}
	}

	protected function updateFromRequest(FormValidator $form_block, $block) {
		$block->title = $form_block->getValue('title');
		$block->tag = $form_block->getValue('tag');
		$block->content = $form_block->getValue('content');
		$block->block_type_id = $form_block->getValue('type');

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