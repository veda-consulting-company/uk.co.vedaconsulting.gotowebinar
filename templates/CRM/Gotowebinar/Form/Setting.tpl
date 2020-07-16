<div class="crm-block crm-form-block crm-webinar-setting-form-block">
  <div class="crm-accordion-wrapper crm-accordion_webinar_setting-accordion crm-accordion-open">
    <div class="crm-accordion-header">
      <div class="icon crm-accordion-pointer"></div>
      {ts}API Key Setting{/ts}
      {ts}Client Secret Setting{/ts}
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      {if !$initial}
       <div>
        <div>
          <label>{ts}Participant Status To Be Considered {/ts}</label>
        </div>
        <div class="listing-box" style="height: 120px">
            {foreach from=$form.participant_status_id item="participant_status_val"}
                <div class="{cycle values="odd-row,even-row"}">
                    {$participant_status_val.html}
                </div>
            {/foreach}
        </div>
      </div>
      <br/>
      <br/>
      {/if}
      {if $responseKey}
        <div class="crm-webinar-information-api-key-block help">
            <strong>Info:</strong> Your account is connected.&nbsp;Here are your Upcoming Webinars
        </div>
      {/if}
      {if $initial}
        <table class="form-layout-compressed">
          <tr class="crm-webinar-setting-api-key-block">
            <td class="label">{$form.api_key.label}</td>
            <td>{$form.api_key.html}<br/>
              <span class="description">{ts}The Consumer Key from your GoToWebinar App{/ts}
              </span>
            </td>
          </tr>
          <tr class="crm-webinar-setting-client-secret-block">
            <td class="label">{$form.client_secret.label}</td>
            <td>{$form.client_secret.html}<br/>
              <span class="description">{ts}The Consumer Secret from your GoToWebinar App{/ts}
              </span>
            </td>
          </tr>
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
        </table>
      {/if}
      {if $responseKey}
      <table class="dataTable">
        <thead >
          <tr>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Subject{/ts}</th>
            <th>{ts}Webinar Key{/ts}</th>
            <th>{ts}Start Time{/ts}</th>
            <th>{ts}End Time{/ts}</th>
         </tr>
         <tbody>
            {foreach from=$upcomingWebinars item=webinar}
              <tr>
                <td>{$webinar.description}</td>
                <td>
                  {$webinar.subject}
                  <p style="color: red;">{$webinar.warning}</p>
                </td>
                <td>{$webinar.webinarKey}</td>
                {assign var=times value=$webinar.times}
                <td>{$times[0].startTime|crmDate}</td>
                <td>{$times[0].endTime|crmDate}</td>
              </tr>
            {/foreach}
          </tbody>
        </table>
      {/if}
      {if $clienterror}
        <table class="form-layout-compressed">
          <tr class="crm-webinar-information-erro-api-key-block">
          <td class="label" style="color:red">{ts} Info:{/ts}</td>
          <td class="label" style="color:red">{ts}{$clienterror.int_err_code}{/ts}&nbsp;&nbsp;&nbsp;&nbsp;{ts}{$clienterror.msg}{/ts}</td>
          </tr>
        </table>
      {/if}
      {if $error}
        <table class="form-layout-compressed">
          <tr class="crm-webinar-information-erro-api-key-block">
          <td class="label" style="color:red">{ts} Info:{/ts}</td>
          <td class="label" style="color:red">{ts}{$error.int_err_code}{/ts}&nbsp;&nbsp;&nbsp;&nbsp;{ts}{$error.msg}{/ts}</td>
          </tr>
        </table>
      {/if}
    </div>
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl"}
    </div>
  </div>
</div>
