<?php
use CRM_Participantletter_ExtensionUtil as E;

/**
 * Class for processing Eventbrite 'Attendee' webhook events.
 *
 */
class CRM_Eventbrite_WebhookProcessor_Attendee extends CRM_Eventbrite_WebhookProcessor {

  private $attendee;
  private $contactId = NULL;
  private $participantId = NULL;
  private $eventId = NULL;

  protected function loadData() {
    if (CRM_Utils_Array::value('resource_uri', $this->data)) {
      $this->attendee = $this->data;
    }
    else {
      $eb = CRM_Eventbrite_EvenbriteApi::singleton();
      $this->attendee = $eb->request("/attendees/{$this->entityId}/", NULL, array('attendee-answers'));
    }
  }

  public function process() {

    // Ensure we have a link for the event; otherwise any Participants would be nonsensical.
    $this->eventId = _eventbrite_civicrmapi('EventbriteLink', 'getValue', array(
      'eb_entity_type' => 'Event',
      'civicrm_entity_type' => 'Event',
      'eb_entity_id' => CRM_Utils_Array::value('event_id', $this->attendee),
      'return' => 'civicrm_entity_id',
    ));
    if (!$this->eventId) {
      CRM_Eventbrite_BAO_EventbriteLog::create(array(
        'message' => "Could not find EventbriteLink record 'Event' for attendee {$this->entityId} with 'event_id': " . CRM_Utils_Array::value('event_id', $this->attendee) . "; cannot process Attendee. In method " . __METHOD__ . ", file " . __FILE__ . ", line " . __LINE__,
        'message_type_id' => CRM_Eventbrite_BAO_EventbriteLog::MESSAGE_TYPE_ID_GENERAL,
      ));
      throw new CRM_Exception("Could not find EventbriteLink 'Event' record for attendee {$this->entityId} with 'event_id': " . CRM_Utils_Array::value('event_id', $this->attendee) . "; cannot process Attendee.");
      return;
    }

    /*
     * Now that we have the latest Attendee data, the basic idea is to do this:
     *
     * Determine which CiviCRM participant and contact to work with:
     *  Start with a collection of contacts matching the latest Attendee data, per the Contact.duplicatecheck API;
     *  Also start with a Participant linked to the Attendee ID, if any.
     *  If there's a linked participant:
     *    If that linked participant Contact is in the duplicatecheck collection, it means identifying info (name, email) hasn't changed.
     *      use that contactId / participantId.
     *      update that contact with the latest Attendee data.
     *      update that participant with the latest Attendee data.
     *    Else, it means identifying info (name, email) HAS changed; we'll preserve the existing contact, and use (or create) another one with this info.
     *      If the duplicatecheck collection contains any contacts,
     *        use the lowest ContactId
     *        update that contact with the latest Attendee data.
     *      Else,
     *        create a new contact and use that contactId (source = "synced from EventBrite")
     *      Update the linked Participant status to 'removed in EB'
     *      Create a new Participant record contactId for this event; use this ParticipantId.
     *    Unlink the linked participant (yes, unconditionally; we'll unconditionally create a new link in a moment)
     *  Else,
     *    create a new contact and use that contactId (source = "synced from EventBrite")
     *    create a new participant record
     *  Create a new Participant/Attendee link using ParticipantId with the latest Attendee data.
     *  Now we should know the contactId, and Contact record has been updated; we also know the ParticipantId and Participant record has been updated.
     *  Update any configured custom fields.
     */

    $linkedParticipantId = $linkId = $linkedContactId = NULL;
    $contactParams = array(
      'contact_type' => 'Individual',
      'first_name' => $this->attendee['profile']['first_name'],
      'last_name' => $this->attendee['profile']['last_name'],
      'email' => $this->attendee['profile']['email'],
    );

    // Start with a collection of contacts matching the latest Attendee data, per the Contact.duplicatecheck API;
    $result = _eventbrite_civicrmapi('Contact', 'duplicatecheck',
      array(
        'match' => $contactParams,
      )
    );
    $duplicateCheckContactIds = array_keys($result['values']);

    // Also start with a Participant linked to the Attendee ID, if any.
    $link = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
      'eb_entity_type' => 'Attendee',
      'civicrm_entity_type' => 'Participant',
      'eb_entity_id' => $this->entityId,
      'sequential' => 1,
      'api.Participant.get' => ['id' => '$value.civicrm_entity_id'],
    ));
    $linkId = CRM_Utils_Array::value('id', $link);
    if (!empty($link['values'])) {
      $linkedParticipantId = CRM_Utils_Array::value('civicrm_entity_id', $link['values'][0]);
      $linkedContactId = $link['values'][0]['api.Participant.get']['values'][0]['contact_id'];
    }

    // If there's a linked participant:
    if ($linkedParticipantId) {
      // If that linked participant Contact is in the duplicatecheck collection, it means identifying info (name, email) hasn't changed.
      if (in_array($linkedContactId, $duplicateCheckContactIds)) {
        // use that contactId / participantId.
        $this->contactId = $linkedContactId;
        $this->participantId = $linkedParticipantId;
        // update that contact with the latest Attendee data.
        $this->updateContact();
        // update that participant with the latest Attendee data.
        $this->updateParticipant();
      }
      // Else, it means identifying info (name, email) HAS changed; we'll preserve the existing contact, and use (or create) another one with this info.
      else {
        // If the duplicatecheck collection contains any contacts,
        if (!empty($duplicateCheckContactIds)) {
          // use the lowest ContactId
          $this->contactId = min($duplicateCheckContactIds);
          // update that contact with the latest Attendee data.
          $this->updateContact();
        }
        else {
          // create a new contact and use that contactId (source = "synced from EventBrite")
          $this->updateContact();
        }
        // Update the linked Participant status to 'removed in EB'
        self::setParticipantStatusRemoved($linkedParticipantId);
        // Create a new Participant record contactId for this event; use this ParticipantId.
        $this->updateParticipant();
      }
      // Unlink the linked participant (yes, unconditionally; we'll unconditionally create a new link in a moment)
      _eventbrite_civicrmapi('EventbriteLink', 'delete', array(
        'id' => $linkId,
      ));
    }
    else {
      $this->updateContact();
      $this->updateParticipant();
    }
    // Create a new Participant/Attendee link using ParticipantId with the latest Attendee data.
    $link = _eventbrite_civicrmapi('EventbriteLink', 'create', array(
      'eb_entity_type' => 'Attendee',
      'civicrm_entity_type' => 'Participant',
      'eb_entity_id' => $this->entityId,
      'civicrm_entity_id' => $this->participantId,
    ));
    // Now we should know the contactId, and Contact record has been updated; we also know the ParticipantId and Participant record has been updated.
    $this->updateCustomFields();

  }

  private function updateContact() {
    $apiParams = array(
      'contact_type' => 'Individual',
      'first_name' => $this->attendee['profile']['first_name'],
      'last_name' => $this->attendee['profile']['last_name'],
      'email' => $this->attendee['profile']['email'],
      'id' => $this->contactId,
    );
    $contactCreate = _eventbrite_civicrmapi('Contact', 'create', $apiParams);
    $this->contactId = CRM_Utils_Array::value('id', $contactCreate);
    $this->updateContactAddresses();
    $this->updateContactPhone('work', CRM_Utils_Array::value('work_phone', $this->attendee['profile']));
    $this->updateContactPhone('home', CRM_Utils_Array::value('home_phone', $this->attendee['profile']));
  }

  private function updateParticipant() {

    // Get role from ticket class.
    $roleId = _eventbrite_civicrmapi('EventbriteLink', 'getValue', array(
      'return' => 'civicrm_entity_id',
      'civicrm_entity_type' => 'ParticipantRole',
      'eb_entity_type' => 'TicketType',
      'eb_entity_id' => CRM_Utils_Array::value('ticket_class_id', $this->attendee),
    ));

    $apiParams = array(
      'id' => $this->participantId,
      'event_id' => $this->eventId,
      'contact_id' => $this->contactId,
      'register_date' => CRM_Utils_Date::processDate(CRM_Utils_Array::value('created', $this->attendee)),
      'role_id' => $roleId,
      'source' => E::ts('Eventbrite Integration'),
    );

    if (CRM_Utils_Array::value('checked_in', $this->attendee)) {
      $apiParams['participant_status'] = 'Attended';
    }
    elseif (CRM_Utils_Array::value('cancelled', $this->attendee)) {
      $apiParams['participant_status'] = 'Cancelled';
    }
    else {
      $apiParams['participant_status'] = 'Registered';
    }

    $participant = _eventbrite_civicrmapi('Participant', 'create', $apiParams);

    $this->participantId = CRM_Utils_Array::value('id', $participant);

    // If participant status is canceled, also cancel the payment record.
    if ($apiParams['participant_status'] == 'Cancelled') {
      $this->cancelParticipantPayments($participant['id']);
    }
  }

  private function updateContactAddresses() {
    if (!empty($this->attendee['profile']['addresses'])) {
      foreach ($this->attendee['profile']['addresses'] as $addressType => $address) {
        $locationTypeId = NULL;
        switch ($addressType) {
          case 'work':
            $locationTypeId = 'Work';
            break;

          case 'bill':
            $locationTypeId = 'Billing';
            break;

          case 'home':
            $locationTypeId = 'Home';
            break;

        }
        if ($locationTypeId) {
          $addresses = _eventbrite_civicrmapi('Address', 'get', array(
            'return' => array('id'),
            'location_type_id' => $locationTypeId,
            'contact_id' => $this->contactId,
          ));
          if ($addresses['count']) {
            $addressId = max(array_keys($addresses['values']));
            if ($addressId) {
              _eventbrite_civicrmapi('Address', 'delete', array(
                'id' => $addressId,
              ));
            }
          }
          $addressCreate = _eventbrite_civicrmapi('Address', 'create', array(
            'location_type_id' => $locationTypeId,
            'contact_id' => $this->contactId,
            'city' => $address['city'],
            'country' => $address['country'],
            'state_province' => $address['region'],
            'postal_code' => $address['postal_code'],
            'street_address' => $address['address_1'],
            'supplemental_address_1' => $address['address_2'],
          ));
        }
      }
    }
  }

  private function updateContactPhone($locationType, $phone = NULL) {
    if ($phone) {
      $phones = _eventbrite_civicrmapi('Phone', 'get', array(
        'return' => array('id'),
        'location_type_id' => $locationType,
        'contact_id' => $this->contactId,
      ));
      if ($phones['count']) {
        $phoneId = max(array_keys($phones['values']));
        if ($phoneId) {
          _eventbrite_civicrmapi('Phone', 'delete', array(
            'id' => $phoneId,
          ));
        }
      }
      $phoneCreate = _eventbrite_civicrmapi('Phone', 'create', array(
        'location_type_id' => $locationType,
        'contact_id' => $this->contactId,
        'phone' => $phone,
      ));
    }
  }

  public static function setParticipantStatusRemoved($participantId) {
    _eventbrite_civicrmapi('participant', 'create', array(
      'id' => $participantId,
      'participant_status' => 'Removed_in_EventBrite',
    ));
    $this->cancelParticipantPayments($participantId);
  }

  private function cancelParticipantPayments($participantId) {
    $participantPayments = _eventbrite_civicrmapi('participantPayment', 'get', array(
      'participant_id' => $participantId,
    ));
    foreach ($participantPayment['values'] as $value) {
      _eventbrite_civicrmapi('contribution', 'create', array(
        'id' => $value['contribution_id'],
        'contribution_status_id' => 'cancelled',
      ));
    }
  }

  private function updateCustomFields() {
    $pid = _eventbrite_civicrmapi('EventbriteLink', 'getValue', array(
      'eb_entity_type' => 'Event',
      'civicrm_entity_type' => 'Event',
      'civicrm_entity_id' => $this->eventId,
      'return' => 'id',
    ));
    $questions = _eventbrite_civicrmapi('EventbriteLink', 'get', array(
      'parent_id' => $pid,
      'eb_entity_type' => 'Question',
      'civicrm_entity_type' => 'CustomField',
    ));

    if (!$questions['count']) {
      // No questions configured for this event, so just return.
      return;
    }

    $keyedQuestions = CRM_Utils_Array::rekey($this->attendee['answers'], 'question_id');

    $contactValues = $participantValues = array();

    foreach ($questions['values'] as $value) {
      $questionId = CRM_Utils_Array::value('eb_entity_id', $value);
      if (!array_key_exists($questionId, $keyedQuestions)) {
        continue;
      }
      $answerValue = CRM_Utils_Array::value('answer', $keyedQuestions[$questionId]);

      $field = civicrm_api3('CustomField', 'getSingle', [
        'sequential' => 1,
        'id' => CRM_Utils_Array::value('civicrm_entity_id', $value),
        'api.CustomGroup.getsingle' => [],
      ]);
      $fieldId = $field['id'];
      $extends = $field['api.CustomGroup.getsingle']['extends'];
      if ($extends == 'Individual' || $extends == 'Contact') {
        $contactValues['custom_' . $fieldId] = $answerValue;
      }
      elseif ($extends == 'Individual' || $extends == 'Contact') {
        $participantValues['custom_' . $fieldId] = $answerValue;
      }
    }

    if (!empty($participantValues)) {
      $participantValues['id'] = $this->participantId;
      $participant = _eventbrite_civicrmapi('participant', 'create', $participantValues);
    }

    if (!empty($contactValues)) {
      $contactValues['id'] = $this->contactId;
      $contact = _eventbrite_civicrmapi('contact', 'create', $contactValues);
    }
  }

}
