<?php

$properties = array();

$tmp = array(
	'tpl' => array(
		'type' => 'textfield',
		'value' => 'tpl.Messenger.item',
	),
	'tplOuter' => array(
		'type' => 'textfield',
		'value' => 'tpl.Messenger.outer',
	),
	'limit' => array(
		'type' => 'numberfield',
		'value' => 10,
	)
);

foreach ($tmp as $k => $v) {
	$properties[] = array_merge(
		array(
			'name' => $k,
			'desc' => PKG_NAME_LOWER . '_prop_' . $k,
			'lexicon' => PKG_NAME_LOWER . ':properties',
		), $v
	);
}

return $properties;