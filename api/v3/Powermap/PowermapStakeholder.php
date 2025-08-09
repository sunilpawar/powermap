<?php
use CRM_Powermap_ExtensionUtil as E;

/**
 * PowermapStakeholder.Create API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_stakeholder_Create_spec(&$spec) {
  $spec['powermap_id'] = [
    'name' => 'powermap_id',
    'title' => 'Power Map ID',
    'description' => 'ID of the power map',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'FKClassName' => 'CRM_Powermap_DAO_PowermapConfig',
  ];
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'description' => 'ID of the stakeholder contact',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  ];
  $spec['position_x'] = [
    'name' => 'position_x',
    'title' => 'Position X',
    'description' => 'X coordinate for positioning',
    'type' => CRM_Utils_Type::T_FLOAT,
  ];
  $spec['position_y'] = [
    'name' => 'position_y',
    'title' => 'Position Y',
    'description' => 'Y coordinate for positioning',
    'type' => CRM_Utils_Type::T_FLOAT,
  ];
  $spec['notes'] = [
    'name' => 'notes',
    'title' => 'Notes',
    'description' => 'Additional notes about this stakeholder assignment',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
}

/**
 * PowermapStakeholder.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_stakeholder_Create($params) {
  // Check permissions
  $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
  if (!in_array($permission, ['owner', 'admin', 'edit'])) {
    throw new API_Exception('Insufficient permissions to add stakeholders to this power map');
  }

  // Set audit fields
  $params['added_by'] = CRM_Core_Session::getLoggedInContactID();
  $params['added_date'] = date('Y-m-d H:i:s');
  $params['is_active'] = 1;

  // Check if stakeholder already exists
  $existing = CRM_Powermap_BAO_PowermapStakeholder::getStakeholder(
    $params['powermap_id'],
    $params['contact_id']
  );

  if ($existing) {
    if ($existing['is_active']) {
      throw new API_Exception('Stakeholder already exists in this power map');
    }
    else {
      // Reactivate existing stakeholder
      $params['id'] = $existing['id'];
    }
  }

  // Create/update the stakeholder
  $stakeholder = CRM_Powermap_BAO_PowermapStakeholder::create($params);

  if (!$stakeholder) {
    throw new API_Exception('Failed to add stakeholder to power map');
  }

  // Log the action
  CRM_Powermap_BAO_PowermapAuditLog::logAction(
    'civicrm_powermap_stakeholder',
    $stakeholder->id,
    $existing ? 'reactivate' : 'create',
    $existing ? ['is_active' => 0] : NULL,
    $params
  );

  return civicrm_api3_create_success([$stakeholder->id => $stakeholder->toArray()], $params, 'PowermapStakeholder', 'Create');
}


/**
 * PowermapStakeholder.Get API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_stakeholder_Get_spec(&$spec) {
  $spec['powermap_id'] = [
    'name' => 'powermap_id',
    'title' => 'Power Map ID',
    'description' => 'Filter by power map',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Powermap_DAO_PowermapConfig',
  ];
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'description' => 'Filter by contact',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  ];
  $spec['contact_type'] = [
    'name' => 'contact_type',
    'title' => 'Contact Type',
    'description' => 'Filter by contact type',
    'type' => CRM_Utils_Type::T_STRING,
  ];
  $spec['search_term'] = [
    'name' => 'search_term',
    'title' => 'Search Term',
    'description' => 'Search in contact name',
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
  $spec['is_active'] = [
    'name' => 'is_active',
    'title' => 'Is Active',
    'description' => 'Filter by active status',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => 1,
  ];
}

/**
 * PowermapStakeholder.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_stakeholder_Get($params) {
  $result = [];

  if (!empty($params['powermap_id'])) {
    // Check permissions for specific power map
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
    if ($permission === 'none') {
      throw new API_Exception('Insufficient permissions to view stakeholders in this power map');
    }

    // Get stakeholders for specific power map
    $filters = [];
    if (!empty($params['contact_type'])) {
      $filters['contact_type'] = $params['contact_type'];
    }
    if (!empty($params['search_term'])) {
      $filters['search_term'] = $params['search_term'];
    }

    $stakeholders = CRM_Powermap_BAO_PowermapStakeholder::getStakeholders($params['powermap_id'], $filters);

    // Apply quadrant filter if specified
    if (!empty($params['quadrant'])) {
      $stakeholders = array_filter($stakeholders, function ($stakeholder) use ($params) {
        return isset($stakeholder['quadrant']) && $stakeholder['quadrant'] === $params['quadrant'];
      });
    }

    // Apply active filter
    if (isset($params['is_active'])) {
      $stakeholders = array_filter($stakeholders, function ($stakeholder) use ($params) {
        return $stakeholder['is_active'] == $params['is_active'];
      });
    }

    foreach ($stakeholders as $stakeholder) {
      $result[$stakeholder['id']] = $stakeholder;
    }

  }
  elseif (!empty($params['contact_id'])) {
    // Get stakeholder engagement history for specific contact
    $powermapId = !empty($params['powermap_id']) ? $params['powermap_id'] : NULL;
    $history = CRM_Powermap_BAO_PowermapStakeholder::getEngagementHistory($params['contact_id'], $powermapId);

    foreach ($history as $index => $engagement) {
      $result[$index] = $engagement;
    }

  }
  else {
    // Get all accessible stakeholders across all maps
    $contactId = CRM_Core_Session::getLoggedInContactID();
    $maps = CRM_Powermap_BAO_PowermapConfig::getAccessibleMaps($contactId);

    foreach ($maps as $map) {
      $stakeholders = CRM_Powermap_BAO_PowermapStakeholder::getStakeholders($map['id']);

      foreach ($stakeholders as $stakeholder) {
        // Apply filters
        if (!empty($params['contact_type']) && $stakeholder['contact_type'] !== $params['contact_type']) {
          continue;
        }
        if (!empty($params['search_term']) && stripos($stakeholder['display_name'], $params['search_term']) === FALSE) {
          continue;
        }
        if (!empty($params['quadrant']) && $stakeholder['quadrant'] !== $params['quadrant']) {
          continue;
        }
        if (isset($params['is_active']) && $stakeholder['is_active'] != $params['is_active']) {
          continue;
        }

        $stakeholder['powermap_name'] = $map['name'];
        $result[$stakeholder['id']] = $stakeholder;
      }
    }
  }

  // Apply API standard options (limit, offset, sort)
  $options = _civicrm_api3_get_options_from_params($params);

  if (!empty($options['sort'])) {
    // Apply sorting - this is simplified, real implementation would be more robust
    $sortField = $options['sort'];
    $sortDirection = 'ASC';
    if (strpos($sortField, ' ') !== FALSE) {
      [$sortField, $sortDirection] = explode(' ', $sortField, 2);
    }

    uasort($result, function ($a, $b) use ($sortField, $sortDirection) {
      $valueA = isset($a[$sortField]) ? $a[$sortField] : '';
      $valueB = isset($b[$sortField]) ? $b[$sortField] : '';

      $comparison = strcasecmp($valueA, $valueB);
      return ($sortDirection === 'DESC') ? -$comparison : $comparison;
    });
  }

  if (!empty($options['offset'])) {
    $result = array_slice($result, $options['offset'], NULL, TRUE);
  }

  if (!empty($options['limit'])) {
    $result = array_slice($result, 0, $options['limit'], TRUE);
  }

  return civicrm_api3_create_success($result, $params, 'PowermapStakeholder', 'Get');
}


/**
 * PowermapStakeholder.Delete API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_stakeholder_Delete_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Stakeholder Assignment ID',
    'description' => 'ID of the stakeholder assignment to delete',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['powermap_id'] = [
    'name' => 'powermap_id',
    'title' => 'Power Map ID',
    'description' => 'ID of the power map',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['contact_id'] = [
    'name' => 'contact_id',
    'title' => 'Contact ID',
    'description' => 'ID of the contact to remove',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['hard_delete'] = [
    'name' => 'hard_delete',
    'title' => 'Hard Delete',
    'description' => 'Whether to permanently delete (true) or just deactivate (false)',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
}

/**
 * PowermapStakeholder.Delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_stakeholder_Delete($params) {
  $hardDelete = !empty($params['hard_delete']);

  if (!empty($params['id'])) {
    // Delete by stakeholder assignment ID
    $stakeholder = new CRM_Powermap_DAO_PowermapStakeholder();
    $stakeholder->id = $params['id'];
    if (!$stakeholder->find(TRUE)) {
      throw new API_Exception('Stakeholder assignment not found');
    }

    // Check permissions
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($stakeholder->powermap_id);
    if (!in_array($permission, ['owner', 'admin', 'edit'])) {
      throw new API_Exception('Insufficient permissions to remove stakeholders from this power map');
    }

    $oldValues = $stakeholder->toArray();

    if ($hardDelete) {
      $stakeholder->delete();
      $newValues = NULL;
      $action = 'delete';
    }
    else {
      $stakeholder->is_active = 0;
      $stakeholder->save();
      $newValues = ['is_active' => 0];
      $action = 'deactivate';
    }

    // Log the action
    CRM_Powermap_BAO_PowermapAuditLog::logAction(
      'civicrm_powermap_stakeholder',
      $params['id'],
      $action,
      $oldValues,
      $newValues
    );

    $result = [$params['id'] => ['id' => $params['id'], 'success' => TRUE]];

  }
  elseif (!empty($params['powermap_id']) && !empty($params['contact_id'])) {
    // Delete by powermap_id and contact_id
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
    if (!in_array($permission, ['owner', 'admin', 'edit'])) {
      throw new API_Exception('Insufficient permissions to remove stakeholders from this power map');
    }

    $results = CRM_Powermap_BAO_PowermapStakeholder::removeStakeholders(
      $params['powermap_id'],
      [$params['contact_id']],
      $hardDelete
    );

    if (!$results[$params['contact_id']]['success']) {
      throw new API_Exception($results[$params['contact_id']]['error']);
    }

    $result = [$params['contact_id'] => $results[$params['contact_id']]];

  }
  else {
    throw new API_Exception('Either id or both powermap_id and contact_id must be provided');
  }

  return civicrm_api3_create_success($result, $params, 'PowermapStakeholder', 'Delete');
}
