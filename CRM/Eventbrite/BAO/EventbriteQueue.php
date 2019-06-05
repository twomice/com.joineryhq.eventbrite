<?php
use CRM_Eventbrite_ExtensionUtil as E;

class CRM_Eventbrite_BAO_EventbriteQueue extends CRM_Eventbrite_DAO_EventbriteQueue {

  const STATUS_ID_NEW = 1;
  const STATUS_ID_PROCESSED = 2;

  /**
   * Create a new EventbriteQueue based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Eventbrite_DAO_EventbriteQueue|NULL
   *
  public static function create($params) {
    $className = 'CRM_Eventbrite_DAO_EventbriteQueue';
    $entityName = 'EventbriteQueue';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  } */

}
