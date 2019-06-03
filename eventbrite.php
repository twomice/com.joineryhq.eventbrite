<?php

/**
 * TODOS:
 *  - use post hook to delete eventbritelink 'event' records on event.delete op.
 *  - ensure webhook creation process creates TWO webhooks -- one for each event
 *    (order.update, attendee.update), using something like "?webhook_version=1"
 *    in case we later get support for multiple events on a single webhook; and
 *    then be sure the process actually confirms both webhooks with their versions
 *    and events.
 */

require_once 'eventbrite.civix.php';
use CRM_Eventbrite_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_config
 */
function eventbrite_civicrm_config(&$config) {
  _eventbrite_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_xmlMenu
 */
function eventbrite_civicrm_xmlMenu(&$files) {
  _eventbrite_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_install
 */
function eventbrite_civicrm_install() {
  _eventbrite_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_postInstall
 */
function eventbrite_civicrm_postInstall() {
  _eventbrite_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_uninstall
 */
function eventbrite_civicrm_uninstall() {
  _eventbrite_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_enable
 */
function eventbrite_civicrm_enable() {
  _eventbrite_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_disable
 */
function eventbrite_civicrm_disable() {
  _eventbrite_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_upgrade
 */
function eventbrite_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _eventbrite_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_managed
 */
function eventbrite_civicrm_managed(&$entities) {
  _eventbrite_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_caseTypes
 */
function eventbrite_civicrm_caseTypes(&$caseTypes) {
  _eventbrite_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules
 */
function eventbrite_civicrm_angularModules(&$angularModules) {
  _eventbrite_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_alterSettingsFolders
 */
function eventbrite_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _eventbrite_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_entityTypes
 */
function eventbrite_civicrm_entityTypes(&$entityTypes) {
  _eventbrite_civix_civicrm_entityTypes($entityTypes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_preProcess
 *
function eventbrite_civicrm_preProcess($formName, &$form) {

} // */

/**
 * For an array of menu items, recursively get the value of the greatest navID
 * attribute.
 * @param <type> $menu
 * @param <type> $max_navID
 */
function _eventbrite_get_max_navID(&$menu, &$max_navID = NULL) {
  foreach ($menu as $id => $item) {
    if (!empty($item['attributes']['navID'])) {
      $max_navID = max($max_navID, $item['attributes']['navID']);
    }
    if (!empty($item['child'])) {
      _eventbrite_get_max_navID($item['child'], $max_navID);
    }
  }
}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_navigationMenu
 */
function eventbrite_civicrm_navigationMenu(&$menu) {
  _eventbrite_get_max_navID($menu, $max_navID);
  _eventbrite_civix_insert_navigation_menu($menu, 'Administer/CiviEvent', array(
    'label' => E::ts('Eventbrite Integration'),
    'name' => 'Eventbrite Integration',
    'url' => NULL,
    'permission' => 'administer CiviCRM',
    'operator' => 'AND',
    'separator' => NULL,
    'navID' => ++$max_navID,
  ));
  _eventbrite_civix_insert_navigation_menu($menu, 'Administer/CiviEvent/Eventbrite Integration', array(
    'label' => E::ts('Settings'),
    'name' => 'Settings',
    'url' => 'civicrm/admin/eventbrite/settings',
    'permission' => 'administer CiviCRM',
    'operator' => 'AND',
    'separator' => NULL,
    'navID' => ++$max_navID,
  ));
  _eventbrite_civix_insert_navigation_menu($menu, 'Administer/CiviEvent/Eventbrite Integration', array(
    'label' => E::ts('Events'),
    'name' => 'Events',
    'url' => 'civicrm/admin/eventbrite/manage/events?action=browse&reset=1',
    'permission' => 'administer CiviCRM',
    'operator' => 'AND',
    'separator' => NULL,
    'navID' => ++$max_navID,
  ));
  _eventbrite_civix_navigationMenu($menu);
}

/**
 * Log CiviCRM API errors to CiviCRM log.
 */
function _eventbrite_log_api_error(CiviCRM_API3_Exception $e, $entity, $action, $params) {
  $message = "CiviCRM API Error '{$entity}.{$action}': ". $e->getMessage() .'; ';
  $message .= "API parameters when this error happened: ". json_encode($params) .'; ';
  $bt = debug_backtrace();
  $error_location = "{$bt[1]['file']}::{$bt[1]['line']}";
  $message .= "Error API called from: $error_location";
  CRM_Core_Error::debug_log_message($message);
}

/**
 * CiviCRM API wrapper. Wraps with try/catch, redirects errors to log, saves
 * typing.
 */
function _eventbrite_civicrmapi($entity, $action, $params, $silence_errors = TRUE) {
  try {
    $result = civicrm_api3($entity, $action, $params);
  } catch (CiviCRM_API3_Exception $e) {
    _eventbrite_log_api_error($e, $entity, $action, $params);
    if (!$silence_errors) {
      throw $e;
    }
  }

  return $result;
}
