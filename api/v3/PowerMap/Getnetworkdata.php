<?php

function _civicrm_api3_power_map_getnetworkdata_spec(&$spec) {
  $spec['influence_min'] = array(
    'name' => 'influence_min',
    'title' => 'Minimum Influence Level',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 1,
  );
  $spec['support_min'] = array(
    'name' => 'support_min',
    'title' => 'Minimum Support Level',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 1,
  );
  $spec['relationship_types'] = array(
    'name' => 'relationship_types',
    'title' => 'Relationship Types',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => '',
  );
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
