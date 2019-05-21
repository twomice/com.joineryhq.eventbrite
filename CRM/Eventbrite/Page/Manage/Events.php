<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Events
 *
 * @author as
 */
use CRM_Textselect_ExtensionUtil as E;

class CRM_Eventbrite_Page_Manage_Events extends CRM_Core_Page_Basic {
  public $useLivePageJS = TRUE;

  static $_links = NULL;

  public function getBAOName() {
    return 'CRM_Eventbrite_BAO_EventbriteLink_Event';
  }

  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/admin/eventbrite/manage/event',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => ts('Edit Event Configuration'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/admin/eventbrite/manage/event',
          'qs' => 'action=delete&id=%%id%%',
          'title' => ts('Delete Event Configuration'),
        ),
      );
    }
    return self::$_links;
  }

  public function editForm() {
    return 'CRM_Eventbrite_Form_Manage_Event';
  }

  public function editName() {
    return 'Event Configuration';
  }

  public function userContext($mode = NULL) {
    return 'civicrm/admin/eventbrite/manage/events';
  }

  public function browse() {
    parent::browse();

    $tpl = CRM_Core_Smarty::singleton();
    $rows = $tpl->get_template_vars('rows');
    $eb = CRM_Eventbrite_EvenbriteApi::singleton();
    foreach ($rows as &$row) {
      $event = $eb->request("/events/{$row['eb_entity_id']}/");
      $row['eb_event_name'] = CRM_Utils_Array::value('text', $event['name']);
    }
    $tpl->assign('rows', $rows);
  }
}
