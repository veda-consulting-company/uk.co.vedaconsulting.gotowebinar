{if $smarty.get.cgcount gt '0'}
  <div id="webinarTableWarpper">
    <h2>Click any row below to populate Webinar Key.</h2><br />
  
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
  
{literal} 
<script> 
cj(document).ready(function() {
  var webinar_settings = cj('#webinarTableWarpper').html();
  if (cj('#webinarTableWarpper').length == 0) {
    cj("input[data-crm-custom='Webinar_Event:Webinar_id']").parent().parent().parent().parent().after(webinar_settings);
  };
		  
  cj(document).tooltip();
     
  cj().crmAPI('CustomField','get',{'sequential' :'1', 'name' :'Webinar_id'},
  {
    success:function (data) {    
      cj.each(data, function(key, value) {
        window.custid = data.id;
        window.ids=data.id;
      });
    }
  });
  
  cj('#webinar_settings tbody').on('click', 'tr', function (){            
    var fieldname ='#custom_'+window.custid+'_1';    
    var name = cj('td', this).eq(2).text();
    cj(fieldname).val(name);
  });
  
  cj('#webinar_settings').dataTable(); 
  // if(cj(window.ids).length==0){
  //   cj('#webinarTableWarpper').hide(); 
  // }
});		    
</script>
{/literal}
{/if}