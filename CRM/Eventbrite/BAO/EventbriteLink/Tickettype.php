<?php

/**
 * Description of Event
 *
 * @author as
 */
class CRM_Eventbrite_BAO_EventbriteLink_Tickettype extends CRM_Eventbrite_BAO_EventbriteLink {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->whereAdd('civicrm_entity_type = "ParticipantRole"');
    parent::__construct();
  }

}
