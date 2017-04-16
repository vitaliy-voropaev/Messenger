<?php

$chunks = array();

$tmp = array(
	'tpl.Messenger.message' => array(
		'file' => 'message',
		'description' => '',
	),
	'tpl.Messenger.office' => array(
		'file' => 'office',
		'description' => '',
	),
	'tpl.Messenger.outer' => array(
		'file' => 'outer',
		'description' => '',
	),
	'tpl.Messenger.notifications' => array(
		'file' => 'notifications',
		'description' => '',
	),
	'tpl.Messenger.dialog' => array(
		'file' => 'dialog',
		'description' => '',
	),
	'tpl.Messenger.dialog.info' => array(
		'file' => 'dialog_info',
		'description' => '',
	),
	'tpl.Messenger.dialog.new' => array(
		'file' => 'dialog_new',
		'description' => '',
	),
	'tpl.Messenger.dialog.row' => array(
		'file' => 'dialog_row',
		'description' => '',
	),
	'tpl.Messenger.dialog.settings' => array(
		'file' => 'dialog_settings',
		'description' => '',
	)
);

foreach ($tmp as $k => $v) {
	/* @avr modChunk $chunk */
	$chunk = $modx->newObject('modChunk');
	$chunk->fromArray(array(
		'id' => 0,
		'name' => $k,
		'description' => @$v['description'],
		'snippet' => file_get_contents($sources['source_core'].'/elements/chunks/chunk.'.$v['file'].'.tpl'),
		'static' => BUILD_CHUNK_STATIC,
		'source' => 1,
		'static_file' => 'core/components/'.PKG_NAME_LOWER.'/elements/chunks/chunk.'.$v['file'].'.tpl',
	),'',true,true);
	
	$chunks[] = $chunk;
}

unset($tmp);
return $chunks;