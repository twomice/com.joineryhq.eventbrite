<?php

/**
 * Description of Events
 *
 * @author as
 */
use CRM_Eventbrite_ExtensionUtil as E;

class CRM_Eventbrite_Page_Manage_Tickettypes extends CRM_Core_Page_Basic {

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
    return 'CRM_Eventbrite_BAO_EventbriteLink_Tickettype';
  }

  private $ebTicketTypes = array();
  private $roleLabels = array();
  private $ebEventId;

  /**
   * @inheritDoc
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => E::ts('Edit'),
          'url' => 'civicrm/admin/eventbrite/manage/tickettypes/',
          'qs' => 'action=update&id=%%id%%&reset=1&pid=' . $this->get('pid'),
          'title' => E::ts('Edit Ticket Type Configuration'),
        ),
        CRM_Core_Action::DELETE => array(
          'name' => E::ts('Delete'),
          'url' => 'civicrm/admin/eventbrite/manage/tickettypes/',
          'qs' => 'action=delete&id=%%id%%&pid=' . $this->get('pid'),
          'title' => E::ts('Delete Ticket Type Configuration'),
        ),
      );
    }
    return self::$_links;
  }

  /**
   * @inheritDoc
   */
  public function run() {
    if (!$this->get('pid')) {
      $this->set('pid', CRM_Utils_Request::retrieve('pid', 'Positive'));
    }

    $breadCrumb = array(
      'title' => E::ts('Eventbrite Events'),
      'url' => CRM_Utils_System::url('civicrm/admin/eventbrite/manage/events', 'action=browse&reset=1&pid=' . $this->get('pid')),
    );
    CRM_Utils_System::appendBreadCrumb(array($breadCrumb));

    $breadCrumb = array(
      'title' => E::ts('Eventbrite Ticket Types'),
      'url' => CRM_Utils_System::url('civicrm/admin/eventbrite/manage/tickettypes', 'action=browse&reset=1&pid=' . $this->get('pid')),
    );
    CRM_Utils_System::appendBreadCrumb(array($breadCrumb));

    return parent::run();
  }

  /**
   * @inheritDoc
   */
  public function browse() {
    if (empty($this->get('pid'))) {
      throw new CRM_Extension_Exception('Missing pid. Cannot continue.');
    }
    $this->ebEventId = _eventbrite_civicrmapi('EventbriteLink', 'getvalue', array(
      'return' => "eb_entity_id",
      'id' => $this->get('pid'),
      'eb_entity_type' => 'event',
      'civicrm_entity_type' => 'event',
    ));
    if (empty($this->ebEventId)) {
      throw new CRM_Core_Exception('Provided pid does not reference a valid Eventbrite event. Cannot continue.');
    }

    $links = &$this->links();

    $baoString = $this->getBAOName();
    $object = new $baoString();
    $where = crm_core_dao::composeQuery('parent_id = %1 AND eb_entity_type = "TicketType"', array(
      '1' => array($this->get('pid'), 'String'),
    ));
    $object->whereAdd($where);

    $rows = [];

    // find all objects
    $object->find();
    while ($object->fetch()) {
      $row = [];
      CRM_Core_DAO::storeValues($object, $row);

      // populate action links
      $this->action($object, $action = NULL, $row, $links, $permission = NULL);

      $row['eb_ticket_type'] = $this->_getEbTicketType($object->eb_entity_id);
      $row['civicrm_role'] = $this->_getRoleLabel($object->civicrm_entity_id);

      // Add to rows with a sortable key.
      $rows["{$row['eb_ticket_type']} {$object->id}"] = $row;
    }
    // Sort rows by keys.
    ksort($rows);
    $this->assign('rows', $rows);
    $this->assign('pid', $this->get('pid'));

    // Get and assign eb event title
    $eb = CRM_Eventbrite_EvenbriteApi::singleton();
    $result = $eb->request("/events/{$this->ebEventId}/");
    $eventTitle = $result['name']['text'];
    $this->assign('eventTitle', $eventTitle);

    CRM_Utils_System::setTitle(E::ts('Eventbrite Integration: Ticket Types') . ': ' . $eventTitle);

  }

  /**
   * @inheritDoc
   */
  public function editForm() {
    return 'CRM_Eventbrite_Form_Manage_Tickettype';
  }

  /**
   * @inheritDoc
   */
  public function editName() {
    return E::ts('Eventbrite Ticket Type Configuration');
  }

  /**
   * @inheritDoc
   */
  public function userContext($mode = NULL) {
    return 'civicrm/admin/eventbrite/manage/tickettypes';
  }

  public function userContextParams($mode = NULL) {
    return 'reset=1&action=browse&pid=' . $this->get('pid');
  }

  private function _getEbTicketType($ticketTypeId) {
    if (empty($ticketTypeId)) {
      return '';
    }

    if (empty($this->ebTicketTypes[$ticketTypeId])) {
      $eb = CRM_Eventbrite_EvenbriteApi::singleton();
      $result = $eb->request("/events/{$this->ebEventId}/ticket_classes/{$ticketTypeId}/");
      $this->ebTicketTypes[$ticketTypeId] = $result['name'];
    }

    return $this->ebTicketTypes[$ticketTypeId];

  }

  private function _getRoleLabel($roleId) {
    if (empty($roleId)) {
      return '';
    }

    if (empty($this->roleLabels[$roleId])) {
      $this->roleLabels[$roleId] = _eventbrite_civicrmapi('OptionValue', 'getvalue', [
        'return' => "label",
        'option_group_id' => "participant_role",
        'value' => $roleId,
      ]);
    }
    return $this->roleLabels[$roleId];
  }

}
