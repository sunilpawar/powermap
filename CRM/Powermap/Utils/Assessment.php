<?php

/**
 * Utility class for Power Mapping assessments
 */
class CRM_Powermap_Utils_Assessment {

  /**
   * Calculate stakeholder assessment scores and recommendations
   */
  public static function calculateAssessmentMetrics($contactId) {
    $metrics = [];

    $influence = CRM_Powermap_BAO_Stakeholder::getInfluenceScore($contactId);
    $support = CRM_Powermap_BAO_Stakeholder::getSupportScore($contactId);

    $metrics['influence_score'] = $influence;
    $metrics['support_score'] = $support;
    $metrics['quadrant'] = CRM_Powermap_BAO_Stakeholder::getStrategicQuadrant($contactId);
    $metrics['strategy'] = CRM_Powermap_BAO_Stakeholder::getEngagementStrategy($contactId);

    // Calculate priority score (0-100)
    $metrics['priority_score'] = self::calculatePriorityScore($influence, $support);

    // Get engagement recommendations
    $metrics['recommendations'] = self::getEngagementRecommendations($metrics['quadrant'], $influence, $support);

    // Calculate risk assessment
    $metrics['risk_level'] = self::calculateRiskLevel($influence, $support);

    return $metrics;
  }

  /**
   * Calculate priority score based on influence and support
   */
  private static function calculatePriorityScore($influence, $support) {
    // High influence stakeholders get higher priority regardless of support
    $influenceWeight = 0.7;
    $supportWeight = 0.3;

    // Normalize scores to 0-100 scale
    $influenceNormalized = ($influence / 3) * 100;
    $supportNormalized = (($support + 2) / 4) * 100; // Support ranges from -2 to +2

    $priorityScore = ($influenceNormalized * $influenceWeight) + ($supportNormalized * $supportWeight);

    return round($priorityScore);
  }

  /**
   * Get specific engagement recommendations based on assessment
   */
  private static function getEngagementRecommendations($quadrant, $influence, $support) {
    $recommendations = [];

    switch ($quadrant) {
      case 'champions':
        $recommendations = [
          'Give speaking opportunities at events',
          'Ask for public endorsements',
          'Provide exclusive briefings and updates',
          'Invite to advisory committees',
          'Request introductions to other stakeholders'
        ];
        break;

      case 'targets':
        if ($support <= -1) {
          $recommendations = [
            'Schedule one-on-one meetings',
            'Address specific concerns directly',
            'Find common ground and shared values',
            'Provide data and evidence',
            'Consider third-party validators'
          ];
        }
        else {
          $recommendations = [
            'Regular relationship building meetings',
            'Provide detailed policy briefings',
            'Invite to stakeholder events',
            'Share success stories and case studies',
            'Build trust through transparency'
          ];
        }
        break;

      case 'grassroots':
        $recommendations = [
          'Recruit for volunteer activities',
          'Provide social media toolkits',
          'Facilitate peer-to-peer outreach',
          'Offer training and capacity building',
          'Create opportunities for testimonials'
        ];
        break;

      case 'monitor':
        $recommendations = [
          'Include in general communications',
          'Monitor for position changes',
          'Maintain basic relationship',
          'Look for opportunities to increase influence',
          'Consider long-term cultivation'
        ];
        break;
    }

    return $recommendations;
  }

  /**
   * Calculate risk level based on influence and opposition
   */
  private static function calculateRiskLevel($influence, $support) {
    if ($influence >= 2 && $support <= -1) {
      return 'high'; // High influence opponents
    }
    elseif ($influence >= 2 && $support <= 0) {
      return 'medium'; // High influence neutrals
    }
    elseif ($influence <= 1 && $support <= -1) {
      return 'low'; // Low influence opponents
    }
    else {
      return 'minimal'; // Supporters or neutrals
    }
  }

  /**
   * Create assessment reminder activities
   */
  public static function createAssessmentReminder($contactId, $dueDate = NULL) {
    if (!$dueDate) {
      $frequency = Civi::settings()->get('powermap_default_reminder_frequency');
      $dueDate = self::calculateNextReminderDate($frequency);
    }

    $activityTypeId = Civi::settings()->get('powermap_activity_type_id');
    if (!$activityTypeId) {
      return FALSE;
    }

    try {
      $result = civicrm_api3('Activity', 'create', [
        'activity_type_id' => $activityTypeId,
        'subject' => 'Power Mapping Assessment Reminder',
        'activity_date_time' => $dueDate,
        'status_id' => 'Scheduled',
        'target_contact_id' => $contactId,
        'details' => 'Time to reassess this stakeholder\'s influence and support levels.',
      ]);

      return $result['id'];
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Failed to create assessment reminder: ' . $e->getMessage());
      return FALSE;
    }
  }

  /**
   * Calculate next reminder date based on frequency
   */
  private static function calculateNextReminderDate($frequency) {
    $now = new DateTime();

    switch ($frequency) {
      case 'weekly':
        $now->add(new DateInterval('P7D'));
        break;
      case 'monthly':
        $now->add(new DateInterval('P1M'));
        break;
      case 'quarterly':
        $now->add(new DateInterval('P3M'));
        break;
      case 'annually':
        $now->add(new DateInterval('P1Y'));
        break;
      default:
        return NULL;
    }

    return $now->format('Y-m-d H:i:s');
  }

  /**
   * Get assessment history for a stakeholder
   */
  public static function getAssessmentHistory($contactId, $limit = 10) {
    $history = [];

    // This would query a history table if we implement version control
    // For now, we'll just return the current assessment
    $customFields = self::getCustomFieldInfo();

    if (empty($customFields)) {
      return $history;
    }

    $query = "
      SELECT pm.*, c.display_name, c.modified_date
      FROM {$customFields['table_name']} pm
      LEFT JOIN civicrm_contact c ON c.id = pm.entity_id
      WHERE pm.entity_id = %1
      ORDER BY c.modified_date DESC
      LIMIT %2
    ";

    $params = [
      1 => [$contactId, 'Integer'],
      2 => [$limit, 'Integer']
    ];

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    while ($dao->fetch()) {
      $history[] = [
        'contact_id' => $dao->entity_id,
        'display_name' => $dao->display_name,
        'assessment_date' => $dao->modified_date,
        'influence_level' => $dao->{$customFields['influence_column']},
        'support_level' => $dao->{$customFields['support_column']},
        'engagement_priority' => $dao->{$customFields['priority_column']},
        'notes' => $dao->{$customFields['notes_column']}
      ];
    }

    return $history;
  }

  /**
   * Validate assessment data
   */
  public static function validateAssessment($assessmentData) {
    $errors = [];

    // Validate influence level
    $validInfluence = ['low', 'medium', 'high'];
    if (!empty($assessmentData['influence_level']) &&
      !in_array($assessmentData['influence_level'], $validInfluence)) {
      $errors['influence_level'] = 'Invalid influence level';
    }

    // Validate support level
    $validSupport = ['strong_opposition', 'opposition', 'neutral', 'support', 'strong_support'];
    if (!empty($assessmentData['support_level']) &&
      !in_array($assessmentData['support_level'], $validSupport)) {
      $errors['support_level'] = 'Invalid support level';
    }

    // Validate engagement priority
    $validPriority = ['low', 'medium', 'high'];
    if (!empty($assessmentData['engagement_priority']) &&
      !in_array($assessmentData['engagement_priority'], $validPriority)) {
      $errors['engagement_priority'] = 'Invalid engagement priority';
    }

    // Validate assessment date
    if (!empty($assessmentData['assessment_date'])) {
      $date = DateTime::createFromFormat('Y-m-d', $assessmentData['assessment_date']);
      if (!$date || $date->format('Y-m-d') !== $assessmentData['assessment_date']) {
        $errors['assessment_date'] = 'Invalid assessment date format';
      }
    }

    return $errors;
  }

  /**
   * Bulk update assessments
   */
  public static function bulkUpdateAssessments($updates) {
    $results = [];

    foreach ($updates as $contactId => $assessmentData) {
      // Validate data
      $errors = self::validateAssessment($assessmentData);
      if (!empty($errors)) {
        $results[$contactId] = ['success' => FALSE, 'errors' => $errors];
        continue;
      }

      try {
        // Update custom fields
        $customFields = self::getCustomFieldInfo();
        if (!empty($customFields)) {
          $params = ['id' => $contactId];

          if (isset($assessmentData['influence_level'])) {
            $params['custom_' . $customFields['influence_id']] = $assessmentData['influence_level'];
          }
          if (isset($assessmentData['support_level'])) {
            $params['custom_' . $customFields['support_id']] = $assessmentData['support_level'];
          }
          if (isset($assessmentData['engagement_priority'])) {
            $params['custom_' . $customFields['priority_id']] = $assessmentData['engagement_priority'];
          }
          if (isset($assessmentData['assessment_notes'])) {
            $params['custom_' . $customFields['notes_id']] = $assessmentData['assessment_notes'];
          }

          civicrm_api3('Contact', 'create', $params);
        }

        $results[$contactId] = ['success' => TRUE];

        // Create reminder if enabled
        if (Civi::settings()->get('powermap_enable_notifications')) {
          self::createAssessmentReminder($contactId);
        }

      }
      catch (Exception $e) {
        $results[$contactId] = ['success' => FALSE, 'error' => $e->getMessage()];
      }
    }

    return $results;
  }

  /**
   * Get custom field information
   */
  public static function getCustomFieldInfo() {
    static $cache = NULL;

    if ($cache !== NULL) {
      return $cache;
    }

    try {
      // Get custom group
      $group = civicrm_api3('CustomGroup', 'getsingle', [
        'name' => 'power_mapping_data',
      ]);

      // Get custom fields
      $fields = civicrm_api3('CustomField', 'get', [
        'custom_group_id' => $group['id'],
        'options' => ['limit' => 0],
      ]);

      $cache = [
        'table_name' => $group['table_name'],
        'group_id' => $group['id']
      ];

      foreach ($fields['values'] as $field) {
        switch ($field['name']) {
          case 'influence_level':
            $cache['influence_id'] = $field['id'];
            $cache['influence_column'] = $field['column_name'];
            break;
          case 'support_level':
            $cache['support_id'] = $field['id'];
            $cache['support_column'] = $field['column_name'];
            break;
          case 'engagement_priority':
            $cache['priority_id'] = $field['id'];
            $cache['priority_column'] = $field['column_name'];
            break;
          case 'assessment_notes':
            $cache['notes_id'] = $field['id'];
            $cache['notes_column'] = $field['column_name'];
            break;
        }
      }

    }
    catch (Exception $e) {
      $cache = [];
    }

    return $cache;
  }

  /**
   * Generate assessment report data
   */
  public static function generateAssessmentReport($filters = []) {
    $report = [
      'summary' => [],
      'stakeholders' => [],
      'recommendations' => []
    ];

    // Build query conditions
    $whereConditions = ['c.is_deleted = 0'];
    $params = [];
    $paramIndex = 1;

    if (!empty($filters['campaign_id'])) {
      $whereConditions[] = "cc.campaign_id = %{$paramIndex}";
      $params[$paramIndex] = [$filters['campaign_id'], 'Integer'];
      $paramIndex++;
    }

    if (!empty($filters['stakeholder_type'])) {
      $whereConditions[] = "pm.stakeholder_type_{$customFieldSuffix} LIKE %{$paramIndex}";
      $params[$paramIndex] = ['%' . $filters['stakeholder_type'] . '%', 'String'];
      $paramIndex++;
    }

    $customFields = self::getCustomFieldInfo();
    if (empty($customFields)) {
      return $report;
    }

    $query = "
      SELECT c.id, c.display_name,
             pm.{$customFields['influence_column']} as influence_level,
             pm.{$customFields['support_column']} as support_level,
             pm.{$customFields['priority_column']} as engagement_priority
      FROM civicrm_contact c
      LEFT JOIN {$customFields['table_name']} pm ON pm.entity_id = c.id
      WHERE " . implode(' AND ', $whereConditions) . "
        AND pm.{$customFields['influence_column']} IS NOT NULL
      ORDER BY c.display_name
    ";

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $summary = ['total' => 0, 'champions' => 0, 'targets' => 0, 'grassroots' => 0, 'monitor' => 0];

    while ($dao->fetch()) {
      $metrics = self::calculateAssessmentMetrics($dao->id);

      $stakeholder = [
        'id' => $dao->id,
        'name' => $dao->display_name,
        'influence_level' => $dao->influence_level,
        'support_level' => $dao->support_level,
        'engagement_priority' => $dao->engagement_priority,
        'quadrant' => $metrics['quadrant'],
        'priority_score' => $metrics['priority_score'],
        'risk_level' => $metrics['risk_level']
      ];

      $report['stakeholders'][] = $stakeholder;

      $summary['total']++;
      $summary[$metrics['quadrant']]++;
    }

    $report['summary'] = $summary;

    // Generate recommendations
    $report['recommendations'] = self::generateReportRecommendations($summary, $report['stakeholders']);

    return $report;
  }

  /**
   * Generate recommendations based on report data
   */
  private static function generateReportRecommendations($summary, $stakeholders) {
    $recommendations = [];

    // Overall strategy recommendations
    if ($summary['champions'] > $summary['targets']) {
      $recommendations[] = [
        'type' => 'strategic',
        'priority' => 'high',
        'title' => 'Leverage Champion Network',
        'description' => 'You have more champions than targets. Focus on empowering champions to influence targets.'
      ];
    }
    else {
      $recommendations[] = [
        'type' => 'strategic',
        'priority' => 'high',
        'title' => 'Convert High-Influence Targets',
        'description' => 'Priority should be converting high-influence targets to supporters through direct engagement.'
      ];
    }

    // Identify high-priority individuals
    usort($stakeholders, function ($a, $b) {
      return $b['priority_score'] - $a['priority_score'];
    });

    $topPriority = array_slice($stakeholders, 0, 5);

    foreach ($topPriority as $stakeholder) {
      if ($stakeholder['quadrant'] === 'targets') {
        $recommendations[] = [
          'type' => 'individual',
          'priority' => 'high',
          'title' => 'Engage ' . $stakeholder['name'],
          'description' => 'High-influence target requiring immediate attention and personalized engagement strategy.'
        ];
      }
    }

    return $recommendations;
  }
}
