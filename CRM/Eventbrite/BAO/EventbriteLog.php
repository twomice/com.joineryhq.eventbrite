<?php
use CRM_Eventbrite_ExtensionUtil as E;

class CRM_Eventbrite_BAO_EventbriteLog extends CRM_Eventbrite_DAO_EventbriteLog {

  const MESSAGE_TYPE_ID_INBOUND_WEBHOOK = 1;
  const MESSAGE_TYPE_ID_EVENTBRITE_ERROR = 2;
  const MESSAGE_TYPE_ID_CIVICRM_ERROR = 3;
  const MESSAGE_TYPE_ID_GENERAL = 4;
  
  /**
   * Create a new EventbriteLog based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Eventbrite_DAO_EventbriteLog|NULL
   */
  public static function create($params) {
    $className = 'CRM_Eventbrite_DAO_EventbriteLog';
    $entityName = 'EventbriteLog';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

}
