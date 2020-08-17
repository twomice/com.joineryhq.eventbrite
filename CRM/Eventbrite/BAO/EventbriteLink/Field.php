<?php

/**
 * Description of Event
 *
 * @author as
 */
class CRM_Eventbrite_BAO_EventbriteLink_Field extends CRM_Eventbrite_BAO_EventbriteLink {

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->whereAdd('eb_entity_type = "Question"');
    parent::__construct();
  }

}
