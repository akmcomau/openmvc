<?php

namespace tests\Unit;

use tests\FrameworkTestCase;
use core\classes\Pagination;

class PaginationTest extends FrameworkTestCase {

	protected function makePagination(array $get = []): Pagination {
		$config = $this->makeConfig();
		$request = $this->makeRequest($config, $get);
		// Mimic what the Dispatcher always does before a controller method
		// (and therefore Pagination) runs in production.
		$request->setControllerClass('\core\controllers\Root');
		$request->setMethodName('index');
		$request->setMethodParams([]);
		return new Pagination($request, 'name', 'asc');
	}

	public function testDefaultsToPageOne(): void {
		$pagination = $this->makePagination();
		$this->assertSame(1, $pagination->getCurrentPage());
	}

	public function testPageParamSetsCurrentPage(): void {
		$pagination = $this->makePagination(['page' => '3']);
		$this->assertSame(3, $pagination->getCurrentPage());
	}

	public function testInvalidDirectionParamIsIgnored(): void {
		// Regression test: 'direction' must be strictly 'asc'/'desc' - any
		// other value (including an attempted 'SQL' injection marker) must
		// never reach Model::getOrderGroupSQL() as the ordering direction.
		$pagination = $this->makePagination(['direction' => 'SQL) DROP TABLE x --']);
		$this->assertSame(['name' => 'asc'], $pagination->getOrdering());
	}

	public function testValidDirectionParamIsAccepted(): void {
		$pagination = $this->makePagination(['direction' => 'desc']);
		$this->assertSame(['name' => 'desc'], $pagination->getOrdering());
	}

	public function testOrderingParamOverridesDefault(): void {
		$pagination = $this->makePagination(['ordering' => 'email']);
		$this->assertSame(['email' => 'asc'], $pagination->getOrdering());
	}

	public function testSetRecordsPerPage(): void {
		$pagination = $this->makePagination();
		$pagination->setRecordsPerPage(5);
		$this->assertSame(['limit' => 5, 'offset' => 0], $pagination->getLimitOffset());
	}

	public function testGetLimitOffsetForLaterPage(): void {
		$pagination = $this->makePagination(['page' => '3']);
		$pagination->setRecordsPerPage(10);
		$this->assertSame(['limit' => 10, 'offset' => 20], $pagination->getLimitOffset());
	}

	public function testGetMaxPage(): void {
		$pagination = $this->makePagination();
		$pagination->setRecordsPerPage(10);
		$pagination->setRecordCount(25);
		$this->assertSame(3.0, $pagination->getMaxPage());
	}

	public function testGetFirstAndLastRecordNumber(): void {
		$pagination = $this->makePagination(['page' => '2']);
		$pagination->setRecordsPerPage(10);
		$pagination->setRecordCount(25);

		$this->assertSame(11, $pagination->getFirstRecordNumber());
		$this->assertSame(20, $pagination->getLastRecordNumber());
	}

	public function testGetLastRecordNumberIsCappedAtTotalRecordCount(): void {
		$pagination = $this->makePagination(['page' => '3']);
		$pagination->setRecordsPerPage(10);
		$pagination->setRecordCount(25);

		// Page 3 would nominally end at record 30, but there are only 25.
		$this->assertSame(21, $pagination->getFirstRecordNumber());
		$this->assertSame(25, $pagination->getLastRecordNumber());
	}

	public function testGetStatusReturnsExpectedRecordBounds(): void {
		$pagination = $this->makePagination(['page' => '2']);
		$pagination->setRecordsPerPage(10);
		$pagination->setRecordCount(25);

		$status = $pagination->getStatus();

		$this->assertSame(10, $status->per_page);
		$this->assertSame(25, $status->total_records);
		$this->assertSame(2, $status->current_page);
		$this->assertSame(3.0, $status->num_pages);
		$this->assertSame(10, $status->record_start);
		$this->assertSame(20, $status->record_end);
		$this->assertNotNull($status->next_link);
		$this->assertNotNull($status->prev_link);
	}

	public function testGetStatusHasNoPrevLinkOnFirstPage(): void {
		$pagination = $this->makePagination();
		$pagination->setRecordsPerPage(10);
		$pagination->setRecordCount(25);

		$status = $pagination->getStatus();

		$this->assertNull($status->prev_link);
		$this->assertNotNull($status->next_link);
	}

	public function testGetStatusHasNoNextLinkOnLastPage(): void {
		$pagination = $this->makePagination(['page' => '3']);
		$pagination->setRecordsPerPage(10);
		$pagination->setRecordCount(25);

		$status = $pagination->getStatus();

		$this->assertNotNull($status->prev_link);
		$this->assertNull($status->next_link);
	}
}
