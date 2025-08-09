<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * Collection of upgrade steps.
 */
class CRM_Powermap_Upgrader extends CRM_Extension_Upgrader_Base {

  // By convention, functions that look like "function upgrade_NNNN()" are
  // upgrade tasks. They are executed in order (like Drupal's hook_update_N).

  /**
   * Example: Run an external SQL script when the module is installed.
   *
   * Note that if a file is present sql\auto_install that will run regardless of this hook.
   */
  public function install(): void {
    // Create custom fields for power mapping
    self::createCustomFields();

    // Create activity type for power mapping assessments
    self::createActivity();

    // Set default settings
    $this->setDefaultSettings();

    // Log successful installation
    CRM_Core_Error::debug_log_message('Power Mapping extension installed successfully.');
  }


  /**
   * Create custom fields for power mapping
   */
  public static function createCustomFields() {

    // Create Power Mapping custom group
    $customGroup = civicrm_api3('CustomGroup', 'create', [
      'title' => 'Power Mapping Data',
      'name' => 'power_mapping_data',
      'extends' => 'Contact',
      'style' => 'Tab with table',
      'collapse_display' => 1,
      'help_pre' => 'Strategic stakeholder assessment data for power mapping visualization.',
      'is_active' => 1,
      'is_multiple' => 0,
      'collapse_adv_display' => 1,
    ]);

    $customGroupId = $customGroup['id'];

    // Influence Level Field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'influence_level',
      'label' => 'Influence Level',
      'data_type' => 'String',
      'html_type' => 'Select',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
      'help_post' => 'Stakeholder\'s ability to affect outcomes (High=3, Medium=2, Low=1)',
      'option_group_id' => self::createOptionGroup('influence_level', [
        'high' => 'High',
        'medium' => 'Medium',
        'low' => 'Low'
      ]),
    ]);

    // Support Level Field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'support_level',
      'label' => 'Support Level',
      'data_type' => 'String',
      'html_type' => 'Select',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
      'help_post' => 'Stakeholder\'s alignment with your cause',
      'option_group_id' => self::createOptionGroup('support_level', [
        'strong_support' => 'Strong Support (+2)',
        'support' => 'Support (+1)',
        'neutral' => 'Neutral (0)',
        'opposition' => 'Opposition (-1)',
        'strong_opposition' => 'Strong Opposition (-2)'
      ]),
    ]);

    // Stakeholder Type Field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'stakeholder_type',
      'label' => 'Stakeholder Type',
      'data_type' => 'String',
      'html_type' => 'Multi-Select',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
      'help_post' => 'Classification of stakeholder role(s)',
      'option_group_id' => self::createOptionGroup('stakeholder_type', [
        'politician' => 'Politician',
        'media' => 'Media Contact',
        'donor' => 'Major Donor',
        'community_leader' => 'Community Leader',
        'business' => 'Business Executive',
        'expert' => 'Subject Matter Expert',
        'activist' => 'Activist/Volunteer',
        'government' => 'Government Official'
      ]),
    ]);

    // Decision Authority Field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'decision_authority',
      'label' => 'Decision Authority',
      'data_type' => 'Memo',
      'html_type' => 'TextArea',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
      'help_post' => 'What this stakeholder can influence or decide',
      'attributes' => 'rows=3 cols=60',
    ]);

    // Engagement Priority Field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'engagement_priority',
      'label' => 'Engagement Priority',
      'data_type' => 'String',
      'html_type' => 'Select',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
      'help_post' => 'Strategic priority level for engagement',
      'option_group_id' => self::createOptionGroup('engagement_priority', [
        'high' => 'High Priority',
        'medium' => 'Medium Priority',
        'low' => 'Low Priority',
        'monitor' => 'Monitor Only'
      ]),
    ]);

    // Last Assessment Date Field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'last_assessment_date',
      'label' => 'Last Assessment Date',
      'data_type' => 'Date',
      'html_type' => 'Select Date',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
      'help_post' => 'When this stakeholder was last assessed',
      'date_format' => 'mm/dd/yy',
    ]);

    // Assessment Notes Field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'assessment_notes',
      'label' => 'Assessment Notes',
      'data_type' => 'Memo',
      'html_type' => 'RichTextEditor',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
      'help_post' => 'Notes and reasoning for influence/support assessments',
      'attributes' => 'rows=4 cols=60',
    ]);


    // relationship Strength Field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'relationship_strength',
      'label' => 'Relationship Strength',
      'data_type' => 'String',
      'html_type' => 'Select',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
      'help_post' => 'Strength of relationship with this stakeholder',
      'option_group_id' => self::createOptionGroup('relationship_strength', [
        'strong' => 'Strong',
        'moderate' => 'Moderate',
        'weak' => 'Weak',
        'none' => 'No Relationship'
      ]),
    ]);

    // Communication Preference Field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'communication_preference',
      'label' => 'Communication Preference  ',
      'data_type' => 'String',
      'html_type' => 'Select',
      'is_required' => 0,
      'is_searchable' => 1,
      'is_active' => 1,
      'help_post' => 'Preferred communication methods',
      'option_group_id' => self::createOptionGroup('communication_preference', [
        'email' => 'Email',
        'phone' => 'Phone',
        'in_person' => 'In Person',
        'social_media' => 'Social Media',
        'formal_letter' => 'Formal Letter'
      ]),
    ]);


  }

  /**
   * Create option groups for custom fields
   */
  private static function createOptionGroup($name, $options) {
    $optionGroup = civicrm_api3('OptionGroup', 'create', [
      'name' => 'powermap_' . $name,
      'title' => ucfirst(str_replace('_', ' ', $name)),
      'is_reserved' => 1,
      'is_active' => 1,
    ]);

    $weight = 1;
    foreach ($options as $value => $label) {
      civicrm_api3('OptionValue', 'create', [
        'option_group_id' => $optionGroup['id'],
        'name' => $value,
        'label' => $label,
        'value' => $value,
        'weight' => $weight++,
        'is_active' => 1,
      ]);
    }

    return $optionGroup['id'];
  }


  private static function createActivity() {
    try {
      // Check if activity type exists
      $existing = civicrm_api3('OptionValue', 'get', [
        'option_group_id' => 'activity_type',
        'name' => 'power_mapping_assessment',
      ]);

      if ($existing['count'] == 0) {
        // Create the activity type
        civicrm_api3('OptionValue', 'create', [
          'option_group_id' => 'activity_type',
          'name' => 'power_mapping_assessment',
          'label' => 'Power Mapping Assessment',
          'description' => 'Stakeholder assessment for power mapping',
          'is_active' => 1,
          'is_reserved' => 0,
        ]);

        // Set as default activity type for power mapping
        Civi::settings()->set('powermap_activity_type_id', 'power_mapping_assessment');
      }

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Upgrade 1300 failed: ' . $e->getMessage());
      return FALSE;
    }
  }
  /**
   * Set default settings
   */
  private function setDefaultSettings() {
    $defaultSettings = [
      'powermap_enable_notifications' => 0,
      'powermap_default_reminder_frequency' => 'quarterly',
      'powermap_default_color_scheme' => 'default',
      'powermap_enable_animations' => 1,
      'powermap_show_tooltips' => 1,
      'powermap_enable_drag_drop' => 1,
      'powermap_assessment_retention_days' => 365,
      'powermap_auto_archive_inactive' => 0,
      'powermap_inactive_threshold_days' => 90,
      'powermap_allow_csv_export' => 1,
      'powermap_allow_json_export' => 1,
      'powermap_allow_pdf_export' => 0,
      'powermap_include_contact_details' => 0,
      'powermap_sync_with_activities' => 0,
      'powermap_sync_with_relationships' => 0,
      'powermap_default_map_visibility' => 'public',
      'powermap_require_approval' => 0,
      'powermap_max_stakeholders_per_map' => 500,
      'powermap_enable_version_control' => 0,
      'powermap_enable_audit_log' => 0,
      'powermap_enable_api_access' => 0,
      'powermap_api_rate_limit' => 1000,
    ];

    foreach ($defaultSettings as $setting => $value) {
      if (Civi::settings()->get($setting) === NULL) {
        Civi::settings()->set($setting, $value);
      }
    }
  }

  /**
   * Example: Work with entities usually not available during the install step.
   *
   * This method can be used for any post-install tasks. For example, if a step
   * of your installation depends on accessing an entity that is itself
   * created during the installation (e.g., a setting or a managed entity), do
   * so here to avoid order of operation problems.
   */
  // public function postInstall(): void {
  //  $customFieldId = civicrm_api3('CustomField', 'getvalue', array(
  //    'return' => array("id"),
  //    'name' => "customFieldCreatedViaManagedHook",
  //  ));
  //  civicrm_api3('Setting', 'create', array(
  //    'myWeirdFieldSetting' => array('id' => $customFieldId, 'weirdness' => 1),
  //  ));
  // }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   *
   * Note that if a file is present sql\auto_uninstall that will run regardless of this hook.
   */
  // public function uninstall(): void {
  //   $this->executeSqlFile('sql/my_uninstall.sql');
  // }

  /**
   * Example: Run a simple query when a module is enabled.
   */
  // public function enable(): void {
  //  CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 1 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a simple query when a module is disabled.
   */
  // public function disable(): void {
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET is_active = 0 WHERE bar = "whiz"');
  // }

  /**
   * Example: Run a couple simple queries.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4200(): bool {
  //   $this->ctx->log->info('Applying update 4200');
  //   CRM_Core_DAO::executeQuery('UPDATE foo SET bar = "whiz"');
  //   CRM_Core_DAO::executeQuery('DELETE FROM bang WHERE willy = wonka(2)');
  //   return TRUE;
  // }

  /**
   * Example: Run an external SQL script.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4201(): bool {
  //   $this->ctx->log->info('Applying update 4201');
  //   // this path is relative to the extension base dir
  //   $this->executeSqlFile('sql/upgrade_4201.sql');
  //   return TRUE;
  // }

  /**
   * Example: Run a slow upgrade process by breaking it up into smaller chunk.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4202(): bool {
  //   $this->ctx->log->info('Planning update 4202'); // PEAR Log interface

  //   $this->addTask(E::ts('Process first step'), 'processPart1', $arg1, $arg2);
  //   $this->addTask(E::ts('Process second step'), 'processPart2', $arg3, $arg4);
  //   $this->addTask(E::ts('Process second step'), 'processPart3', $arg5);
  //   return TRUE;
  // }
  // public function processPart1($arg1, $arg2) { sleep(10); return TRUE; }
  // public function processPart2($arg3, $arg4) { sleep(10); return TRUE; }
  // public function processPart3($arg5) { sleep(10); return TRUE; }

  /**
   * Example: Run an upgrade with a query that touches many (potentially
   * millions) of records by breaking it up into smaller chunks.
   *
   * @return TRUE on success
   * @throws CRM_Core_Exception
   */
  // public function upgrade_4203(): bool {
  //   $this->ctx->log->info('Planning update 4203'); // PEAR Log interface

  //   $minId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(min(id),0) FROM civicrm_contribution');
  //   $maxId = CRM_Core_DAO::singleValueQuery('SELECT coalesce(max(id),0) FROM civicrm_contribution');
  //   for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
  //     $endId = $startId + self::BATCH_SIZE - 1;
  //     $title = E::ts('Upgrade Batch (%1 => %2)', array(
  //       1 => $startId,
  //       2 => $endId,
  //     ));
  //     $sql = '
  //       UPDATE civicrm_contribution SET foobar = apple(banana()+durian)
  //       WHERE id BETWEEN %1 and %2
  //     ';
  //     $params = array(
  //       1 => array($startId, 'Integer'),
  //       2 => array($endId, 'Integer'),
  //     );
  //     $this->addTask($title, 'executeSql', $sql, $params);
  //   }
  //   return TRUE;
  // }

}
