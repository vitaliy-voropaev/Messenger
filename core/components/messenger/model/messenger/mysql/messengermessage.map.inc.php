<?php
$xpdo_meta_map['MessengerMessage']= array (
  'package' => 'messenger',
  'version' => '1.1',
  'table' => 'messenger_messages',
  'extends' => 'xPDOSimpleObject',
  'fields' => 
  array (
    'message' => '',
    'from' => '',
    'dialog' => '',
    'timestamp' => NULL,
  ),
  'fieldMeta' => 
  array (
    'message' => 
    array (
      'dbtype' => 'text',
      'phptype' => 'text',
      'null' => true,
      'default' => '',
    ),
    'from' => 
    array (
      'dbtype' => 'text',
      'phptype' => 'text',
      'null' => true,
      'default' => '',
    ),
    'dialog' => 
    array (
      'dbtype' => 'text',
      'phptype' => 'text',
      'null' => true,
      'default' => '',
    ),
    'timestamp' => 
    array (
      'dbtype' => 'timestamp',
      'phptype' => 'timestamp',
      'null' => true,
    ),
  ),
);
