<?php

function _civicrm_api3_power_map_exporttocsv_spec(&$spec) {
  $spec['influence_min'] = [
    'name' => 'influence_min',
    'title' => 'Minimum Influence Level',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 1,
  ];
  $spec['support_min'] = [
    'name' => 'support_min',
    'title' => 'Minimum Support Level',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => 1,
  ];
  $spec['group_id'] = [
    'name' => 'group_id',
    'title' => 'Groups',
    'type' => CRM_Utils_Type::T_INT,
    'api.default' => '',
  ];
}

function civicrm_api3_power_map_exporttocsv($params) {
  try {
    $result = CRM_Powermap_API_PowerMap::exportToCSV($params);
    return civicrm_api3_create_success($result, $params, 'PowerMap', 'exporttocsv');
  }
  catch (Exception $e) {
    return civicrm_api3_create_error($e->getMessage(), $params);
  }
}
