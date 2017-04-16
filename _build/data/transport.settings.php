<?php

$settings = array();

$tmp = array(
	'push_id' => array(
		'xtype' => 'textfield',
		'value' => '',
		'area' => 'Messenger_'
	),
	'push_key' => array(
		'xtype' => 'textfield',
		'value' => '',
		'area' => 'Messenger_'
	),
	'use_push' => array(
		'xtype' => 'combo-boolean',
		'value' => true,
		'area' => 'Messenger_'
	)
);

foreach ($tmp as $k => $v) {
	
	/* @var modSystemSetting $setting */
	$setting = $modx->newObject('modSystemSetting');
	
	$setting->fromArray(array_merge(
		array(
			'key' => 'messenger_'.$k,
			'namespace' => PKG_NAME_LOWER,
		), $v
	),'',true,true);
	
	$settings[] = $setting;
}

unset($tmp);
return $settings;
