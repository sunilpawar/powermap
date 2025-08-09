<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * Business Access Object for PowermapAuditLog entity
 */
class CRM_Powermap_BAO_PowermapAuditLog extends CRM_Powermap_DAO_PowermapAuditLog {

  /**
   * Create a new PowermapAuditLog based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Powermap_DAO_PowermapAuditLog|NULL
   */
  public static function create($params) {
    $className = 'CRM_Powermap_DAO_PowermapAuditLog';
    $entityName = 'PowermapAuditLog';
    $hook = empty($params['id']) ? 'create' : 'edit';

    // Don't trigger hooks for audit log to avoid infinite loops
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();

    return $instance;
  }

  /**
   * Get the list of action options
   *
   * @return array
   */
  public static function getActionOptions() {
    return [
      'create' => E::ts('Create'),
      'update' => E::ts('Update'),
      'delete' => E::ts('Delete'),
      'view' => E::ts('View'),
      'export' => E::ts('Export'),
      'import' => E::ts('Import'),
    ];
  }

  /**
   * Log an action
   *
   * @param string $entityTable
   * @param int $entityId
   * @param string $action
   * @param array $oldValues
   * @param array $newValues
   * @param int $contactId
   * @return void
   */
  public static function logAction($entityTable, $entityId, $action, $oldValues = NULL, $newValues = NULL, $contactId = NULL) {
    // Check if audit logging is enabled
    if (!Civi::settings()->get('powermap_enable_audit_log')) {
      return;
    }

    if (!$contactId) {
      $contactId = CRM_Core_Session::getLoggedInContactID();
    }

    $params = [
      'entity_table' => $entityTable,
      'entity_id' => $entityId,
      'contact_id' => $contactId,
      'action' => $action,
      'old_values' => $oldValues ? json_encode($oldValues) : NULL,
      'new_values' => $newValues ? json_encode($newValues) : NULL,
      'log_date' => date('Y-m-d H:i:s'),
      'ip_address' => $_SERVER['REMOTE_ADDR'] ?? NULL,
      'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? NULL,
    ];

    try {
      self::create($params);
    }
    catch (Exception $e) {
      // Don't let audit logging failures break the main operation
      CRM_Core_Error::debug_log_message('Audit logging failed: ' . $e->getMessage());
    }
  }

  /**
   * Get audit log entries for an entity
   *
   * @param string $entityTable
   * @param int $entityId
   * @param int $limit
   * @return array
   */
  public static function getEntityAuditLog($entityTable, $entityId, $limit = 50) {
    $sql = "
      SELECT
        al.*,
        c.display_name as contact_name
      FROM civicrm_powermap_audit_log al
      LEFT JOIN civicrm_contact c ON c.id = al.contact_id
      WHERE al.entity_table = %1 AND al.entity_id = %2
      ORDER BY al.log_date DESC
      LIMIT %3
    ";

    $params = [
      1 => [$entityTable, 'String'],
      2 => [$entityId, 'Integer'],
      3 => [$limit, 'Integer']
    ];

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $auditLog = [];
    while ($dao->fetch()) {
      $auditLog[] = [
        'id' => $dao->id,
        'entity_table' => $dao->entity_table,
        'entity_id' => $dao->entity_id,
        'contact_id' => $dao->contact_id,
        'contact_name' => $dao->contact_name,
        'action' => $dao->action,
        'old_values' => json_decode($dao->old_values, TRUE),
        'new_values' => json_decode($dao->new_values, TRUE),
        'log_date' => $dao->log_date,
        'ip_address' => $dao->ip_address,
        'user_agent' => $dao->user_agent,
      ];
    }

    return $auditLog;
  }

  /**
   * Get recent activity across all power mapping entities
   *
   * @param int $limit
   * @param array $filters
   * @return array
   */
  public static function getRecentActivity($limit = 25, $filters = []) {
    $whereConditions = ['1 = 1'];
    $params = [];
    $paramIndex = 1;

    // Apply filters
    if (!empty($filters['contact_id'])) {
      $whereConditions[] = "al.contact_id = %{$paramIndex}";
      $params[$paramIndex] = [$filters['contact_id'], 'Integer'];
      $paramIndex++;
    }

    if (!empty($filters['entity_table'])) {
      $whereConditions[] = "al.entity_table = %{$paramIndex}";
      $params[$paramIndex] = [$filters['entity_table'], 'String'];
      $paramIndex++;
    }

    if (!empty($filters['action'])) {
      $whereConditions[] = "al.action = %{$paramIndex}";
      $params[$paramIndex] = [$filters['action'], 'String'];
      $paramIndex++;
    }

    if (!empty($filters['date_from'])) {
      $whereConditions[] = "al.log_date >= %{$paramIndex}";
      $params[$paramIndex] = [$filters['date_from'], 'String'];
      $paramIndex++;
    }

    if (!empty($filters['date_to'])) {
      $whereConditions[] = "al.log_date <= %{$paramIndex}";
      $params[$paramIndex] = [$filters['date_to'], 'String'];
      $paramIndex++;
    }

    $params[$paramIndex] = [$limit, 'Integer'];

    $sql = "
      SELECT
        al.*,
        c.display_name as contact_name,
        CASE
          WHEN al.entity_table = 'civicrm_powermap_config' THEN pm.name
          WHEN al.entity_table = 'civicrm_powermap_stakeholder' THEN cont.display_name
          ELSE CONCAT(al.entity_table, ' #', al.entity_id)
        END as entity_name
      FROM civicrm_powermap_audit_log al
      LEFT JOIN civicrm_contact c ON c.id = al.contact_id
      LEFT JOIN civicrm_powermap_config pm ON pm.id = al.entity_id AND al.entity_table = 'civicrm_powermap_config'
      LEFT JOIN civicrm_powermap_stakeholder ps ON ps.id = al.entity_id AND al.entity_table = 'civicrm_powermap_stakeholder'
      LEFT JOIN civicrm_contact cont ON cont.id = ps.contact_id
      WHERE " . implode(' AND ', $whereConditions) . "
      ORDER BY al.log_date DESC
      LIMIT %{$paramIndex}
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $activities = [];
    while ($dao->fetch()) {
      $activities[] = [
        'id' => $dao->id,
        'entity_table' => $dao->entity_table,
        'entity_id' => $dao->entity_id,
        'entity_name' => $dao->entity_name,
        'contact_id' => $dao->contact_id,
        'contact_name' => $dao->contact_name,
        'action' => $dao->action,
        'old_values' => json_decode($dao->old_values, TRUE),
        'new_values' => json_decode($dao->new_values, TRUE),
        'log_date' => $dao->log_date,
        'ip_address' => $dao->ip_address,
      ];
    }

    return $activities;
  }

  /**
   * Clean up old audit log entries
   *
   * @param int $retentionDays
   * @return int Number of records deleted
   */
  public static function cleanupOldEntries($retentionDays = NULL) {
    if (!$retentionDays) {
      $retentionDays = Civi::settings()->get('powermap_assessment_retention_days') ?: 365;
    }

    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));

    $sql = "DELETE FROM civicrm_powermap_audit_log WHERE log_date < %1";
    $params = [1 => [$cutoffDate, 'String']];

    $result = CRM_Core_DAO::executeQuery($sql, $params);
    return $result->affectedRows();
  }

  /**
   * Get audit statistics
   *
   * @param array $filters
   * @return array
   */
  public static function getAuditStatistics($filters = []) {
    $whereConditions = ['1 = 1'];
    $params = [];
    $paramIndex = 1;

    // Apply date filters
    if (!empty($filters['date_from'])) {
      $whereConditions[] = "log_date >= %{$paramIndex}";
      $params[$paramIndex] = [$filters['date_from'], 'String'];
      $paramIndex++;
    }

    if (!empty($filters['date_to'])) {
      $whereConditions[] = "log_date <= %{$paramIndex}";
      $params[$paramIndex] = [$filters['date_to'], 'String'];
      $paramIndex++;
    }

    $sql = "
      SELECT
        COUNT(*) as total_entries,
        COUNT(DISTINCT contact_id) as unique_users,
        COUNT(DISTINCT entity_table) as unique_entity_types,
        SUM(CASE WHEN action = 'create' THEN 1 ELSE 0 END) as creates,
        SUM(CASE WHEN action = 'update' THEN 1 ELSE 0 END) as updates,
        SUM(CASE WHEN action = 'delete' THEN 1 ELSE 0 END) as deletes,
        SUM(CASE WHEN action = 'view' THEN 1 ELSE 0 END) as views,
        MIN(log_date) as earliest_entry,
        MAX(log_date) as latest_entry
      FROM civicrm_powermap_audit_log
      WHERE " . implode(' AND ', $whereConditions);

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if ($dao->fetch()) {
      return [
        'total_entries' => $dao->total_entries,
        'unique_users' => $dao->unique_users,
        'unique_entity_types' => $dao->unique_entity_types,
        'creates' => $dao->creates,
        'updates' => $dao->updates,
        'deletes' => $dao->deletes,
        'views' => $dao->views,
        'earliest_entry' => $dao->earliest_entry,
        'latest_entry' => $dao->latest_entry,
      ];
    }

    return [];
  }
}
