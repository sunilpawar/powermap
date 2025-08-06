<?php
use CRM_Powermap_ExtensionUtil as E;

/**
 * Powermap.Get API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/api-architecture/
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
  ];}

/**
 * Powermap.Get API
 *
 * @param array $params
 *
 * @return array
 *   API result descriptor
 *
 * @see civicrm_api3_create_success
 *
 * @throws API_Exception
 */
function civicrm_api3_powermap_Get($params) {
  $stakeholders = [];

  // Build base query
  $query = "
    SELECT
      c.id,
      c.display_name,
      c.contact_type,
      pm.influence_level_{$customFieldSuffix} as influence_level,
      pm.support_level_{$customFieldSuffix} as support_level,
      pm.stakeholder_type_{$customFieldSuffix} as stakeholder_type,
      pm.engagement_priority_{$customFieldSuffix} as engagement_priority
    FROM civicrm_contact c
    LEFT JOIN civicrm_value_power_mapping_data_1 pm ON pm.entity_id = c.id
    WHERE c.is_deleted = 0
      AND (pm.influence_level_{$customFieldSuffix} IS NOT NULL OR pm.support_level_{$customFieldSuffix} IS NOT NULL)
  ";

  // Add filters based on parameters
  $whereConditions = [];
  $queryParams = [];

  if (!empty($params['campaign_id'])) {
    // Add campaign filter logic here
  }

  if (!empty($params['stakeholder_type'])) {
    $whereConditions[] = "pm.stakeholder_type_{$customFieldSuffix} LIKE %1";
    $queryParams[1] = ['%' . $params['stakeholder_type'] . '%', 'String'];
  }

  if (!empty($whereConditions)) {
    $query .= ' AND ' . implode(' AND ', $whereConditions);
  }

  $query .= ' ORDER BY c.display_name';

  $dao = CRM_Core_DAO::executeQuery($query, $queryParams);

  while ($dao->fetch()) {
    $influenceScore = CRM_Powermap_BAO_Stakeholder::getInfluenceScore($dao->id);
    $supportScore = CRM_Powermap_BAO_Stakeholder::getSupportScore($dao->id);
    $quadrant = CRM_Powermap_BAO_Stakeholder::getStrategicQuadrant($dao->id);
    $strategy = CRM_Powermap_BAO_Stakeholder::getEngagementStrategy($dao->id);

    // Apply quadrant filter if specified
    if (!empty($params['quadrant']) && $quadrant !== $params['quadrant']) {
      continue;
    }

    $stakeholders[] = [
      'id' => $dao->id,
      'name' => $dao->display_name,
      'contact_type' => $dao->contact_type,
      'influence_level' => $dao->influence_level,
      'support_level' => $dao->support_level,
      'stakeholder_type' => $dao->stakeholder_type,
      'engagement_priority' => $dao->engagement_priority,
      'influence_score' => $influenceScore,
      'support_score' => $supportScore,
      'quadrant' => $quadrant,
      'strategy' => $strategy,
    ];
  }

  return civicrm_api3_create_success($stakeholders, $params, 'Powermap', 'get');
}
