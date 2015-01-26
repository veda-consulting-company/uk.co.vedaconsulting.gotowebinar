<div class="crm-block crm-form-block crm-webinar-setting-form-block">
  <div class="crm-accordion-wrapper crm-accordion_webinar_setting-accordion crm-accordion-open">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div> 
      {ts}API Key Setting{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">

      <table class="form-layout-compressed">
    	  <tr class="crm-webinar-setting-api-key-block">
          <td class="label">{$form.api_key.label}</td>
          <td>{$form.api_key.html}<br/>
      	    <span class="description">{ts}API Key from Webinar{/ts}
	          </span>
          </td>
        </tr>
        {if $initial}
        <tr class="crm-webinar-setting-api-key-email">
          <td class="label">{$form.email_address.label}</td>
          <td>{$form.email_address.html}<br/>
      	    <span class="description">{ts}Username to connect Webinar account{/ts}
	          </span>
          </td>
        </tr>
        <tr class="crm-webinar-setting-api-key-password">
          <td class="label">{$form.password.label}</td>
          <td>{$form.password.html}<br/>
      	    <span class="description">{ts}Password to connect Webinar account{/ts}
	          </span>
          </td>
        </tr>
        {/if}
        {if $responseKey}
            <tr class="crm-webinar-information-api-key-block">
                <td class="label" style="color:green"><b>{ts} Info:{/ts}</td>
                <td class="label" style="color:green"><b>{ts}Your account is connected.&nbsp;Here are your Upcoming Webinars {/ts}</td>
            </tr>
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
        {/if}
        {if $clienterror}
            <tr class="crm-webinar-information-erro-api-key-block">
            <td class="label" style="color:red"><b>{ts} Info:{/ts}</td>
            <td class="label" style="color:red">{ts}{$clienterror.int_err_code}{/ts}&nbsp;&nbsp;&nbsp;&nbsp;{ts}{$clienterror.msg}{/ts}</td>
            </tr>
        {/if}
        {if $error}
            <tr class="crm-webinar-information-erro-api-key-block">
            <td class="label" style="color:red"><b>{ts} Info:{/ts}</td>
            <td class="label" style="color:red">{ts}{$error.int_err_code}{/ts}&nbsp;&nbsp;&nbsp;&nbsp;{ts}{$error.msg}{/ts}</td>
            </tr>
        {/if}
      </table>
    </div>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl"}
    </div>
  </div>
</div>
    
