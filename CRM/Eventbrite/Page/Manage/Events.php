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
    // Note on array keys:  for the following reasons, we're using non-existent
    // values of CRM_Core_Action::[constants] for array keys:
    //   * We want all the goodies that come with the link-building code invoked
    //     by parent::browse(). The alternative is hard-coding in the template or
    //     some other klunky thing, but we lose soem of those goodies.
    //   * Anyone who can access this page should have all the actions, so the
    //     one thing we don't need is permissions handling.
    //   * The link-building code invoked by parent::browse() expects a limited
    //     number of possible array keys, such as CRM_Core_Action::DELETE. We're
    //     using a couple of those, but also using some of our own. We could abuse
    //     some rately used ones like CRM_Core_Action::FOLLOWUP, but that becomes
    //     nonsensical after doing it once or twice.  So for these actions of our
    //     own, we need to create some new array key.
    //   * Any key with a value of CRM_Core_Action::MAX_ACTION or greater will
    //     cause some other keys to go missing (namely CRM_Core_Action::DELETE),
    //     so once we start down this path for one action, we need to stick to
    //     it for the rest of them.
    //   * No negative side-effects of this approach were found in development.
    //     If they arise, we should re-think this.
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::MAX_ACTION => array(
          'name' => E::ts('Ticket Types'),
          'url' => 'civicrm/admin/eventbrite/manage/tickettypes/',
          'qs' => 'action=browse&pid=%%id%%&reset=1',
          'title' => E::ts('Edit Event Ticket Types'),
          'class' => 'no-popup',
        ),
        (CRM_Core_Action::MAX_ACTION * 2) => array(
          'name' => E::ts('Questions'),
          'url' => 'civicrm/admin/eventbrite/manage/fields/',
          'qs' => 'action=browse&pid=%%id%%&reset=1',
          'title' => E::ts('Edit Event Questions'),
          'class' => 'no-popup',
        ),
        (CRM_Core_Action::MAX_ACTION * 3) => array(
          'name' => E::ts('Edit'),
          'url' => 'civicrm/admin/eventbrite/manage/events/',
          'qs' => 'action=update&id=%%id%%&reset=1',
          'title' => E::ts('Edit Event Configuration'),
        ),
        (CRM_Core_Action::MAX_ACTION * 4) => array(
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
      if (empty($event['name'])) {
        // The configured event was apparently not found in EB, so just skip it.
        // It will appear without an EB name in the table, but that's inevitable
        // since we can't find the actual EB name.
        continue;
      }
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
