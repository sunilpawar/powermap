<?php
use CRM_Powermap_ExtensionUtil as E;

/**
 * PowermapStatistics.Get API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_statistics_Get_spec(&$spec) {
  $spec['powermap_id'] = [
    'name' => 'powermap_id',
    'title' => 'Power Map ID',
    'description' => 'Get statistics for specific power map',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Powermap_DAO_PowermapConfig',
  ];
  $spec['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => 'Campaign ID',
    'description' => 'Filter by campaign',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['date_from'] = [
    'name' => 'date_from',
    'title' => 'Date From',
    'description' => 'Include assessments from this date',
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['date_to'] = [
    'name' => 'date_to',
    'title' => 'Date To',
    'description' => 'Include assessments until this date',
    'type' => CRM_Utils_Type::T_DATE,
  ];
  $spec['include_trends'] = [
    'name' => 'include_trends',
    'title' => 'Include Trends',
    'description' => 'Include trend analysis',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
  $spec['include_audit'] = [
    'name' => 'include_audit',
    'title' => 'Include Audit Stats',
    'description' => 'Include audit log statistics',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
}

/**
 * PowermapStatistics.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_statistics_Get($params) {
  $result = [];

  if (!empty($params['powermap_id'])) {
    // Check permissions for specific power map
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
    if ($permission === 'none') {
      throw new API_Exception('Insufficient permissions to view statistics for this power map');
    }

    // Get power map statistics
    $stats = CRM_Powermap_BAO_PowermapConfig::getStatistics($params['powermap_id']);

    // Get quadrant counts
    $quadrantCounts = CRM_Powermap_BAO_PowermapStakeholder::getQuadrantCounts($params['powermap_id']);

    $result = [
      'powermap_id' => $params['powermap_id'],
      'basic_stats' => $stats,
      'quadrant_distribution' => $quadrantCounts,
    ];

    // Include assessment history statistics if requested
    if (!empty($params['include_trends'])) {
      $dateRange = [];
      if (!empty($params['date_from'])) {
        $dateRange['from'] = $params['date_from'];
      }
      if (!empty($params['date_to'])) {
        $dateRange['to'] = $params['date_to'];
      }

      $assessmentStats = CRM_Powermap_BAO_PowermapAssessmentHistory::getAssessmentStatistics(
        $params['powermap_id'],
        $dateRange
      );
      $result['assessment_statistics'] = $assessmentStats;
    }

    // Include audit statistics if requested
    if (!empty($params['include_audit'])) {
      $auditFilters = [];
      if (!empty($params['date_from'])) {
        $auditFilters['date_from'] = $params['date_from'];
      }
      if (!empty($params['date_to'])) {
        $auditFilters['date_to'] = $params['date_to'];
      }

      $auditStats = CRM_Powermap_BAO_PowermapAuditLog::getAuditStatistics($auditFilters);
      $result['audit_statistics'] = $auditStats;
    }

  }
  else {
    // Get overall statistics across all accessible power maps
    $contactId = CRM_Core_Session::getLoggedInContactID();
    $maps = CRM_Powermap_BAO_PowermapConfig::getAccessibleMaps($contactId);

    $overallStats = [
      'total_maps' => count($maps),
      'total_stakeholders' => 0,
      'total_champions' => 0,
      'total_targets' => 0,
      'total_grassroots' => 0,
      'total_monitor' => 0,
      'maps_by_visibility' => ['public' => 0, 'private' => 0, 'group' => 0],
      'maps_by_campaign' => [],
    ];

    foreach ($maps as $map) {
      // Apply campaign filter if specified
      if (!empty($params['campaign_id']) && $map['campaign_id'] != $params['campaign_id']) {
        continue;
      }

      $quadrantCounts = CRM_Powermap_BAO_PowermapStakeholder::getQuadrantCounts($map['id']);

      $overallStats['total_stakeholders'] += $quadrantCounts['total'];
      $overallStats['total_champions'] += $quadrantCounts['champions'];
      $overallStats['total_targets'] += $quadrantCounts['targets'];
      $overallStats['total_grassroots'] += $quadrantCounts['grassroots'];
      $overallStats['total_monitor'] += $quadrantCounts['monitor'];

      $overallStats['maps_by_visibility'][$map['visibility']]++;

      if (!empty($map['campaign_title'])) {
        if (!isset($overallStats['maps_by_campaign'][$map['campaign_title']])) {
          $overallStats['maps_by_campaign'][$map['campaign_title']] = 0;
        }
        $overallStats['maps_by_campaign'][$map['campaign_title']]++;
      }
    }

    // Calculate percentages
    if ($overallStats['total_stakeholders'] > 0) {
      $overallStats['quadrant_percentages'] = [
        'champions' => round(($overallStats['total_champions'] / $overallStats['total_stakeholders']) * 100, 1),
        'targets' => round(($overallStats['total_targets'] / $overallStats['total_stakeholders']) * 100, 1),
        'grassroots' => round(($overallStats['total_grassroots'] / $overallStats['total_stakeholders']) * 100, 1),
        'monitor' => round(($overallStats['total_monitor'] / $overallStats['total_stakeholders']) * 100, 1),
      ];
    }
    else {
      $overallStats['quadrant_percentages'] = [
        'champions' => 0, 'targets' => 0, 'grassroots' => 0, 'monitor' => 0
      ];
    }

    $result = ['overall_statistics' => $overallStats];

    // Include system-wide assessment statistics if requested
    if (!empty($params['include_trends'])) {
      $dateRange = [];
      if (!empty($params['date_from'])) {
        $dateRange['from'] = $params['date_from'];
      }
      if (!empty($params['date_to'])) {
        $dateRange['to'] = $params['date_to'];
      }

      $assessmentStats = CRM_Powermap_BAO_PowermapAssessmentHistory::getAssessmentStatistics(
        NULL, // All power maps
        $dateRange
      );
      $result['system_assessment_statistics'] = $assessmentStats;
    }

    // Include system-wide audit statistics if requested
    if (!empty($params['include_audit'])) {
      $auditFilters = [];
      if (!empty($params['date_from'])) {
        $auditFilters['date_from'] = $params['date_from'];
      }
      if (!empty($params['date_to'])) {
        $auditFilters['date_to'] = $params['date_to'];
      }

      $auditStats = CRM_Powermap_BAO_PowermapAuditLog::getAuditStatistics($auditFilters);
      $result['system_audit_statistics'] = $auditStats;
    }
  }

  // Add metadata
  $result['metadata'] = [
    'generated_at' => date('Y-m-d H:i:s'),
    'generated_by' => CRM_Core_Session::getLoggedInContactDisplayName(),
    'parameters' => $params,
  ];

  return civicrm_api3_create_success($result, $params, 'PowermapStatistics', 'Get');
}
