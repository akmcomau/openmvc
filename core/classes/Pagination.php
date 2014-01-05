<?php

namespace core\classes;

class Pagination {

	/**
	 * The configuration object
	 * @var Config $config
	 */
	protected $config;

	/**
	 * The URL object
	 * @var URL $url
	 */
	protected $url;

	/**
	 * The request object
	 * @var Request $request
	 */
	protected $request;

	protected $ordering;
	protected $direction;
	protected $records_per_page;
	protected $num_pagination_links;
	protected $current_page;

	protected $record_count = 0;

	public function __construct(Request $request, $default_ordering, $default_direction = 'asc') {
		$this->current_page = 1;
		$this->config = $request->getConfig();
		$this->request = $request;
		$this->ordering = $default_ordering;
		$this->direction = $default_direction;
		$this->records_per_page = $this->config->siteConfig()->records_per_page;
		$this->num_pagination_links = $this->config->siteConfig()->num_pagination_links;
		$this->url = new URL($this->config);

		if ((int)$this->request->requestParam('page')) {
			$this->current_page = (int)$this->request->requestParam('page');
		}
		if ($this->request->requestParam('direction')) {
			$this->direction = $this->request->requestParam('direction');
		}
		if ($this->request->requestParam('ordering')) {
			$this->ordering = $this->request->requestParam('ordering');
		}
	}

	public function setRecordCount($record_count) {
		$this->record_count = $record_count;
	}

	public function getLimitOffset() {
		return [
		   'limit'  => $this->records_per_page,
		   'offset' => ($this->current_page-1)*$this->records_per_page
		];
	}

	public function getOrdering() {
		return [$this->ordering => $this->direction];
	}

	public function getMaxPage() {
		return ceil($this->record_count/$this->records_per_page);
	}

	public function getPageLinks() {
		$num_links  = $this->num_pagination_links;
		$half_links = floor($num_links/2);
		$curr_page  = $this->current_page;

		$min_page = $curr_page - $half_links;
		$max_page = $curr_page + $half_links;
		if ($min_page < 1) {
			$max_page += abs($min_page)+1;
			$min_page = 1;
		}
		if ($max_page > $this->getMaxPage()) {
			if ($min_page > 1) {
				if ($min_page > ($max_page - $this->getMaxPage())) {
					$min_page -= $max_page - $this->getMaxPage();
					$max_page  = $this->getMaxPage();
				}
				else {
					$min_page = 1;
					$max_page = $this->getMaxPage();
				}
			}
			else {
				$max_page = $this->getMaxPage();
			}
		}

		if ($min_page == $max_page) {
			return '';
		}

		$pages = '';

		if ($curr_page > 1) {
			$params = array_merge($this->request->get_params, ['ordering' => $this->ordering, 'page' => 1]);
			$url = $this->request->currentUrl();
			$pages .= '<a href="'.$url.'?'.http_build_query($params).'"><i class="fa fa-arrow-left"></i></a>';

			$params = array_merge($this->request->get_params, ['ordering' => $this->ordering, 'page' => ($curr_page-1)]);
			$url = $this->request->currentUrl();
			$pages .= '<a href="'.$url.'?'.http_build_query($params).'"><i class="fa fa-double-angle-left"></i></a>';
		}

		for ($i=$min_page; $i<=$max_page; $i++) {
			$class = '';
			if ($curr_page == $i) {
				$class = ' class="current"';
			}

			$params = array_merge($this->request->get_params, ['ordering' => $this->ordering, 'page' => $i]);
			$url = $this->request->currentUrl();
			$pages .= '<a'.$class.' href="'.$url.'?'.http_build_query($params).'">'.$i.'</a>';
		}

		if ($curr_page < $this->getMaxPage()) {
			$params = array_merge($this->request->get_params, ['ordering' => $this->ordering, 'page' => ($curr_page+1)]);
			$url = $this->request->currentUrl();
			$pages .= '<a href="'.$url.'?'.http_build_query($params).'"><i class="fa fa-double-angle-right"></i></a>';

			$params = array_merge($this->request->get_params, ['ordering' => $this->ordering, 'page' => $this->getMaxPage()]);
$url = $this->request->currentUrl();
			$pages .= '<a href="'.$url.'?'.http_build_query($params).'"><i class="fa fa-arrow-right"></i></a>';
		}

		return $pages;
	}

	public function getSortUrls($column) {
		$controller = $this->request->getControllerName();
		$method     = $this->request->getMethodName();
		$params     = $this->request->getMethodParams();

		$params_up   = array_merge($this->request->get_params, ['ordering' => $column, 'direction' => 'asc',  'page' => 1]);
		$params_down = array_merge($this->request->get_params, ['ordering' => $column, 'direction' => 'desc', 'page' => 1]);

		if ($column == $this->ordering && strtolower($this->direction) == 'asc') {
			$sort_asc = '<i class="fa fa-arrow-up"></i> ';
		}
		else {
			$sort_asc = $this->url->getUrl($controller, $method, $params, $params_up);
			$sort_asc = '<a href="'.$sort_asc.'"><i class="fa fa-arrow-up"></i></a> ';
		}
		if ($column == $this->ordering && strtolower($this->direction) == 'desc') {
			$sort_desc = '<i class="fa fa-arrow-down"></i>';
		}
		else {
			$sort_desc = $this->url->getUrl($controller, $method, $params, $params_down);
			$sort_desc = '<a href="'.$sort_desc.'"><i class="fa fa-arrow-down"></i></a>';
		}

		return $sort_asc.$sort_desc;
	}
}