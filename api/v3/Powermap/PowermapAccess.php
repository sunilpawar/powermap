<?php
use CRM_Powermap_ExtensionUtil as E;

/**
 * PowermapAccess.Create API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_access_Create_spec(&$spec) {
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
    'description' => 'ID of the contact for individual access',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Contact',
  ];
  $spec['group_id'] = [
    'name' => 'group_id',
    'title' => 'Group ID',
    'description' => 'ID of the group for group access',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Group',
  ];
  $spec['permission_level'] = [
    'name' => 'permission_level',
    'title' => 'Permission Level',
    'description' => 'Level of permission granted',
    'type' => CRM_Utils_Type::T_STRING,
    'api.default' => 'view',
    'options' => CRM_Powermap_BAO_PowermapAccess::getPermissionLevelOptions(),
  ];
}

/**
 * PowermapAccess.Create API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_access_Create($params) {
  // Validate that either contact_id or group_id is provided
  if (empty($params['contact_id']) && empty($params['group_id'])) {
    throw new API_Exception('Either contact_id or group_id must be provided');
  }

  if (!empty($params['contact_id']) && !empty($params['group_id'])) {
    throw new API_Exception('Cannot specify both contact_id and group_id');
  }

  // Check permissions
  $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
  if (!in_array($permission, ['owner', 'admin'])) {
    throw new API_Exception('Insufficient permissions to grant access to this power map');
  }

  // Use BAO method to grant access
  $accessParams = [
    'permission_level' => $params['permission_level'] ?? 'view',
  ];

  if (!empty($params['contact_id'])) {
    $accessParams['contact_id'] = $params['contact_id'];
  }

  if (!empty($params['group_id'])) {
    $accessParams['group_id'] = $params['group_id'];
  }

  $access = CRM_Powermap_BAO_PowermapAccess::grantAccess($params['powermap_id'], $accessParams);

  if (!$access) {
    throw new API_Exception('Failed to grant access to power map');
  }

  return civicrm_api3_create_success([$access->id => $access->toArray()], $params, 'PowermapAccess', 'Create');
}

/**
 * PowermapAccess.Get API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_access_Get_spec(&$spec) {
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
  $spec['group_id'] = [
    'name' => 'group_id',
    'title' => 'Group ID',
    'description' => 'Filter by group',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Contact_DAO_Group',
  ];
  $spec['permission_level'] = [
    'name' => 'permission_level',
    'title' => 'Permission Level',
    'description' => 'Filter by permission level',
    'type' => CRM_Utils_Type::T_STRING,
    'options' => CRM_Powermap_BAO_PowermapAccess::getPermissionLevelOptions(),
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
 * PowermapAccess.Get API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_access_Get($params) {
  $result = [];

  if (!empty($params['powermap_id'])) {
    // Check permissions for specific power map
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
    if (!in_array($permission, ['owner', 'admin'])) {
      throw new API_Exception('Insufficient permissions to view access list for this power map');
    }

    // Get access list for the power map
    $accessList = CRM_Powermap_BAO_PowermapAccess::getAccessList($params['powermap_id']);

    foreach ($accessList as $access) {
      // Apply filters
      if (!empty($params['contact_id']) && $access['contact_id'] != $params['contact_id']) {
        continue;
      }
      if (!empty($params['group_id']) && $access['group_id'] != $params['group_id']) {
        continue;
      }
      if (!empty($params['permission_level']) && $access['permission_level'] != $params['permission_level']) {
        continue;
      }
      if (isset($params['is_active']) && $access['is_active'] != $params['is_active']) {
        continue;
      }

      $result[$access['id']] = $access;
    }

  }
  elseif (!empty($params['contact_id'])) {
    // Get all power maps accessible by specific contact
    $contactId = $params['contact_id'];
    $maps = CRM_Powermap_BAO_PowermapConfig::getAccessibleMaps($contactId);

    foreach ($maps as $map) {
      $accessLevel = CRM_Powermap_BAO_PowermapAccess::getContactAccess($map['id'], $contactId);
      if ($accessLevel) {
        $accessRecord = [
          'id' => $map['id'] . '_' . $contactId, // Synthetic ID
          'powermap_id' => $map['id'],
          'powermap_name' => $map['name'],
          'contact_id' => $contactId,
          'permission_level' => $accessLevel,
          'type' => 'computed'
        ];

        // Apply filters
        if (!empty($params['permission_level']) && $accessRecord['permission_level'] != $params['permission_level']) {
          continue;
        }

        $result[$accessRecord['id']] = $accessRecord;
      }
    }

  }
  else {
    // Get all access records user can see (only owners/admins of maps)
    $contactId = CRM_Core_Session::getLoggedInContactID();
    $maps = CRM_Powermap_BAO_PowermapConfig::getAccessibleMaps($contactId);

    foreach ($maps as $map) {
      $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($map['id']);
      if (in_array($permission, ['owner', 'admin'])) {
        $accessList = CRM_Powermap_BAO_PowermapAccess::getAccessList($map['id']);

        foreach ($accessList as $access) {
          // Apply filters
          if (!empty($params['contact_id']) && $access['contact_id'] != $params['contact_id']) {
            continue;
          }
          if (!empty($params['group_id']) && $access['group_id'] != $params['group_id']) {
            continue;
          }
          if (!empty($params['permission_level']) && $access['permission_level'] != $params['permission_level']) {
            continue;
          }
          if (isset($params['is_active']) && $access['is_active'] != $params['is_active']) {
            continue;
          }

          $access['powermap_name'] = $map['name'];
          $result[$access['id']] = $access;
        }
      }
    }
  }

  // Apply API standard options
  $options = _civicrm_api3_get_options_from_params($params);

  if (!empty($options['offset'])) {
    $result = array_slice($result, $options['offset'], NULL, TRUE);
  }

  if (!empty($options['limit'])) {
    $result = array_slice($result, 0, $options['limit'], TRUE);
  }

  return civicrm_api3_create_success($result, $params, 'PowermapAccess', 'Get');
}

/**
 * PowermapAccess.Delete API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_access_Delete_spec(&$spec) {
  $spec['id'] = [
    'name' => 'id',
    'title' => 'Access Record ID',
    'description' => 'ID of the access record to delete',
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
    'description' => 'ID of the contact to revoke access from',
    'type' => CRM_Utils_Type::T_INT,
  ];
  $spec['group_id'] = [
    'name' => 'group_id',
    'title' => 'Group ID',
    'description' => 'ID of the group to revoke access from',
    'type' => CRM_Utils_Type::T_INT,
  ];
}

/**
 * PowermapAccess.Delete API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_access_Delete($params) {
  if (!empty($params['id'])) {
    // Delete by access record ID
    $access = new CRM_Powermap_DAO_PowermapAccess();
    $access->id = $params['id'];
    if (!$access->find(TRUE)) {
      throw new API_Exception('Access record not found');
    }

    // Check permissions
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($access->powermap_id);
    if (!in_array($permission, ['owner', 'admin'])) {
      throw new API_Exception('Insufficient permissions to revoke access to this power map');
    }

    $oldValues = $access->toArray();

    // Soft delete by setting is_active = 0
    $access->is_active = 0;
    $access->save();

    // Log the action
    CRM_Powermap_BAO_PowermapAuditLog::logAction(
      'civicrm_powermap_access',
      $params['id'],
      'revoke',
      $oldValues,
      ['is_active' => 0]
    );

    $result = [$params['id'] => ['id' => $params['id'], 'success' => TRUE]];

  }
  elseif (!empty($params['powermap_id'])) {
    // Revoke access using powermap_id and contact_id/group_id
    $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($params['powermap_id']);
    if (!in_array($permission, ['owner', 'admin'])) {
      throw new API_Exception('Insufficient permissions to revoke access to this power map');
    }

    $contactId = !empty($params['contact_id']) ? $params['contact_id'] : NULL;
    $groupId = !empty($params['group_id']) ? $params['group_id'] : NULL;

    if (!$contactId && !$groupId) {
      throw new API_Exception('Either contact_id or group_id must be provided');
    }

    $success = CRM_Powermap_BAO_PowermapAccess::revokeAccess(
      $params['powermap_id'],
      $contactId,
      $groupId
    );

    if (!$success) {
      throw new API_Exception('Failed to revoke access');
    }

    $resultId = $params['powermap_id'] . '_' . ($contactId ?? $groupId);
    $result = [$resultId => ['id' => $resultId, 'success' => TRUE]];

  }
  else {
    throw new API_Exception('Either id or powermap_id with contact_id/group_id must be provided');
  }

  return civicrm_api3_create_success($result, $params, 'PowermapAccess', 'Delete');
}
