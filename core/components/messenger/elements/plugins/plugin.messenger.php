<?php
if (!$Messenger = $modx->getService('messenger', 'Messenger', $modx->getOption('messenger_core_path', null, $modx->getOption('core_path') . 'components/messenger/') . 'model/messenger/', $scriptProperties)) {
	return 'Could not load Messenger class!'; die();
}
switch ($modx->event->name) {
	case 'OnLoadWebDocument':
		$authenticated = $modx->user->isAuthenticated($modx->context->get('key'));
		if ($authenticated) {
			$Messenger->initialize($modx->context->key, $scriptProperties);
			$Messenger->userOnline($modx->user->id);
		}

	break;
}