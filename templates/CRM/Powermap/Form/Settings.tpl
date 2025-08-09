{* Power Mapping Settings Template *}
<div class="crm-powermap-settings-form crm-block crm-form-block">

  {* Page Header *}
  <div class="crm-powermap-header">
    <div class="crm-powermap-title">
      <h1>{ts}Power Mapping Settings{/ts}</h1>
      <div class="crm-powermap-breadcrumb">
        <a href="{crmURL p='civicrm/powermap/dashboard' q='reset=1'}">{ts}Dashboard{/ts}</a> &raquo;
        <span>{ts}Settings{/ts}</span>
      </div>
    </div>
    <div class="crm-powermap-actions">
      <a href="{crmURL p='civicrm/powermap/dashboard' q='reset=1'}" class="button crm-button">
        <span><i class="crm-i fa-arrow-circle-left"></i> {ts}Back to Dashboard{/ts}</span>
      </a>
    </div>
  </div>

  <div class="crm-powermap-settings-container">

    {* General Settings Section *}
    <fieldset class="crm-powermap-general-settings">
      <legend>{ts}General Settings{/ts}</legend>

      <div class="crm-section">
        <div class="label">{$form.enable_notifications.label}</div>
        <div class="content">
          {$form.enable_notifications.html}
          <div class="description">{ts}Enable automatic assessment reminder notifications{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{$form.default_reminder_frequency.label}</div>
        <div class="content">
          {$form.default_reminder_frequency.html}
          <div class="description">{ts}How often to remind users to reassess stakeholders{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    </fieldset>

    {* Default Assessment Values Section *}
    <fieldset class="crm-powermap-defaults">
      <legend>{ts}Default Assessment Values{/ts}</legend>
      <div class="description">{ts}These values will be automatically assigned to newly added stakeholders{/ts}</div>

      <div class="crm-section">
        <div class="label">{$form.default_influence_level.label}</div>
        <div class="content">
          {$form.default_influence_level.html}
          <div class="description">{ts}Default influence level for new stakeholders{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{$form.default_support_level.label}</div>
        <div class="content">
          {$form.default_support_level.html}
          <div class="description">{ts}Default support level for new stakeholders{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{$form.default_engagement_priority.label}</div>
        <div class="content">
          {$form.default_engagement_priority.html}
          <div class="description">{ts}Default engagement priority for new stakeholders{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    </fieldset>

    {* Visualization Settings Section *}
    <fieldset class="crm-powermap-visualization">
      <legend>{ts}Visualization Settings{/ts}</legend>

      <div class="crm-section">
        <div class="label">{$form.default_color_scheme.label}</div>
        <div class="content">
          {$form.default_color_scheme.html}
          <div class="description">{ts}Default color scheme for power mapping visualizations{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{ts}Display Options{/ts}</div>
        <div class="content">
          <div class="settings-checkboxes">
            <div class="checkbox-item">
              {$form.enable_animations.html}
              <label for="enable_animations">{$form.enable_animations.label}</label>
            </div>
            <div class="checkbox-item">
              {$form.show_tooltips.html}
              <label for="show_tooltips">{$form.show_tooltips.label}</label>
            </div>
            <div class="checkbox-item">
              {$form.enable_drag_drop.html}
              <label for="enable_drag_drop">{$form.enable_drag_drop.label}</label>
            </div>
          </div>
          <div class="description">{ts}Configure default visualization behavior{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    </fieldset>

    {* Data Management Settings Section *}
    <fieldset class="crm-powermap-data-management">
      <legend>{ts}Data Management{/ts}</legend>

      <div class="crm-section">
        <div class="label">{$form.assessment_retention_days.label}</div>
        <div class="content">
          {$form.assessment_retention_days.html}
          <div class="description">{ts}Number of days to retain assessment history (0 = keep forever){/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{$form.auto_archive_inactive.label}</div>
        <div class="content">
          {$form.auto_archive_inactive.html}
          <div class="description">{ts}Automatically archive power maps that haven't been updated recently{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section" id="inactive-threshold-section" style="display: none;">
        <div class="label">{$form.inactive_threshold_days.label}</div>
        <div class="content">
          {$form.inactive_threshold_days.html}
          <div class="description">{ts}Number of days without updates before considering a map inactive{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    </fieldset>

    {* Export Settings Section *}
    <fieldset class="crm-powermap-export">
      <legend>{ts}Export Settings{/ts}</legend>

      <div class="crm-section">
        <div class="label">{ts}Allowed Export Formats{/ts}</div>
        <div class="content">
          <div class="settings-checkboxes">
            <div class="checkbox-item">
              {$form.allow_csv_export.html}
              <label for="allow_csv_export">{$form.allow_csv_export.label}</label>
            </div>
            <div class="checkbox-item">
              {$form.allow_json_export.html}
              <label for="allow_json_export">{$form.allow_json_export.label}</label>
            </div>
            <div class="checkbox-item">
              {$form.allow_pdf_export.html}
              <label for="allow_pdf_export">{$form.allow_pdf_export.label}</label>
            </div>
          </div>
          <div class="description">{ts}Control which export formats are available to users{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{$form.include_contact_details.label}</div>
        <div class="content">
          {$form.include_contact_details.html}
          <div class="description">{ts}Include personal contact information in exports by default{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    </fieldset>

    {* Integration Settings Section *}
    <fieldset class="crm-powermap-integration">
      <legend>{ts}CiviCRM Integration{/ts}</legend>

      <div class="crm-section">
        <div class="label">{$form.sync_with_activities.label}</div>
        <div class="content">
          {$form.sync_with_activities.html}
          <div class="description">{ts}Create activity records when assessments are updated{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section" id="activity-type-section" style="display: none;">
        <div class="label">{$form.activity_type_id.label}</div>
        <div class="content">
          {$form.activity_type_id.html}
          <div class="description">{ts}Activity type to use for assessment activities{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{$form.sync_with_relationships.label}</div>
        <div class="content">
          {$form.sync_with_relationships.html}
          <div class="description">{ts}Use CiviCRM relationship data for stakeholder connections{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    </fieldset>

    {* Permission Settings Section *}
    <fieldset class="crm-powermap-permissions">
      <legend>{ts}Permission Settings{/ts}</legend>

      <div class="crm-section">
        <div class="label">{$form.default_map_visibility.label}</div>
        <div class="content">
          {$form.default_map_visibility.html}
          <div class="description">{ts}Default visibility setting for new power maps{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{$form.require_approval.label}</div>
        <div class="content">
          {$form.require_approval.html}
          <div class="description">{ts}Require approval before new power maps become active{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section" id="approval-contact-section" style="display: none;">
        <div class="label">{$form.approval_contact_id.label}</div>
        <div class="content">
          {$form.approval_contact_id.html}
          <div class="description">{ts}Contact who will receive approval requests{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    </fieldset>

    {* Advanced Settings Section *}
    <fieldset class="crm-powermap-advanced">
      <legend>{ts}Advanced Settings{/ts}</legend>

      <div class="crm-section">
        <div class="label">{$form.max_stakeholders_per_map.label}</div>
        <div class="content">
          {$form.max_stakeholders_per_map.html}
          <div class="description">{ts}Maximum number of stakeholders allowed per power map (0 = unlimited){/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section">
        <div class="label">{ts}Audit & Version Control{/ts}</div>
        <div class="content">
          <div class="settings-checkboxes">
            <div class="checkbox-item">
              {$form.enable_version_control.html}
              <label for="enable_version_control">{$form.enable_version_control.label}</label>
            </div>
            <div class="checkbox-item">
              {$form.enable_audit_log.html}
              <label for="enable_audit_log">{$form.enable_audit_log.label}</label>
            </div>
          </div>
          <div class="description">{ts}Track changes and maintain assessment history{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    </fieldset>

    {* API Settings Section *}
    <fieldset class="crm-powermap-api">
      <legend>{ts}API Settings{/ts}</legend>

      <div class="crm-section">
        <div class="label">{$form.enable_api_access.label}</div>
        <div class="content">
          {$form.enable_api_access.html}
          <div class="description">{ts}Allow external access to power mapping data via API{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>

      <div class="crm-section" id="api-rate-limit-section" style="display: none;">
        <div class="label">{$form.api_rate_limit.label}</div>
        <div class="content">
          {$form.api_rate_limit.html}
          <div class="description">{ts}Maximum API requests per hour per user{/ts}</div>
        </div>
        <div class="clear"></div>
      </div>
    </fieldset>

    {* Form Buttons *}
    <div class="crm-submit-buttons">
      {include file="CRM/common/formButtons.tpl" location="bottom"}

      <span class="crm-button crm-button-type-next">
        <button type="button" id="reset-defaults" class="crm-form-submit">
          <span><i class="crm-i fa-refresh"></i> {ts}Reset to Defaults{/ts}</span>
        </button>
      </span>
    </div>

  </div>
</div>

{* Custom CSS for settings form *}
<style type="text/css">
  {literal}
  .crm-powermap-settings-form {
    max-width: 1200px;
    margin: 0 auto;
  }

  .crm-powermap-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 2px solid #e9ecef;
  }

  .crm-powermap-title h1 {
    margin: 0 0 5px 0;
    color: #2c5aa0;
  }

  .crm-powermap-breadcrumb {
    font-size: 14px;
    color: #666;
  }

  .crm-powermap-breadcrumb a {
    color: #2c5aa0;
    text-decoration: none;
  }

  .crm-powermap-settings-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
  }

  .crm-powermap-settings-container fieldset {
    margin: 0;
    padding: 30px;
    border: none;
    border-bottom: 1px solid #e9ecef;
  }

  .crm-powermap-settings-container fieldset:last-of-type {
    border-bottom: none;
  }

  .crm-powermap-settings-container legend {
    font-size: 18px;
    font-weight: 600;
    color: #2c5aa0;
    margin-bottom: 20px;
    padding: 0;
    border: none;
    width: auto;
  }

  .settings-checkboxes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
  }

  .checkbox-item {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .checkbox-item input[type="checkbox"] {
    margin: 0;
  }

  .checkbox-item label {
    margin: 0;
    font-weight: normal;
  }

  @media (max-width: 768px) {
    .crm-powermap-header {
      flex-direction: column;
      align-items: flex-start;
      gap: 15px;
    }

    .settings-checkboxes {
      grid-template-columns: 1fr;
    }
  }
  {/literal}
</style>

{* JavaScript for settings form *}
<script type="text/javascript">
  {literal}
  CRM.$(function($) {

    // Show/hide dependent fields
    function toggleDependentFields() {
      // Auto archive settings
      if ($('#auto_archive_inactive').is(':checked')) {
        $('#inactive-threshold-section').show();
      } else {
        $('#inactive-threshold-section').hide();
      }

      // Activity sync settings
      if ($('#sync_with_activities').is(':checked')) {
        $('#activity-type-section').show();
      } else {
        $('#activity-type-section').hide();
      }

      // Approval settings
      if ($('#require_approval').is(':checked')) {
        $('#approval-contact-section').show();
      } else {
        $('#approval-contact-section').hide();
      }

      // API settings
      if ($('#enable_api_access').is(':checked')) {
        $('#api-rate-limit-section').show();
      } else {
        $('#api-rate-limit-section').hide();
      }
    }

    // Initial state
    toggleDependentFields();

    // Bind change events
    $('#auto_archive_inactive').change(toggleDependentFields);
    $('#sync_with_activities').change(toggleDependentFields);
    $('#require_approval').change(toggleDependentFields);
    $('#enable_api_access').change(toggleDependentFields);

    // Reset to defaults button
    $('#reset-defaults').click(function() {
      CRM.confirm({
        title: '{/literal}{ts escape="js"}Reset Settings{/ts}{literal}',
        message: '{/literal}{ts escape="js"}Are you sure you want to reset all settings to their default values? This cannot be undone.{/ts}{literal}',
        options: {
          yes: '{/literal}{ts escape="js"}Reset{/ts}{literal}',
          no: '{/literal}{ts escape="js"}Cancel{/ts}{literal}'
        }
      }).on('crmConfirm:yes', function() {
        // Reset form fields to defaults
        $('#enable_notifications').prop('checked', false);
        $('#default_reminder_frequency').val('quarterly');
        $('#default_influence_level').val('');
        $('#default_support_level').val('');
        $('#default_engagement_priority').val('');
        $('#default_color_scheme').val('default');
        $('#enable_animations').prop('checked', true);
        $('#show_tooltips').prop('checked', true);
        $('#enable_drag_drop').prop('checked', true);
        $('#assessment_retention_days').val('365');
        $('#auto_archive_inactive').prop('checked', false);
        $('#inactive_threshold_days').val('90');
        $('#allow_csv_export').prop('checked', true);
        $('#allow_json_export').prop('checked', true);
        $('#allow_pdf_export').prop('checked', false);
        $('#include_contact_details').prop('checked', false);
        $('#sync_with_activities').prop('checked', false);
        $('#activity_type_id').val('');
        $('#sync_with_relationships').prop('checked', false);
        $('#default_map_visibility').val('public');
        $('#require_approval').prop('checked', false);
        $('#approval_contact_id').val('');
        $('#max_stakeholders_per_map').val('500');
        $('#enable_version_control').prop('checked', false);
        $('#enable_audit_log').prop('checked', false);
        $('#enable_api_access').prop('checked', false);
        $('#api_rate_limit').val('1000');

        // Trigger change events
        toggleDependentFields();

        CRM.alert('{/literal}{ts escape="js"}Settings have been reset to defaults. Click "Save Settings" to apply changes.{/ts}{literal}', '{/literal}{ts escape="js"}Settings Reset{/ts}{literal}', 'info');
      });
    });

    // Form validation
    $('form').on('submit', function(e) {
      var isValid = true;
      var errors = [];

      // Validate numeric fields
      var numericFields = [
        'assessment_retention_days',
        'inactive_threshold_days',
        'max_stakeholders_per_map',
        'api_rate_limit'
      ];

      numericFields.forEach(function(field) {
        var value = $('#' + field).val();
        if (value && (isNaN(value) || parseInt(value) < 0)) {
          errors.push($('label[for="' + field + '"]').text() + ' must be a positive number.');
          isValid = false;
        }
      });

      // Validate dependencies
      if ($('#require_approval').is(':checked') && !$('#approval_contact_id').val()) {
        errors.push('Approval contact is required when approval is enabled.');
        isValid = false;
      }

      if ($('#sync_with_activities').is(':checked') && !$('#activity_type_id').val()) {
        errors.push('Activity type is required when activity sync is enabled.');
        isValid = false;
      }

      if (!isValid) {
        e.preventDefault();
        CRM.alert(errors.join('<br/>'), '{/literal}{ts escape="js"}Validation Error{/ts}{literal}', 'error');
      }
    });

    // Initialize Select2 dropdowns
    $('.crm-select2').select2({
      width: '100%',
      placeholder: function() {
        return $(this).attr('placeholder') || $(this).find('option:first').text();
      },
      allowClear: true
    });

    // Enhance numeric inputs
    $('input[type="text"]').each(function() {
      var $input = $(this);
      var fieldName = $input.attr('name');

      if (fieldName && (fieldName.includes('days') || fieldName.includes('limit') || fieldName.includes('max'))) {
        $input.attr('type', 'number');
        $input.attr('min', '0');
        $input.attr('step', '1');
      }
    });

    // Add tooltips for complex settings
    var tooltips = {
      'enable_version_control': '{/literal}{ts escape="js"}Track changes to assessments over time. Requires additional database storage.{/ts}{literal}',
      'enable_audit_log': '{/literal}{ts escape="js"}Log all user actions for compliance and debugging. May impact performance with large datasets.{/ts}{literal}',
      'enable_api_access': '{/literal}{ts escape="js"}Allow external applications to access power mapping data via REST API. Ensure proper permissions are configured.{/ts}{literal}',
      'assessment_retention_days': '{/literal}{ts escape="js"}Set to 0 to keep assessment history forever. Large values may impact database performance.{/ts}{literal}'
    };

    Object.keys(tooltips).forEach(function(fieldId) {
      $('#' + fieldId).parent().append(
        '<div class="help-tooltip" title="' + tooltips[fieldId] + '" style="display: inline-block; margin-left: 5px;">' +
        '<i class="crm-i fa-question-circle" style="color: #666;"></i>' +
        '</div>'
      );
    });

    // Initialize tooltips
    $('.help-tooltip').tooltip({
      position: { my: "left+15 center", at: "right center" }
    });
  });
  {/literal}
</script>
