<?php
// This file declares managed database records.
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see 'hook_civicrm_managed' at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array(
  'ParticipantStatus_Removed' => array(
    'name' => 'ParticipantStatus_Removed',
    'entity' => 'ParticipantStatusType',
    'params' =>
    array(
      'version' => 3,
      'name' => 'Removed_in_EventBrite',
      'label' => 'Removed in EventBrite',
      'class' => 'Negative',
      'is_reserved' => '1',
      'is_active' => '1',
      'is_counted' => '0',
      'visibility_id' => '2',
      'weight' => '1000',
    ),
  ),
);
