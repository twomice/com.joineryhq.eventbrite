
<div class="help">
  <p>{ts}Use the links below to manage the configuration for Eventbrite Integration.{/ts}</p>
</div>

<div class="crm-content-block">
  <table class="form-layout">
    {foreach from=$links item=link key=idx}
      <tr class="{cycle values="odd-row,even-row"}">
          <td style="vertical-align: top; width:24px;">
              <i class="crm-i {$link.icon}" aria-hidden="true" style="font-size: 2em"></i>
          </td>
          <td class="report font-size11pt" style="vertical-align: text-top;" width="20%">
              <a href="{$link.url}" id="id_{$idx}">{$link.title}</a>
          </td>
          <td class="description"  style="vertical-align: text-top;" width="75%">
              {$link.desc}
          </td>
      </tr>
    {/foreach}
  </table>
</div>
