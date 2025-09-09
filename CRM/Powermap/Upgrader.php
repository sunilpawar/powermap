<?php

use CRM_Powermap_ExtensionUtil as E;

class CRM_Powermap_Upgrader extends CRM_Extension_Upgrader_Base {
  const BATCH_SIZE = 200;

  /**
   * Example: Work with entities usually not available during the install step.
   */
  public function install() {
    $this->createCustomFields();
  }

  /**
   * Example: Work with entities usually not available during the install step.
   */
  public function enable() {
    // Custom work here
  }

  /**
   * Example: Run an external SQL script when the module is uninstalled.
   */
  public function uninstall() {
    $this->removeCustomFields();
  }

  /**
   * Create custom fields for PowerMap functionality
   */
  private function createCustomFields() {
    // Create custom group for PowerMap data
    $customGroup = civicrm_api3('CustomGroup', 'create', [
      'title' => 'PowerMap Data',
      'name' => 'powermap_data',
      'extends' => 'Contact',
      'is_active' => 1,
      'is_multiple' => 0,
      'collapse_display' => 0,
      'help_pre' => 'PowerMap stakeholder analysis data',
    ]);

    $customGroupId = $customGroup['id'];

    // Influence Level field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'influence_level',
      'label' => 'Influence Level',
      'data_type' => 'Int',
      'html_type' => 'Select',
      'option_values' => [
        1 => '1 - Low',
        2 => '2 - Medium-Low',
        3 => '3 - Medium',
        4 => '4 - High',
        5 => '5 - Very High',
      ],
      'is_active' => 1,
      'is_searchable' => 1,
      'help_post' => 'Rate the stakeholder\'s level of influence in the network',
    ]);

    // Support Level field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'support_level',
      'label' => 'Support Level',
      'data_type' => 'Int',
      'html_type' => 'Select',
      'option_values' => [
        1 => '1 - Strong Opposition',
        2 => '2 - Opposition',
        3 => '3 - Neutral',
        4 => '4 - Support',
        5 => '5 - Strong Support',
      ],
      'is_active' => 1,
      'is_searchable' => 1,
      'help_post' => 'Rate the stakeholder\'s support level for your organization/project',
    ]);

    // PowerMap Notes field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'powermap_notes',
      'label' => 'PowerMap Notes',
      'data_type' => 'Memo',
      'html_type' => 'TextArea',
      'is_active' => 1,
      'is_searchable' => 1,
      'help_post' => 'Additional notes about this stakeholder in the power map context',
    ]);

    // Network Position field
    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'network_position',
      'label' => 'Network Position',
      'data_type' => 'String',
      'html_type' => 'Select',
      'option_values' => [
        'core' => 'Core Network',
        'inner' => 'Inner Circle',
        'outer' => 'Outer Circle',
        'peripheral' => 'Peripheral',
      ],
      'is_active' => 1,
      'is_searchable' => 1,
      'help_post' => 'Position of stakeholder in the network hierarchy',
    ]);

    civicrm_api3('CustomField', 'create', [
      'custom_group_id' => $customGroupId,
      'name' => 'relationship_strength',
      'label' => 'Relationship Strength',
      'data_type' => 'Int',
      'html_type' => 'Select',
      'option_values' => [
        1 => '1 - Weak',
        2 => '2 - Moderate',
        3 => '3 - Strong',
      ],
      'is_active' => 1,
      'is_searchable' => 1,
      'help_post' => 'Strength of relationship connections',
    ]);
  }

  /**
   * Remove custom fields on uninstall
   */
  private function removeCustomFields() {
    try {
      $customGroup = civicrm_api3('CustomGroup', 'get', [
        'name' => 'powermap_data',
      ]);

      if (!empty($customGroup['values'])) {
        foreach ($customGroup['values'] as $group) {
          civicrm_api3('CustomGroup', 'delete', [
            'id' => $group['id'],
          ]);
        }
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error removing PowerMap custom fields: ' . $e->getMessage());
    }
  }
}
