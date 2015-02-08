{if $smarty.get.action eq 'add'}
{else}
<table id="webinar_settings">
    <tr style="background-color: #CDE8FE;">
               <td><b>{ts}Description{/ts}</td>
               <td><b>{ts}Subject{/ts}</td>
               <td><b>{ts}Webinar Key{/ts}</td>
               <td><b>{ts}Start Time{/ts}</td>
               <td><b>{ts}End Time{/ts}</td>

    </tr>
    {foreach from=$upcomingWebinars item=webinar}
        <tr>
        <td>{$webinar.description}</td>
        <td>{$webinar.subject}</td>
        <td>{$webinar.webinarKey}</td>
        {assign var=times value=$webinar.times}
        <td>{$times[0].startTime|crmDate}</td>
        <td>{$times[0].endTime|crmDate}</td>
        </tr>
    {/foreach}
        
</table>
 
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

