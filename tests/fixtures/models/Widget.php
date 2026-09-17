<?php

namespace tests\fixtures\models;

use core\classes\Model;

/**
 * A minimal, fully self-contained Model subclass used to exercise
 * Model's SQL-building logic (quote(), generateWhereClause(),
 * getOrderGroupSQL(), getColumnName(), generateFromClause()) without
 * touching any real database or the real site/model discovery machinery.
 */
class Widget extends Model {

	protected $table = 'widget';
	protected $primary_key = 'widget_id';

	protected $columns = [
		'widget_id' => [
			'data_type'      => 'bigint',
			'auto_increment' => TRUE,
			'null_allowed'   => FALSE,
		],
		'widget_name' => [
			'data_type'    => 'text',
			'null_allowed' => FALSE,
		],
		'widget_active' => [
			'data_type'     => 'bool',
			'null_allowed'  => FALSE,
			'default_value' => 'TRUE',
		],
		'widget_created' => [
			'data_type'    => 'datetime',
			'null_allowed' => TRUE,
		],
	];

	protected $indexes = ['widget_name'];
	protected $uniques = ['widget_name'];

	protected $relationships = [
		'widget_category' => [
			'where_fields' => ['category_name'],
			'join_clause'  => 'JOIN widget_category ON widget_category.id = widget.category_id',
		],
	];
}
