{if $action eq 1 || $action eq 2 || $action eq 4 || $action eq 8}
  {include file="CRM/Eventbrite/Form/Manage/Event.tpl"}
{else}

  <div class="help">
    <p>{ts}Manage your Eventbrite Event configurations here{/ts}</p>
  </div>

  <div class="crm-content-block crm-block">
  {if $rows}
  {if !($action eq 1 and $action eq 2)}
      <div class="action-link">
        {crmButton p="civicrm/admin/eventbrite/manage/event" q="action=add&reset=1" icon="plus-circle"}{ts}Add Event{/ts}{/crmButton}
      </div>
  {/if}

  <div id="ltype">

      {strip}
    {* handle enable/disable actions*}
    {include file="CRM/common/enableDisableApi.tpl"}
      {include file="CRM/common/jsortable.tpl"}
          <table id="options" class="display">
          <thead>
          <tr>
            <th id="sortable">{ts}Eventbrite Event{/ts}</th>
            <th id="sortable">{ts}CiviCRM Event{/ts}</th>
            <th></th>
          </tr>
          </thead>
          {foreach from=$rows item=row}
          <tr id="eventbrite-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if} ">
              {crmAPI var='result' entity='Event' action='getsingle' sequential=0 return="title" id=$row.civicrm_entity_id}
              <td class="crm-eventbrite-eventbrite-entity" data-field="eb_entity_id">{$row.eb_event_name} (ID: {$row.eb_entity_id})</td>
              <td class="crm-eventbrite-civicrm-entity" data-field="civicrm_entity_id">{$result.title} (ID: {$row.civicrm_entity_id})</td>
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
      {crmButton p="civicrm/admin/eventbrite/manage/events" q="action=add&reset=1" icon="plus-circle"}{ts}Add Event{/ts}{/crmButton}
      {crmButton p="civicrm/admin" q="reset=1" class="cancel" icon="times"}{ts}Done{/ts}{/crmButton}
    </div>

  </div>
{/if}