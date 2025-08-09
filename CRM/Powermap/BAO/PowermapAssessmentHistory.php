<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * Business Access Object for PowermapAssessmentHistory entity
 */
class CRM_Powermap_BAO_PowermapAssessmentHistory extends CRM_Powermap_DAO_PowermapAssessmentHistory {

  /**
   * Create a new PowermapAssessmentHistory based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Powermap_DAO_PowermapAssessmentHistory|NULL
   */
  public static function create($params) {
    $className = 'CRM_Powermap_DAO_PowermapAssessmentHistory';
    $entityName = 'PowermapAssessmentHistory';
    $hook = empty($params['id']) ? 'create' : 'edit';

    CRM_Utils_Hook::pre($hook, $entityName, $params['id'] ?? NULL, $params);
    $instance = new $className();
    $instance->copyValues($params);
    $instance->save();
    CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);

    return $instance;
  }

  /**
   * Get the list of influence level options
   *
   * @return array
   */
  public static function getInfluenceLevelOptions() {
    return [
      'low' => E::ts('Low'),
      'medium' => E::ts('Medium'),
      'high' => E::ts('High'),
    ];
  }

  /**
   * Get the list of support level options
   *
   * @return array
   */
  public static function getSupportLevelOptions() {
    return [
      'strong_opposition' => E::ts('Strong Opposition'),
      'opposition' => E::ts('Opposition'),
      'neutral' => E::ts('Neutral'),
      'support' => E::ts('Support'),
      'strong_support' => E::ts('Strong Support'),
    ];
  }

  /**
   * Get the list of engagement priority options
   *
   * @return array
   */
  public static function getEngagementPriorityOptions() {
    return [
      'low' => E::ts('Low Priority'),
      'medium' => E::ts('Medium Priority'),
      'high' => E::ts('High Priority'),
      'monitor' => E::ts('Monitor Only'),
    ];
  }

  /**
   * Record an assessment change
   *
   * @param int $contactId
   * @param int $powermapId
   * @param array $assessmentData
   * @param string $changeReason
   * @return CRM_Powermap_DAO_PowermapAssessmentHistory
   */
  public static function recordAssessment($contactId, $powermapId, $assessmentData, $changeReason = '') {
    $params = [
      'contact_id' => $contactId,
      'powermap_id' => $powermapId,
      'influence_level' => $assessmentData['influence_level'] ?? NULL,
      'support_level' => $assessmentData['support_level'] ?? NULL,
      'engagement_priority' => $assessmentData['engagement_priority'] ?? NULL,
      'stakeholder_type' => $assessmentData['stakeholder_type'] ?? NULL,
      'assessment_notes' => $assessmentData['assessment_notes'] ?? NULL,
      'assessed_date' => date('Y-m-d H:i:s'),
      'assessed_by' => CRM_Core_Session::getLoggedInContactID(),
      'change_reason' => $changeReason,
    ];

    return self::create($params);
  }

  /**
   * Get assessment history for a contact
   *
   * @param int $contactId
   * @param int $limit
   * @param int $powermapId Optional filter by power map
   * @return array
   */
  public static function getContactHistory($contactId, $limit = 10, $powermapId = NULL) {
    $whereConditions = ['ah.contact_id = %1'];
    $params = [1 => [$contactId, 'Integer']];
    $paramIndex = 2;

    if ($powermapId) {
      $whereConditions[] = "ah.powermap_id = %{$paramIndex}";
      $params[$paramIndex] = [$powermapId, 'Integer'];
      $paramIndex++;
    }

    $params[$paramIndex] = [$limit, 'Integer'];

    $sql = "
      SELECT
        ah.*,
        c.display_name as contact_name,
        assessor.display_name as assessed_by_name,
        pm.name as powermap_name
      FROM civicrm_powermap_assessment_history ah
      JOIN civicrm_contact c ON c.id = ah.contact_id
      LEFT JOIN civicrm_contact assessor ON assessor.id = ah.assessed_by
      LEFT JOIN civicrm_powermap_config pm ON pm.id = ah.powermap_id
      WHERE " . implode(' AND ', $whereConditions) . "
      ORDER BY ah.assessed_date DESC
      LIMIT %{$paramIndex}
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $history = [];
    while ($dao->fetch()) {
      $history[] = [
        'id' => $dao->id,
        'contact_id' => $dao->contact_id,
        'contact_name' => $dao->contact_name,
        'powermap_id' => $dao->powermap_id,
        'powermap_name' => $dao->powermap_name,
        'influence_level' => $dao->influence_level,
        'support_level' => $dao->support_level,
        'engagement_priority' => $dao->engagement_priority,
        'stakeholder_type' => $dao->stakeholder_type,
        'assessment_notes' => $dao->assessment_notes,
        'assessed_date' => $dao->assessed_date,
        'assessed_by' => $dao->assessed_by,
        'assessed_by_name' => $dao->assessed_by_name,
        'change_reason' => $dao->change_reason,
      ];
    }

    return $history;
  }

  /**
   * Compare two assessments and get the differences
   *
   * @param array $oldAssessment
   * @param array $newAssessment
   * @return array
   */
  public static function compareAssessments($oldAssessment, $newAssessment) {
    $changes = [];
    $fields = ['influence_level', 'support_level', 'engagement_priority', 'stakeholder_type'];

    foreach ($fields as $field) {
      $oldValue = $oldAssessment[$field] ?? NULL;
      $newValue = $newAssessment[$field] ?? NULL;

      if ($oldValue != $newValue) {
        $changes[$field] = [
          'old' => $oldValue,
          'new' => $newValue,
          'label' => self::getFieldLabel($field)
        ];
      }
    }

    return $changes;
  }

  /**
   * Get field label for display
   *
   * @param string $field
   * @return string
   */
  private static function getFieldLabel($field) {
    $labels = [
      'influence_level' => E::ts('Influence Level'),
      'support_level' => E::ts('Support Level'),
      'engagement_priority' => E::ts('Engagement Priority'),
      'stakeholder_type' => E::ts('Stakeholder Type'),
    ];

    return $labels[$field] ?? $field;
  }

  /**
   * Get assessment trends for a contact
   *
   * @param int $contactId
   * @param int $powermapId
   * @return array
   */
  public static function getAssessmentTrends($contactId, $powermapId = NULL) {
    $whereConditions = ['contact_id = %1'];
    $params = [1 => [$contactId, 'Integer']];
    $paramIndex = 2;

    if ($powermapId) {
      $whereConditions[] = "powermap_id = %{$paramIndex}";
      $params[$paramIndex] = [$powermapId, 'Integer'];
      $paramIndex++;
    }

    $sql = "
      SELECT
        assessed_date,
        influence_level,
        support_level,
        engagement_priority,
        CASE influence_level
          WHEN 'high' THEN 3
          WHEN 'medium' THEN 2
          WHEN 'low' THEN 1
          ELSE 0
        END as influence_score,
        CASE support_level
          WHEN 'strong_support' THEN 2
          WHEN 'support' THEN 1
          WHEN 'neutral' THEN 0
          WHEN 'opposition' THEN -1
          WHEN 'strong_opposition' THEN -2
          ELSE 0
        END as support_score
      FROM civicrm_powermap_assessment_history
      WHERE " . implode(' AND ', $whereConditions) . "
      ORDER BY assessed_date ASC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $trends = [];
    while ($dao->fetch()) {
      $trends[] = [
        'date' => $dao->assessed_date,
        'influence_level' => $dao->influence_level,
        'support_level' => $dao->support_level,
        'engagement_priority' => $dao->engagement_priority,
        'influence_score' => $dao->influence_score,
        'support_score' => $dao->support_score,
      ];
    }

    return $trends;
  }

  /**
   * Get assessment statistics for a power map
   *
   * @param int $powermapId
   * @param array $dateRange
   * @return array
   */
  public static function getAssessmentStatistics($powermapId = NULL, $dateRange = []) {
    $whereConditions = ['1 = 1'];
    $params = [];
    $paramIndex = 1;

    if ($powermapId) {
      $whereConditions[] = "powermap_id = %{$paramIndex}";
      $params[$paramIndex] = [$powermapId, 'Integer'];
      $paramIndex++;
    }

    if (!empty($dateRange['from'])) {
      $whereConditions[] = "assessed_date >= %{$paramIndex}";
      $params[$paramIndex] = [$dateRange['from'], 'String'];
      $paramIndex++;
    }

    if (!empty($dateRange['to'])) {
      $whereConditions[] = "assessed_date <= %{$paramIndex}";
      $params[$paramIndex] = [$dateRange['to'], 'String'];
      $paramIndex++;
    }

    $sql = "
      SELECT
        COUNT(*) as total_assessments,
        COUNT(DISTINCT contact_id) as unique_contacts,
        COUNT(DISTINCT assessed_by) as unique_assessors,
        MIN(assessed_date) as earliest_assessment,
        MAX(assessed_date) as latest_assessment,
        AVG(CASE influence_level
          WHEN 'high' THEN 3
          WHEN 'medium' THEN 2
          WHEN 'low' THEN 1
          ELSE 0
        END) as avg_influence_score,
        AVG(CASE support_level
          WHEN 'strong_support' THEN 2
          WHEN 'support' THEN 1
          WHEN 'neutral' THEN 0
          WHEN 'opposition' THEN -1
          WHEN 'strong_opposition' THEN -2
          ELSE 0
        END) as avg_support_score
      FROM civicrm_powermap_assessment_history
      WHERE " . implode(' AND ', $whereConditions);

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    if ($dao->fetch()) {
      return [
        'total_assessments' => $dao->total_assessments,
        'unique_contacts' => $dao->unique_contacts,
        'unique_assessors' => $dao->unique_assessors,
        'earliest_assessment' => $dao->earliest_assessment,
        'latest_assessment' => $dao->latest_assessment,
        'avg_influence_score' => round($dao->avg_influence_score, 2),
        'avg_support_score' => round($dao->avg_support_score, 2),
      ];
    }

    return [];
  }

  /**
   * Clean up old assessment history
   *
   * @param int $retentionDays
   * @return int Number of records deleted
   */
  public static function cleanupOldHistory($retentionDays = NULL) {
    if (!$retentionDays) {
      $retentionDays = Civi::settings()->get('powermap_assessment_retention_days') ?: 365;
    }

    // Keep history longer than audit logs
    $retentionDays = $retentionDays * 2;
    $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));

    $sql = "DELETE FROM civicrm_powermap_assessment_history WHERE assessed_date < %1";
    $params = [1 => [$cutoffDate, 'String']];

    $result = CRM_Core_DAO::executeQuery($sql, $params);
    return $result->affectedRows();
  }

  /**
   * Export assessment history data
   *
   * @param array $filters
   * @return array
   */
  public static function exportHistoryData($filters = []) {
    $whereConditions = ['1 = 1'];
    $params = [];
    $paramIndex = 1;

    // Apply filters
    if (!empty($filters['contact_id'])) {
      $whereConditions[] = "ah.contact_id = %{$paramIndex}";
      $params[$paramIndex] = [$filters['contact_id'], 'Integer'];
      $paramIndex++;
    }

    if (!empty($filters['powermap_id'])) {
      $whereConditions[] = "ah.powermap_id = %{$paramIndex}";
      $params[$paramIndex] = [$filters['powermap_id'], 'Integer'];
      $paramIndex++;
    }

    if (!empty($filters['date_from'])) {
      $whereConditions[] = "ah.assessed_date >= %{$paramIndex}";
      $params[$paramIndex] = [$filters['date_from'], 'String'];
      $paramIndex++;
    }

    if (!empty($filters['date_to'])) {
      $whereConditions[] = "ah.assessed_date <= %{$paramIndex}";
      $params[$paramIndex] = [$filters['date_to'], 'String'];
      $paramIndex++;
    }

    $sql = "
      SELECT
        ah.*,
        c.display_name as contact_name,
        c.contact_type,
        assessor.display_name as assessed_by_name,
        pm.name as powermap_name
      FROM civicrm_powermap_assessment_history ah
      JOIN civicrm_contact c ON c.id = ah.contact_id
      LEFT JOIN civicrm_contact assessor ON assessor.id = ah.assessed_by
      LEFT JOIN civicrm_powermap_config pm ON pm.id = ah.powermap_id
      WHERE " . implode(' AND ', $whereConditions) . "
      ORDER BY ah.assessed_date DESC
    ";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    $data = [];
    while ($dao->fetch()) {
      $data[] = [
        'contact_name' => $dao->contact_name,
        'contact_type' => $dao->contact_type,
        'powermap_name' => $dao->powermap_name,
        'influence_level' => $dao->influence_level,
        'support_level' => $dao->support_level,
        'engagement_priority' => $dao->engagement_priority,
        'stakeholder_type' => $dao->stakeholder_type,
        'assessment_notes' => strip_tags($dao->assessment_notes),
        'assessed_date' => $dao->assessed_date,
        'assessed_by_name' => $dao->assessed_by_name,
        'change_reason' => $dao->change_reason,
      ];
    }

    return $data;
  }
}
