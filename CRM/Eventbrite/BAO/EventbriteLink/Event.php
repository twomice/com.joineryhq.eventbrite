<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

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
