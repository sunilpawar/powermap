<?php
use CRM_Powermap_ExtensionUtil as E;

/**
 * PowermapConfig.Create API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_config_Create_spec(&$spec) {
  $spec['name'] = [
    'name' => 'name',
    'title' => 'Power Map Name',
    'description' => 'Name of the power map',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
  ];
  $spec['description'] = [
    'name' => 'description',
    'title' => 'Description',
    'description' => 'Description of the power map',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => 'Campaign ID',
    'description' => 'Associated campaign ID',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['visibility'] = [
    'name' => 'visibility',
    'title' => 'Visibility',
    'description' => 'Visibility setting (public, private, group)',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => 'public',
    'options' => CRM_Powermap_BAO_PowermapConfig::getVisibilityOptions(),
  ];
  $spec['settings'] = [
    'name' => 'settings',
    'title' => 'Settings',
    'description' => 'JSON encoded settings',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
}

/**
 * PowermapConfig.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_config_Create($params) {
  // Set audit fields
  $params['created_id'] = CRM_Core_Session::getLoggedInContactID();
  $params['created_date'] = date('Y-m-d H:i:s');
  $params['modified_id'] = $params['created_id'];
  $params['modified_date'] = $params['created_date'];

  // Create the power map
  $powermapConfig = CRM_Powermap_BAO_PowermapConfig::create($params);

  if (!$powermapConfig) {
    throw new API_Exception('Failed to create power map');
  }

  // Log the action
  CRM_Powermap_BAO_PowermapAuditLog::logAction(
    'civicrm_powermap_config',
    $powermapConfig->id,
    'create',
    NULL,
    $params
  );

  return civicrm_api3_create_success([$powermapConfig->id => $powermapConfig->toArray()], $params, 'PowermapConfig', 'Create');
}


/**
 * PowermapConfig.Get API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_config_Get_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Power Map ID',
    'description' => 'Unique identifier for the power map',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['name'] = [
    'name' => 'name',
    'title' => 'Power Map Name',
    'description' => 'Name of the power map',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['campaign_id'] = [
    'name' => 'campaign_id',
    'title' => 'Campaign ID',
    'description' => 'Filter by campaign',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['created_id'] = [
    'name' => 'created_id',
    'title' => 'Created By',
    'description' => 'Filter by creator',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['visibility'] = [
    'name' => 'visibility',
    'title' => 'Visibility',
    'description' => 'Filter by visibility setting',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['is_active'] = [
    'name' => 'is_active',
    'title' => 'Is Active',
    'description' => 'Filter by active status',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => 1,
  ];
}

/**
 * PowermapConfig.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_config_Get($params) {
  $contactId = CRM_Core_Session::getLoggedInContactID();

  // Get accessible maps for the current user
  $filters = [];
  if (!empty($params['campaign_id'])) {
    $filters['campaign_id'] = $params['campaign_id'];
  }
  if (!empty($params['name'])) {
    $filters['name'] = $params['name'];
  }

  $maps = CRM_Powermap_BAO_PowermapConfig::getAccessibleMaps($contactId, $filters);

  // Apply additional filters
  if (!empty($params['id'])) {
    $maps = array_filter($maps, function ($map) use ($params) {
      return $map['id'] == $params['id'];
    });
  }

  if (isset($params['visibility'])) {
    $maps = array_filter($maps, function ($map) use ($params) {
      return $map['visibility'] == $params['visibility'];
    });
  }

  if (isset($params['created_id'])) {
    $maps = array_filter($maps, function ($map) use ($params) {
      return $map['created_id'] == $params['created_id'];
    });
  }

  if (isset($params['is_active'])) {
    $maps = array_filter($maps, function ($map) use ($params) {
      return $map['is_active'] == $params['is_active'];
    });
  }

  // Apply API standard filters (limit, offset, etc.)
  $options = _civicrm_api3_get_options_from_params($params);

  if (!empty($options['offset'])) {
    $maps = array_slice($maps, $options['offset'], NULL, TRUE);
  }

  if (!empty($options['limit'])) {
    $maps = array_slice($maps, 0, $options['limit'], TRUE);
  }

  // Convert to expected format
  $result = [];
  foreach ($maps as $map) {
    $result[$map['id']] = $map;
  }

  return civicrm_api3_create_success($result, $params, 'PowermapConfig', 'Get');
}
