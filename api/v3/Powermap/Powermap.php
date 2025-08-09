<?php
use CRM_Powermap_ExtensionUtil as E;

/**
 * Powermap.Export API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_Export_spec(&$spec) {
  $spec['powermap_id'] = [
    'name' => 'powermap_id',
    'title' => 'Power Map ID',
    'description' => 'ID of the power map to export',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Powermap_DAO_PowermapConfig',
  ];
  $spec['format'] = [
    'name' => 'format',
    'title' => 'Export Format',
    'description' => 'Format for export',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => 'csv',
    'options' => [
      'csv' => 'CSV',
      'json' => 'JSON',
      'pdf' => 'PDF',
      'excel' => 'Excel',
      'network' => 'Network Data (JSON)',
    ],
  ];
  $spec['include_contact_details'] = [
    'name' => 'include_contact_details',
    'title' => 'Include Contact Details',
    'description' => 'Include detailed contact information',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
  $spec['include_scores'] = [
    'name' => 'include_scores',
    'title' => 'Include Scores',
    'description' => 'Include calculated influence/support scores',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  ];
  $spec['include_notes'] = [
    'name' => 'include_notes',
    'title' => 'Include Notes',
    'description' => 'Include assessment notes',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
  $spec['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => 'Campaign ID',
    'description' => 'Filter by campaign',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['stakeholder_type'] = [
    'name' => 'stakeholder_type',
    'title' => 'Stakeholder Type',
    'description' => 'Filter by stakeholder type',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['download'] = [
    'name' => 'download',
    'title' => 'Download File',
    'description' => 'Whether to trigger file download',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
}

/**
 * Powermap.Export API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_Export($params) {
  // Check if exports are allowed
  $format = strtolower($params['format'] ?? 'csv');

  $allowedFormats = [];
  if (Civi::settings()->get('powermap_allow_csv_export')) {
    $allowedFormats[] = 'csv';
  }
  if (Civi::settings()->get('powermap_allow_json_export')) {
    $allowedFormats[] = 'json';
    $allowedFormats[] = 'network';
  }
  if (Civi::settings()->get('powermap_allow_pdf_export')) {
    $allowedFormats[] = 'pdf';
  }
  // Excel exports use CSV settings
  if (Civi::settings()->get('powermap_allow_csv_export')) {
    $allowedFormats[] = 'excel';
  }

  if (!in_array($format, $allowedFormats)) {
    throw new API_Exception("Export format '{$format}' is not enabled in settings");
  }

  // Validate power map access if specified
  if (!empty($params['powermap_id'])) {
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
    if ($permission === 'none') {
      throw new API_Exception('Insufficient permissions to export this power map');
    }
  }

  // Prepare export options
  $options = [
    'include_contact_details' => !empty($params['include_contact_details']),
    'include_scores' => !empty($params['include_scores']),
    'include_notes' => !empty($params['include_notes']),
  ];

  // Add filters
  if (!empty($params['campaign_id'])) {
    $options['campaign_id'] = $params['campaign_id'];
  }
  if (!empty($params['stakeholder_type'])) {
    $options['stakeholder_type'] = $params['stakeholder_type'];
  }
  if (!empty($params['powermap_id'])) {
    $options['map_name'] = CRM_Core_DAO::singleValueQuery(
      "SELECT name FROM civicrm_powermap_config WHERE id = %1",
      [1 => [$params['powermap_id'], 'Integer']]
    );
  }

  try {
    if ($format === 'network') {
      // Export network data for visualization
      $data = CRM_Powermap_Utils_Export::exportNetworkData($params['powermap_id'] ?? 0, $options);

      if (!empty($params['download'])) {
        $filename = 'powermap_network_' . date('Y-m-d_H-i-s') . '.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        CRM_Utils_System::civiExit();
      }

      return civicrm_api3_create_success(['network_data' => $data], $params, 'Powermap', 'Export');

    }
    else {
      // Standard exports
      $exportResult = CRM_Powermap_Utils_Export::exportPowerMap(
        $params['powermap_id'] ?? 0,
        $format,
        $options
      );

      if (!empty($params['download'])) {
        // Trigger file download
        CRM_Powermap_Utils_Export::downloadFile(
          $exportResult['file_path'],
          $exportResult['filename'],
          $exportResult['mime_type']
        );
      }

      // Log the export action
      if (!empty($params['powermap_id'])) {
        CRM_Powermap_BAO_PowermapAuditLog::logAction(
          'civicrm_powermap_config',
          $params['powermap_id'],
          'export',
          NULL,
          ['format' => $format, 'options' => $options]
        );
      }

      return civicrm_api3_create_success([
        'file_path' => $exportResult['file_path'],
        'filename' => $exportResult['filename'],
        'format' => $format,
        'download_url' => CRM_Utils_System::url('civicrm/powermap/download',
          'file=' . urlencode($exportResult['filename']), TRUE
        )
      ], $params, 'Powermap', 'Export');
    }

  }
  catch (Exception $e) {
    throw new API_Exception('Export failed: ' . $e->getMessage());
  }
}


/**
 * Powermap.Get API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_Get_spec(&$spec) {
  $spec['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => 'Campaign ID',
    'description' => 'Filter by campaign',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['stakeholder_type'] = [
    'name' => 'stakeholder_type',
    'title' => 'Stakeholder Type',
    'description' => 'Filter by stakeholder type',
    'type' => CRM_Utils_Type::T_STRING,
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
  $spec['contact_type'] = [
    'name' => 'contact_type',
    'title' => 'Contact Type',
    'description' => 'Filter by contact type',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['include_metrics'] = [
    'name' => 'include_metrics',
    'title' => 'Include Metrics',
    'description' => 'Include calculated metrics and scores',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  ];
}

/**
 * Powermap.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_Get($params) {
  $stakeholders = [];

  // Get custom field information
  $customFields = CRM_Powermap_Utils_Assessment::getCustomFieldInfo();
  if (empty($customFields)) {
    throw new API_Exception('Power mapping custom fields not found. Please ensure the extension is properly installed.');
  }

  // Build base query
  $whereConditions = ['c.is_deleted = 0'];
  $queryParams = [];
  $paramIndex = 1;

  // Ensure we only get contacts with power mapping data
  $whereConditions[] = "pm_data.{$customFields['influence_column']} IS NOT NULL";

  // Apply filters
  if (!empty($params['contact_type'])) {
    $whereConditions[] = "c.contact_type = %{$paramIndex}";
    $queryParams[$paramIndex] = [$params['contact_type'], 'String'];
    $paramIndex++;
  }

  if (!empty($params['influence_level'])) {
    $whereConditions[] = "pm_data.{$customFields['influence_column']} = %{$paramIndex}";
    $queryParams[$paramIndex] = [$params['influence_level'], 'String'];
    $paramIndex++;
  }

  if (!empty($params['support_level'])) {
    $whereConditions[] = "pm_data.{$customFields['support_column']} = %{$paramIndex}";
    $queryParams[$paramIndex] = [$params['support_level'], 'String'];
    $paramIndex++;
  }

  if (!empty($params['stakeholder_type'])) {
    $whereConditions[] = "pm_data.{$customFields['type_column']} LIKE %{$paramIndex}";
    $queryParams[$paramIndex] = ['%' . $params['stakeholder_type'] . '%', 'String'];
    $paramIndex++;
  }

  // Campaign filter (if stakeholder is in a power map associated with the campaign)
  if (!empty($params['campaign_id'])) {
    $whereConditions[] = "EXISTS (
      SELECT 1 FROM civicrm_powermap_stakeholder ps
      JOIN civicrm_powermap_config pmc ON pmc.id = ps.powermap_id
      WHERE ps.contact_id = c.id
        AND ps.is_active = 1
        AND pmc.campaign_id = %{$paramIndex}
    )";
    $queryParams[$paramIndex] = [$params['campaign_id'], 'Integer'];
    $paramIndex++;
  }

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
    $stakeholder = [
      'id' => $dao->id,
      'name' => $dao->display_name,
      'display_name' => $dao->display_name,
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
      $influenceScore = CRM_Powermap_BAO_Stakeholder::getInfluenceScore($dao->id);
      $supportScore = CRM_Powermap_BAO_Stakeholder::getSupportScore($dao->id);
      $quadrant = CRM_Powermap_BAO_Stakeholder::getStrategicQuadrant($dao->id);
      $strategy = CRM_Powermap_BAO_Stakeholder::getEngagementStrategy($dao->id);

      $stakeholder['influence_score'] = $influenceScore;
      $stakeholder['support_score'] = $supportScore;
      $stakeholder['quadrant'] = $quadrant;
      $stakeholder['strategy'] = $strategy;

      // Apply quadrant filter if specified
      if (!empty($params['quadrant']) && $quadrant !== $params['quadrant']) {
        continue;
      }

      // Calculate additional metrics
      $metrics = CRM_Powermap_Utils_Assessment::calculateAssessmentMetrics($dao->id);
      $stakeholder['priority_score'] = $metrics['priority_score'];
      $stakeholder['risk_level'] = $metrics['risk_level'];
      $stakeholder['recommendations'] = $metrics['recommendations'];
    }

    $stakeholders[$dao->id] = $stakeholder;
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

    uasort($stakeholders, function ($a, $b) use ($sortField, $sortDirection) {
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
    $stakeholders = array_slice($stakeholders, $options['offset'], NULL, TRUE);
  }

  if (!empty($options['limit'])) {
    $stakeholders = array_slice($stakeholders, 0, $options['limit'], TRUE);
  }

  return civicrm_api3_create_success($stakeholders, $params, 'Powermap', 'Get');
}
