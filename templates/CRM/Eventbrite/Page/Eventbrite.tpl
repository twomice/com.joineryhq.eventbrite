
<div class="help">
  <p>{ts}Use the links below to manage the configuration for Eventbrite Integration.{/ts}</p>
</div>

<div class="crm-content-block">
  <table class="form-layout">
    {foreach from=$links item=link key=idx}
      <tr class="{cycle values="odd-row,even-row"}">
          <td style="vertical-align: top; width:24px;">
              <a href="#"><img src="{$config->resourceBase}i/{if $link.icon}{$link.icon}{else}admin/small/option.png{/if}" alt="$link.title"/></a>
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
