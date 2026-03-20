<?php
use CRM_Eventbrite_ExtensionUtil as E;

return [
  'name' => 'EventbriteQueue',
  'table' => 'civicrm_eventbrite_queue',
  'class' => 'CRM_Eventbrite_DAO_EventbriteQueue',
  'getInfo' => fn() => [
    'title' => E::ts('Eventbrite Queue'),
    'title_plural' => E::ts('Eventbrite Queues'),
    'description' => E::ts('Queued Eventbrite webhook events'),
    'log' => TRUE,
    'add' => '5.0',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique EventbriteQueue ID'),
      'add' => '5.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'created_date' => [
      'title' => E::ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => E::ts('When was the queue entry created.'),
      'add' => '5.0',
      'default' => 'CURRENT_TIMESTAMP',
      'usage' => ['export'],
    ],
    'message' => [
      'title' => E::ts('Message'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Contents of the webhook event notification'),
      'add' => '5.0',
    ],
    'status_id' => [
      'title' => E::ts('Status ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Status. See CRM_Eventbrite_BAO_EventbriteQueue::STATUS_ID_*'),
      'add' => '5.0',
      'default' => 1,
    ],
  ],
];
