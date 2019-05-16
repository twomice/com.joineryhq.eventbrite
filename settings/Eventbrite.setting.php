<?php

use CRM_Eventbrite_ExtensionUtil as E;

return array(
  'eventbrite_api_token' => array(
    'group_name' => 'Eventbrite Settings',
    'group' => 'eventbrite',
    'name' => 'eventbrite_api_token',
    'type' => 'String',
    'add' => '5.0',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => E::ts('See "Creating Your Personal OAuth Token" on the Eventbrite documentation page <a href="https://www.eventbrite.com/platform/docs/authentication" target="blank">Authenticating Your Access to the Eventbrite API</a>.'),
    'title' =>  E::ts('Eventbrite Personal OAuth Token'),
//    'help_text' => '',
    'html_type' => 'Text',
//    'html_attributes' => array(
//      'size' => 10,
//    ),
    'quick_form_type' => 'Element',
  ),
 );