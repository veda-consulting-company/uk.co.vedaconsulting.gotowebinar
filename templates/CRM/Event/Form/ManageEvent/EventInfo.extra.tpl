{if $smarty.get.action eq 'add'}
{else}

    <table id="webinar_settings" style="width:670px;height:400px"
        title="DataGrid - CardView" singleSelect="true" fitColumns="true" remoteSort="false"
        pagination="true" sortOrder="desc" sortName="webinar_settings" >
  
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
            <td sortable="true">{$webinar.description}</td>	   
		    <td class='subject' sortable="true">{$webinar.subject}</td>
            <td class='webminarKey' sortable="true">{$webinar.webinarKey}</td>
			<td sortable="true">{$times[0].startTime|crmDate}</td>
            <td sortable="true">{$times[0].endTime|crmDate}</td>
		</tr>
        {/foreach}
	    </tbody>	
    </table>
		
    {literal} 
    <script> 

    cj(document).ready(function(){
        cj(".webminarKey").click(function(){
        cj('input:#custom_7_-1').val(cj(this).html());
	    });
		cj('#webinar_settings').dataTable();
    });
    
    </script> 
    {/literal} 

   {literal} 
   <script> 

    cj( document ).ready(function() {
        var custom = "{/literal}{$customDataSubType}{literal}";
        if(custom) {
            var webinar_settings = cj('#webinar_settings').html();
            webinar_settings = webinar_settings.replace("<tbody>", "");
            webinar_settings = webinar_settings.replace("</tbody>", ""); 
           cj("input[data-crm-custom='Webinar_Event:Webinar_id']").parent().parent().after(webinar_settings);
        }
    });
    </script>
    {/literal}
{/if}
