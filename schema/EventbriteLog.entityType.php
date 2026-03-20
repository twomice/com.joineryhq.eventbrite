<?php
use CRM_Eventbrite_ExtensionUtil as E;

return [
  'name' => 'EventbriteLog',
  'table' => 'civicrm_eventbrite_log',
  'class' => 'CRM_Eventbrite_DAO_EventbriteLog',
  'getInfo' => fn() => [
    'title' => E::ts('Eventbrite Log'),
    'title_plural' => E::ts('Eventbrite Logs'),
    'description' => E::ts('Log messages for Eventbrite Integration'),
    'log' => TRUE,
    'add' => '5.0',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique EventbriteLog ID'),
      'add' => '5.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'created_date' => [
      'title' => E::ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => E::ts('When was the log entry created.'),
      'add' => '5.0',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => ['export'],
    ],
    'message_type_id' => [
      'title' => E::ts('Message Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Message type. See CRM_Eventbrite_BAO_EventbriteLog::MESSAGE_TYPE_ID_*'),
      'add' => '5.0',
    ],
    'message' => [
      'title' => E::ts('Message'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'required' => TRUE,
      'description' => E::ts('Logged message'),
      'add' => '5.0',
    ],
  ],
];
