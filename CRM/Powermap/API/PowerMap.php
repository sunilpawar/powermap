<?php

class CRM_Powermap_API_PowerMap {

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
   * Get network data for visualization
   */
  public static function getNetworkData($params = []) {
    $contacts = [];
    $relationships = [];

    // Apply filters if provided
    $influenceFilter = CRM_Utils_Array::value('influence_min', $params, 1);
    $supportFilter = CRM_Utils_Array::value('support_min', $params, 1);
    $relationshipTypes = CRM_Utils_Array::value('relationship_types', $params, []);

    // Get filtered contacts with custom fields
    $contactParams = [
      'sequential' => 1,
      'is_deleted' => 0,
      'options' => ['limit' => 1000],
      'return' => [
        'id',
        'display_name',
        'contact_type',
        'contact_sub_type'
      ],
    ];

    try {
      $contactResult = civicrm_api3('Contact', 'get', $contactParams);

      foreach ($contactResult['values'] as $contact) {
        // Get custom field values
        $influence = self::getCustomFieldValue($contact['id'], 'influence_level');
        $support = self::getCustomFieldValue($contact['id'], 'support_level');
        $notes = self::getCustomFieldValue($contact['id'], 'powermap_notes');

        // Use defaults if custom fields don't exist
        if ($influence === NULL) {
          $influence = rand(1, 5); // Demo data
        }
        if ($support === NULL) {
          $support = rand(1, 5); // Demo data
        }

        // Apply filters
        if ($influence >= $influenceFilter && $support >= $supportFilter) {
          $contacts[] = [
            'id' => (int)$contact['id'],
            'name' => !empty($contact['display_name']) ? $contact['display_name'] : 'Contact ' . $contact['id'],
            'type' => $contact['contact_type'],
            'subtype' => CRM_Utils_Array::value('contact_sub_type', $contact, ''),
            'influence' => (int)$influence,
            'support' => (int)$support,
            'group' => self::getInfluenceGroup($influence),
            'notes' => $notes ?: '',
          ];
        }
      }

      // Get relationships between filtered contacts
      $contactIds = array_column($contacts, 'id');

      if (!empty($contactIds)) {
        $relationshipParams = [
          'sequential' => 1,
          'is_active' => 1,
          'options' => ['limit' => 0],
          'contact_id_a' => ['IN' => $contactIds],
          'contact_id_b' => ['IN' => $contactIds],
          'return' => [
            'id',
            'contact_id_a',
            'contact_id_b',
            'relationship_type_id',
            'start_date',
            'end_date'
          ]
        ];

        if (!empty($relationshipTypes)) {
          $relationshipParams['relationship_type_id'] = ['IN' => $relationshipTypes];
        }

        $relationshipResult = civicrm_api3('Relationship', 'get', $relationshipParams);

        foreach ($relationshipResult['values'] as $relationship) {
          // Only include relationships where both contacts are in our filtered set
          if (in_array($relationship['contact_id_a'], $contactIds) &&
            in_array($relationship['contact_id_b'], $contactIds) &&
            $relationship['contact_id_a'] != $relationship['contact_id_b']) {

            $relationshipType = self::getRelationshipTypeName($relationship['relationship_type_id']);
            $strength = self::getRelationshipStrength($relationship['id']);

            $relationships[] = [
              'id' => (int)$relationship['id'],
              'source' => (int)$relationship['contact_id_a'],
              'target' => (int)$relationship['contact_id_b'],
              'type' => $relationshipType,
              'strength' => $strength,
              'start_date' => CRM_Utils_Array::value('start_date', $relationship, ''),
              'end_date' => CRM_Utils_Array::value('end_date', $relationship, ''),
            ];
          }
        }
      }

      return [
        'nodes' => $contacts,
        'links' => $relationships,
        'stats' => self::calculateStats($contacts, $relationships),
        'metadata' => [
          'total_contacts' => count($contactResult['values']),
          'filtered_contacts' => count($contacts),
          'total_relationships' => count($relationships),
          'filters_applied' => [
            'influence_min' => $influenceFilter,
            'support_min' => $supportFilter,
            'relationship_types' => $relationshipTypes
          ]
        ]
      ];

    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('PowerMap API Error: ' . $e->getMessage());

      // Return demo data if there's an error
      return self::getDemoData();
    }
  }

  /**
   * Export network data to CSV
   */
  public static function exportToCSV($params = []) {
    $data = self::getNetworkData($params);

    $csvData = [];
    $csvData[] = [
      'ID',
      'Name',
      'Type',
      'Subtype',
      'Influence',
      'Support',
      'Connections',
      'Strong Connections',
      'Notes'
    ];

    foreach ($data['nodes'] as $node) {
      $connections = 0;
      $strongConnections = 0;

      foreach ($data['links'] as $link) {
        if ($link['source'] == $node['id'] || $link['target'] == $node['id']) {
          $connections++;
          if ($link['strength'] >= 3) {
            $strongConnections++;
          }
        }
      }

      $csvData[] = [
        $node['id'],
        $node['name'],
        $node['type'],
        $node['subtype'],
        $node['influence'],
        $node['support'],
        $connections,
        $strongConnections,
        $node['notes']
      ];
    }

    return $csvData;
  }

  /**
   * Get available relationship types
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
      return [];
    }
  }

  /**
   * Get demo data when no real data is available
   */
  private static function getDemoData() {
    return [
      'nodes' => [
        [
          'id' => 1,
          'name' => 'John Smith',
          'type' => 'Individual',
          'subtype' => '',
          'influence' => 5,
          'support' => 4,
          'group' => 'high',
          'notes' => 'Key decision maker'
        ],
        [
          'id' => 2,
          'name' => 'Mary Johnson',
          'type' => 'Individual',
          'subtype' => '',
          'influence' => 4,
          'support' => 5,
          'group' => 'high',
          'notes' => 'Strong supporter'
        ],
        [
          'id' => 3,
          'name' => 'Tech Corporation',
          'type' => 'Organization',
          'subtype' => '',
          'influence' => 3,
          'support' => 2,
          'group' => 'medium',
          'notes' => 'Potential opposition'
        ],
        [
          'id' => 4,
          'name' => 'Community Group',
          'type' => 'Organization',
          'subtype' => '',
          'influence' => 2,
          'support' => 5,
          'group' => 'low',
          'notes' => 'Grassroots support'
        ],
        [
          'id' => 5,
          'name' => 'City Council',
          'type' => 'Organization',
          'subtype' => '',
          'influence' => 5,
          'support' => 3,
          'group' => 'high',
          'notes' => 'Regulatory authority'
        ],
      ],
      'links' => [
        [
          'id' => 1,
          'source' => 1,
          'target' => 2,
          'type' => 'Colleague',
          'strength' => 2,
          'start_date' => '',
          'end_date' => ''
        ],
        [
          'id' => 2,
          'source' => 2,
          'target' => 3,
          'type' => 'Advisor',
          'strength' => 3,
          'start_date' => '',
          'end_date' => ''
        ],
        [
          'id' => 3,
          'source' => 1,
          'target' => 4,
          'type' => 'Member',
          'strength' => 1,
          'start_date' => '',
          'end_date' => ''
        ],
        [
          'id' => 4,
          'source' => 3,
          'target' => 5,
          'type' => 'Reports To',
          'strength' => 2,
          'start_date' => '',
          'end_date' => ''
        ],
        [
          'id' => 5,
          'source' => 4,
          'target' => 5,
          'type' => 'Advocate',
          'strength' => 1,
          'start_date' => '',
          'end_date' => ''
        ],
      ],
      'stats' => [
        'total' => 5,
        'high_influence' => 3,
        'supporters' => 2,
        'opposition' => 1,
      ],
      'metadata' => [
        'total_contacts' => 5,
        'filtered_contacts' => 5,
        'total_relationships' => 5,
        'is_demo_data' => TRUE
      ]
    ];
  }

  private static function getCustomFieldValue($contactId, $fieldName, $default = NULL) {
    try {
      // Try to get the custom field by name
      $customField = civicrm_api3('CustomField', 'get', [
        'sequential' => 1,
        'name' => $fieldName,
        'return' => ['id', 'custom_group_id']
      ]);

      if (!empty($customField['values'][0])) {
        $fieldId = $customField['values'][0]['id'];

        $result = civicrm_api3('Contact', 'get', [
          'sequential' => 1,
          'id' => $contactId,
          'return' => ['custom_' . $fieldId]
        ]);

        if (!empty($result['values'][0]['custom_' . $fieldId])) {
          return $result['values'][0]['custom_' . $fieldId];
        }
      }
    }
    catch (Exception $e) {
      // Field doesn't exist or other error
    }

    return $default;
  }

  private static function getInfluenceGroup($influence) {
    if ($influence >= 4) {
      return 'high';
    }
    if ($influence >= 3) {
      return 'medium';
    }
    return 'low';
  }

  private static function getRelationshipTypeName($typeId) {
    try {
      $result = civicrm_api3('RelationshipType', 'get', [
        'sequential' => 1,
        'id' => $typeId,
        'return' => ['label_a_b', 'name_a_b'],
      ]);

      if (!empty($result['values'][0]['label_a_b'])) {
        return $result['values'][0]['label_a_b'];
      }

      if (!empty($result['values'][0]['name_a_b'])) {
        return $result['values'][0]['name_a_b'];
      }
    }
    catch (Exception $e) {
      // Return default
    }

    return 'Related to';
  }

  private static function getRelationshipStrength($relationshipId) {
    // Try to get from custom field, otherwise return default
    $strength = self::getCustomFieldValue($relationshipId, 'relationship_strength');

    if ($strength !== NULL) {
      return (int)$strength;
    }

    // Return random strength for demo purposes
    return rand(1, 3);
  }

  private static function calculateStats($contacts, $relationships) {
    $total = count($contacts);
    $highInfluence = count(array_filter($contacts, function ($c) {
      return $c['influence'] >= 4;
    }));
    $supporters = count(array_filter($contacts, function ($c) {
      return $c['support'] >= 4;
    }));
    $opposition = count(array_filter($contacts, function ($c) {
      return $c['support'] <= 2;
    }));

    // Calculate network metrics
    $avgInfluence = $total > 0 ? array_sum(array_column($contacts, 'influence')) / $total : 0;
    $avgSupport = $total > 0 ? array_sum(array_column($contacts, 'support')) / $total : 0;

    // Calculate network density
    $maxPossibleConnections = $total > 1 ? $total * ($total - 1) / 2 : 0;
    $density = $maxPossibleConnections > 0 ? count($relationships) / $maxPossibleConnections : 0;

    return [
      'total' => $total,
      'high_influence' => $highInfluence,
      'supporters' => $supporters,
      'opposition' => $opposition,
      'neutral' => $total - $supporters - $opposition,
      'avg_influence' => round($avgInfluence, 2),
      'avg_support' => round($avgSupport, 2),
      'network_density' => round($density * 100, 1),
      'total_relationships' => count($relationships),
      'strong_relationships' => count(array_filter($relationships, function ($r) {
        return $r['strength'] >= 3;
      }))
    ];
  }

  /**
   * Get network analysis data
   */
  public static function getNetworkAnalysis($params = []) {
    $data = self::getNetworkData($params);

    // Calculate centrality measures
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
        'total_edges_analyzed' => count($data['links'])
      ]
    ];
  }

  /**
   * Update stakeholder information
   */
  public static function updateStakeholder($params = []) {
    $contactId = CRM_Utils_Array::value('contact_id', $params);
    $influence = CRM_Utils_Array::value('influence_level', $params);
    $support = CRM_Utils_Array::value('support_level', $params);
    $notes = CRM_Utils_Array::value('notes', $params);

    if (empty($contactId)) {
      throw new Exception('Contact ID is required');
    }

    try {
      // Update custom fields
      $updateParams = ['id' => $contactId];

      if ($influence !== NULL) {
        $updateParams['custom_influence_level'] = $influence;
      }

      if ($support !== NULL) {
        $updateParams['custom_support_level'] = $support;
      }

      if ($notes !== NULL) {
        $updateParams['custom_powermap_notes'] = $notes;
      }

      $result = civicrm_api3('Contact', 'create', $updateParams);

      return [
        'contact_id' => $contactId,
        'updated_fields' => array_keys($updateParams),
        'success' => TRUE
      ];

    }
    catch (Exception $e) {
      throw new Exception('Failed to update stakeholder: ' . $e->getMessage());
    }
  }

  /**
   * Create relationship between stakeholders
   */
  public static function createRelationship($params = []) {
    $contactIdA = CRM_Utils_Array::value('contact_id_a', $params);
    $contactIdB = CRM_Utils_Array::value('contact_id_b', $params);
    $relationshipTypeId = CRM_Utils_Array::value('relationship_type_id', $params);
    $strength = CRM_Utils_Array::value('strength', $params, 2);

    if (empty($contactIdA) || empty($contactIdB) || empty($relationshipTypeId)) {
      throw new Exception('Contact IDs and relationship type are required');
    }

    if ($contactIdA == $contactIdB) {
      throw new Exception('Cannot create relationship between same contact');
    }

    try {
      // Check if relationship already exists
      $existing = civicrm_api3('Relationship', 'get', [
        'contact_id_a' => $contactIdA,
        'contact_id_b' => $contactIdB,
        'relationship_type_id' => $relationshipTypeId,
        'is_active' => 1
      ]);

      if ($existing['count'] > 0) {
        throw new Exception('Relationship already exists between these contacts');
      }

      // Create the relationship
      $relationshipParams = [
        'contact_id_a' => $contactIdA,
        'contact_id_b' => $contactIdB,
        'relationship_type_id' => $relationshipTypeId,
        'is_active' => 1,
        'start_date' => date('Y-m-d')
      ];

      $relationship = civicrm_api3('Relationship', 'create', $relationshipParams);

      // Try to set strength if custom field exists
      if ($relationship['id']) {
        try {
          civicrm_api3('Relationship', 'create', [
            'id' => $relationship['id'],
            'custom_relationship_strength' => $strength
          ]);
        }
        catch (Exception $e) {
          // Custom field might not exist, ignore
        }
      }

      return [
        'relationship_id' => $relationship['id'],
        'contact_id_a' => $contactIdA,
        'contact_id_b' => $contactIdB,
        'relationship_type_id' => $relationshipTypeId,
        'strength' => $strength,
        'success' => TRUE
      ];

    }
    catch (Exception $e) {
      throw new Exception('Failed to create relationship: ' . $e->getMessage());
    }
  }

  /**
   * Delete relationship
   */
  public static function deleteRelationship($params = []) {
    $relationshipId = CRM_Utils_Array::value('relationship_id', $params);

    if (empty($relationshipId)) {
      throw new Exception('Relationship ID is required');
    }

    try {
      $result = civicrm_api3('Relationship', 'delete', [
        'id' => $relationshipId
      ]);

      return [
        'relationship_id' => $relationshipId,
        'deleted' => TRUE,
        'success' => TRUE
      ];

    }
    catch (Exception $e) {
      throw new Exception('Failed to delete relationship: ' . $e->getMessage());
    }
  }

  /**
   * Get contact suggestions for stakeholder selection
   */
  public static function getContactSuggestions($params = []) {
    $searchTerm = CRM_Utils_Array::value('search_term', $params, '');
    $contactTypes = CRM_Utils_Array::value('contact_types', $params, ['Individual', 'Organization']);
    $limit = CRM_Utils_Array::value('limit', $params, 20);

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
        'search_term' => $searchTerm
      ];

    }
    catch (Exception $e) {
      return [
        'suggestions' => [],
        'total' => 0,
        'error' => $e->getMessage()
      ];
    }
  }

  /**
   * Validate data integrity
   */
  public static function validateData($params = []) {
    $issues = [];
    $stats = [
      'total_contacts' => 0,
      'contacts_without_influence' => 0,
      'contacts_without_support' => 0,
      'orphaned_relationships' => 0,
      'duplicate_relationships' => 0
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

      // Check for missing custom field values
      foreach ($contacts['values'] as $contact) {
        $influence = self::getCustomFieldValue($contact['id'], 'influence_level');
        $support = self::getCustomFieldValue($contact['id'], 'support_level');

        if ($influence === NULL) {
          $stats['contacts_without_influence']++;
          $issues[] = [
            'type' => 'missing_influence',
            'contact_id' => $contact['id'],
            'contact_name' => $contact['display_name'],
            'message' => 'Missing influence level'
          ];
        }

        if ($support === NULL) {
          $stats['contacts_without_support']++;
          $issues[] = [
            'type' => 'missing_support',
            'contact_id' => $contact['id'],
            'contact_name' => $contact['display_name'],
            'message' => 'Missing support level'
          ];
        }
      }

      // Check relationships
      $relationships = civicrm_api3('Relationship', 'get', [
        'sequential' => 1,
        'is_active' => 1,
        'options' => ['limit' => 0],
        'return' => ['id', 'contact_id_a', 'contact_id_b', 'relationship_type_id']
      ]);

      $relationshipMap = [];
      foreach ($relationships['values'] as $rel) {
        // Check for orphaned relationships
        if (!in_array($rel['contact_id_a'], $contactIds) || !in_array($rel['contact_id_b'], $contactIds)) {
          $stats['orphaned_relationships']++;
          $issues[] = [
            'type' => 'orphaned_relationship',
            'relationship_id' => $rel['id'],
            'message' => 'Relationship references deleted contact(s)'
          ];
        }

        // Check for duplicates
        $key = $rel['contact_id_a'] . '-' . $rel['contact_id_b'] . '-' . $rel['relationship_type_id'];
        $reverseKey = $rel['contact_id_b'] . '-' . $rel['contact_id_a'] . '-' . $rel['relationship_type_id'];

        if (isset($relationshipMap[$key]) || isset($relationshipMap[$reverseKey])) {
          $stats['duplicate_relationships']++;
          $issues[] = [
            'type' => 'duplicate_relationship',
            'relationship_id' => $rel['id'],
            'message' => 'Duplicate relationship found'
          ];
        }

        $relationshipMap[$key] = $rel['id'];
      }

      return [
        'validation_stats' => $stats,
        'issues' => $issues,
        'total_issues' => count($issues),
        'validation_date' => date('Y-m-d H:i:s')
      ];

    }
    catch (Exception $e) {
      return [
        'validation_stats' => $stats,
        'issues' => [],
        'total_issues' => 0,
        'error' => $e->getMessage()
      ];
    }
  }
}