<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * Form controller class for Power Mapping Settings
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Powermap_Form_Settings extends CRM_Core_Form {

  public function preProcess() {
    CRM_Utils_System::setTitle(E::ts('Power Mapping Settings'));

    // Check permissions
    if (!CRM_Core_Permission::check('administer power mapping')) {
      CRM_Core_Error::statusBounce(E::ts('You do not have permission to access this page.'));
    }
  }

  public function buildForm() {

    // General Settings Section
    $this->add('checkbox', 'enable_notifications', E::ts('Enable Assessment Notifications'));
    $this->add('select', 'default_reminder_frequency', E::ts('Default Reminder Frequency'), [
      'never' => E::ts('Never'),
      'weekly' => E::ts('Weekly'),
      'monthly' => E::ts('Monthly'),
      'quarterly' => E::ts('Quarterly'),
      'annually' => E::ts('Annually')
    ]);

    // Default Assessment Values
    $this->add('select', 'default_influence_level', E::ts('Default Influence Level'), [
      '' => E::ts('- No Default -'),
      'low' => E::ts('Low'),
      'medium' => E::ts('Medium'),
      'high' => E::ts('High')
    ]);

    $this->add('select', 'default_support_level', E::ts('Default Support Level'), [
      '' => E::ts('- No Default -'),
      'strong_opposition' => E::ts('Strong Opposition'),
      'opposition' => E::ts('Opposition'),
      'neutral' => E::ts('Neutral'),
      'support' => E::ts('Support'),
      'strong_support' => E::ts('Strong Support')
    ]);

    $this->add('select', 'default_engagement_priority', E::ts('Default Engagement Priority'), [
      '' => E::ts('- No Default -'),
      'low' => E::ts('Low Priority'),
      'medium' => E::ts('Medium Priority'),
      'high' => E::ts('High Priority')
    ]);

    // Visualization Settings
    $this->add('select', 'default_color_scheme', E::ts('Default Color Scheme'), [
      'default' => E::ts('Default (Red/Orange/Green)'),
      'blue' => E::ts('Blue Gradient'),
      'purple' => E::ts('Purple Gradient'),
      'colorblind' => E::ts('Colorblind Friendly')
    ]);

    $this->add('checkbox', 'enable_animations', E::ts('Enable Animations'));
    $this->add('checkbox', 'show_tooltips', E::ts('Show Detailed Tooltips'));
    $this->add('checkbox', 'enable_drag_drop', E::ts('Enable Drag & Drop by Default'));

    // Data Management Settings
    $this->add('text', 'assessment_retention_days', E::ts('Assessment History Retention (days)'));
    $this->add('checkbox', 'auto_archive_inactive', E::ts('Auto-archive Inactive Maps'));
    $this->add('text', 'inactive_threshold_days', E::ts('Consider Inactive After (days)'));

    // Export Settings
    $this->add('checkbox', 'allow_csv_export', E::ts('Allow CSV Export'));
    $this->add('checkbox', 'allow_json_export', E::ts('Allow JSON Export'));
    $this->add('checkbox', 'allow_pdf_export', E::ts('Allow PDF Export'));
    $this->add('checkbox', 'include_contact_details', E::ts('Include Contact Details in Exports'));

    // Integration Settings
    $this->add('checkbox', 'sync_with_activities', E::ts('Create Activities for Assessments'));
    $this->add('select', 'activity_type_id', E::ts('Activity Type for Assessments'),
      $this->getActivityTypes());
    $this->add('checkbox', 'sync_with_relationships', E::ts('Use CiviCRM Relationships'));

    // Permission Settings
    $this->add('select', 'default_map_visibility', E::ts('Default Map Visibility'), [
      'public' => E::ts('Public'),
      'private' => E::ts('Private'),
      'group' => E::ts('Group Access')
    ]);

    $this->add('checkbox', 'require_approval', E::ts('Require Approval for New Maps'));
    $this->add('entityRef', 'approval_contact_id', E::ts('Approval Contact'), [
      'entity' => 'Contact',
      'select' => ['minimumInputLength' => 0],
    ]);

    // Advanced Settings
    $this->add('text', 'max_stakeholders_per_map', E::ts('Maximum Stakeholders per Map'));
    $this->add('checkbox', 'enable_version_control', E::ts('Enable Version Control'));
    $this->add('checkbox', 'enable_audit_log', E::ts('Enable Audit Log'));

    // API Settings
    $this->add('checkbox', 'enable_api_access', E::ts('Enable API Access'));
    $this->add('text', 'api_rate_limit', E::ts('API Rate Limit (requests per hour)'));

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => E::ts('Save Settings'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);

    parent::buildForm();
  }

  public function setDefaultValues() {
    $defaults = [];

    // Load current settings
    $settings = [
      'enable_notifications',
      'default_reminder_frequency',
      'default_influence_level',
      'default_support_level',
      'default_engagement_priority',
      'default_color_scheme',
      'enable_animations',
      'show_tooltips',
      'enable_drag_drop',
      'assessment_retention_days',
      'auto_archive_inactive',
      'inactive_threshold_days',
      'allow_csv_export',
      'allow_json_export',
      'allow_pdf_export',
      'include_contact_details',
      'sync_with_activities',
      'activity_type_id',
      'sync_with_relationships',
      'default_map_visibility',
      'require_approval',
      'approval_contact_id',
      'max_stakeholders_per_map',
      'enable_version_control',
      'enable_audit_log',
      'enable_api_access',
      'api_rate_limit'
    ];

    foreach ($settings as $setting) {
      $value = Civi::settings()->get('powermap_' . $setting);
      if ($value !== NULL) {
        $defaults[$setting] = $value;
      }
    }

    // Set default values if not set
    if (!isset($defaults['default_reminder_frequency'])) {
      $defaults['default_reminder_frequency'] = 'quarterly';
    }
    if (!isset($defaults['default_color_scheme'])) {
      $defaults['default_color_scheme'] = 'default';
    }
    if (!isset($defaults['assessment_retention_days'])) {
      $defaults['assessment_retention_days'] = '365';
    }
    if (!isset($defaults['inactive_threshold_days'])) {
      $defaults['inactive_threshold_days'] = '90';
    }
    if (!isset($defaults['max_stakeholders_per_map'])) {
      $defaults['max_stakeholders_per_map'] = '500';
    }
    if (!isset($defaults['api_rate_limit'])) {
      $defaults['api_rate_limit'] = '1000';
    }

    return $defaults;
  }

  public function postProcess() {
    $values = $this->exportValues();

    // Save settings
    $settings = [
      'enable_notifications',
      'default_reminder_frequency',
      'default_influence_level',
      'default_support_level',
      'default_engagement_priority',
      'default_color_scheme',
      'enable_animations',
      'show_tooltips',
      'enable_drag_drop',
      'assessment_retention_days',
      'auto_archive_inactive',
      'inactive_threshold_days',
      'allow_csv_export',
      'allow_json_export',
      'allow_pdf_export',
      'include_contact_details',
      'sync_with_activities',
      'activity_type_id',
      'sync_with_relationships',
      'default_map_visibility',
      'require_approval',
      'approval_contact_id',
      'max_stakeholders_per_map',
      'enable_version_control',
      'enable_audit_log',
      'enable_api_access',
      'api_rate_limit'
    ];

    foreach ($settings as $setting) {
      $value = $values[$setting] ?? NULL;
      Civi::settings()->set('powermap_' . $setting, $value);
    }

    // Clear cache
    CRM_Core_BAO_Cache::deleteGroup('powermap_settings');

    CRM_Core_Session::setStatus(
      E::ts('Power Mapping settings have been saved.'),
      E::ts('Settings Saved'),
      'success'
    );

    $url = CRM_Utils_System::url('civicrm/powermap/settings', 'reset=1');
    CRM_Utils_System::redirect($url);
  }

  /**
   * Get activity types for dropdown
   */
  private function getActivityTypes() {
    $options = ['' => E::ts('- Select Activity Type -')];

    try {
      $result = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_type',
        'is_active' => 1,
        'options' => ['limit' => 0, 'sort' => 'weight ASC'],
      ]);

      foreach ($result['values'] as $activityType) {
        $options[$activityType['value']] = $activityType['label'];
      }
    }
    catch (Exception $e) {
      // Handle error
    }

    return $options;
  }

  /**
   * Validate form values
   */
  public function validate() {
    $errors = [];

    $values = $this->exportValues();

    // Validate numeric fields
    if (!empty($values['assessment_retention_days']) &&
      (!is_numeric($values['assessment_retention_days']) || $values['assessment_retention_days'] < 1)) {
      $errors['assessment_retention_days'] = E::ts('Assessment retention days must be a positive number.');
    }

    if (!empty($values['inactive_threshold_days']) &&
      (!is_numeric($values['inactive_threshold_days']) || $values['inactive_threshold_days'] < 1)) {
      $errors['inactive_threshold_days'] = E::ts('Inactive threshold days must be a positive number.');
    }

    if (!empty($values['max_stakeholders_per_map']) &&
      (!is_numeric($values['max_stakeholders_per_map']) || $values['max_stakeholders_per_map'] < 1)) {
      $errors['max_stakeholders_per_map'] = E::ts('Maximum stakeholders per map must be a positive number.');
    }

    if (!empty($values['api_rate_limit']) &&
      (!is_numeric($values['api_rate_limit']) || $values['api_rate_limit'] < 1)) {
      $errors['api_rate_limit'] = E::ts('API rate limit must be a positive number.');
    }

    // Validate dependencies
    if (!empty($values['require_approval']) && empty($values['approval_contact_id'])) {
      $errors['approval_contact_id'] = E::ts('Approval contact is required when approval is enabled.');
    }

    if (!empty($values['sync_with_activities']) && empty($values['activity_type_id'])) {
      $errors['activity_type_id'] = E::ts('Activity type is required when activity sync is enabled.');
    }

    foreach ($errors as $field => $message) {
      $this->setElementError($field, $message);
    }

    return empty($errors);
  }
}
