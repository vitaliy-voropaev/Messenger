<?php
$xpdo_meta_map['MessengerDialog']= array (
  'package' => 'messenger',
  'version' => '1.1',
  'table' => 'messenger_dialogues',
  'extends' => 'xPDOSimpleObject',
  'fields' => 
  array (
    'name' => '',
    'users' => '',
    'owner' => '',
  ),
  'fieldMeta' => 
  array (
    'name' => 
    array (
      'dbtype' => 'longtext',
      'phptype' => 'text',
      'null' => true,
      'default' => '',
    ),
    'users' => 
    array (
      'dbtype' => 'longtext',
      'phptype' => 'text',
      'null' => true,
      'default' => '',
    ),
    'owner' => 
    array (
      'dbtype' => 'text',
      'phptype' => 'text',
      'null' => true,
      'default' => '',
    ),
  ),
);
