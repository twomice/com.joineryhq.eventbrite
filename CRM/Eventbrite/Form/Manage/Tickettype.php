<?php


/**
 * Description of Events
 *
 * @author as
 */
use CRM_Eventbrite_ExtensionUtil as E;

class CRM_Eventbrite_Form_Manage_Tickettype extends CRM_Admin_Form {

  public function preProcess() {
    parent::preProcess();
    if (!($this->_action & CRM_Core_Action::DELETE)) {
      if (!$this->get('pid')) {
        $this->set('pid', CRM_Utils_Request::retrieve('pid', 'Positive'));
        $parentLink = _eventbrite_civicrmapi('EventbriteLink', 'getSingle', array(
          'return' => array('eb_entity_id', 'civicrm_entity_id'),
          'id' => $this->get('pid'),
          'eb_entity_type' => 'event',
          'civicrm_entity_type' => 'event',
        ));
        if (empty($parentLink)) {
          throw new CRM_Core_Exception('Provided pid does not reference a valid configuration. Cannot continue.');
        }
        $this->set('parentLink', $parentLink);
      }
    }
  }
  
  /**
   * Explicitly declare the entity api name.
   */
  public function getDefaultEntity() {
    return 'EventbriteLink';
  }

  public function buildQuickForm() {
    parent::buildQuickForm();
    $descriptions = array();

    if ($this->_action & CRM_Core_Action::DELETE) {
      $descriptions['delete_warning'] = ts('Are you sure you want to delete this configuration?');
    }
    else {
      // Get Eventbrite ticket types for the given Eventbrite event.
      $eb = CRM_Eventbrite_EvenbriteApi::singleton();
      $parentLink = $this->get('parentLink');
      $result = $eb->request("/events/{$parentLink['eb_entity_id']}/ticket_classes/");
      $ebTicketTypeOptions = array('' => '');
      if ($ebTicketTypes = CRM_Utils_Array::value('ticket_classes', $result)) {
        foreach ($ebTicketTypes as $ebTicketType) {
          $ebTicketTypeOptions[$ebTicketType['id']] = "{$ebTicketType['name']} (ID: {$ebTicketType['id']})";
        }
      }
      asort($ebTicketTypeOptions);

      // Get all active civicrm roles
      $civicrmRoleOptions = array('' => '') + CRM_Event_BAO_Participant::buildOptions('participant_role_id');

      $emptyOptionsMessage = '';
      if (count($ebTicketTypeOptions) <= 1) {
        $emptyOptionsMessage .= E::ts('No Eventbrite ticket types were found for this event.');
      }
      if (count($civicrmRoleOptions) <= 1) {
        $emptyOptionsMessage .= ' ' . E::ts('No participant roles are configured in CiviCRM.');
      }
      if (empty($emptyOptionsMessage)) {
        $this->add(
          'select', // field type
          'eb_entity_id', // field name
          E::ts('Evenbrite Ticket Type'), // field label
          $ebTicketTypeOptions, // list of options
          TRUE // is required
        );
        $this->add(
          'select', // field type
          'civicrm_entity_id', // field name
          E::ts('CiviCRM Role'), // field label
          $civicrmRoleOptions, // list of options
          TRUE // is required
        );
      }
      else {
        CRM_Core_Error::statusBounce($emptyOptionsMessage);
      }

    }

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());
    $this->assign('descriptions', $descriptions);
    $this->assign('id', $this->_id);
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    if ($this->_id && (!($this->_action & CRM_Core_Action::DELETE))) {
      $result = _eventbrite_civicrmapi('EventbriteLink', 'getSingle', array(
        'id' => $this->_id,
        'eb_entity_type' => 'TicketType',
      ));
      return $result;
    }
  }

  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      $result = _eventbrite_civicrmapi('EventbriteLink', 'delete', array(
        'id' => $this->_id,
      ));
    }
    else {
      // store the submitted values in an array
      $submitted = $this->exportValues();
      $apiParams = array(
        'civicrm_entity_type' => 'ParticipantRole',
        'civicrm_entity_id' => $submitted['civicrm_entity_id'],
        'eb_entity_type' => 'TicketType',
        'eb_entity_id' => $submitted['eb_entity_id'],
        'parent_id' => $this->get('pid'),
      );

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $apiParams['id'] = $this->_id;
      }
      $result = _eventbrite_civicrmapi('EventbriteLink', 'create', $apiParams);
    }
    CRM_Core_Session::setStatus(ts('Settings have been saved.'), ts('Saved'), 'success');
  }


  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * @inheritDoc
   */
  public function validate() {
    $error = parent::validate();
    if (!($this->_action & CRM_Core_Action::DELETE)) {
      $submitted = $this->exportValues();
      $apiParams = array(
        'civicrm_entity_type' => "ParticipantRole",
        'eb_entity_type' => "TicketType",
        'eb_entity_id' => CRM_Utils_Array::value('eb_entity_id', $submitted),
        'parent_id' => $this->get('pid'),
      );
      if ($id = $this->_id) {
        $apiParams['id'] = array('!=' => $id);
      }
      if ($count = _eventbrite_civicrmapi('EventbriteLink', 'getcount', $apiParams)) {
        $errorMessage = E::ts('This Eventbrite ticket type is already linked to a CiviCRM participant role.');
        $this->_errors['eb_entity_id'] = $errorMessage;
      }
    }
    return (0 == count($this->_errors));

  }

}
