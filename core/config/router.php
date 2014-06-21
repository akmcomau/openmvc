<?php
$_ROUTER = ['forward' => [], 'reverse' => []];

/*
// basic rewrite
$_ROUTER['forward']['|^/a/\d+|'] = [
	'controller' => 'Root',
	'method' => 'page/about_us',
];
$_ROUTER['reverse']['Root']['page/about_us'] = '/a/134111';

// basic route with params
$_ROUTER['forward']['|^/p/(\d+)(/(.*))$|'] = [
	'controller' => 'Products',
	'method' => 'view',
	'params' => [1, 3],
];
$_ROUTER['reverse']['Products']['view'] = function($params) {
	return '/p/'.(int)$params[0].(isset($params[1]) ? '/'.urlencode($params[1]) : '');
};

// rewrite with a callback
$_ROUTER['forward']['|^/c/(\d+)(/(.*))$|'] = function($request, $matches) {
	return [
		'controller' => 'Products',
		'method' => 'index',
		'params' => ['category', $matches[1], $matches[3]],
	];
};
$_ROUTER['reverse']['Products']['index'] = function($params) {
	if (count($params) > 1 && $params[0] == 'category') {
		return '/c/'.(int)$params[1].(isset($params[2]) ? '/'.urlencode($params[2]) : '');
	}
	return FALSE;
};
*/