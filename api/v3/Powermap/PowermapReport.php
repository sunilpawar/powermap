<?php
use CRM_Powermap_ExtensionUtil as E;

/**
 * PowermapReport.Generate API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_report_Generate_spec(&$spec) {
  $spec['powermap_id'] = [
    'name' => 'powermap_id',
    'title' => 'Power Map ID',
    'description' => 'Generate report for specific power map',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Powermap_DAO_PowermapConfig',
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
  $spec['include_recommendations'] = [
    'name' => 'include_recommendations',
    'title' => 'Include Recommendations',
    'description' => 'Include strategic recommendations',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  ];
  $spec['include_details'] = [
    'name' => 'include_details',
    'title' => 'Include Details',
    'description' => 'Include detailed stakeholder information',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => TRUE,
  ];
  $spec['format'] = [
    'name' => 'format',
    'title' => 'Report Format',
    'description' => 'Format of the generated report',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => 'array',
    'options' => [
      'array' => 'Structured Array',
      'html' => 'HTML',
      'markdown' => 'Markdown',
    ],
  ];
}

/**
 * PowermapReport.Generate API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_report_Generate($params) {
  // Check permissions if power map is specified
  if (!empty($params['powermap_id'])) {
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
    if ($permission === 'none') {
      throw new API_Exception('Insufficient permissions to generate reports for this power map');
    }
  }

  // Build filters
  $filters = [];
  if (!empty($params['campaign_id'])) {
    $filters['campaign_id'] = $params['campaign_id'];
  }
  if (!empty($params['stakeholder_type'])) {
    $filters['stakeholder_type'] = $params['stakeholder_type'];
  }
  if (!empty($params['quadrant'])) {
    $filters['quadrant'] = $params['quadrant'];
  }

  // Generate the assessment report
  $reportData = CRM_Powermap_Utils_Assessment::generateAssessmentReport($filters);

  // If specific power map, get additional details
  if (!empty($params['powermap_id'])) {
    $mapInfo = civicrm_api3('PowermapConfig', 'getsingle', [
      'id' => $params['powermap_id']
    ]);
    $reportData['map_info'] = $mapInfo;

    // Get power map statistics
    $stats = CRM_Powermap_BAO_PowermapConfig::getStatistics($params['powermap_id']);
    $reportData['map_statistics'] = $stats;
  }

  // Filter stakeholders by power map if specified
  if (!empty($params['powermap_id'])) {
    $stakeholdersInMap = CRM_Powermap_BAO_PowermapStakeholder::getStakeholders($params['powermap_id']);
    $mapContactIds = array_column($stakeholdersInMap, 'contact_id');

    $reportData['stakeholders'] = array_filter($reportData['stakeholders'], function ($stakeholder) use ($mapContactIds) {
      return in_array($stakeholder['id'], $mapContactIds);
    });

    // Recalculate summary
    $reportData['summary'] = [
      'total' => count($reportData['stakeholders']),
      'champions' => 0,
      'targets' => 0,
      'grassroots' => 0,
      'monitor' => 0
    ];

    foreach ($reportData['stakeholders'] as $stakeholder) {
      $reportData['summary'][$stakeholder['quadrant']]++;
    }
  }

  // Add detailed engagement strategies if requested
  if (!empty($params['include_details'])) {
    foreach ($reportData['stakeholders'] as &$stakeholder) {
      $metrics = CRM_Powermap_Utils_Assessment::calculateAssessmentMetrics($stakeholder['id']);
      $stakeholder['detailed_strategy'] = $metrics['strategy'];
      $stakeholder['recommendations'] = $metrics['recommendations'];
    }
  }

  // Add executive summary
  $reportData['executive_summary'] = [
    'total_stakeholders' => $reportData['summary']['total'],
    'champion_ratio' => $reportData['summary']['total'] > 0 ?
      round(($reportData['summary']['champions'] / $reportData['summary']['total']) * 100, 1) : 0,
    'target_ratio' => $reportData['summary']['total'] > 0 ?
      round(($reportData['summary']['targets'] / $reportData['summary']['total']) * 100, 1) : 0,
    'engagement_readiness' => calculateEngagementReadiness($reportData['summary']),
    'top_priorities' => getTopPriorityStakeholders($reportData['stakeholders'], 5),
  ];

  // Format based on requested format
  $format = $params['format'] ?? 'array';

  switch ($format) {
    case 'html':
      $result = [
        'report_html' => formatReportAsHTML($reportData, $params),
        'raw_data' => $reportData
      ];
      break;

    case 'markdown':
      $result = [
        'report_markdown' => formatReportAsMarkdown($reportData, $params),
        'raw_data' => $reportData
      ];
      break;

    default:
      $result = $reportData;
      break;
  }

  // Add metadata
  $result['metadata'] = [
    'generated_at' => date('Y-m-d H:i:s'),
    'generated_by' => CRM_Core_Session::getLoggedInContactDisplayName(),
    'parameters' => $params,
    'format' => $format,
  ];

  // Log the report generation
  if (!empty($params['powermap_id'])) {
    CRM_Powermap_BAO_PowermapAuditLog::logAction(
      'civicrm_powermap_config',
      $params['powermap_id'],
      'report',
      NULL,
      ['filters' => $filters, 'format' => $format]
    );
  }

  return civicrm_api3_create_success($result, $params, 'PowermapReport', 'Generate');
}

/**
 * Calculate engagement readiness score
 */
function calculateEngagementReadiness($summary) {
  if ($summary['total'] == 0) {
    return ['score' => 0, 'status' => 'No Data'];
  }

  $championRatio = $summary['champions'] / $summary['total'];
  $targetRatio = $summary['targets'] / $summary['total'];

  // Higher champion ratio and lower target ratio = better readiness
  $score = ($championRatio * 60) + ((1 - $targetRatio) * 40);
  $score = round($score);

  if ($score >= 80) {
    $status = 'Excellent';
  }
  elseif ($score >= 60) {
    $status = 'Good';
  }
  elseif ($score >= 40) {
    $status = 'Fair';
  }
  else {
    $status = 'Needs Work';
  }

  return ['score' => $score, 'status' => $status];
}

/**
 * Get top priority stakeholders
 */
function getTopPriorityStakeholders($stakeholders, $limit = 5) {
  // Sort by priority score descending
  usort($stakeholders, function ($a, $b) {
    return ($b['priority_score'] ?? 0) - ($a['priority_score'] ?? 0);
  });

  return array_slice($stakeholders, 0, $limit);
}

/**
 * Format report as HTML
 */
function formatReportAsHTML($reportData, $params) {
  $html = '<div class="powermap-report">';
  $html .= '<h1>Power Mapping Report</h1>';

  if (!empty($reportData['map_info'])) {
    $html .= '<h2>Map: ' . htmlspecialchars($reportData['map_info']['name']) . '</h2>';
    if (!empty($reportData['map_info']['description'])) {
      $html .= '<p>' . htmlspecialchars($reportData['map_info']['description']) . '</p>';
    }
  }

  $html .= '<h3>Executive Summary</h3>';
  $html .= '<ul>';
  $html .= '<li>Total Stakeholders: ' . $reportData['executive_summary']['total_stakeholders'] . '</li>';
  $html .= '<li>Champion Ratio: ' . $reportData['executive_summary']['champion_ratio'] . '%</li>';
  $html .= '<li>Target Ratio: ' . $reportData['executive_summary']['target_ratio'] . '%</li>';
  $html .= '<li>Engagement Readiness: ' . $reportData['executive_summary']['engagement_readiness']['status'] .
    ' (' . $reportData['executive_summary']['engagement_readiness']['score'] . '/100)</li>';
  $html .= '</ul>';

  // Add more HTML formatting as needed...

  $html .= '</div>';
  return $html;
}

/**
 * Format report as Markdown
 */
function formatReportAsMarkdown($reportData, $params) {
  $markdown = "# Power Mapping Report\n\n";

  if (!empty($reportData['map_info'])) {
    $markdown .= "## Map: " . $reportData['map_info']['name'] . "\n\n";
    if (!empty($reportData['map_info']['description'])) {
      $markdown .= $reportData['map_info']['description'] . "\n\n";
    }
  }

  $markdown .= "## Executive Summary\n\n";
  $markdown .= "- **Total Stakeholders:** " . $reportData['executive_summary']['total_stakeholders'] . "\n";
  $markdown .= "- **Champion Ratio:** " . $reportData['executive_summary']['champion_ratio'] . "%\n";
  $markdown .= "- **Target Ratio:** " . $reportData['executive_summary']['target_ratio'] . "%\n";
  $markdown .= "- **Engagement Readiness:** " . $reportData['executive_summary']['engagement_readiness']['status'] .
    " (" . $reportData['executive_summary']['engagement_readiness']['score'] . "/100)\n\n";

  // Add more Markdown formatting as needed...

  return $markdown;
}
