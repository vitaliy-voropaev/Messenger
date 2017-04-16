<?php
$xpdo_meta_map['MessengerUser']= array (
  'package' => 'messenger',
  'version' => '1.1',
  'table' => 'messenger_users',
  'extends' => 'xPDOSimpleObject',
  'fields' => 
  array (
    'internalKey' => NULL,
    'lasthit' => NULL,
  ),
  'fieldMeta' => 
  array (
    'internalKey' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'phptype' => 'integer',
      'null' => false,
    ),
    'lasthit' => 
    array (
      'dbtype' => 'timestamp',
      'phptype' => 'timestamp',
      'null' => false,
    ),
  ),
);
