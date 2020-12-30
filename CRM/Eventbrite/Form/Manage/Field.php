<?php

/**
 * Description of Events
 *
 * @author as
 */
use CRM_Eventbrite_ExtensionUtil as E;

class CRM_Eventbrite_Form_Manage_Field extends CRM_Admin_Form {

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
      $descriptions['delete_warning'] = E::ts('Are you sure you want to delete this configuration?');
    }
    else {
      $parentLink = $this->get('parentLink');

      // Get Eventbrite questions for the given Eventbrite event.
      $eb = CRM_Eventbrite_EvenbriteApi::singleton();
      $result = $eb->request("/events/{$parentLink['eb_entity_id']}/questions/");
      $ebFieldOptions = array('' => '');
      if ($ebFields = CRM_Utils_Array::value('questions', $result)) {
        foreach ($ebFields as $ebField) {
          $ebFieldOptions[$ebField['id']] = "{$ebField['question']['text']} (ID: {$ebField['id']})";
        }
      }
      asort($ebFieldOptions);

      // Get all active civicrm custom fields for contact and participant
      $civicrmFieldOptions = array('' => '');
      $result = _eventbrite_civicrmapi('CustomGroup', 'get', [
        'extends' => ['IN' => ["participant", "individual", "contact"]],
        'is_active' => 1,
        'options' => array(
          'limit' => 0,
          'sort' => 'title ASC',
        ),
        'api.CustomField.get' => array(
          'is_view' => 0,
          'is_active' => 1,
          'options' => array(
            'limit' => 0,
            'sort' => 'label ASC',
          ),
        ),
      ]);
      foreach ($result['values'] as $customGroup) {
        if (CRM_Utils_Array::value('extends_entity_column_id', $customGroup) == 2) {
          if (!in_array($parentLink['civicrm_entity_id'], CRM_Utils_Array::value('extends_entity_column_value', $customGroup, array()))) {
            // This custom group is limited to specific events, and this event is not one of them.
            continue;
          }
        }
        elseif (CRM_Utils_Array::value('extends_entity_column_id', $customGroup) == 3) {
          $eventTypeId = _eventbrite_civicrmapi('Event', 'getvalue', [
            'return' => "event_type_id",
            'id' => $parentLink['civicrm_entity_id'],
          ]);
          if (!in_array($eventTypeId, CRM_Utils_Array::value('extends_entity_column_value', $customGroup, array()))) {
            // This custom group is limited to specific types of events, and this event type is not one of them.
            continue;
          }
        }
        foreach ($customGroup['api.CustomField.get']['values'] as $customField) {
          $civicrmFieldOptions[$customField['id']] = "{$customGroup['title']}::{$customField['label']}";
        }
      }

      $emptyOptionsMessage = '';
      if (count($ebFieldOptions) <= 1) {
        $emptyOptionsMessage .= E::ts('No Eventbrite questions were found for this event.');
      }

      if (count($civicrmFieldOptions) <= 1) {
        $emptyOptionsMessage .= ' ' . E::ts('No Contact, Individual, or Participant custom fields are configured for this event.');
      }
      if (empty($emptyOptionsMessage)) {
        $this->add(
          // field type
          'select',
          // field name
          'eb_entity_id',
          // field label
          E::ts('Evenbrite Question'),
          // list of options
          $ebFieldOptions,
          // is required
          TRUE
        );

        $this->add(
          // field type
          'select',
          // field name
          'civicrm_entity_id',
          // field label
          E::ts('CiviCRM Custom Field'),
          // list of options
          $civicrmFieldOptions,
          // is required
          TRUE
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
        'eb_entity_type' => 'Question',
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
        'civicrm_entity_type' => 'CustomField',
        'civicrm_entity_id' => $submitted['civicrm_entity_id'],
        'eb_entity_type' => 'Question',
        'eb_entity_id' => $submitted['eb_entity_id'],
        'parent_id' => $this->get('pid'),
      );

      if ($this->_action & CRM_Core_Action::UPDATE) {
        $apiParams['id'] = $this->_id;
      }
      $result = _eventbrite_civicrmapi('EventbriteLink', 'create', $apiParams);
    }
    CRM_Core_Session::setStatus(E::ts('Settings have been saved.'), E::ts('Saved'), 'success');
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
        'civicrm_entity_type' => "CustomField",
        'eb_entity_type' => "Question",
        'eb_entity_id' => CRM_Utils_Array::value('eb_entity_id', $submitted),
        'parent_id' => $this->get('pid'),
      );
      if ($id = $this->_id) {
        $apiParams['id'] = array('!=' => $id);
      }
      if ($count = _eventbrite_civicrmapi('EventbriteLink', 'getcount', $apiParams)) {
        $errorMessage = E::ts('This Eventbrite question is already linked to a CiviCRM custom field.');
        $this->_errors['eb_entity_id'] = $errorMessage;
      }
    }
    return (0 == count($this->_errors));

  }

}
