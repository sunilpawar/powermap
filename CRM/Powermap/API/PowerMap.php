<?php

class CRM_Powermap_API_PowerMap {

  /**
   * Get custom field information with caching
   *
   * @param string $fieldName Name of the custom field
   * @return array|null Custom field information or null if not found
   */
  public static function getCustomFieldInfo($fieldName) {
    static $cache = [];

    if (isset($cache[$fieldName])) {
      return $cache[$fieldName];
    }

    try {
      $result = civicrm_api3('CustomField', 'getsingle', [
        'name' => $fieldName,
      ]);

      $groupResult = civicrm_api3('CustomGroup', 'getsingle', [
        'id' => $result['custom_group_id'],
      ]);

      $cache[$fieldName] = [
        'table_name' => $groupResult['table_name'],
        'column_name' => $result['column_name'],
        'column_id' => $result['id'],
        'column_field' => 'custom_' . $result['id'],
      ];

      return $cache[$fieldName];
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Get network data for visualization using the common service
   *
   * This method now delegates to the NetworkDataService for consistent data handling
   * across API and page requests.
   *
   * @param array $params Parameters including:
   *   - group_id: Filter by CiviCRM group
   *   - contact_id: Specific contact IDs (array or comma-separated string)
   *   - only_relationship: Show only contacts with relationships
   *   - influence_min: Minimum influence level (1-5)
   *   - support_min: Minimum support level (1-5)
   *   - relationship_types: Array of relationship type IDs to include
   * @return array Network data structure with nodes, links, stats, and metadata
   */
  public static function getNetworkData($params = []) {
    return CRM_Powermap_Service_NetworkDataService::getNetworkData($params);
  }

  /**
   * Export network data to CSV format using the common service
   *
   * @param array $params Parameters for filtering (same as getNetworkData)
   * @return array CSV data ready for download
   */
  public static function exportToCSV($params = []) {
    return CRM_Powermap_Service_NetworkDataService::exportToCSV($params);
  }

  /**
   * Get available relationship types
   *
   * @return array List of active relationship types with their labels
   */
  public static function getRelationshipTypes() {
    try {
      $result = civicrm_api3('RelationshipType', 'get', [
        'sequential' => 1,
        'is_active' => 1,
        'options' => ['limit' => 0],
        'return' => ['id', 'label_a_b', 'label_b_a', 'name_a_b']
      ]);

      $types = [];
      foreach ($result['values'] as $type) {
        $types[] = [
          'id' => (int)$type['id'],
          'label' => $type['label_a_b'],
          'reverse_label' => CRM_Utils_Array::value('label_b_a', $type, ''),
          'name' => CRM_Utils_Array::value('name_a_b', $type, '')
        ];
      }

      return $types;
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error fetching relationship types: ' . $e->getMessage());
      return [];
    }
  }

  /**
   * Get network analysis data with advanced metrics
   *
   * @param array $params Parameters for filtering
   * @return array Comprehensive network analysis including centrality measures
   */
  public static function getNetworkAnalysis($params = []) {
    $data = CRM_Powermap_Service_NetworkDataService::getNetworkData($params);

    // Calculate centrality measures using the BAO class
    $centrality = CRM_Powermap_BAO_PowerMapAnalysis::calculateNetworkCentrality();

    // Get key influencers
    $keyInfluencers = CRM_Powermap_BAO_PowerMapAnalysis::identifyKeyInfluencers(10);

    // Get network statistics
    $networkStats = CRM_Powermap_BAO_PowerMapAnalysis::getNetworkStatistics();

    return [
      'network_data' => $data,
      'centrality_measures' => $centrality,
      'key_influencers' => $keyInfluencers,
      'network_statistics' => $networkStats,
      'analysis_metadata' => [
        'analysis_date' => date('Y-m-d H:i:s'),
        'total_nodes_analyzed' => count($data['nodes']),
        'total_edges_analyzed' => count($data['links']),
        'filters_applied' => $params
      ]
    ];
  }

  /**
   * Update stakeholder information
   *
   * @param array $params Parameters:
   *   - contact_id: Required. Contact ID to update
   *   - influence_level: Influence level (1-5)
   *   - support_level: Support level (1-5)
   *   - notes: PowerMap notes
   *   - network_position: Network position classification
   * @return array Update result
   * @throws Exception if contact_id is missing or update fails
   */
  public static function updateStakeholder($params = []) {
    $contactId = CRM_Utils_Array::value('contact_id', $params);
    $influence = CRM_Utils_Array::value('influence_level', $params);
    $support = CRM_Utils_Array::value('support_level', $params);
    $notes = CRM_Utils_Array::value('notes', $params);
    $networkPosition = CRM_Utils_Array::value('network_position', $params);

    if (empty($contactId)) {
      throw new Exception('Contact ID is required');
    }

    try {
      // Verify contact exists and is not deleted
      $contactCheck = civicrm_api3('Contact', 'get', [
        'id' => $contactId,
        'is_deleted' => 0,
        'return' => ['id']
      ]);

      if ($contactCheck['count'] == 0) {
        throw new Exception('Contact not found or is deleted');
      }

      $updateParams = ['id' => $contactId];

      // Get custom field information
      $customFields = [
        'influence_level' => self::getCustomFieldInfo('influence_level'),
        'support_level' => self::getCustomFieldInfo('support_level'),
        'powermap_notes' => self::getCustomFieldInfo('powermap_notes'),
        'network_position' => self::getCustomFieldInfo('network_position'),
      ];

      // Add custom field updates if provided and fields exist
      if ($influence !== NULL && $customFields['influence_level']) {
        $influence = max(1, min(5, (int)$influence)); // Validate range
        $updateParams[$customFields['influence_level']['column_field']] = $influence;
      }

      if ($support !== NULL && $customFields['support_level']) {
        $support = max(1, min(5, (int)$support)); // Validate range
        $updateParams[$customFields['support_level']['column_field']] = $support;
      }

      if ($notes !== NULL && $customFields['powermap_notes']) {
        $updateParams[$customFields['powermap_notes']['column_field']] = $notes;
      }

      if ($networkPosition !== NULL && $customFields['network_position']) {
        $validPositions = ['core', 'inner', 'outer', 'peripheral'];
        if (in_array($networkPosition, $validPositions)) {
          $updateParams[$customFields['network_position']['column_field']] = $networkPosition;
        }
      }

      $result = civicrm_api3('Contact', 'create', $updateParams);

      return [
        'contact_id' => $contactId,
        'updated_fields' => array_keys($updateParams),
        'success' => TRUE,
        'updated_at' => date('Y-m-d H:i:s')
      ];

    }
    catch (Exception $e) {
      throw new Exception('Failed to update stakeholder: ' . $e->getMessage());
    }
  }

  /**
   * Create relationship between stakeholders
   *
   * @param array $params Parameters:
   *   - contact_id_a: Required. Source contact ID
   *   - contact_id_b: Required. Target contact ID
   *   - relationship_type_id: Required. Relationship type ID
   *   - strength: Relationship strength (1-3, default 2)
   *   - start_date: Relationship start date (optional)
   *   - end_date: Relationship end date (optional)
   *   - description: Relationship description (optional)
   * @return array Creation result
   * @throws Exception if required parameters missing or creation fails
   */
  public static function createRelationship($params = []) {
    $contactIdA = CRM_Utils_Array::value('contact_id_a', $params);
    $contactIdB = CRM_Utils_Array::value('contact_id_b', $params);
    $relationshipTypeId = CRM_Utils_Array::value('relationship_type_id', $params);
    $strength = CRM_Utils_Array::value('strength', $params, 2);
    $startDate = CRM_Utils_Array::value('start_date', $params);
    $endDate = CRM_Utils_Array::value('end_date', $params);
    $description = CRM_Utils_Array::value('description', $params);

    if (empty($contactIdA) || empty($contactIdB) || empty($relationshipTypeId)) {
      throw new Exception('Contact IDs and relationship type are required');
    }

    if ($contactIdA == $contactIdB) {
      throw new Exception('Cannot create relationship between same contact');
    }

    try {
      // Verify both contacts exist
      $contactsCheck = civicrm_api3('Contact', 'get', [
        'id' => ['IN' => [$contactIdA, $contactIdB]],
        'is_deleted' => 0,
        'return' => ['id']
      ]);

      if ($contactsCheck['count'] != 2) {
        throw new Exception('One or both contacts not found or are deleted');
      }

      // Verify relationship type exists
      $relTypeCheck = civicrm_api3('RelationshipType', 'get', [
        'id' => $relationshipTypeId,
        'is_active' => 1,
        'return' => ['id']
      ]);

      if ($relTypeCheck['count'] == 0) {
        throw new Exception('Relationship type not found or is inactive');
      }

      // Check if relationship already exists
      $existing = civicrm_api3('Relationship', 'get', [
        'contact_id_a' => $contactIdA,
        'contact_id_b' => $contactIdB,
        'relationship_type_id' => $relationshipTypeId,
        'is_active' => 1
      ]);

      if ($existing['count'] > 0) {
        throw new Exception('Active relationship already exists between these contacts');
      }

      // Create the relationship
      $relationshipParams = [
        'contact_id_a' => $contactIdA,
        'contact_id_b' => $contactIdB,
        'relationship_type_id' => $relationshipTypeId,
        'is_active' => 1,
      ];

      // Add optional parameters
      if ($startDate) {
        $relationshipParams['start_date'] = $startDate;
      }
      else {
        $relationshipParams['start_date'] = date('Y-m-d');
      }

      if ($endDate) {
        $relationshipParams['end_date'] = $endDate;
      }

      if ($description) {
        $relationshipParams['description'] = $description;
      }

      $relationship = civicrm_api3('Relationship', 'create', $relationshipParams);

      // Try to set strength if custom field exists
      if ($relationship['id']) {
        $strengthField = self::getCustomFieldInfo('relationship_strength');
        if ($strengthField) {
          try {
            $strength = max(1, min(3, (int)$strength)); // Validate range
            civicrm_api3('Relationship', 'create', [
              'id' => $relationship['id'],
              $strengthField['column_field'] => $strength
            ]);
          }
          catch (Exception $e) {
            // Log warning but don't fail the relationship creation
            CRM_Core_Error::debug_log_message('Could not set relationship strength: ' . $e->getMessage());
          }
        }
      }

      return [
        'relationship_id' => $relationship['id'],
        'contact_id_a' => $contactIdA,
        'contact_id_b' => $contactIdB,
        'relationship_type_id' => $relationshipTypeId,
        'strength' => $strength,
        'success' => TRUE,
        'created_at' => date('Y-m-d H:i:s')
      ];

    }
    catch (Exception $e) {
      throw new Exception('Failed to create relationship: ' . $e->getMessage());
    }
  }

  /**
   * Delete relationship
   *
   * @param array $params Parameters:
   *   - relationship_id: Required. Relationship ID to delete
   * @return array Deletion result
   * @throws Exception if relationship_id missing or deletion fails
   */
  public static function deleteRelationship($params = []) {
    $relationshipId = CRM_Utils_Array::value('relationship_id', $params);

    if (empty($relationshipId)) {
      throw new Exception('Relationship ID is required');
    }

    try {
      // Verify relationship exists
      $relCheck = civicrm_api3('Relationship', 'get', [
        'id' => $relationshipId,
        'return' => ['id', 'contact_id_a', 'contact_id_b']
      ]);

      if ($relCheck['count'] == 0) {
        throw new Exception('Relationship not found');
      }

      $relationship = $relCheck['values'][$relationshipId];

      $result = civicrm_api3('Relationship', 'delete', [
        'id' => $relationshipId
      ]);

      return [
        'relationship_id' => $relationshipId,
        'contact_id_a' => $relationship['contact_id_a'],
        'contact_id_b' => $relationship['contact_id_b'],
        'deleted' => TRUE,
        'success' => TRUE,
        'deleted_at' => date('Y-m-d H:i:s')
      ];

    }
    catch (Exception $e) {
      throw new Exception('Failed to delete relationship: ' . $e->getMessage());
    }
  }

  /**
   * Get contact suggestions for stakeholder selection
   *
   * @param array $params Parameters:
   *   - search_term: Search term for contact names
   *   - contact_types: Array of contact types to include (default: Individual, Organization)
   *   - limit: Maximum number of results (default: 20)
   *   - group_id: Optional group ID to filter contacts
   * @return array Contact suggestions with metadata
   */
  public static function getContactSuggestions($params = []) {
    $searchTerm = CRM_Utils_Array::value('search_term', $params, '');
    $contactTypes = CRM_Utils_Array::value('contact_types', $params, ['Individual', 'Organization']);
    $limit = CRM_Utils_Array::value('limit', $params, 20);
    $groupId = CRM_Utils_Array::value('group_id', $params);

    try {
      $contactParams = [
        'sequential' => 1,
        'is_deleted' => 0,
        'contact_type' => ['IN' => $contactTypes],
        'options' => ['limit' => $limit, 'sort' => 'display_name ASC'],
        'return' => ['id', 'display_name', 'contact_type', 'contact_sub_type', 'email']
      ];

      if (!empty($searchTerm)) {
        $contactParams['display_name'] = ['LIKE' => '%' . $searchTerm . '%'];
      }

      // Filter by group if specified
      if (!empty($groupId)) {
        // Get contacts in the group
        $groupContacts = civicrm_api3('GroupContact', 'get', [
          'group_id' => $groupId,
          'status' => 'Added',
          'return' => ['contact_id']
        ]);

        if ($groupContacts['count'] > 0) {
          $groupContactIds = array_column($groupContacts['values'], 'contact_id');
          $contactParams['id'] = ['IN' => $groupContactIds];
        }
        else {
          // No contacts in group
          return [
            'suggestions' => [],
            'total' => 0,
            'search_term' => $searchTerm,
            'group_id' => $groupId
          ];
        }
      }

      $result = civicrm_api3('Contact', 'get', $contactParams);

      $suggestions = [];
      foreach ($result['values'] as $contact) {
        $suggestions[] = [
          'id' => (int)$contact['id'],
          'name' => $contact['display_name'],
          'type' => $contact['contact_type'],
          'subtype' => CRM_Utils_Array::value('contact_sub_type', $contact, ''),
          'email' => CRM_Utils_Array::value('email', $contact, ''),
          'label' => $contact['display_name'] . ' (' . $contact['contact_type'] . ')'
        ];
      }

      return [
        'suggestions' => $suggestions,
        'total' => $result['count'],
        'search_term' => $searchTerm,
        'group_id' => $groupId,
        'contact_types' => $contactTypes
      ];

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting contact suggestions: ' . $e->getMessage());
      return [
        'suggestions' => [],
        'total' => 0,
        'error' => $e->getMessage(),
        'search_term' => $searchTerm
      ];
    }
  }

  /**
   * Validate data integrity across the PowerMap system
   *
   * @param array $params Parameters (currently unused, reserved for future filters)
   * @return array Validation results with issues and statistics
   */
  public static function validateData($params = []) {
    $issues = [];
    $stats = [
      'total_contacts' => 0,
      'contacts_without_influence' => 0,
      'contacts_without_support' => 0,
      'orphaned_relationships' => 0,
      'duplicate_relationships' => 0,
      'inactive_relationships' => 0,
      'missing_relationship_types' => 0
    ];

    try {
      // Get all contacts
      $contacts = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'is_deleted' => 0,
        'options' => ['limit' => 0],
        'return' => ['id', 'display_name']
      ]);

      $stats['total_contacts'] = $contacts['count'];
      $contactIds = array_column($contacts['values'], 'id');

      // Get custom field information
      $customFields = [
        'influence_level' => self::getCustomFieldInfo('influence_level'),
        'support_level' => self::getCustomFieldInfo('support_level'),
      ];

      // Check for missing custom field values
      foreach ($contacts['values'] as $contact) {
        if ($customFields['influence_level']) {
          $influence = self::getCustomFieldValueForContact($contact['id'], 'influence_level');
          if ($influence === NULL) {
            $stats['contacts_without_influence']++;
            $issues[] = [
              'type' => 'missing_influence',
              'contact_id' => $contact['id'],
              'contact_name' => $contact['display_name'],
              'message' => 'Missing influence level',
              'severity' => 'warning'
            ];
          }
        }

        if ($customFields['support_level']) {
          $support = self::getCustomFieldValueForContact($contact['id'], 'support_level');
          if ($support === NULL) {
            $stats['contacts_without_support']++;
            $issues[] = [
              'type' => 'missing_support',
              'contact_id' => $contact['id'],
              'contact_name' => $contact['display_name'],
              'message' => 'Missing support level',
              'severity' => 'warning'
            ];
          }
        }
      }

      // Check relationships
      $relationships = civicrm_api3('Relationship', 'get', [
        'sequential' => 1,
        'options' => ['limit' => 0],
        'return' => ['id', 'contact_id_a', 'contact_id_b', 'relationship_type_id', 'is_active']
      ]);

      $relationshipMap = [];
      $activeRelationshipTypes = [];

      foreach ($relationships['values'] as $rel) {
        // Check for orphaned relationships
        if (!in_array($rel['contact_id_a'], $contactIds) || !in_array($rel['contact_id_b'], $contactIds)) {
          $stats['orphaned_relationships']++;
          $issues[] = [
            'type' => 'orphaned_relationship',
            'relationship_id' => $rel['id'],
            'message' => 'Relationship references deleted contact(s)',
            'severity' => 'error'
          ];
        }

        // Check for inactive relationships
        if (!$rel['is_active']) {
          $stats['inactive_relationships']++;
        }

        // Only process active relationships for duplicate checking
        if ($rel['is_active']) {
          // Check for duplicates
          $key = $rel['contact_id_a'] . '-' . $rel['contact_id_b'] . '-' . $rel['relationship_type_id'];
          $reverseKey = $rel['contact_id_b'] . '-' . $rel['contact_id_a'] . '-' . $rel['relationship_type_id'];

          if (isset($relationshipMap[$key]) || isset($relationshipMap[$reverseKey])) {
            $stats['duplicate_relationships']++;
            $issues[] = [
              'type' => 'duplicate_relationship',
              'relationship_id' => $rel['id'],
              'duplicate_of' => $relationshipMap[$key] ?? $relationshipMap[$reverseKey],
              'message' => 'Duplicate relationship found',
              'severity' => 'warning'
            ];
          }

          $relationshipMap[$key] = $rel['id'];
          $activeRelationshipTypes[] = $rel['relationship_type_id'];
        }
      }

      // Check for missing relationship types
      $uniqueRelTypes = array_unique($activeRelationshipTypes);
      foreach ($uniqueRelTypes as $relTypeId) {
        try {
          $relType = civicrm_api3('RelationshipType', 'getsingle', [
            'id' => $relTypeId,
            'return' => ['id', 'is_active']
          ]);

          if (!$relType['is_active']) {
            $stats['missing_relationship_types']++;
            $issues[] = [
              'type' => 'missing_relationship_type',
              'relationship_type_id' => $relTypeId,
              'message' => 'Relationship type is inactive but has active relationships',
              'severity' => 'error'
            ];
          }
        }
        catch (Exception $e) {
          $stats['missing_relationship_types']++;
          $issues[] = [
            'type' => 'missing_relationship_type',
            'relationship_type_id' => $relTypeId,
            'message' => 'Relationship type not found',
            'severity' => 'error'
          ];
        }
      }

      // Calculate data quality score
      $totalIssues = count($issues);
      $criticalIssues = count(array_filter($issues, function ($issue) {
        return $issue['severity'] === 'error';
      }));
      $dataQualityScore = $stats['total_contacts'] > 0 ?
        max(0, 100 - (($criticalIssues * 10) + (($totalIssues - $criticalIssues) * 2))) : 100;

      return [
        'validation_stats' => $stats,
        'issues' => $issues,
        'total_issues' => $totalIssues,
        'critical_issues' => $criticalIssues,
        'data_quality_score' => round($dataQualityScore, 1),
        'validation_date' => date('Y-m-d H:i:s'),
        'recommendations' => self::generateDataQualityRecommendations($stats, $issues)
      ];

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error validating PowerMap data: ' . $e->getMessage());
      return [
        'validation_stats' => $stats,
        'issues' => [],
        'total_issues' => 0,
        'error' => $e->getMessage(),
        'validation_date' => date('Y-m-d H:i:s')
      ];
    }
  }

  /**
   * Generate data quality recommendations based on validation results
   *
   * @param array $stats Validation statistics
   * @param array $issues Found issues
   * @return array Recommendations for improving data quality
   */
  private static function generateDataQualityRecommendations($stats, $issues) {
    $recommendations = [];

    if ($stats['contacts_without_influence'] > 0) {
      $recommendations[] = [
        'type' => 'missing_data',
        'priority' => 'medium',
        'message' => "Set influence levels for {$stats['contacts_without_influence']} contacts to improve network analysis accuracy"
      ];
    }

    if ($stats['contacts_without_support'] > 0) {
      $recommendations[] = [
        'type' => 'missing_data',
        'priority' => 'medium',
        'message' => "Set support levels for {$stats['contacts_without_support']} contacts to improve stakeholder mapping"
      ];
    }

    if ($stats['orphaned_relationships'] > 0) {
      $recommendations[] = [
        'type' => 'data_cleanup',
        'priority' => 'high',
        'message' => "Clean up {$stats['orphaned_relationships']} orphaned relationships to prevent visualization errors"
      ];
    }

    if ($stats['duplicate_relationships'] > 0) {
      $recommendations[] = [
        'type' => 'data_cleanup',
        'priority' => 'medium',
        'message' => "Remove {$stats['duplicate_relationships']} duplicate relationships to avoid network distortion"
      ];
    }

    if ($stats['missing_relationship_types'] > 0) {
      $recommendations[] = [
        'type' => 'configuration',
        'priority' => 'high',
        'message' => "Fix {$stats['missing_relationship_types']} missing or inactive relationship types"
      ];
    }

    // Add general recommendations based on data volume
    if ($stats['total_contacts'] < 10) {
      $recommendations[] = [
        'type' => 'data_expansion',
        'priority' => 'low',
        'message' => 'Consider adding more contacts to create a meaningful network visualization'
      ];
    }

    return $recommendations;
  }

  /**
   * Helper method to get custom field value for a specific contact
   *
   * @param int $contactId Contact ID
   * @param string $fieldName Custom field name
   * @return mixed|null Field value or null if not found
   */
  private static function getCustomFieldValueForContact($contactId, $fieldName) {
    try {
      $fieldInfo = self::getCustomFieldInfo($fieldName);
      if (!$fieldInfo) {
        return NULL;
      }

      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $contactId,
        'return' => [$fieldInfo['column_field']]
      ]);

      if (!empty($result['values'][0][$fieldInfo['column_field']])) {
        return $result['values'][0][$fieldInfo['column_field']];
      }

      return NULL;
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Get PowerMap system information and statistics
   *
   * @return array System information including version, configuration, and health status
   */
  public static function getSystemInfo() {
    try {
      // Get extension info
      $extensionInfo = civicrm_api3('Extension', 'get', [
        'key' => 'com.skvare.powermap',
        'return' => ['version', 'status']
      ]);

      // Get custom field status
      $customFields = [
        'influence_level' => self::getCustomFieldInfo('influence_level'),
        'support_level' => self::getCustomFieldInfo('support_level'),
        'powermap_notes' => self::getCustomFieldInfo('powermap_notes'),
        'network_position' => self::getCustomFieldInfo('network_position'),
        'relationship_strength' => self::getCustomFieldInfo('relationship_strength'),
      ];

      $customFieldStatus = [];
      foreach ($customFields as $fieldName => $fieldInfo) {
        $customFieldStatus[$fieldName] = [
          'exists' => $fieldInfo !== NULL,
          'field_id' => $fieldInfo ? $fieldInfo['column_id'] : NULL,
          'table_name' => $fieldInfo ? $fieldInfo['table_name'] : NULL
        ];
      }

      // Get basic statistics
      $contactCount = civicrm_api3('Contact', 'getcount', ['is_deleted' => 0]);
      $relationshipCount = civicrm_api3('Relationship', 'getcount', ['is_active' => 1]);
      $relationshipTypeCount = civicrm_api3('RelationshipType', 'getcount', ['is_active' => 1]);

      return [
        'extension_info' => $extensionInfo['values'] ?? [],
        'custom_fields' => $customFieldStatus,
        'system_statistics' => [
          'total_contacts' => $contactCount,
          'total_relationships' => $relationshipCount,
          'relationship_types' => $relationshipTypeCount,
          'last_checked' => date('Y-m-d H:i:s')
        ],
        'system_health' => [
          'custom_fields_configured' => count(array_filter($customFieldStatus, function ($field) {
            return $field['exists'];
          })),
          'total_custom_fields' => count($customFieldStatus),
          'configuration_complete' => count(array_filter($customFieldStatus, function ($field) {
              return $field['exists'];
            })) >= 3
        ]
      ];

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting PowerMap system info: ' . $e->getMessage());
      return [
        'error' => $e->getMessage(),
        'last_checked' => date('Y-m-d H:i:s'),
        'system_health' => [
          'status' => 'error',
          'configuration_complete' => FALSE
        ]
      ];
    }
  }

  /**
   * Bulk update multiple stakeholders
   *
   * @param array $params Parameters:
   *   - updates: Array of update objects, each containing contact_id and fields to update
   *   - validate_contacts: Whether to validate contact existence (default: true)
   * @return array Bulk update results
   */
  public static function bulkUpdateStakeholders($params = []) {
    $updates = CRM_Utils_Array::value('updates', $params, []);
    $validateContacts = CRM_Utils_Array::value('validate_contacts', $params, TRUE);

    if (empty($updates) || !is_array($updates)) {
      throw new Exception('Updates array is required');
    }

    $results = [
      'successful' => [],
      'failed' => [],
      'total_processed' => 0,
      'total_successful' => 0,
      'total_failed' => 0
    ];

    foreach ($updates as $index => $update) {
      $results['total_processed']++;

      try {
        if (empty($update['contact_id'])) {
          throw new Exception('Contact ID is required for update at index ' . $index);
        }

        // Validate contact exists if requested
        if ($validateContacts) {
          $contactCheck = civicrm_api3('Contact', 'getcount', [
            'id' => $update['contact_id'],
            'is_deleted' => 0
          ]);

          if ($contactCheck == 0) {
            throw new Exception('Contact not found or is deleted');
          }
        }

        $updateResult = self::updateStakeholder($update);
        $results['successful'][] = $updateResult;
        $results['total_successful']++;

      }
      catch (Exception $e) {
        $results['failed'][] = [
          'contact_id' => $update['contact_id'] ?? 'unknown',
          'index' => $index,
          'error' => $e->getMessage(),
          'failed_at' => date('Y-m-d H:i:s')
        ];
        $results['total_failed']++;
      }
    }

    $results['success_rate'] = $results['total_processed'] > 0 ?
      round(($results['total_successful'] / $results['total_processed']) * 100, 2) : 0;

    return $results;
  }

  /**
   * Bulk create multiple relationships
   *
   * @param array $params Parameters:
   *   - relationships: Array of relationship objects
   *   - validate_contacts: Whether to validate contact existence (default: true)
   *   - skip_duplicates: Whether to skip existing relationships (default: true)
   * @return array Bulk creation results
   */
  public static function bulkCreateRelationships($params = []) {
    $relationships = CRM_Utils_Array::value('relationships', $params, []);
    $validateContacts = CRM_Utils_Array::value('validate_contacts', $params, TRUE);
    $skipDuplicates = CRM_Utils_Array::value('skip_duplicates', $params, TRUE);

    if (empty($relationships) || !is_array($relationships)) {
      throw new Exception('Relationships array is required');
    }

    $results = [
      'successful' => [],
      'failed' => [],
      'skipped' => [],
      'total_processed' => 0,
      'total_successful' => 0,
      'total_failed' => 0,
      'total_skipped' => 0
    ];

    foreach ($relationships as $index => $relationship) {
      $results['total_processed']++;

      try {
        // Check for duplicates if requested
        if ($skipDuplicates) {
          $existing = civicrm_api3('Relationship', 'get', [
            'contact_id_a' => $relationship['contact_id_a'],
            'contact_id_b' => $relationship['contact_id_b'],
            'relationship_type_id' => $relationship['relationship_type_id'],
            'is_active' => 1
          ]);

          if ($existing['count'] > 0) {
            $results['skipped'][] = [
              'contact_id_a' => $relationship['contact_id_a'],
              'contact_id_b' => $relationship['contact_id_b'],
              'relationship_type_id' => $relationship['relationship_type_id'],
              'reason' => 'Relationship already exists',
              'existing_id' => $existing['id']
            ];
            $results['total_skipped']++;
            continue;
          }
        }

        $createResult = self::createRelationship($relationship);
        $results['successful'][] = $createResult;
        $results['total_successful']++;

      }
      catch (Exception $e) {
        $results['failed'][] = [
          'contact_id_a' => $relationship['contact_id_a'] ?? 'unknown',
          'contact_id_b' => $relationship['contact_id_b'] ?? 'unknown',
          'relationship_type_id' => $relationship['relationship_type_id'] ?? 'unknown',
          'index' => $index,
          'error' => $e->getMessage(),
          'failed_at' => date('Y-m-d H:i:s')
        ];
        $results['total_failed']++;
      }
    }

    $results['success_rate'] = $results['total_processed'] > 0 ?
      round(($results['total_successful'] / $results['total_processed']) * 100, 2) : 0;

    return $results;
  }

  /**
   * Import PowerMap data from various formats
   *
   * @param array $params Parameters:
   *   - format: Import format ('csv', 'json', 'excel')
   *   - data: Import data (array or file content)
   *   - mapping: Field mapping configuration
   *   - validate_data: Whether to validate data before import (default: true)
   *   - create_missing_contacts: Whether to create missing contacts (default: false)
   * @return array Import results
   */
  public static function importData($params = []) {
    $format = CRM_Utils_Array::value('format', $params);
    $data = CRM_Utils_Array::value('data', $params);
    $mapping = CRM_Utils_Array::value('mapping', $params, []);
    $validateData = CRM_Utils_Array::value('validate_data', $params, TRUE);
    $createMissingContacts = CRM_Utils_Array::value('create_missing_contacts', $params, FALSE);

    if (empty($format) || empty($data)) {
      throw new Exception('Format and data are required for import');
    }

    $results = [
      'contacts_processed' => 0,
      'contacts_created' => 0,
      'contacts_updated' => 0,
      'relationships_created' => 0,
      'errors' => [],
      'warnings' => [],
      'import_summary' => []
    ];

    try {
      // Parse data based on format
      $parsedData = self::parseImportData($data, $format, $mapping);

      // Validate data if requested
      if ($validateData) {
        $validationResult = self::validateImportData($parsedData);
        if (!empty($validationResult['errors'])) {
          throw new Exception('Data validation failed: ' . implode(', ', $validationResult['errors']));
        }
        $results['warnings'] = $validationResult['warnings'];
      }

      // Process contacts
      foreach ($parsedData['contacts'] as $contactData) {
        try {
          $results['contacts_processed']++;

          // Check if contact exists
          $existingContact = NULL;
          if (!empty($contactData['email'])) {
            $existing = civicrm_api3('Contact', 'get', [
              'email' => $contactData['email'],
              'is_deleted' => 0
            ]);
            if ($existing['count'] > 0) {
              $existingContact = $existing['values'][array_keys($existing['values'])[0]];
            }
          }

          if ($existingContact) {
            // Update existing contact
            $updateParams = array_merge($contactData, ['id' => $existingContact['id']]);
            self::updateStakeholder($updateParams);
            $results['contacts_updated']++;
          }
          elseif ($createMissingContacts) {
            // Create new contact
            $createParams = [
              'contact_type' => $contactData['contact_type'] ?? 'Individual',
              'display_name' => $contactData['display_name'] ?? $contactData['name'],
            ];
            if (!empty($contactData['email'])) {
              $createParams['email'] = $contactData['email'];
            }

            $newContact = civicrm_api3('Contact', 'create', $createParams);

            // Update with PowerMap fields
            $contactData['contact_id'] = $newContact['id'];
            self::updateStakeholder($contactData);
            $results['contacts_created']++;
          }
          else {
            $results['warnings'][] = "Contact not found: " . ($contactData['name'] ?? 'Unknown');
          }

        }
        catch (Exception $e) {
          $results['errors'][] = "Contact processing error: " . $e->getMessage();
        }
      }

      // Process relationships
      if (!empty($parsedData['relationships'])) {
        $relationshipResult = self::bulkCreateRelationships([
          'relationships' => $parsedData['relationships'],
          'skip_duplicates' => TRUE
        ]);
        $results['relationships_created'] = $relationshipResult['total_successful'];
        $results['errors'] = array_merge($results['errors'],
          array_column($relationshipResult['failed'], 'error'));
      }

      $results['import_summary'] = [
        'total_contacts_processed' => $results['contacts_processed'],
        'total_contacts_created' => $results['contacts_created'],
        'total_contacts_updated' => $results['contacts_updated'],
        'total_relationships_created' => $results['relationships_created'],
        'total_errors' => count($results['errors']),
        'total_warnings' => count($results['warnings']),
        'import_date' => date('Y-m-d H:i:s')
      ];

      return $results;

    }
    catch (Exception $e) {
      throw new Exception('Import failed: ' . $e->getMessage());
    }
  }

  /**
   * Parse import data based on format
   *
   * @param mixed $data Import data
   * @param string $format Data format
   * @param array $mapping Field mapping
   * @return array Parsed data
   */
  private static function parseImportData($data, $format, $mapping) {
    switch (strtolower($format)) {
      case 'json':
        if (is_string($data)) {
          $decoded = json_decode($data, TRUE);
          if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON data');
          }
          return $decoded;
        }
        return $data;

      case 'csv':
        // Basic CSV parsing - in a real implementation, you'd use a proper CSV parser
        if (is_string($data)) {
          $lines = explode("\n", $data);
          $headers = str_getcsv(array_shift($lines));
          $contacts = [];

          foreach ($lines as $line) {
            if (trim($line)) {
              $values = str_getcsv($line);
              $contact = array_combine($headers, $values);
              $contacts[] = self::mapImportFields($contact, $mapping);
            }
          }

          return ['contacts' => $contacts, 'relationships' => []];
        }
        break;

      default:
        throw new Exception('Unsupported import format: ' . $format);
    }

    return ['contacts' => [], 'relationships' => []];
  }

  /**
   * Map import fields based on mapping configuration
   *
   * @param array $data Raw data
   * @param array $mapping Field mapping
   * @return array Mapped data
   */
  private static function mapImportFields($data, $mapping) {
    $mapped = [];

    foreach ($mapping as $sourceField => $targetField) {
      if (isset($data[$sourceField])) {
        $mapped[$targetField] = $data[$sourceField];
      }
    }

    // If no mapping provided, use data as-is
    if (empty($mapping)) {
      $mapped = $data;
    }

    return $mapped;
  }

  /**
   * Validate import data
   *
   * @param array $data Parsed import data
   * @return array Validation results
   */
  private static function validateImportData($data) {
    $errors = [];
    $warnings = [];

    // Validate contacts
    if (empty($data['contacts'])) {
      $warnings[] = 'No contacts found in import data';
    }
    else {
      foreach ($data['contacts'] as $index => $contact) {
        if (empty($contact['name']) && empty($contact['display_name'])) {
          $errors[] = "Contact at index {$index} missing name";
        }

        if (!empty($contact['influence_level'])) {
          $influence = (int)$contact['influence_level'];
          if ($influence < 1 || $influence > 5) {
            $warnings[] = "Contact at index {$index} has invalid influence level: {$influence}";
          }
        }

        if (!empty($contact['support_level'])) {
          $support = (int)$contact['support_level'];
          if ($support < 1 || $support > 5) {
            $warnings[] = "Contact at index {$index} has invalid support level: {$support}";
          }
        }
      }
    }

    // Validate relationships
    if (!empty($data['relationships'])) {
      foreach ($data['relationships'] as $index => $relationship) {
        if (empty($relationship['contact_id_a']) || empty($relationship['contact_id_b'])) {
          $errors[] = "Relationship at index {$index} missing contact IDs";
        }

        if (empty($relationship['relationship_type_id'])) {
          $errors[] = "Relationship at index {$index} missing relationship type ID";
        }
      }
    }

    return [
      'errors' => $errors,
      'warnings' => $warnings,
      'is_valid' => empty($errors)
    ];
  }

  /**
   * Get available import/export formats and their configurations
   *
   * @return array Available formats with their specifications
   */
  public static function getImportExportFormats() {
    return [
      'csv' => [
        'name' => 'CSV (Comma Separated Values)',
        'description' => 'Standard CSV format for contacts and relationships',
        'supports_import' => TRUE,
        'supports_export' => TRUE,
        'required_headers' => ['name', 'influence_level', 'support_level'],
        'optional_headers' => ['email', 'contact_type', 'notes', 'network_position'],
        'example_header' => 'name,email,contact_type,influence_level,support_level,notes'
      ],
      'json' => [
        'name' => 'JSON (JavaScript Object Notation)',
        'description' => 'Structured JSON format with full relationship support',
        'supports_import' => TRUE,
        'supports_export' => TRUE,
        'structure' => [
          'contacts' => 'Array of contact objects',
          'relationships' => 'Array of relationship objects'
        ],
        'example' => [
          'contacts' => [
            [
              'name' => 'John Doe',
              'email' => 'john@example.com',
              'influence_level' => 4,
              'support_level' => 5
            ]
          ],
          'relationships' => [
            [
              'contact_id_a' => 1,
              'contact_id_b' => 2,
              'relationship_type_id' => 1,
              'strength' => 3
            ]
          ]
        ]
      ],
      'excel' => [
        'name' => 'Excel (.xlsx)',
        'description' => 'Microsoft Excel format (future enhancement)',
        'supports_import' => FALSE,
        'supports_export' => FALSE,
        'status' => 'planned'
      ]
    ];
  }
}