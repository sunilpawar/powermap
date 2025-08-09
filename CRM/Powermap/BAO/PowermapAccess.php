<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * Business Access Object for PowermapAccess entity
 */
class CRM_Powermap_BAO_PowermapAccess extends CRM_Powermap_DAO_PowermapAccess {

  /**
   * Create a new PowermapAccess based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Powermap_DAO_PowermapAccess|NULL
   */
  public static function create($params) {
    $className = 'CRM_Powermap_DAO_PowermapAccess';
    $entityName = 'PowermapAccess';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, $params['id'] ?? NULL, $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Get the list of permission level options
   *
   * @return array
   */
  public static function getPermissionLevelOptions() {
    return [
      'view' => E::ts('View'),
      'edit' => E::ts('Edit'),
      'admin' => E::ts('Admin'),
    ];
  }

  /**
   * Grant access to a power map
   *
   * @param int $powermapId
   * @param array $accessParams
   * @return bool
   */
  public static function grantAccess($powermapId, $accessParams) {
    // Validate that either contact_id or group_id is provided
    if (empty($accessParams['contact_id']) && empty($accessParams['group_id'])) {
      throw new CRM_Core_Exception('Either contact_id or group_id must be provided');
    }

    // Check if user has permission to grant access
    $currentUserPermission = CRM_Powermap_BAO_PowermapConfig::checkAccess($powermapId);
    if (!in_array($currentUserPermission, ['owner', 'admin'])) {
      throw new CRM_Core_Exception('Insufficient permissions to grant access');
    }

    $params = [
      'powermap_id' => $powermapId,
      'permission_level' => $accessParams['permission_level'] ?? 'view',
      'granted_date' => date('Y-m-d H:i:s'),
      'granted_by' => CRM_Core_Session::getLoggedInContactID(),
      'is_active' => 1
    ];

    if (!empty($accessParams['contact_id'])) {
      $params['contact_id'] = $accessParams['contact_id'];
    }

    if (!empty($accessParams['group_id'])) {
      $params['group_id'] = $accessParams['group_id'];
    }

    return self::create($params);
  }

  /**
   * Revoke access to a power map
   *
   * @param int $powermapId
   * @param int $contactId
   * @param int $groupId
   * @return bool
   */
  public static function revokeAccess($powermapId, $contactId = NULL, $groupId = NULL) {
    // Check permissions
    $currentUserPermission = CRM_Powermap_BAO_PowermapConfig::checkAccess($powermapId);
    if (!in_array($currentUserPermission, ['owner', 'admin'])) {
      throw new CRM_Core_Exception('Insufficient permissions to revoke access');
    }

    $whereConditions = ['powermap_id = %1'];
    $params = [1 => [$powermapId, 'Integer']];
    $paramIndex = 2;

    if ($contactId) {
      $whereConditions[] = "contact_id = %{$paramIndex}";
      $params[$paramIndex] = [$contactId, 'Integer'];
      $paramIndex++;
    }

    if ($groupId) {
      $whereConditions[] = "group_id = %{$paramIndex}";
      $params[$paramIndex] = [$groupId, 'Integer'];
      $paramIndex++;
    }

    $sql = "
      UPDATE civicrm_powermap_access
      SET is_active = 0
      WHERE " . implode(' AND ', $whereConditions);

    return CRM_Core_DAO::executeQuery($sql, $params);
  }

  /**
   * Get access list for a power map
   *
   * @param int $powermapId
   * @return array
   */
  public static function getAccessList($powermapId) {
    $sql = "
      SELECT
        pma.*,
        c.display_name as contact_name,
        g.title as group_title,
        gb.display_name as granted_by_name
      FROM civicrm_powermap_access pma
      LEFT JOIN civicrm_contact c ON c.id = pma.contact_id
      LEFT JOIN civicrm_group g ON g.id = pma.group_id
      LEFT JOIN civicrm_contact gb ON gb.id = pma.granted_by
      WHERE pma.powermap_id = %1 AND pma.is_active = 1
      ORDER BY pma.granted_date DESC
    ";

    $params = [1 => [$powermapId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $accessList = [];
    while ($dao->fetch()) {
      $accessList[] = [
        'id' => $dao->id,
        'powermap_id' => $dao->powermap_id,
        'contact_id' => $dao->contact_id,
        'contact_name' => $dao->contact_name,
        'group_id' => $dao->group_id,
        'group_title' => $dao->group_title,
        'permission_level' => $dao->permission_level,
        'granted_date' => $dao->granted_date,
        'granted_by' => $dao->granted_by,
        'granted_by_name' => $dao->granted_by_name,
        'type' => $dao->contact_id ? 'individual' : 'group'
      ];
    }

    return $accessList;
  }

  /**
   * Check if contact has specific access to power map
   *
   * @param int $powermapId
   * @param int $contactId
   * @return string|null Permission level or NULL if no access
   */
  public static function getContactAccess($powermapId, $contactId) {
    $sql = "
      SELECT MAX(
        CASE pma.permission_level
          WHEN 'admin' THEN 4
          WHEN 'edit' THEN 3
          WHEN 'view' THEN 2
          ELSE 1
        END
      ) as max_permission_level
      FROM civicrm_powermap_access pma
      LEFT JOIN civicrm_group_contact gc ON gc.group_id = pma.group_id AND gc.status = 'Added'
      WHERE pma.powermap_id = %1
        AND pma.is_active = 1
        AND (
          pma.contact_id = %2 OR
          gc.contact_id = %2
        )
    ";

    $params = [
      1 => [$powermapId, 'Integer'],
      2 => [$contactId, 'Integer']
    ];

    $maxLevel = CRM_Core_DAO::singleValueQuery($sql, $params);

    switch ($maxLevel) {
      case 4:
        return 'admin';
      case 3:
        return 'edit';
      case 2:
        return 'view';
      default:
        return NULL;
    }
  }

  /**
   * Update access permissions
   *
   * @param int $accessId
   * @param array $params
   * @return bool
   */
  public static function updateAccess($accessId, $params) {
    // Get the access record to check powermap_id
    $access = new self();
    $access->id = $accessId;
    if (!$access->find(TRUE)) {
      throw new CRM_Core_Exception('Access record not found');
    }

    // Check permissions
    $currentUserPermission = CRM_Powermap_BAO_PowermapConfig::checkAccess($access->powermap_id);
    if (!in_array($currentUserPermission, ['owner', 'admin'])) {
      throw new CRM_Core_Exception('Insufficient permissions to update access');
    }

    $params['id'] = $accessId;
    return self::create($params);
  }

  /**
   * Bulk grant access to multiple contacts/groups
   *
   * @param int $powermapId
   * @param array $grantData Array of access grants
   * @return array Results
   */
  public static function bulkGrantAccess($powermapId, $grantData) {
    // Check permissions
    $currentUserPermission = CRM_Powermap_BAO_PowermapConfig::checkAccess($powermapId);
    if (!in_array($currentUserPermission, ['owner', 'admin'])) {
      throw new CRM_Core_Exception('Insufficient permissions to grant access');
    }

    $results = [];
    $grantedBy = CRM_Core_Session::getLoggedInContactID();
    $grantedDate = date('Y-m-d H:i:s');

    foreach ($grantData as $grant) {
      try {
        $params = [
          'powermap_id' => $powermapId,
          'permission_level' => $grant['permission_level'] ?? 'view',
          'granted_date' => $grantedDate,
          'granted_by' => $grantedBy,
          'is_active' => 1
        ];

        if (!empty($grant['contact_id'])) {
          $params['contact_id'] = $grant['contact_id'];
        }

        if (!empty($grant['group_id'])) {
          $params['group_id'] = $grant['group_id'];
        }

        $access = self::create($params);
        $results[] = ['success' => TRUE, 'id' => $access->id];

      }
      catch (Exception $e) {
        $results[] = ['success' => FALSE, 'error' => $e->getMessage()];
      }
    }

    return $results;
  }

  /**
   * Get contacts with access to power map
   *
   * @param int $powermapId
   * @return array
   */
  public static function getContactsWithAccess($powermapId) {
    $sql = "
      SELECT DISTINCT
        c.id,
        c.display_name,
        c.email,
        pma.permission_level,
        pma.granted_date,
        'direct' as access_type
      FROM civicrm_powermap_access pma
      JOIN civicrm_contact c ON c.id = pma.contact_id
      WHERE pma.powermap_id = %1
        AND pma.is_active = 1
        AND c.is_deleted = 0

      UNION

      SELECT DISTINCT
        c.id,
        c.display_name,
        c.email,
        pma.permission_level,
        pma.granted_date,
        'group' as access_type
      FROM civicrm_powermap_access pma
      JOIN civicrm_group_contact gc ON gc.group_id = pma.group_id
      JOIN civicrm_contact c ON c.id = gc.contact_id
      WHERE pma.powermap_id = %1
        AND pma.is_active = 1
        AND gc.status = 'Added'
        AND c.is_deleted = 0

      ORDER BY display_name
    ";

    $params = [1 => [$powermapId, 'Integer']];
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $contacts = [];
    while ($dao->fetch()) {
      $contacts[] = [
        'id' => $dao->id,
        'display_name' => $dao->display_name,
        'email' => $dao->email,
        'permission_level' => $dao->permission_level,
        'granted_date' => $dao->granted_date,
        'access_type' => $dao->access_type
      ];
    }

    return $contacts;
  }
}
