<?php
use CRM_Eventbrite_ExtensionUtil as E;

class CRM_Eventbrite_BAO_EventbriteLink extends CRM_Eventbrite_DAO_EventbriteLink {

  /**
   * Create a new EventbriteLink based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Eventbrite_DAO_EventbriteLink|NULL
   */
  // public static function create($params) {
  //   $className = 'CRM_Eventbrite_DAO_EventbriteLink';
  //   $entityName = 'EventbriteLink';
  //   $hook = empty($params['id']) ? 'create' : 'edit';

  //   CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
  //   $instance = new $className();
  //   $instance->copyValues($params);
  //   $instance->save();
  //   CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

  //   return $instance;
  // } */

  /**
   * Fetch object based on array of properties.
   *
   * @param array $params
   *   (reference ) an assoc array of name/value pairs.
   * @param array $defaults
   *   (reference ) an assoc array to hold the flattened values.
   *
   * @return CRM_Core_BAO_OptionGroup
   */
  public static function retrieve(&$params, &$defaults) {
    $eventbriteLink = new CRM_Eventbrite_DAO_EventbriteLink();
    $eventbriteLink->copyValues($params);
    if ($eventbriteLink->find(TRUE)) {
      CRM_Core_DAO::storeValues($eventbriteLink, $defaults);
      return $eventbriteLink;
    }
    return NULL;
  }

}
