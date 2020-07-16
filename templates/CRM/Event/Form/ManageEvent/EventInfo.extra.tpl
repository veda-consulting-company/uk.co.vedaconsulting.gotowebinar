{if $error_message}
  {$error_message.message}
{/if}
{if $upcomingWebinars}
<div id="webinarTableWrapper">
  <div class="crm-accordion-wrapper crm-accordion-open">
    <div class="crm-accordion-header">
        {ts}Webinar Key Settings{/ts}
    </div>
    <div class="crm-accordion-body">
      <div class="help">Click any row below to populate Webinar Key.</div>
      <table id="webinar_settings" cellspacing="0" width="100%" >
        <thead>
          <tr>
            <th>Description</th>
            <th>Subject</th>
            <th>Webinar Key</th>
            <th>Start Time</th>
            <th>End Time</th>
          </tr>
        </thead>
        <tbody>
          {foreach from=$upcomingWebinars item=webinar}
          {assign var=times value=$webinar.times}
            <tr>
              <td style="cursor: pointer; sortable="true">{$webinar.description}</td>
              <td style="cursor: pointer; class='subject' sortable="true">{$webinar.subject}</td>
              <td class='webminarKey' sortable="true" style="cursor: pointer;" title="Click the key to populate Webinar Key.">{$webinar.webinarKey}</td>
              <td style="cursor: pointer; sortable="true">{$times[0].startTime|crmDate}</td>
              <td style="cursor: pointer; sortable="true">{$times[0].endTime|crmDate}</td>
            </tr>
          {/foreach}
        </tbody>
      </table>
    </div>
  </div>
</div>
{literal}
<script>
  (function(cj){
    var webinarSettingsWrapperSelector = '#webinarTableWrapper';
    var webinarSettingsTableSelector = '#webinar_settings';
    var webinarKeyFieldSelector = 'input[data-crm-custom="Webinar_Event:Webinar_id"]';
    var formContainerSelector = '#crm-main-content-wrapper #EventInfo';

    cj(document).ready(function() {
      cj(document).tooltip();

      cj(webinarSettingsTableSelector).find('tbody').on('click', 'tr', function (){
        var name = cj('td', this).eq(2).text();

        cj(webinarKeyFieldSelector).val(name);
      });

      cj(webinarSettingsTableSelector).dataTable();

      // Attach this wrapper just before action buttons.
      cj(formContainerSelector)
        .find('.crm-submit-buttons:last-of-type')
        .before(cj(webinarSettingsWrapperSelector));
    });
  })(cj);
</script>
{/literal}
{/if}
