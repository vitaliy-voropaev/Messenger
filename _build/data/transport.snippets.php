<?php

$snippets = array();
$tmp = array(
	'Messenger' => 'messenger',
	'MessengerNotifications' => 'messengernotifications',
);

foreach ($tmp as $k => $v) {
	
	/** @var modSnippet $snippet */
	$snippet = $modx->newObject('modSnippet');
	
	/** @noinspection PhpUndefinedVariableInspection */
	$snippet->fromArray(array(
		'id' => 0,
		'name' => $k,
		'description' => '',
		'snippet' => getSnippetContent($sources['source_core'] . '/elements/snippets/snippet.' . $v . '.php'),
		'static' => BUILD_SNIPPET_STATIC,
		'source' => 1,
		'static_file' => 'core/components/' . PKG_NAME_LOWER . '/elements/snippets/snippet.' . $v . '.php',
	), '', true, true);
	
	/** @noinspection PhpIncludeInspection */
	$properties = include $sources['build'] . 'properties/properties.' . $v . '.php';
	$snippet->setProperties($properties);
	$snippets[] = $snippet;
}

unset($properties);
return $snippets;