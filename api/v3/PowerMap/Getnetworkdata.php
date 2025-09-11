<?php

function _civicrm_api3_power_map_getnetworkdata_spec(&$spec) {
  $spec['group_id'] = [
    'name' => 'group_id',
    'title' => 'Group ID',
    'description' => 'Filter contacts by CiviCRM group',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => '',
  ];
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact ID(s)',
    'description' => 'Specific contact IDs to include (array or comma-separated)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => '',
  ];
  $spec['only_relationship'] = [
    'name' => 'only_relationship',
    'title' => 'Only Relationships',
    'description' => 'Show only contacts with relationships',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
  $spec['influence_min'] = [
    'name' => 'influence_min',
    'title' => 'Minimum Influence Level',
    'description' => 'Filter by minimum influence level (1-5)',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 1,
  ];
  $spec['support_min'] = [
    'name' => 'support_min',
    'title' => 'Minimum Support Level',
    'description' => 'Filter by minimum support level (1-5)',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 1,
  ];
  $spec['relationship_types'] = [
    'name' => 'relationship_types',
    'title' => 'Relationship Types',
    'description' => 'Filter by relationship type IDs (array or comma-separated)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => '',
  ];
}

function civicrm_api3_power_map_getnetworkdata($params) {
  try {
    $result = CRM_Powermap_API_PowerMap::getNetworkData($params);
    return civicrm_api3_create_success($result, $params, 'PowerMap', 'getnetworkdata');
  }
  catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage(), $params);
  }
}