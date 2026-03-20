<?php
use CRM_Eventbrite_ExtensionUtil as E;

return [
  'name' => 'EventbriteLink',
  'table' => 'civicrm_eventbrite_link',
  'class' => 'CRM_Eventbrite_DAO_EventbriteLink',
  'getInfo' => fn() => [
    'title' => E::ts('Eventbrite Link'),
    'title_plural' => E::ts('Eventbrite Links'),
    'description' => E::ts('Links between CiviCRM entities and Eventbrite entities'),
    'log' => TRUE,
    'add' => '5.0',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique EventbriteLink ID'),
      'add' => '5.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'civicrm_entity_type' => [
      'title' => E::ts('Civicrm Entity Type'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Type of CiviCRM entity'),
      'add' => '5.0',
    ],
    'civicrm_entity_id' => [
      'title' => E::ts('Civicrm Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('ID of CiviCRM entity'),
      'add' => '5.0',
    ],
    'eb_entity_type' => [
      'title' => E::ts('Eb Entity Type'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Type of Eventbrite entity'),
      'add' => '5.0',
    ],
    'eb_entity_id' => [
      'title' => E::ts('Eb Entity ID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('ID of Eventbrite entity'),
      'add' => '5.0',
    ],
    'parent_id' => [
      'title' => E::ts('Parent ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to id'),
      'add' => '5.0',
      'entity_reference' => [
        'entity' => 'EventbriteLink',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
