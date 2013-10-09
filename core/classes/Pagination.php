<?php

namespace core\classes;

class Pagination {

	protected $config;
	protected $request;
	protected $ordering;
	protected $records_per_page;
	protected $num_pagination_links;
	protected $current_page;

	protected $record_count = 0;

	public function __construct(Request $request, array $ordering) {
		$this->config = $request->getConfig();
		$this->request = $request;
		$this->ordering = $ordering;
		$this->records_per_page = $this->config->siteConfig()->records_per_page;
		$this->num_pagination_links = $this->config->siteConfig()->num_pagination_links;

		if ((int)$this->request->requestParam('page')) {
			$this->current_page = (int)$this->request->requestParam('page');
		}
		else {
			$this->current_page = 1;
		}
	}

	public function setRecordCount($record_count) {
		$this->record_count = $record_count;
	}

	public function getPagination() {
		return [
		   'limit'  => $this->records_per_page,
		   'offset' => ($this->current_page-1)*$this->records_per_page
		];
	}

	public function getOrdering() {
		if ($this->request->requestParam('ordering')) {
			return [$this->request->requestParam('ordering')];
		}
		return $this->ordering;
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
			$params = ['ordering' => join(',', $this->ordering), 'page' => 1];
			$url = $this->request->currentURL();
			$pages .= '<a href="'.$url.'?'.http_build_query($params).'"><i class="icon-arrow-left"></i></a>';

			$params = ['ordering' => join(',', $this->ordering), 'page' => ($curr_page-1)];
			$url = $this->request->currentURL();
			$pages .= '<a href="'.$url.'?'.http_build_query($params).'"><i class="icon-double-angle-left"></i></a>';
		}

		for ($i=$min_page; $i<=$max_page; $i++) {
			$class = '';
			if ($curr_page == $i) {
				$class = ' class="current"';
			}

			$params = ['ordering' => join(',', $this->ordering), 'page' => $i];
			$url = $this->request->currentURL();
			$pages .= '<a'.$class.' href="'.$url.'?'.http_build_query($params).'">'.$i.'</a>';
		}

		if ($curr_page < $this->getMaxPage()) {
			$params = ['ordering' => join(',', $this->ordering), 'page' => ($curr_page+1)];
			$url = $this->request->currentURL();
			$pages .= '<a href="'.$url.'?'.http_build_query($params).'"><i class="icon-double-angle-right"></i></a>';

			$params = ['ordering' => join(',', $this->ordering), 'page' => $this->getMaxPage()];
			$url = $this->request->currentURL();
			$pages .= '<a href="'.$url.'?'.http_build_query($params).'"><i class="icon-arrow-right"></i></a>';
		}

		return $pages;
	}
}