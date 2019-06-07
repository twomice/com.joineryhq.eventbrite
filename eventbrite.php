<?php

/**
 * TODOS:
 *  - use post hook to delete eventbritelink 'event' records on event.delete op.
 */

require_once 'eventbrite.civix.php';
use CRM_Eventbrite_ExtensionUtil as E;

/**
 * Implements hook_civicrm_post().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_post
 */
function eventbrite_civicrm_post($op, $objectName, $objectId, &$objectRef) {
  if ($op == 'delete') {
    if (in_array($objectName, array('Participant', 'Contribution', 'Event'))) {
      $result = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
        'civicrm_entity_type' => $objectName,
        'civicrm_entity_id' => $objectId,
        'api.EventbriteLink.delete' => array(),
      ));
    }
    if ($objectName == 'Participant') {
      // Also delete PrimaryParticant links.
      $result = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
        'civicrm_entity_type' => 'PrimaryParticipant',
        'civicrm_entity_id' => $objectId,
        'api.EventbriteLink.delete' => array(),
      ));
    }
  }
}

/**
 * Implements hook_civicrm_pageRun().
 *
 * @link http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_pageRun
 */
function eventbrite_civicrm_pageRun(&$page) {
  // If permission and configs are right, display EventbriteLink data for certain entities.
  if (
    CRM_Core_Permission::check('Administer CiviCRM')
    && _eventbrite_civicrmapi('Setting', 'getvalue', array('name' => "eventbrite_is_link_inspect"))
  ) {
    $pageName = $page->getVar('_name');

    $entityPerPage = array(
      'CRM_Event_Page_Tab' => 'participant',
      'CRM_Contribute_Page_Tab' => 'contribution',
      'CRM_Contact_Page_View_Summary' => 'contact',
    );
    if ($entity = CRM_Utils_Array::value($pageName, $entityPerPage)) {
      $ebToken = _eventbrite_civicrmapi('Setting', 'getvalue', array('name' => "eventbrite_api_token"));
      switch ($entity) {
        case 'contact':
          $link = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
            'civicrm_entity_type' => 'event',
            'eb_entity_type' => 'event',
            'options' => array(
              'limit' => 0,
            )
          ));
          $eventIds = crm_utils_array::collect('civicrm_entity_id', $link['values']);

          $cid = $page->getVar('_contactId');
          $participant = _eventbrite_civicrmapi('participant', 'get', array(
            'contact_id' => $cid,
            'event_id' => array('IN' => $eventIds),
            'api.EventbriteLink.get' => array(
              'civicrm_entity_type' => 'participant',
              'eb_entity_type' => 'attendee',
              'civicrm_entity_id' => '$value.id',
            ),
          ));
          $participantAttendees = array();
          foreach ($participant['values'] as $value) {
            if (!empty($value['api.EventbriteLink.get']['values'][0])) {
              $pid = $value['api.EventbriteLink.get']['values'][0]['civicrm_entity_id'];
              $aid = $value['api.EventbriteLink.get']['values'][0]['eb_entity_id'];
              $participantAttendees[$pid] = $aid;
            }
          }

          if (!empty($participantAttendees)) {
            $msg = E::ts('This contact is linked to Eventbrite Attendees throug participant records:') . '<ul>';
            foreach ($participantAttendees as $participantId => $attendeeId) {
              $tsParams = array(
                '1' => $participantId,
                '2' => $attendeeId,
                '3' => CRM_Utils_System::url('civicrm/contact/view/participant', "reset=1&id=$participantId&cid=$cid&action=view&context=participant&selectedChild=event"),
                '4' => "https://www.eventbriteapi.com/v3/attendees/{$attendeeId}/?token={$ebToken}",
              );
              $msg .= '<li>' . E::ts('<a href="%3">Participant %1</a> is linked to <a href="%4" target="_blank">Eventbrite Attendee %2</a>.', $tsParams) . '</li>';
            }
            $msg .= '</ul>';
            CRM_Core_Session::setStatus($msg, E::ts('Eventbrite linked entity'), 'info');
          }
          break;

        case 'participant':
          if ($page->_id) {
            $link = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
              'civicrm_entity_type' => 'participant',
              'eb_entity_type' => 'attendee',
              'civicrm_entity_id' => $page->_id,
              'sequential' => 1,
            ));
            if(!empty($link['values'][0])) {
              $attendeeId = $link['values'][0]['eb_entity_id'];
              $tsParams = array(
                '1' => $attendeeId,
                '2' => "https://www.eventbriteapi.com/v3/attendees/{$attendeeId}/?token={$ebToken}",
              );
              $msg = E::ts('This participant is linked to <a href="%2" target="_blank">Eventbrite Attendee: %1</a>.', $tsParams);
              CRM_Core_Session::setStatus($msg, E::ts('Eventbrite linked entity'), 'info', array('expires' => 0));
            }
          }
          break;

        case 'contribution':
          if ($page->_id) {
            $link = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
              'civicrm_entity_type' => 'contribution',
              'eb_entity_type' => 'order',
              'civicrm_entity_id' => $page->_id,
              'sequential' => 1,
            ));
            if(!empty($link['values'][0])) {
              $orderId = $link['values'][0]['eb_entity_id'];
              $tsParams = array(
                '1' => $orderId,
                '2' => "https://www.eventbriteapi.com/v3/orders/{$orderId}/?token={$ebToken}&expand=attendees,attendee-answers",

              );
              $msg = E::ts('This contribution is linked to <a href="%2" target="_blank">Eventbrite Order: %1</a>.', $tsParams);
              CRM_Core_Session::setStatus($msg, E::ts('Eventbrite linked entity'), 'info', array('expires' => 0));
            }
          }
          break;
      }
    }
  }
}

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
function _eventbrite_log_api_error(CiviCRM_API3_Exception $e, $entity, $action, $contextMessage = NULL, $params) {
  $message = "CiviCRM API Error '{$entity}.{$action}': " . $e->getMessage() . '; ';
  $message .= "API parameters when this error happened: " . json_encode($params) . '; ';
  $bt = debug_backtrace();
  $error_location = "{$bt[1]['file']}::{$bt[1]['line']}";
  $message .= "Error API called from: $error_location";
  CRM_Core_Error::debug_log_message($message);

  $eventbriteLogMessage = $message;
  if ($contextMessage) {
    $eventbriteLogMessage .= "; Context: $contextMessage";
  }
  CRM_Eventbrite_BAO_EventbriteLog::create(array(
    'message' => $eventbriteLogMessage,
    'message_type_id' => CRM_Eventbrite_BAO_EventbriteLog::MESSAGE_TYPE_ID_CIVICRM_API_ERROR,
  ));
}

/**
 * CiviCRM API wrapper. Wraps with try/catch, redirects errors to log, saves
 * typing.
 *
 * @param string $entity as in civicrm_api3($ENTITY, ..., ...)
 * @param string $action as in civicrm_api3(..., $ACTION, ...)
 * @param array $params as in civicrm_api3(..., ..., $PARAMS)
 * @param string $contextMessage Additional message for inclusion in EventbriteLog upon any failures.
 * @param bool $silence_errors If TRUE, throw any exceptions we catch; otherwise don't.
 *
 * @return Array result of civicrm_api3()
 * @throws CiviCRM_API3_Exception
 */
function _eventbrite_civicrmapi($entity, $action, $params, $contextMessage = NULL, $silence_errors = TRUE) {
  try {
    $result = civicrm_api3($entity, $action, $params);
  }
  catch (CiviCRM_API3_Exception $e) {
    _eventbrite_log_api_error($e, $entity, $action, $contextMessage, $params);
    if (!$silence_errors) {
      throw $e;
    }
  }

  return $result;
}
