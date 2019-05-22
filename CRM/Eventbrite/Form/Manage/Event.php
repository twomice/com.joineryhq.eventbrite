<?php


/**
 * Description of Events
 *
 * @author as
 */
use CRM_Eventbrite_ExtensionUtil as E;

class CRM_Eventbrite_Form_Manage_Event extends CRM_Admin_Form {
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
      $civicrmEventOptions = $ebEventOptions = array('' => '');

      // Get Eventbrite events for the default Eventbrite organizaiton.
      $organizationId = _eventbrite_civicrmapi('Setting', 'getvalue', [
        'name' => "eventbrite_api_organization_id",
      ]);
      $eb = CRM_Eventbrite_EvenbriteApi::singleton();
      if ($ebEvents = CRM_Utils_Array::value('events', $eb->request("/organizations/{$organizationId}/events/"))) {
        foreach ($ebEvents as $ebEvent) {
          $ebEventOptions[$ebEvent['id']] = "{$ebEvent['name']['text']} (ID: {$ebEvent['id']})";
        }
      }
      asort($ebEventOptions);

      $this->add(
        'select', // field type
        'eb_entity_id', // field name
        E::ts('Evenbrite Event'), // field label
        $ebEventOptions, // list of options
        TRUE // is required
      );

      // Get all active civicrm events
      $result = _eventbrite_civicrmapi('event', 'get', array(
        'is_active' => 1,
        'is_template' => 0,
        'options' => array(
          'limit' => 0,
          'order' => 'title',
        ),
      ));
      foreach ($result['values'] as $value) {
        $civicrmEventOptions[$value['id']] = $value['title'] . " (ID: {$value['id']})";
      }
      $this->add(
        'select', // field type
        'civicrm_entity_id', // field name
        E::ts('CiviCRM Event'), // field label
        $civicrmEventOptions, // list of options
        TRUE // is required
      );

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
        'civicrm_entity_type' => 'event',
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
        'civicrm_entity_type' => 'event',
        'civicrm_entity_id' => $submitted['civicrm_entity_id'],
        'eb_entity_type' => 'event',
        'eb_entity_id' => $submitted['eb_entity_id'],
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
        'civicrm_entity_type' => "event",
        'eb_entity_type' => "event",
        'eb_entity_id' => CRM_Utils_Array::value('eb_entity_id', $submitted),
      );
      if ($id = $this->_id) {
        $apiParams['id'] = array('!=' => $id);
      }
      if (_eventbrite_civicrmapi('EventbriteLink', 'getcount', $apiParams)) {
        $errorMessage = E::ts('This Eventbrite event is already linked to a CiviCRM event.');
        $this->_errors['eb_entity_id'] = $errorMessage;
      }
    }
    return (0 == count($this->_errors));

  }

}
