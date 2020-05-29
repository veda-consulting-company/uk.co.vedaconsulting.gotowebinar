{if $error_message}
  {$error_message.message}
{/if}
{if $upcomingWebinars}
<div class="crm-accordion-wrapper crm-accordion-open">
  <div class="crm-accordion-header">
      {ts}Webinar Key Settings{/ts}
  </div>
  <div class="crm-accordion-body">
    <div id="webinarTableWarpper">
      <h4><strong>Click any row below to populate Webinar Key.</strong></h4>

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
cj(document).ready(function() {
  var webinar_settings = cj('#webinarTableWarpper').html();
  if (cj('#webinarTableWarpper').length == 0) {
    cj("input[data-crm-custom='Webinar_Event:Webinar_id']").parent().parent().parent().parent().after(webinar_settings);
  };

  cj(document).tooltip();

  cj('#webinar_settings tbody').on('click', 'tr', function (){
    var name = cj('td', this).eq(2).text();
    cj("input[data-crm-custom='Webinar_Event:Webinar_id']").val(name);
  });

  cj('#webinar_settings').dataTable();
  // if(cj(window.ids).length==0){
  //   cj('#webinarTableWarpper').hide();
  // }
});
</script>
{/literal}
{/if}
