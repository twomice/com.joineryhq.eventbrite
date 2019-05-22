<?php

/**
 * Description of Events
 *
 * @author as
 */
use CRM_Eventbrite_ExtensionUtil as E;

class CRM_Eventbrite_Page_Manage_Events extends CRM_Core_Page_Basic {

  /**
   * @inheritDoc
   */
  public $useLivePageJS = TRUE;

  /**
   * @inheritDoc
   */
  static $_links = NULL;

  /**
   * @inheritDoc
   */
  public function getBAOName() {
    return 'CRM_Eventbrite_BAO_EventbriteLink_Event';
  }

  /**
   * @inheritDoc
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => E::ts('Edit'),
          'url' => 'civicrm/admin/eventbrite/manage/events/',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => E::ts('Edit Event Configuration'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => E::ts('Delete'),
          'url' => 'civicrm/admin/eventbrite/manage/events/',
          'qs' => 'action=delete&id=%%id%%',
          'title' => E::ts('Delete Event Configuration'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * @inheritDoc
   */
  public function run() {
    $breadCrumb = array(
      'title' => E::ts('Eventbrite Events'),
      'url' => CRM_Utils_System::url('civicrm/admin/eventbrite/manage/events', 'action=browse&reset=1'),
    );
    CRM_Utils_System::appendBreadCrumb(array($breadCrumb));

    return parent::run();
  }

  /**
   * @inheritDoc
   */
  public function browse() {
    parent::browse();

    $rows = $this->get_template_vars('rows');
    $eb = CRM_Eventbrite_EvenbriteApi::singleton();
    foreach ($rows as &$row) {
      $event = $eb->request("/events/{$row['eb_entity_id']}/");
      $row['eb_event_name'] = CRM_Utils_Array::value('text', $event['name']);
    }
    $this->assign('rows', $rows);
  }

  /**
   * @inheritDoc
   */
  public function editForm() {
    return 'CRM_Eventbrite_Form_Manage_Event';
  }

  /**
   * @inheritDoc
   */
  public function editName() {
    return E::ts('Eventbrite Event Configuration');
  }

  /**
   * @inheritDoc
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/eventbrite/manage/events';
  }

}
