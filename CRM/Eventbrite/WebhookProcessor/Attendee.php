<?php

/**
 * Class for processing Eventbrite 'Attendee' webhook events.
 *
 */
class CRM_Eventbrite_WebhookProcessor_Attendee extends CRM_Eventbrite_WebhookProcessor {

  function process() {

    $eb = CRM_Eventbrite_EvenbriteApi::singleton();
    $attendee = $eb->request("/attendees/{$this->entityId}/");
    dsm($attendee, "attendee {$this->entityId}");



    /**
     * The latest Attendee data is matched to existing Contacts by way of the Contact.duplicatecheck API;
        If the matched Contacts include the Participant linked to the Attendee ID, that Contact is updated with the latest Attendee data.
        Otherwise:
        The existing Contact is retained.
        The existing Contact is unlinked from the Attendee ID.
        The existing Participant record is changed:
        marked with a participant status of "Removed in EventBrite" (which has a class of "Negative");
        Unlinked from the Attendee ID.
        A new Contact is created, and linked to the Attendee ID.
        A new Participant is created for this new Contact with a status corresponding to the current Attendee status, and linked to the Attendee ID.
     */
  }
}
