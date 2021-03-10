<?php
use CRM_Eventbrite_ExtensionUtil as E;

class CRM_Eventbrite_Page_Eventbrite extends CRM_Core_Page {

  public function run() {
    $breadCrumb = array(
      'title' => E::ts('Eventbrite Integration'),
      'url' => CRM_Utils_System::url('civicrm/admin/eventbrite'),
    );
    CRM_Utils_System::appendBreadCrumb(array($breadCrumb));

    $links = array(
      array(
        'icon' => 'fa-cogs',
        'url' => CRM_Utils_System::url('civicrm/admin/eventbrite/settings', 'reset=1'),
        'title' => E::ts('Settings'),
        'desc' => E::ts('Eventbrite API key; debug settings.'),
      ),
      array(
        'icon' => 'fa-calendar-check-o',
        'url' => CRM_Utils_System::url('civicrm/admin/eventbrite/manage/events'),
        'title' => E::ts('Events'),
        'desc' => E::ts('Configurations for specific events.'),
      ),

    );

    $this->assign('links', $links);
    return parent::run();

  }

}
