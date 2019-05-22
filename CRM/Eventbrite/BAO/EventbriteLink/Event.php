<?php

/**
 * Description of Event
 *
 * @author as
 */
class CRM_Eventbrite_BAO_EventbriteLink_Event extends CRM_Eventbrite_BAO_EventbriteLink {
  /**
   * Class constructor.
   */
  public function __construct() {
    $this->whereAdd('civicrm_entity_type = "Event"');
    parent::__construct();
  }

}
