<?php

if (empty($_REQUEST['action'])) {
	die('Access denied');
}
else {
	$action = $_REQUEST['action'];
}

define('MODX_API_MODE', true);
require_once dirname(dirname(dirname(dirname(__FILE__)))).'/index.php';

$modx->getService('error','error.modError');
$modx->getRequest();
$modx->setLogLevel(modX::LOG_LEVEL_ERROR);
$modx->setLogTarget('FILE');
$modx->error->message = null;

// Get properties
$properties = array();


/* @var Tickets $Tickets */
define('MODX_ACTION_MODE', true);
$Messenger = $modx->getService('messenger','Messenger',$modx->getOption('messenger.core_path',null,$modx->getOption('core_path').'components/messenger/').'model/messenger/', $properties);
if ($modx->error->hasError() || !($Messenger instanceof Messenger)) {
	die('Error');
}

switch ($action) {
	
	case 'message/load': $response = $Messenger->messageLoad($_POST['message_id'], $_POST['type'], true); break;
	case 'message/send': $response = $Messenger->messageSend($_POST['dialog'], $_POST['message']); break;
	case 'message/read': $response = $Messenger->messageRead($_POST['messages'], $_POST['dialog']); break;

	case 'dialogues/load': $response = $Messenger->dialogues_load(true, $_POST['dialog_id']); break;

	case 'dialog/new': $response = $Messenger->dialogNew(); break;
	case 'dialog/new/users': $response = $Messenger->dialog_find_users(); break;
	case 'dialog/load': $response = $Messenger->dialog_load($_POST['dialog'], true); break;

	default:
		$message = $_REQUEST['action'] != $action ? 'tickets_err_register_globals' : 'tickets_err_unknown';
		$response = $modx->toJSON(array('success' => false, 'message' => $modx->lexicon($message)));
}

if (is_array($response)) {
	$response = $modx->toJSON($response);
}

@session_write_close();
exit($response);