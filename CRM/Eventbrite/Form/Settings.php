<?php

require_once 'CRM/Core/Form.php';

use CRM_Eventbrite_ExtensionUtil as E;

/**
 * Form controller class for extension Settings form.
 * Borrowed heavily from
 * https://github.com/eileenmcnaughton/nz.co.fuzion.civixero/blob/master/CRM/Civixero/Form/XeroSettings.php
 *
 * @see http://wiki.civicrm.org/confluence/display/CRMDOC43/QuickForm+Reference
 */
class CRM_Eventbrite_Form_Settings extends CRM_Core_Form {

  static $settingFilter = array('group' => 'eventbrite');
  static $extensionName = 'com.joineryhq.eventbrite';
  private $_submittedValues = array();
  private $_settings = array();

  public function __construct(
  $state = NULL, $action = CRM_Core_Action::NONE, $method = 'post', $name = NULL
  ) {

    $this->setSettings();

    parent::__construct(
      $state = NULL, $action = CRM_Core_Action::NONE, $method = 'post', $name = NULL
    );
  }

  public function buildQuickForm() {
    $settings = $this->_settings;
    foreach ($settings as $name => $setting) {
      if (isset($setting['quick_form_type'])) {
        switch ($setting['html_type']) {
          case 'Select':
            $this->add(
              $setting['html_type'], // field type
              $setting['name'], // field name
              $setting['title'], // field label
              $this->getSettingOptions($setting), NULL, $setting['html_attributes']
            );
            break;

          case 'CheckBox':
            $this->addCheckBox(
              $setting['name'], // field name
              $setting['title'], // field label
              array_flip($this->getSettingOptions($setting))
            );
            break;

          case 'Radio':
            $this->addRadio(
              $setting['name'], // field name
              $setting['title'], // field label
              $this->getSettingOptions($setting)
            );
            break;

          default:
            $add = 'add' . $setting['quick_form_type'];
            if ($add == 'addElement') {
              $this->$add($setting['html_type'], $name, ts($setting['title']), CRM_Utils_Array::value('html_attributes', $setting, array()));
            }
            else {
              $this->$add($name, ts($setting['title']));
            }
            break;

        }
      }
      $descriptions[$setting['name']] = ts($setting['description']);

      if (!empty($setting['X_form_rules_args'])) {
        $rules_args = (array) $setting['X_form_rules_args'];
        foreach ($rules_args as $rule_args) {
          array_unshift($rule_args, $setting['name']);
          call_user_func_array(array($this, 'addRule'), $rule_args);
        }
      }
    }
    $this->assign("descriptions", $descriptions);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));

    $style_path = CRM_Core_Resources::singleton()->getPath(self::$extensionName, 'css/extension.css');
    if ($style_path) {
      CRM_Core_Resources::singleton()->addStyleFile(self::$extensionName, 'css/extension.css');
    }

    // export form elements
    $this->assign('elementNames', $this->getRenderableElementNames());

    if ($this->_validateTokenOnFormLoad()) {
      $this->_confirmWebhookOnFormLoad();
    }

    parent::buildQuickForm();
  }

  public function postProcess() {
    $this->_submittedValues = $this->exportValues();
    $this->saveSettings();
    CRM_Utils_System::redirect(CRM_Utils_System::url('civicrm/admin/eventbrite/settings', 'reset=1'));
    parent::postProcess();
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  private function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons". These
    // items don't have labels. We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

  /**
   * Define the list of settings we are going to allow to be set on this form.
   */
  private function setSettings() {
    if (empty($this->_settings)) {
      $this->_settings = self::getSettings();
    }
  }

  private static function getSettings() {
    $settings = _eventbrite_civicrmapi('setting', 'getfields', array('filters' => self::$settingFilter));
    return $settings['values'];
  }

  /**
   * Get the settings we are going to allow to be set on this form.
   */
  private function saveSettings() {
    $settings = $this->_settings;
    $values = array_intersect_key($this->_submittedValues, $settings);
    _eventbrite_civicrmapi('setting', 'create', $values);

    // Save any that are not submitted, as well (e.g., checkboxes that aren't checked).
    $unsettings = array_fill_keys(array_keys(array_diff_key($settings, $this->_submittedValues)), NULL);
    _eventbrite_civicrmapi('setting', 'create', $unsettings);

    // Assume the token is only for one Eventbrite organization; fetch and record
    // that organization ID.
    $eb = CRM_Eventbrite_EvenbriteApi::singleton();
    if ($organizations = CRM_Utils_Array::value('organizations', $eb->request('users/me/organizations/'))) {
      $organizationId = $organizations[0]['id'];
      _eventbrite_civicrmapi('setting', 'create', array(
        'eventbrite_api_organization_id' => $organizationId,
      ));
    }

    CRM_Core_Session::setStatus(" ", ts('Settings saved.'), "success");
  }

  /**
   * Set defaults for form.
   *
   * @see CRM_Core_Form::setDefaultValues()
   */
  public function setDefaultValues() {
    static $ret;
    if (!isset($ret)) {
      $result = _eventbrite_civicrmapi('setting', 'get', array(
        'return' => array_keys($this->_settings),
        'sequential' => 1,
      ));
      $ret = CRM_Utils_Array::value(0, $result['values']);
    }
    return $ret;
  }

  public static function getGroupOptions() {
    $options = array();
    $result = _eventbrite_civicrmapi('Group', 'get', array(
      'is_active' => 1,
      'options' => array('limit' => 0),
    ));
    foreach ($result['values'] as $id => $value) {
      $options[$id] = $value['title'];
    }
    asort($options);
    $options = array(0 => '- ' . ts('none') . ' -') + $options;
    return $options;
  }

  public static function getActivityTypeOptions() {
    $options = array();
    $result = _eventbrite_civicrmapi('OptionValue', 'get', array(
      'option_group_id' => "activity_type",
      'is_active' => 1,
      'options' => array('limit' => 0),
    ));
    foreach ($result['values'] as $id => $value) {
      $options[$value['value']] = $value['label'];
    }
    asort($options);
    return $options;
  }

  public static function getActivityStatusOptions() {
    $options = array();
    $result = _eventbrite_civicrmapi('OptionValue', 'get', array(
      'option_group_id' => "activity_status",
      'is_active' => 1,
      'options' => array('limit' => 0),
    ));
    foreach ($result['values'] as $id => $value) {
      $options[$value['value']] = $value['label'];
    }
    asort($options);
    return $options;
  }

  public function getSettingOptions($setting) {
    if (!empty($setting['X_options_callback']) && is_callable($setting['X_options_callback'])) {
      return call_user_func($setting['X_options_callback']);
    }
    else {
      return CRM_Utils_Array::value('X_options', $setting, array());
    }
  }

  /**
   * Upon displaying the form (i.e., only if it's not being submitted now),
   * perform a validation check on the saved Eventbrite token (if there is one)
   * and print a message if it's invalid.
   */
  private function _validateTokenOnFormLoad() {
    $isPass = TRUE;
    if (!$this->_flagSubmitted) {
      if ($token = CRM_Utils_Array::value('eventbrite_api_token', $this->setDefaultValues())) {
        $eb = CRM_Eventbrite_EvenbriteApi::singleton();
        $result = $eb->request('/users/me/');
        if ($error = CRM_Utils_Array::value('error', $result)) {
          $isPass = FALSE;
          $error_message = CRM_Utils_Array::value('status_code', $result);
          $error_message .= ': ' . $error;
          $error_message .= ': ' . CRM_Utils_Array::value('error_description', $result);
          $msg = E::ts('Eventbrite said: <em>%1</em>', array('1' => $error_message));
          CRM_Core_Session::setStatus($msg, E::ts('Eventbrite token'), 'error');
        }
      }
    }
    return $isPass;
  }

  private function _confirmWebhookOnFormLoad() {
    if (!$this->_flagSubmitted) {
      if ($token = CRM_Utils_Array::value('eventbrite_api_token', $this->setDefaultValues())) {
        $eb = CRM_Eventbrite_EvenbriteApi::singleton();
        $result = $eb->request('/webhooks/');
        $endPoints = CRM_Utils_Array::collect('endpoint_url', $result['webhooks']);
        $myListener = CRM_Eventbrite_Utils::getWebhookListenerUrl();
        if (!in_array($myListener, $endPoints)) {
          $body = array(
            'endpoint_url' => $myListener,
            'actions' => array("event.created", "event.published", "event.unpublished", "event.updated", "attendee.updated", "barcode.checked_in", "barcode.un_checked_in", "order.placed", "order.refunded", "order.updated", "organizer.updated", "ticket_class.created", "ticket_class.deleted", "ticket_class.updated", "venue.updated"),
          );
          $result = $eb->request('/webhooks/', $body, NULL, 'POST');
          if ($error = CRM_Utils_Array::value('error', $result)) {
            $error_message = CRM_Utils_Array::value('status_code', $result);
            $error_message .= ': ' . $error;
            $error_message .= ': ' . CRM_Utils_Array::value('error_description', $result);
            $msg = E::ts('Error establishing webhook configuration via Eventbrite API. Eventbrite said: <em>%1</em>', array('1' => $error_message));
            CRM_Core_Session::setStatus($msg, E::ts('Eventbrite webhook'), 'error');
          }
        }
      }
    }
  }

}
