<?php
if (!$Messenger = $modx->getService('messenger', 'Messenger', $modx->getOption('messenger_core_path', null, $modx->getOption('core_path') . 'components/messenger/') . 'model/messenger/', $scriptProperties)) {
    return 'Could not load Messenger class!';
}

$notifications = $Messenger->checkUnread(false);
$output = $modx->getChunk('tpl.Messenger.notifications', array('count' => $notifications));

return $output;