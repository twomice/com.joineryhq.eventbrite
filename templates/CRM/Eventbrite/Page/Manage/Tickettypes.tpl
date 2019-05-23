{if $action eq 1 || $action eq 2 || $action eq 4 || $action eq 8}
  {include file="CRM/Eventbrite/Form/Manage/Tickettype.tpl"}
{else}

  <div class="help">
    <p>{ts 1=$eventTitle}Manage Eventbrite Ticket Types for the event <em>%1</em>here{/ts}</p>
  </div>

  <div class="crm-content-block crm-block">
  {if $rows}
    <div class="action-link">
      {crmButton p="civicrm/admin/eventbrite/manage/tickettypes" q="action=add&reset=1" icon="plus-circle"}{ts}Add Ticket Type{/ts}{/crmButton}
    </div>

    <div id="ltype">

      {strip}
    {* handle enable/disable actions*}
    {include file="CRM/common/enableDisableApi.tpl"}
      {include file="CRM/common/jsortable.tpl"}
          <table id="options" class="display">
          <thead>
          <tr>
            <th id="sortable">{ts}Eventbrite Ticket Type{/ts}</th>
            <th id="sortable">{ts}CiviCRM Participant Role{/ts}</th>
            <th></th>
          </tr>
          </thead>
          {foreach from=$rows item=row}
          <tr id="eventbrite-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if} ">
              <td class="crm-eventbrite-eventbrite-tickettype" data-field="eb_entity_id">{$row.eb_ticket_type}</td>
              <td class="crm-eventbrite-civicrm-role" data-field="civicrm_entity_id">{$row.civicrm_role}</td>
              <td>{$row.action|replace:'xx':$row.id}</td>
          </tr>
          {/foreach}
          </table>
          {/strip}

  </div>
  {else}
      <div class="messages status no-popup">
        <img src="{$config->resourceBase}i/Inform.gif" alt="{ts}status{/ts}"/>
        {ts}None found.{/ts}
      </div>
  {/if}
    <div class="action-link">
      {crmButton p="civicrm/admin/eventbrite/manage/tickettypes" q="action=add&reset=1&pid=$pid" icon="plus-circle"}{ts}Add Ticket Type{/ts}{/crmButton}
      {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>

  </div>
{/if}