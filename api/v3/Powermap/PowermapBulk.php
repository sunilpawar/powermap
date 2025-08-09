<?php
use CRM_Powermap_ExtensionUtil as E;

/**
 * PowermapBulk.Process API specification
 *
 * @param array $spec description of fields supported by this API call
 */
function _civicrm_api3_powermap_bulk_Process_spec(&$spec) {
  $spec['operation'] = [
    'name' => 'operation',
    'title' => 'Bulk Operation',
    'description' => 'Type of bulk operation to perform',
    'type' => CRM_Utils_Type::T_STRING,
    'api.required' => 1,
    'options' => [
      'add_stakeholders' => 'Add Stakeholders to Power Map',
      'remove_stakeholders' => 'Remove Stakeholders from Power Map',
      'update_assessments' => 'Bulk Update Assessments',
      'grant_access' => 'Bulk Grant Access',
      'revoke_access' => 'Bulk Revoke Access',
      'import_from_map' => 'Import from Another Power Map',
      'update_positions' => 'Update Stakeholder Positions',
    ],
  ];
  $spec['powermap_id'] = [
    'name' => 'powermap_id',
    'title' => 'Power Map ID',
    'description' => 'ID of the target power map',
    'type' => CRM_Utils_Type::T_INT,
    'api.required' => 1,
    'FKClassName' => 'CRM_Powermap_DAO_PowermapConfig',
  ];
  $spec['contact_ids'] = [
    'name' => 'contact_ids',
    'title' => 'Contact IDs',
    'description' => 'Array of contact IDs for bulk operations',
    'type' => CRM_Utils_Type::T_STRING,
    'api.multiple' => 1,
  ];
  $spec['source_powermap_id'] = [
    'name' => 'source_powermap_id',
    'title' => 'Source Power Map ID',
    'description' => 'Source power map for import operations',
    'type' => CRM_Utils_Type::T_INT,
    'FKClassName' => 'CRM_Powermap_DAO_PowermapConfig',
  ];
  $spec['assessments'] = [
    'name' => 'assessments',
    'title' => 'Assessment Updates',
    'description' => 'Array of assessment updates (contact_id => assessment_data)',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['access_grants'] = [
    'name' => 'access_grants',
    'title' => 'Access Grants',
    'description' => 'Array of access grants data',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['position_updates'] = [
    'name' => 'position_updates',
    'title' => 'Position Updates',
    'description' => 'Array of position updates (contact_id => {x, y})',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['options'] = [
    'name' => 'options',
    'title' => 'Operation Options',
    'description' => 'Additional options for the operation',
    'type' => CRM_Utils_Type::T_TEXT,
  ];
  $spec['hard_delete'] = [
    'name' => 'hard_delete',
    'title' => 'Hard Delete',
    'description' => 'Whether to permanently delete or just deactivate',
    'type' => CRM_Utils_Type::T_BOOLEAN,
    'api.default' => FALSE,
  ];
}

/**
 * PowermapBulk.Process API
 *
 * @param array $params
 * @return array API result descriptor
 * @throws API_Exception
 */
function civicrm_api3_powermap_bulk_Process($params) {
  $operation = $params['operation'];
  $powermapId = $params['powermap_id'];

  // Check basic permissions
  $permission = CRM_Powermap_BAO_PowermapConfig::checkAccess($powermapId);
  if ($permission === 'none') {
    throw new API_Exception('Insufficient permissions to perform bulk operations on this power map');
  }

  $results = [];

  switch ($operation) {
    case 'add_stakeholders':
      if (!in_array($permission, ['owner', 'admin', 'edit'])) {
        throw new API_Exception('Insufficient permissions to add stakeholders');
      }

      $contactIds = $params['contact_ids'] ?? [];
      if (empty($contactIds)) {
        throw new API_Exception('contact_ids parameter is required for add_stakeholders operation');
      }

      $options = [];
      if (!empty($params['options'])) {
        $options = json_decode($params['options'], TRUE) ?: [];
      }

      $results = CRM_Powermap_BAO_PowermapStakeholder::addStakeholders($powermapId, $contactIds, $options);
      break;

    case 'remove_stakeholders':
      if (!in_array($permission, ['owner', 'admin', 'edit'])) {
        throw new API_Exception('Insufficient permissions to remove stakeholders');
      }

      $contactIds = $params['contact_ids'] ?? [];
      if (empty($contactIds)) {
        throw new API_Exception('contact_ids parameter is required for remove_stakeholders operation');
      }

      $hardDelete = !empty($params['hard_delete']);
      $results = CRM_Powermap_BAO_PowermapStakeholder::removeStakeholders($powermapId, $contactIds, $hardDelete);
      break;

    case 'update_assessments':
      if (!in_array($permission, ['owner', 'admin', 'edit'])) {
        throw new API_Exception('Insufficient permissions to update assessments');
      }

      $assessments = [];
      if (!empty($params['assessments'])) {
        $assessments = json_decode($params['assessments'], TRUE);
        if (!$assessments) {
          throw new API_Exception('Invalid assessments data format');
        }
      }
      else {
        throw new API_Exception('assessments parameter is required for update_assessments operation');
      }

      $results = CRM_Powermap_Utils_Assessment::bulkUpdateAssessments($assessments);
      break;

    case 'grant_access':
      if (!in_array($permission, ['owner', 'admin'])) {
        throw new API_Exception('Insufficient permissions to grant access');
      }

      $accessGrants = [];
      if (!empty($params['access_grants'])) {
        $accessGrants = json_decode($params['access_grants'], TRUE);
        if (!$accessGrants) {
          throw new API_Exception('Invalid access_grants data format');
        }
      }
      else {
        throw new API_Exception('access_grants parameter is required for grant_access operation');
      }

      $results = CRM_Powermap_BAO_PowermapAccess::bulkGrantAccess($powermapId, $accessGrants);
      break;

    case 'revoke_access':
      if (!in_array($permission, ['owner', 'admin'])) {
        throw new API_Exception('Insufficient permissions to revoke access');
      }

      $contactIds = $params['contact_ids'] ?? [];
      if (empty($contactIds)) {
        throw new API_Exception('contact_ids parameter is required for revoke_access operation');
      }

      $results = [];
      foreach ($contactIds as $contactId) {
        try {
          CRM_Powermap_BAO_PowermapAccess::revokeAccess($powermapId, $contactId, NULL);
          $results[$contactId] = ['success' => TRUE];
        }
        catch (Exception $e) {
          $results[$contactId] = ['success' => FALSE, 'error' => $e->getMessage()];
        }
      }
      break;

    case 'import_from_map':
      if (!in_array($permission, ['owner', 'admin', 'edit'])) {
        throw new API_Exception('Insufficient permissions to import stakeholders');
      }

      $sourcePowermapId = $params['source_powermap_id'] ?? NULL;
      if (!$sourcePowermapId) {
        throw new API_Exception('source_powermap_id parameter is required for import_from_map operation');
      }

      $options = [];
      if (!empty($params['options'])) {
        $options = json_decode($params['options'], TRUE) ?: [];
      }

      $results = CRM_Powermap_BAO_PowermapStakeholder::importFromPowerMap($sourcePowermapId, $powermapId, $options);
      break;

    case 'update_positions':
      if (!in_array($permission, ['owner', 'admin', 'edit'])) {
        throw new API_Exception('Insufficient permissions to update positions');
      }

      $positionUpdates = [];
      if (!empty($params['position_updates'])) {
        $positionUpdates = json_decode($params['position_updates'], TRUE);
        if (!$positionUpdates) {
          throw new API_Exception('Invalid position_updates data format');
        }
      }
      else {
        throw new API_Exception('position_updates parameter is required for update_positions operation');
      }

      $results = CRM_Powermap_BAO_PowermapStakeholder::bulkUpdatePositions($powermapId, $positionUpdates);
      break;

    default:
      throw new API_Exception('Unknown operation: ' . $operation);
  }

  // Log the bulk operation
  CRM_Powermap_BAO_PowermapAuditLog::logAction(
    'civicrm_powermap_config',
    $powermapId,
    'bulk_' . $operation,
    NULL,
    [
      'operation' => $operation,
      'count' => count($results),
      'success_count' => count(array_filter($results, function ($r) {
        return $r['success'] ?? FALSE;
      }))
    ]
  );

  // Calculate summary statistics
  $summary = [
    'operation' => $operation,
    'powermap_id' => $powermapId,
    'total_processed' => count($results),
    'successful' => 0,
    'failed' => 0,
    'errors' => []
  ];

  foreach ($results as $id => $result) {
    if ($result['success'] ?? FALSE) {
      $summary['successful']++;
    }
    else {
      $summary['failed']++;
      if (!empty($result['error'])) {
        $summary['errors'][] = "ID {$id}: " . $result['error'];
      }
    }
  }

  return civicrm_api3_create_success([
    'summary' => $summary,
    'results' => $results
  ], $params, 'PowermapBulk', 'Process');
}
