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

class CRM_Eventbrite_Form_Manage_Event extends CRM_Core_Form {
  public function buildQuickForm() {
    $descriptions = array();
    
    $this->action = CRM_Utils_Array::value('action', $_REQUEST, 'browse');
    $this->id = CRM_Utils_Array::value('id', $_REQUEST);
    if ($this->action == 'delete') {
      $descriptions['delete_warning'] = ts('Are you sure you want to delete this configuration?');
    }
    else {
      $civicrmEventOptions = $ebEventOptions = array('' => '');
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
      
      $this->add(
        'select', // field type
        'eb_entity_id', // field name
        E::ts('Evenbrite Event'), // field label
        $ebEventOptions, // list of options
        TRUE // is required
      );

      $this->add('hidden', 'action', $this->action);
    }
    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    // export form elements
    $this->add('hidden', 'action', $this->action);
    $this->add('hidden', 'id', $this->id);
    $this->assign('elementNames', $this->getRenderableElementNames());
    $this->assign('descriptions', $descriptions);
    parent::buildQuickForm();
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    if ($this->id && ($this->action != 'delete')) {
      $result = _eventbrite_civicrmapi('EventbriteLink', 'getSingle', array(
        'id' => $this->id,
        'civicrm_entity_type' => 'event',
      ));
      return $result;
    }
  }

  public function postProcess() {
    $submitted = $this->exportValues();
      dsm($this, 'this in action ' . $submitted['action']);

    switch ($submitted['action']) {
      case 'add':
      case 'update':
        $apiParams = array(
          'civicrm_entity_type' => 'event',
          'civicrm_entity_id' => $submitted['civicrm_entity_id'],
          'eb_entity_type' => 'event',
          'eb_entity_id' => $submitted['eb_entity_id'],
        );
        if (!empty($this->id)) {
          $apiParams['id'] = $this->id;
        }
        $result = _eventbrite_civicrmapi('EventbriteLink', 'create', $apiParams);
        break;
      case 'delete':
        $result = _eventbrite_civicrmapi('EventbriteLink', 'delete', array(
          'id' => $this->id,
//          'civicrm_entity_type' => 'event',
        ));

        break;
    }

    CRM_Core_Session::setStatus(ts('Settings have been saved.'), ts('Saved'), 'success');
    parent::postProcess();
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
    dsm($this->_elements, '$this->_elements');
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    dsm($elementNames, '$elementNames');
    return $elementNames;
  }

}
