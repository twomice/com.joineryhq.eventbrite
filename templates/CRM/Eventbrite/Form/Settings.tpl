{* HEADER *}
<div class="crm-content-block crm-block">

  <div class="crm-submit-buttons">
    {* Display top submit button only if there are more than three elements on the page *}
    {if ($elementNames|@count) gt 3}
      {include file="CRM/common/formButtons.tpl" location="top"}
    {/if}
  </div>

  {* FIELDS (AUTOMATIC LAYOUT) *}

  {foreach from=$elementNames item=elementName}
    <div class="crm-section">
      <div class="label">{$form.$elementName.label}</div>
      <div class="content">{$form.$elementName.html}<div class="description">{$descriptions.$elementName}</div></div>
      <div class="clear"></div>
    </div>
  {/foreach}

  {* FOOTER *}
  <div class="crm-submit-buttons">
  {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>

</div>