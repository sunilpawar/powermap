<?php
use CRM_Powermap_ExtensionUtil as E;

/**
 * PowermapAssessment.Create API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_assessment_Create_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'description' => 'ID of the contact being assessed',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  ];
  $spec['powermap_id'] = [
    'name' => 'powermap_id',
    'title' => 'Power Map ID',
    'description' => 'ID of the power map (optional)',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Powermap_DAO_PowermapConfig',
  ];
  $spec['influence_level'] = [
    'name' => 'influence_level',
    'title' => 'Influence Level',
    'description' => 'Stakeholder influence level',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => [
      'low' => 'Low',
      'medium' => 'Medium',
      'high' => 'High',
    ],
  ];
  $spec['support_level'] = [
    'name' => 'support_level',
    'title' => 'Support Level',
    'description' => 'Stakeholder support level',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => [
      'strong_opposition' => 'Strong Opposition',
      'opposition' => 'Opposition',
      'neutral' => 'Neutral',
      'support' => 'Support',
      'strong_support' => 'Strong Support',
    ],
  ];
  $spec['engagement_priority'] = [
    'name' => 'engagement_priority',
    'title' => 'Engagement Priority',
    'description' => 'Priority level for engagement',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => [
      'low' => 'Low Priority',
      'medium' => 'Medium Priority',
      'high' => 'High Priority',
      'monitor' => 'Monitor Only',
    ],
  ];
  $spec['stakeholder_type'] = [
    'name' => 'stakeholder_type',
    'title' => 'Stakeholder Type',
    'description' => 'Type/classification of stakeholder',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['assessment_notes'] = [
    'name' => 'assessment_notes',
    'title' => 'Assessment Notes',
    'description' => 'Notes about the assessment',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['change_reason'] = [
    'name' => 'change_reason',
    'title' => 'Change Reason',
    'description' => 'Reason for assessment change',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
}

/**
 * PowermapAssessment.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_assessment_Create($params) {
  // Check if power map is specified and validate permissions
  if (!empty($params['powermap_id'])) {
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
    if (!in_array($permission, ['owner', 'admin', 'edit'])) {
      throw new API_Exception('Insufficient permissions to create assessments for this power map');
    }
  }

  // Validate assessment data
  $assessmentData = [
    'influence_level' => $params['influence_level'] ?? NULL,
    'support_level' => $params['support_level'] ?? NULL,
    'engagement_priority' => $params['engagement_priority'] ?? NULL,
    'stakeholder_type' => $params['stakeholder_type'] ?? NULL,
    'assessment_notes' => $params['assessment_notes'] ?? NULL,
  ];

  $errors = CRM_Powermap_Utils_Assessment::validateAssessment($assessmentData);
  if (!empty($errors)) {
    throw new API_Exception('Validation errors: ' . implode(', ', $errors));
  }

  // Get current assessment to compare changes
  $customFields = CRM_Powermap_Utils_Assessment::getCustomFieldInfo();
  $oldAssessment = [];

  if (!empty($customFields)) {
    $query = "
      SELECT *
      FROM {$customFields['table_name']}
      WHERE entity_id = %1
    ";
    $dao = CRM_Core_DAO::executeQuery($query, [1 => [$params['contact_id'], 'Integer']]);
    if ($dao->fetch()) {
      $oldAssessment = [
        'influence_level' => $dao->{$customFields['influence_column']} ?? NULL,
        'support_level' => $dao->{$customFields['support_column']} ?? NULL,
        'engagement_priority' => $dao->{$customFields['priority_column']} ?? NULL,
        'stakeholder_type' => $dao->{$customFields['type_column']} ?? NULL,
        'assessment_notes' => $dao->{$customFields['notes_column']} ?? NULL,
      ];
    }
  }

  // Update custom fields
  $updateParams = ['id' => $params['contact_id']];

  if (!empty($customFields)) {
    if (isset($params['influence_level'])) {
      $updateParams['custom_' . $customFields['influence_id']] = $params['influence_level'];
    }
    if (isset($params['support_level'])) {
      $updateParams['custom_' . $customFields['support_id']] = $params['support_level'];
    }
    if (isset($params['engagement_priority'])) {
      $updateParams['custom_' . $customFields['priority_id']] = $params['engagement_priority'];
    }
    if (isset($params['stakeholder_type'])) {
      $updateParams['custom_' . $customFields['type_id']] = $params['stakeholder_type'];
    }
    if (isset($params['assessment_notes'])) {
      $updateParams['custom_' . $customFields['notes_id']] = $params['assessment_notes'];
    }
    if (isset($params['assessment_date'])) {
      $updateParams['custom_' . $customFields['date_id']] = $params['assessment_date'];
    }
    else {
      $updateParams['custom_' . $customFields['date_id']] = date('Y-m-d H:i:s');
    }

    // Update the contact
    $result = civicrm_api3('Contact', 'create', $updateParams);

    if ($result['is_error']) {
      throw new API_Exception('Failed to update assessment: ' . $result['error_message']);
    }
  }

  // Record assessment history
  $historyRecord = CRM_Powermap_BAO_PowermapAssessmentHistory::recordAssessment(
    $params['contact_id'],
    $params['powermap_id'] ?? NULL,
    $assessmentData,
    $params['change_reason'] ?? ''
  );

  // Create reminder if enabled
  if (Civi::settings()->get('powermap_enable_notifications')) {
    CRM_Powermap_Utils_Assessment::createAssessmentReminder($params['contact_id']);
  }

  // Create activity if sync is enabled
  if (Civi::settings()->get('powermap_sync_with_activities')) {
    $activityTypeId = Civi::settings()->get('powermap_activity_type_id');
    if ($activityTypeId) {
      try {
        civicrm_api3('Activity', 'create', [
          'activity_type_id' => $activityTypeId,
          'target_contact_id' => $params['contact_id'],
          'subject' => 'Power Mapping Assessment Updated',
          'details' => 'Assessment updated: ' . json_encode($assessmentData),
          'status_id' => 'Completed',
          'activity_date_time' => date('Y-m-d H:i:s'),
        ]);
      }
      catch (Exception $e) {
        // Don't fail if activity creation fails
        CRM_Core_Error::debug_log_message('Failed to create assessment activity: ' . $e->getMessage());
      }
    }
  }

  // Calculate assessment metrics
  $metrics = CRM_Powermap_Utils_Assessment::calculateAssessmentMetrics($params['contact_id']);

  $resultData = [
    'contact_id' => $params['contact_id'],
    'assessment_id' => $historyRecord->id,
    'assessment_data' => $assessmentData,
    'metrics' => $metrics,
    'history_recorded' => TRUE,
  ];

  return civicrm_api3_create_success([$params['contact_id'] => $resultData], $params, 'PowermapAssessment', 'Create');
}

/**
 * PowermapAssessment.Get API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_assessment_Get_spec(&$spec) {
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'description' => 'Filter by contact',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  ];
  $spec['powermap_id'] = [
    'name' => 'powermap_id',
    'title' => 'Power Map ID',
    'description' => 'Filter by power map',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Powermap_DAO_PowermapConfig',
  ];
  $spec['influence_level'] = [
    'name' => 'influence_level',
    'title' => 'Influence Level',
    'description' => 'Filter by influence level',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => [
      'low' => 'Low',
      'medium' => 'Medium',
      'high' => 'High',
    ],
  ];
  $spec['support_level'] = [
    'name' => 'support_level',
    'title' => 'Support Level',
    'description' => 'Filter by support level',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => [
      'strong_opposition' => 'Strong Opposition',
      'opposition' => 'Opposition',
      'neutral' => 'Neutral',
      'support' => 'Support',
      'strong_support' => 'Strong Support',
    ],
  ];
  $spec['quadrant'] = [
    'name' => 'quadrant',
    'title' => 'Strategic Quadrant',
    'description' => 'Filter by strategic quadrant',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => [
      'champions' => 'Champions',
      'targets' => 'Targets',
      'grassroots' => 'Grassroots',
      'monitor' => 'Monitor',
    ],
  ];
  $spec['include_history'] = [
    'name' => 'include_history',
    'title' => 'Include History',
    'description' => 'Include assessment history',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
  $spec['include_metrics'] = [
    'name' => 'include_metrics',
    'title' => 'Include Metrics',
    'description' => 'Include calculated metrics',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  ];
}

/**
 * PowermapAssessment.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_assessment_Get($params) {
  $result = [];
  $customFields = CRM_Powermap_Utils_Assessment::getCustomFieldInfo();

  if (empty($customFields)) {
    throw new API_Exception('Power mapping custom fields not found. Please ensure the extension is properly installed.');
  }

  // Build base query
  $whereConditions = ['c.is_deleted = 0'];
  $queryParams = [];
  $paramIndex = 1;

  // Contact filter
  if (!empty($params['contact_id'])) {
    $whereConditions[] = "c.id = %{$paramIndex}";
    $queryParams[$paramIndex] = [$params['contact_id'], 'Integer'];
    $paramIndex++;
  }

  // Influence level filter
  if (!empty($params['influence_level'])) {
    $whereConditions[] = "pm_data.{$customFields['influence_column']} = %{$paramIndex}";
    $queryParams[$paramIndex] = [$params['influence_level'], 'String'];
    $paramIndex++;
  }

  // Support level filter
  if (!empty($params['support_level'])) {
    $whereConditions[] = "pm_data.{$customFields['support_column']} = %{$paramIndex}";
    $queryParams[$paramIndex] = [$params['support_level'], 'String'];
    $paramIndex++;
  }

  // Ensure we only get contacts with assessments
  $whereConditions[] = "pm_data.{$customFields['influence_column']} IS NOT NULL";

  $query = "
    SELECT
      c.id,
      c.display_name,
      c.contact_type,
      c.contact_sub_type,
      e.email,
      p.phone,
      org.display_name as organization_name,
      pm_data.{$customFields['influence_column']} as influence_level,
      pm_data.{$customFields['support_column']} as support_level,
      pm_data.{$customFields['priority_column']} as engagement_priority,
      pm_data.{$customFields['type_column']} as stakeholder_type,
      pm_data.{$customFields['authority_column']} as decision_authority,
      pm_data.{$customFields['notes_column']} as assessment_notes,
      pm_data.{$customFields['date_column']} as last_assessment_date
    FROM civicrm_contact c
    LEFT JOIN {$customFields['table_name']} pm_data ON pm_data.entity_id = c.id
    LEFT JOIN civicrm_email e ON e.contact_id = c.id AND e.is_primary = 1
    LEFT JOIN civicrm_phone p ON p.contact_id = c.id AND p.is_primary = 1
    LEFT JOIN civicrm_contact org ON org.id = c.employer_id
    WHERE " . implode(' AND ', $whereConditions) . "
    ORDER BY c.display_name
  ";

  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);

  while ($dao->fetch()) {
    $assessment = [
      'contact_id' => $dao->id,
      'contact_name' => $dao->display_name,
      'contact_type' => $dao->contact_type,
      'contact_sub_type' => $dao->contact_sub_type,
      'email' => $dao->email,
      'phone' => $dao->phone,
      'organization_name' => $dao->organization_name,
      'influence_level' => $dao->influence_level,
      'support_level' => $dao->support_level,
      'engagement_priority' => $dao->engagement_priority,
      'stakeholder_type' => $dao->stakeholder_type,
      'decision_authority' => $dao->decision_authority,
      'assessment_notes' => $dao->assessment_notes,
      'last_assessment_date' => $dao->last_assessment_date,
    ];

    // Include calculated metrics if requested
    if (!empty($params['include_metrics'])) {
      $metrics = CRM_Powermap_Utils_Assessment::calculateAssessmentMetrics($dao->id);
      $assessment['metrics'] = $metrics;

      // Apply quadrant filter if specified
      if (!empty($params['quadrant']) && $metrics['quadrant'] !== $params['quadrant']) {
        continue;
      }

      $assessment['quadrant'] = $metrics['quadrant'];
      $assessment['influence_score'] = $metrics['influence_score'];
      $assessment['support_score'] = $metrics['support_score'];
      $assessment['priority_score'] = $metrics['priority_score'];
      $assessment['risk_level'] = $metrics['risk_level'];
    }

    // Include assessment history if requested
    if (!empty($params['include_history'])) {
      $powermapId = !empty($params['powermap_id']) ? $params['powermap_id'] : NULL;
      $assessment['history'] = CRM_Powermap_BAO_PowermapAssessmentHistory::getContactHistory(
        $dao->id,
        10,
        $powermapId
      );
    }

    // Check power map access if specified
    if (!empty($params['powermap_id'])) {
      $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
      if ($permission === 'none') {
        continue; // Skip if no access to this power map
      }
      $assessment['powermap_access'] = $permission;
    }

    $result[$dao->id] = $assessment;
  }

  // Apply API standard options
  $options = _civicrm_api3_get_options_from_params($params);

  if (!empty($options['sort'])) {
    // Apply sorting
    $sortField = $options['sort'];
    $sortDirection = 'ASC';
    if (strpos($sortField, ' ') !== FALSE) {
      [$sortField, $sortDirection] = explode(' ', $sortField, 2);
    }

    uasort($result, function ($a, $b) use ($sortField, $sortDirection) {
      $valueA = isset($a[$sortField]) ? $a[$sortField] : '';
      $valueB = isset($b[$sortField]) ? $b[$sortField] : '';

      if (is_numeric($valueA) && is_numeric($valueB)) {
        $comparison = $valueA - $valueB;
      }
      else {
        $comparison = strcasecmp($valueA, $valueB);
      }

      return ($sortDirection === 'DESC') ? -$comparison : $comparison;
    });
  }

  if (!empty($options['offset'])) {
    $result = array_slice($result, $options['offset'], NULL, TRUE);
  }

  if (!empty($options['limit'])) {
    $result = array_slice($result, 0, $options['limit'], TRUE);
  }

  return civicrm_api3_create_success($result, $params, 'PowermapAssessment', 'Get');
}
