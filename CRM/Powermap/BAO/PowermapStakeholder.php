<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * Business Access Object for PowermapStakeholder entity
 */
class CRM_Powermap_BAO_PowermapStakeholder extends CRM_Powermap_DAO_PowermapStakeholder {

  /**
   * Create a new PowermapStakeholder based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Powermap_DAO_PowermapStakeholder|NULL
   */
  public static function create($params) {
    $className = 'CRM_Powermap_DAO_PowermapStakeholder';
    $entityName = 'PowermapStakeholder';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, $params['id'] ?? NULL, $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Add stakeholders to a power map
   *
   * @param int $powermapId
   * @param array $contactIds
   * @param array $options
   * @return array Results of the operation
   */
  public static function addStakeholders($powermapId, $contactIds, $options = []) {
    // Check permissions
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($powermapId);
    if (!in_array($permission, ['owner', 'admin', 'edit'])) {
      throw new CRM_Core_Exception('Insufficient permissions to add stakeholders');
    }

    $results = [];
    $addedBy = CRM_Core_Session::getLoggedInContactID();
    $addedDate = date('Y-m-d H:i:s');

    foreach ($contactIds as $contactId) {
      try {
        // Check if stakeholder already exists
        $existing = self::getStakeholder($powermapId, $contactId);
        if ($existing) {
          if ($existing['is_active']) {
            $results[$contactId] = ['success' => FALSE, 'error' => 'Stakeholder already exists'];
            continue;
          }
          else {
            // Reactivate existing stakeholder
            $params = [
              'id' => $existing['id'],
              'is_active' => 1,
              'added_date' => $addedDate,
              'added_by' => $addedBy
            ];
          }
        }
        else {
          // Create new stakeholder
          $params = [
            'powermap_id' => $powermapId,
            'contact_id' => $contactId,
            'added_date' => $addedDate,
            'added_by' => $addedBy,
            'is_active' => 1
          ];

          // Add default position if provided
          if (!empty($options['default_position_x'])) {
            $params['position_x'] = $options['default_position_x'];
          }
          if (!empty($options['default_position_y'])) {
            $params['position_y'] = $options['default_position_y'];
          }
        }

        $stakeholder = self::create($params);
        $results[$contactId] = ['success' => TRUE, 'id' => $stakeholder->id];

        // Log the action
        CRM_Powermap_BAO_PowermapAuditLog::logAction(
          'civicrm_powermap_stakeholder',
          $stakeholder->id,
          $existing ? 'reactivate' : 'create',
          $existing ? ['is_active' => 0] : NULL,
          ['powermap_id' => $powermapId, 'contact_id' => $contactId]
        );

      }
      catch (Exception $e) {
        $results[$contactId] = ['success' => FALSE, 'error' => $e->getMessage()];
      }
    }

    return $results;
  }

  /**
   * Remove stakeholders from a power map
   *
   * @param int $powermapId
   * @param array $contactIds
   * @param bool $hardDelete Whether to delete permanently or just deactivate
   * @return array Results of the operation
   */
  public static function removeStakeholders($powermapId, $contactIds, $hardDelete = FALSE) {
    // Check permissions
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($powermapId);
    if (!in_array($permission, ['owner', 'admin', 'edit'])) {
      throw new CRM_Core_Exception('Insufficient permissions to remove stakeholders');
    }

    $results = [];

    foreach ($contactIds as $contactId) {
      try {
        $stakeholder = self::getStakeholder($powermapId, $contactId);
        if (!$stakeholder) {
          $results[$contactId] = ['success' => FALSE, 'error' => 'Stakeholder not found'];
          continue;
        }

        if ($hardDelete) {
          // Permanently delete
          $deleteStakeholder = new self();
          $deleteStakeholder->id = $stakeholder['id'];
          $deleteStakeholder->delete();
        }
        else {
          // Soft delete (deactivate)
          $params = [
            'id' => $stakeholder['id'],
            'is_active' => 0
          ];
          self::create($params);
        }

        $results[$contactId] = ['success' => TRUE];

        // Log the action
        CRM_Powermap_BAO_PowermapAuditLog::logAction(
          'civicrm_powermap_stakeholder',
          $stakeholder['id'],
          $hardDelete ? 'delete' : 'deactivate',
          ['is_active' => 1],
          $hardDelete ? NULL : ['is_active' => 0]
        );

      }
      catch (Exception $e) {
        $results[$contactId] = ['success' => FALSE, 'error' => $e->getMessage()];
      }
    }

    return $results;
  }

  /**
   * Update stakeholder position
   *
   * @param int $powermapId
   * @param int $contactId
   * @param float $positionX
   * @param float $positionY
   * @param array $additionalParams
   * @return bool
   */
  public static function updatePosition($powermapId, $contactId, $positionX, $positionY, $additionalParams = []) {
    // Check permissions
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($powermapId);
    if (!in_array($permission, ['owner', 'admin', 'edit'])) {
      throw new CRM_Core_Exception('Insufficient permissions to update stakeholder position');
    }

    $stakeholder = self::getStakeholder($powermapId, $contactId);
    if (!$stakeholder) {
      throw new CRM_Core_Exception('Stakeholder not found');
    }

    // Get old position for audit log
    $oldPosition = [
      'position_x' => $stakeholder['position_x'],
      'position_y' => $stakeholder['position_y']
    ];

    $params = array_merge([
      'id' => $stakeholder['id'],
      'position_x' => $positionX,
      'position_y' => $positionY
    ], $additionalParams);

    $updated = self::create($params);

    // Log the position change
    CRM_Powermap_BAO_PowermapAuditLog::logAction(
      'civicrm_powermap_stakeholder',
      $stakeholder['id'],
      'update',
      $oldPosition,
      ['position_x' => $positionX, 'position_y' => $positionY]
    );

    return TRUE;
  }

  /**
   * Get stakeholder by power map and contact ID
   *
   * @param int $powermapId
   * @param int $contactId
   * @return array|null
   */
  public static function getStakeholder($powermapId, $contactId) {
    $sql = "
      SELECT ps.*, c.display_name
      FROM civicrm_powermap_stakeholder ps
      JOIN civicrm_contact c ON c.id = ps.contact_id
      WHERE ps.powermap_id = %1 AND ps.contact_id = %2
    ";

    $params = [
      1 => [$powermapId, 'Integer'],
      2 => [$contactId, 'Integer']
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if ($dao->fetch()) {
      return [
        'id' => $dao->id,
        'powermap_id' => $dao->powermap_id,
        'contact_id' => $dao->contact_id,
        'display_name' => $dao->display_name,
        'added_date' => $dao->added_date,
        'added_by' => $dao->added_by,
        'is_active' => $dao->is_active,
        'position_x' => $dao->position_x,
        'position_y' => $dao->position_y,
        'notes' => $dao->notes
      ];
    }

    return NULL;
  }

  /**
   * Get all stakeholders for a power map with detailed information
   *
   * @param int $powermapId
   * @param array $filters
   * @return array
   */
  public static function getStakeholders($powermapId, $filters = []) {
    // Check permissions
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($powermapId);
    if ($permission === 'none') {
      throw new CRM_Core_Exception('Insufficient permissions to view stakeholders');
    }

    // Get custom field info
    $customFields = self::getCustomFieldInfo();
    if (empty($customFields)) {
      $customFields = ['table_name' => 'civicrm_value_power_mapping_data_1'];
    }

    $whereConditions = ['ps.powermap_id = %1', 'ps.is_active = 1', 'c.is_deleted = 0'];
    $params = [1 => [$powermapId, 'Integer']];
    $paramIndex = 2;

    // Apply filters
    if (!empty($filters['contact_type'])) {
      $whereConditions[] = "c.contact_type = %{$paramIndex}";
      $params[$paramIndex] = [$filters['contact_type'], 'String'];
      $paramIndex++;
    }

    if (!empty($filters['search_term'])) {
      $whereConditions[] = "c.display_name LIKE %{$paramIndex}";
      $params[$paramIndex] = ['%' . $filters['search_term'] . '%', 'String'];
      $paramIndex++;
    }

    $sql = "
      SELECT
        ps.*,
        c.display_name,
        c.contact_type,
        c.contact_sub_type,
        e.email,
        p.phone,
        org.display_name as organization_name,
        added_by_contact.display_name as added_by_name
    ";

    // Add custom field columns if available
    if (!empty($customFields['influence_column'])) {
      $sql .= ",
        pm_data.{$customFields['influence_column']} as influence_level,
        pm_data.{$customFields['support_column']} as support_level,
        pm_data.{$customFields['priority_column']} as engagement_priority,
        pm_data.{$customFields['type_column']} as stakeholder_type,
        pm_data.{$customFields['authority_column']} as decision_authority,
        pm_data.{$customFields['notes_column']} as assessment_notes,
        pm_data.{$customFields['date_column']} as last_assessment_date
      ";
    }

    $sql .= "
      FROM civicrm_powermap_stakeholder ps
      JOIN civicrm_contact c ON c.id = ps.contact_id
      LEFT JOIN civicrm_email e ON e.contact_id = c.id AND e.is_primary = 1
      LEFT JOIN civicrm_phone p ON p.contact_id = c.id AND p.is_primary = 1
      LEFT JOIN civicrm_contact org ON org.id = c.employer_id
      LEFT JOIN civicrm_contact added_by_contact ON added_by_contact.id = ps.added_by
    ";

    // Add custom field join if available
    if (!empty($customFields['table_name'])) {
      $sql .= " LEFT JOIN {$customFields['table_name']} pm_data ON pm_data.entity_id = c.id";
    }

    $sql .= "
      WHERE " . implode(' AND ', $whereConditions) . "
      ORDER BY c.display_name
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $stakeholders = [];
    while ($dao->fetch()) {
      $stakeholder = [
        'id' => $dao->id,
        'powermap_id' => $dao->powermap_id,
        'contact_id' => $dao->contact_id,
        'display_name' => $dao->display_name,
        'contact_type' => $dao->contact_type,
        'contact_sub_type' => $dao->contact_sub_type,
        'email' => $dao->email,
        'phone' => $dao->phone,
        'organization_name' => $dao->organization_name,
        'added_date' => $dao->added_date,
        'added_by' => $dao->added_by,
        'added_by_name' => $dao->added_by_name,
        'is_active' => $dao->is_active,
        'position_x' => $dao->position_x,
        'position_y' => $dao->position_y,
        'notes' => $dao->notes
      ];

      // Add assessment data if available
      if (!empty($customFields['influence_column'])) {
        $stakeholder['influence_level'] = $dao->influence_level ?? NULL;
        $stakeholder['support_level'] = $dao->support_level ?? NULL;
        $stakeholder['engagement_priority'] = $dao->engagement_priority ?? NULL;
        $stakeholder['stakeholder_type'] = $dao->stakeholder_type ?? NULL;
        $stakeholder['decision_authority'] = $dao->decision_authority ?? NULL;
        $stakeholder['assessment_notes'] = $dao->assessment_notes ?? NULL;
        $stakeholder['last_assessment_date'] = $dao->last_assessment_date ?? NULL;

        // Calculate derived values
        $stakeholder['influence_score'] = self::getInfluenceScore($dao->contact_id);
        $stakeholder['support_score'] = self::getSupportScore($dao->contact_id);
        $stakeholder['quadrant'] = self::getStrategicQuadrant($dao->contact_id);
        $stakeholder['strategy'] = self::getEngagementStrategy($dao->contact_id);
      }

      $stakeholders[] = $stakeholder;
    }

    return $stakeholders;
  }

  /**
   * Get stakeholder counts by quadrant for a power map
   *
   * @param int $powermapId
   * @return array
   */
  public static function getQuadrantCounts($powermapId) {
    $customFields = self::getCustomFieldInfo();
    if (empty($customFields)) {
      return ['total' => 0, 'champions' => 0, 'targets' => 0, 'grassroots' => 0, 'monitor' => 0];
    }

    $sql = "
      SELECT
        COUNT(*) as total,
        SUM(CASE
          WHEN pm_data.{$customFields['influence_column']} = 'high'
               AND pm_data.{$customFields['support_column']} IN ('support', 'strong_support')
          THEN 1 ELSE 0 END) as champions,
        SUM(CASE
          WHEN pm_data.{$customFields['influence_column']} = 'high'
               AND pm_data.{$customFields['support_column']} IN ('opposition', 'strong_opposition', 'neutral')
          THEN 1 ELSE 0 END) as targets,
        SUM(CASE
          WHEN pm_data.{$customFields['influence_column']} IN ('low', 'medium')
               AND pm_data.{$customFields['support_column']} IN ('support', 'strong_support')
          THEN 1 ELSE 0 END) as grassroots,
        SUM(CASE
          WHEN NOT (
            (pm_data.{$customFields['influence_column']} = 'high' AND pm_data.{$customFields['support_column']} IN ('support', 'strong_support')) OR
            (pm_data.{$customFields['influence_column']} = 'high' AND pm_data.{$customFields['support_column']} IN ('opposition', 'strong_opposition', 'neutral')) OR
            (pm_data.{$customFields['influence_column']} IN ('low', 'medium') AND pm_data.{$customFields['support_column']} IN ('support', 'strong_support'))
          ) OR pm_data.{$customFields['influence_column']} IS NULL
          THEN 1 ELSE 0 END) as monitor
      FROM civicrm_powermap_stakeholder ps
      JOIN civicrm_contact c ON c.id = ps.contact_id
      LEFT JOIN {$customFields['table_name']} pm_data ON pm_data.entity_id = c.id
      WHERE ps.powermap_id = %1
        AND ps.is_active = 1
        AND c.is_deleted = 0
    ";

    $params = [1 => [$powermapId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if ($dao->fetch()) {
      return [
        'total' => $dao->total,
        'champions' => $dao->champions,
        'targets' => $dao->targets,
        'grassroots' => $dao->grassroots,
        'monitor' => $dao->monitor
      ];
    }

    return ['total' => 0, 'champions' => 0, 'targets' => 0, 'grassroots' => 0, 'monitor' => 0];
  }

  /**
   * Bulk update stakeholder positions
   *
   * @param int $powermapId
   * @param array $updates Array of contact_id => ['x' => x, 'y' => y]
   * @return array Results
   */
  public static function bulkUpdatePositions($powermapId, $updates) {
    // Check permissions
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($powermapId);
    if (!in_array($permission, ['owner', 'admin', 'edit'])) {
      throw new CRM_Core_Exception('Insufficient permissions to update stakeholder positions');
    }

    $results = [];

    foreach ($updates as $contactId => $position) {
      try {
        self::updatePosition(
          $powermapId,
          $contactId,
          $position['x'],
          $position['y']
        );
        $results[$contactId] = ['success' => TRUE];
      }
      catch (Exception $e) {
        $results[$contactId] = ['success' => FALSE, 'error' => $e->getMessage()];
      }
    }

    return $results;
  }

  /**
   * Import stakeholders from another power map
   *
   * @param int $sourcePowermapId
   * @param int $targetPowermapId
   * @param array $options
   * @return array Results
   */
  public static function importFromPowerMap($sourcePowermapId, $targetPowermapId, $options = []) {
    // Check permissions on both maps
    $sourcePermission = CRM_Powermap_BAO_PowermapConfig::checkAccess($sourcePowermapId);
    $targetPermission = CRM_Powermap_BAO_PowermapConfig::checkAccess($targetPowermapId);

    if (!in_array($sourcePermission, ['owner', 'admin', 'edit', 'view'])) {
      throw new CRM_Core_Exception('Insufficient permissions to read source power map');
    }

    if (!in_array($targetPermission, ['owner', 'admin', 'edit'])) {
      throw new CRM_Core_Exception('Insufficient permissions to modify target power map');
    }

    // Get stakeholders from source map
    $sourceStakeholders = self::getStakeholders($sourcePowermapId);
    $contactIds = array_column($sourceStakeholders, 'contact_id');

    // Add stakeholders to target map
    $results = self::addStakeholders($targetPowermapId, $contactIds, $options);

    // Copy positions if requested
    if ($options['copy_positions'] ?? FALSE) {
      $positionUpdates = [];
      foreach ($sourceStakeholders as $stakeholder) {
        if ($stakeholder['position_x'] !== NULL || $stakeholder['position_y'] !== NULL) {
          $positionUpdates[$stakeholder['contact_id']] = [
            'x' => $stakeholder['position_x'],
            'y' => $stakeholder['position_y']
          ];
        }
      }

      if (!empty($positionUpdates)) {
        $positionResults = self::bulkUpdatePositions($targetPowermapId, $positionUpdates);

        // Merge position results
        foreach ($positionResults as $contactId => $result) {
          if (isset($results[$contactId]) && $results[$contactId]['success']) {
            $results[$contactId]['position_updated'] = $result['success'];
          }
        }
      }
    }

    return $results;
  }

  /**
   * Get stakeholder engagement history
   *
   * @param int $contactId
   * @param int $powermapId
   * @return array
   */
  public static function getEngagementHistory($contactId, $powermapId = NULL) {
    $whereConditions = ['ps.contact_id = %1'];
    $params = [1 => [$contactId, 'Integer']];
    $paramIndex = 2;

    if ($powermapId) {
      $whereConditions[] = "ps.powermap_id = %{$paramIndex}";
      $params[$paramIndex] = [$powermapId, 'Integer'];
      $paramIndex++;
    }

    $sql = "
      SELECT
        ps.*,
        pm.name as powermap_name,
        added_by_contact.display_name as added_by_name
      FROM civicrm_powermap_stakeholder ps
      JOIN civicrm_powermap_config pm ON pm.id = ps.powermap_id
      LEFT JOIN civicrm_contact added_by_contact ON added_by_contact.id = ps.added_by
      WHERE " . implode(' AND ', $whereConditions) . "
      ORDER BY ps.added_date DESC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $history = [];
    while ($dao->fetch()) {
      $history[] = [
        'powermap_id' => $dao->powermap_id,
        'powermap_name' => $dao->powermap_name,
        'added_date' => $dao->added_date,
        'added_by' => $dao->added_by,
        'added_by_name' => $dao->added_by_name,
        'is_active' => $dao->is_active,
        'position_x' => $dao->position_x,
        'position_y' => $dao->position_y,
        'notes' => $dao->notes
      ];
    }

    return $history;
  }

  /**
   * Helper methods for assessment scores (delegated to existing BAO)
   */
  public static function getInfluenceScore($contactId) {
    return CRM_Powermap_BAO_Stakeholder::getInfluenceScore($contactId);
  }

  public static function getSupportScore($contactId) {
    return CRM_Powermap_BAO_Stakeholder::getSupportScore($contactId);
  }

  public static function getStrategicQuadrant($contactId) {
    return CRM_Powermap_BAO_Stakeholder::getStrategicQuadrant($contactId);
  }

  public static function getEngagementStrategy($contactId) {
    return CRM_Powermap_BAO_Stakeholder::getEngagementStrategy($contactId);
  }

  /**
   * Get custom field information
   */
  private static function getCustomFieldInfo() {
    static $cache = NULL;

    if ($cache !== NULL) {
      return $cache;
    }

    try {
      $group = civicrm_api3('CustomGroup', 'getsingle', [
        'name' => 'power_mapping_data',
      ]);

      $fields = civicrm_api3('CustomField', 'get', [
        'custom_group_id' => $group['id'],
        'options' => ['limit' => 0],
      ]);

      $cache = [
        'table_name' => $group['table_name'],
        'group_id' => $group['id']
      ];

      foreach ($fields['values'] as $field) {
        $cache[$field['name'] . '_column'] = $field['column_name'];
        $cache[$field['name'] . '_id'] = $field['id'];
      }

    }
    catch (Exception $e) {
      $cache = [];
    }

    return $cache;
  }
}
