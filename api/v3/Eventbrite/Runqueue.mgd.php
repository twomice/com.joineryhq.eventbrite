<?php
// This file declares a managed database record of type "Job".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return array(
  0 =>
  array(
    'name' => 'Cron:Eventbrite.Runqueue',
    'entity' => 'Job',
    'params' =>
    array(
      'version' => 3,
      'name' => 'Call Eventbrite.Runqueue API',
      'description' => 'Call Eventbrite.Runqueue API',
      'run_frequency' => 'Always',
      'api_entity' => 'Eventbrite',
      'api_action' => 'Runqueue',
      'parameters' => 'runInNonProductionEnvironment=TRUE',
    ),
  ),
);
