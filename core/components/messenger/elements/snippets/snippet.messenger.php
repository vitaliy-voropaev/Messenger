<?php
if (!$Messenger = $modx->getService('messenger', 'Messenger', $modx->getOption('messenger_core_path', null, $modx->getOption('core_path') . 'components/messenger/') . 'model/messenger/', $scriptProperties)) {
    return 'Could not load Messenger class!';
}

$Messenger->initialize($modx->context->key, $scriptProperties);

$messages = $Messenger->messageLoad(false, false, false);
$dialogues = $Messenger->dialogues_load(false, false);
$dialog = $Messenger->dialog_load('', false);

$output = $modx->getChunk('tpl.Messenger.outer', array(
    'dialogues' => $dialogues,
    'dialog' => $dialog
));

return $output;