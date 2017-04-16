<?php
$xpdo_meta_map['MessengerMessageUnread']= array (
  'package' => 'messenger',
  'version' => '1.1',
  'table' => 'messenger_messages_unread',
  'extends' => 'xPDOObject',
  'fields' => 
  array (
    'message_id' => 0,
    'dialog' => 0,
    'from' => 0,
    'to' => 0,
  ),
  'fieldMeta' => 
  array (
    'message_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'attributes' => 'unsigned',
      'null' => false,
      'default' => 0,
    ),
    'dialog' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'attributes' => 'unsigned',
      'null' => false,
      'default' => 0,
    ),
    'from' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'attributes' => 'unsigned',
      'null' => false,
      'default' => 0,
    ),
    'to' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'attributes' => 'unsigned',
      'null' => false,
      'default' => 0,
    ),
  ),
);
