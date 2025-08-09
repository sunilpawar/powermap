<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * Business Access Object for PowermapConfig entity
 */
class CRM_Powermap_BAO_PowermapConfig extends CRM_Powermap_DAO_PowermapConfig {

  /**
   * Create a new PowermapConfig based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Powermap_DAO_PowermapConfig|NULL
   */
  public static function create($params) {
    $className = 'CRM_Powermap_DAO_PowermapConfig';
    $entityName = 'PowermapConfig';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, $params['id'] ?? NULL, $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Get the list of visibility options
   *
   * @return array
   */
  public static function getVisibilityOptions() {
    return [
      'public' => E::ts('Public'),
      'private' => E::ts('Private'),
      'group' => E::ts('Group Access'),
    ];
  }

  /**
   * Check if current user can access this power map
   *
   * @param int $powermapId
   * @param int $contactId
   * @return string Permission level (owner, admin, edit, view, none)
   */
  public static function checkAccess($powermapId, $contactId = NULL) {
    if (!$contactId) {
      $contactId = CRM_Core_Session::getLoggedInContactID();
    }

    // Check if user is owner
    $createdBy = CRM_Core_DAO::singleValueQuery(
      "SELECT created_id FROM civicrm_powermap_config WHERE id = %1",
      [1 => [$powermapId, 'Integer']]
    );

    if ($createdBy == $contactId) {
      return 'owner';
    }

    // Check visibility and explicit permissions
    $sql = "
      SELECT
        pm.visibility,
        MAX(CASE pma.permission_level
          WHEN 'admin' THEN 4
          WHEN 'edit' THEN 3
          WHEN 'view' THEN 2
          ELSE 1
        END) as max_permission_level
      FROM civicrm_powermap_config pm
      LEFT JOIN civicrm_powermap_access pma ON pma.powermap_id = pm.id AND pma.is_active = 1
      LEFT JOIN civicrm_group_contact gc ON gc.group_id = pma.group_id AND gc.status = 'Added'
      WHERE pm.id = %1
        AND (
          pma.contact_id = %2 OR
          gc.contact_id = %2 OR
          pma.contact_id IS NULL
        )
      GROUP BY pm.id, pm.visibility
    ";

    $params = [
      1 => [$powermapId, 'Integer'],
      2 => [$contactId, 'Integer']
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if ($dao->fetch()) {
      // Convert numeric permission level back to string
      switch ($dao->max_permission_level) {
        case 4:
          return 'admin';
        case 3:
          return 'edit';
        case 2:
          return 'view';
        default:
          // Check if map is public
          return ($dao->visibility === 'public') ? 'view' : 'none';
      }
    }

    return 'none';
  }

  /**
   * Get power maps accessible by user
   *
   * @param int $contactId
   * @param array $filters
   * @return array
   */
  public static function getAccessibleMaps($contactId = NULL, $filters = []) {
    if (!$contactId) {
      $contactId = CRM_Core_Session::getLoggedInContactID();
    }

    $whereConditions = ['pm.is_active = 1'];
    $params = [1 => [$contactId, 'Integer']];
    $paramIndex = 2;

    // Apply filters
    if (!empty($filters['campaign_id'])) {
      $whereConditions[] = "pm.campaign_id = %{$paramIndex}";
      $params[$paramIndex] = [$filters['campaign_id'], 'Integer'];
      $paramIndex++;
    }

    if (!empty($filters['name'])) {
      $whereConditions[] = "pm.name LIKE %{$paramIndex}";
      $params[$paramIndex] = ['%' . $filters['name'] . '%', 'String'];
      $paramIndex++;
    }

    $sql = "
      SELECT DISTINCT
        pm.*,
        c.display_name as created_by_name,
        camp.title as campaign_title,
        COUNT(ps.id) as stakeholder_count
      FROM civicrm_powermap_config pm
      LEFT JOIN civicrm_contact c ON c.id = pm.created_id
      LEFT JOIN civicrm_campaign camp ON camp.id = pm.campaign_id
      LEFT JOIN civicrm_powermap_access pma ON pma.powermap_id = pm.id AND pma.is_active = 1
      LEFT JOIN civicrm_group_contact gc ON gc.group_id = pma.group_id AND gc.status = 'Added'
      LEFT JOIN civicrm_powermap_stakeholder ps ON ps.powermap_id = pm.id AND ps.is_active = 1
      WHERE " . implode(' AND ', $whereConditions) . "
        AND (
          pm.visibility = 'public' OR
          pm.created_id = %1 OR
          pma.contact_id = %1 OR
          gc.contact_id = %1
        )
      GROUP BY pm.id
      ORDER BY pm.modified_date DESC, pm.created_date DESC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $maps = [];
    while ($dao->fetch()) {
      $maps[] = [
        'id' => $dao->id,
        'name' => $dao->name,
        'description' => $dao->description,
        'campaign_id' => $dao->campaign_id,
        'campaign_title' => $dao->campaign_title,
        'created_id' => $dao->created_id,
        'created_by_name' => $dao->created_by_name,
        'created_date' => $dao->created_date,
        'modified_id' => $dao->modified_id,
        'modified_date' => $dao->modified_date,
        'is_active' => $dao->is_active,
        'visibility' => $dao->visibility,
        'settings' => json_decode($dao->settings, TRUE),
        'stakeholder_count' => $dao->stakeholder_count,
        'access_level' => self::checkAccess($dao->id, $contactId)
      ];
    }

    return $maps;
  }

  /**
   * Delete power map and all related data
   *
   * @param int $powermapId
   * @return bool
   */
  public static function deleteWithRelatedData($powermapId) {
    // Check permissions
    $permission = self::checkAccess($powermapId);
    if (!in_array($permission, ['owner', 'admin'])) {
      throw new CRM_Core_Exception('Insufficient permissions to delete power map');
    }

    $transaction = new CRM_Core_Transaction();

    try {
      // Delete in correct order (respecting foreign keys)
      $tables = [
        'civicrm_powermap_access',
        'civicrm_powermap_stakeholder',
        'civicrm_powermap_assessment_history',
        'civicrm_powermap_config'
      ];

      foreach ($tables as $table) {
        $whereField = ($table === 'civicrm_powermap_assessment_history') ? 'powermap_id' :
          (($table === 'civicrm_powermap_config') ? 'id' : 'powermap_id');

        CRM_Core_DAO::executeQuery("
          DELETE FROM {$table} WHERE {$whereField} = %1
        ", [1 => [$powermapId, 'Integer']]);
      }

      $transaction->commit();
      return TRUE;

    }
    catch (Exception $e) {
      $transaction->rollback();
      throw $e;
    }
  }

  /**
   * Get power map statistics
   *
   * @param int $powermapId
   * @return array
   */
  public static function getStatistics($powermapId) {
    // Get stakeholder counts by quadrant
    $customFields = CRM_Powermap_Utils_Assessment::getCustomFieldInfo();
    if (empty($customFields)) {
      return [];
    }

    $sql = "
      SELECT
        COUNT(*) as total_stakeholders,
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
          )
          THEN 1 ELSE 0 END) as monitor,
        MAX(ps.added_date) as last_updated
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
        'total_stakeholders' => $dao->total_stakeholders,
        'champions' => $dao->champions,
        'targets' => $dao->targets,
        'grassroots' => $dao->grassroots,
        'monitor' => $dao->monitor,
        'last_updated' => $dao->last_updated,
        'assessment_coverage' => $dao->total_stakeholders > 0 ?
          round((($dao->champions + $dao->targets + $dao->grassroots + $dao->monitor) / $dao->total_stakeholders) * 100, 1) : 0
      ];
    }

    return [];
  }

  /**
   * Duplicate a power map
   *
   * @param int $powermapId
   * @param array $newParams
   * @return int New power map ID
   */
  public static function duplicate($powermapId, $newParams = []) {
    // Check permissions
    $permission = self::checkAccess($powermapId);
    if (!in_array($permission, ['owner', 'admin', 'edit'])) {
      throw new CRM_Core_Exception('Insufficient permissions to duplicate power map');
    }

    // Get original power map
    $original = new self();
    $original->id = $powermapId;
    if (!$original->find(TRUE)) {
      throw new CRM_Core_Exception('Power map not found');
    }

    $transaction = new CRM_Core_Transaction();

    try {
      // Create new power map
      $newMap = new self();
      $newMap->name = $newParams['name'] ?? ($original->name . ' (Copy)');
      $newMap->description = $newParams['description'] ?? $original->description;
      $newMap->campaign_id = $newParams['campaign_id'] ?? $original->campaign_id;
      $newMap->created_id = CRM_Core_Session::getLoggedInContactID();
      $newMap->created_date = date('Y-m-d H:i:s');
      $newMap->is_active = $newParams['is_active'] ?? 1;
      $newMap->visibility = $newParams['visibility'] ?? $original->visibility;
      $newMap->settings = $original->settings;
      $newMap->save();

      // Copy stakeholders if requested
      if ($newParams['copy_stakeholders'] ?? TRUE) {
        $sql = "
          INSERT INTO civicrm_powermap_stakeholder
          (powermap_id, contact_id, added_date, added_by, is_active, position_x, position_y, notes)
          SELECT %1, contact_id, %2, %3, is_active, position_x, position_y, notes
          FROM civicrm_powermap_stakeholder
          WHERE powermap_id = %4 AND is_active = 1
        ";

        $params = [
          1 => [$newMap->id, 'Integer'],
          2 => [date('Y-m-d H:i:s'), 'String'],
          3 => [CRM_Core_Session::getLoggedInContactID(), 'Integer'],
          4 => [$powermapId, 'Integer']
        ];

        CRM_Core_DAO::executeQuery($sql, $params);
      }

      $transaction->commit();
      return $newMap->id;

    }
    catch (Exception $e) {
      $transaction->rollback();
      throw $e;
    }
  }
}
