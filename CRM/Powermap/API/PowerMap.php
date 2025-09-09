<?php

class CRM_Powermap_API_PowerMap {

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

    // Get filtered contacts
    $contactParams = [
      'sequential' => 1,
      'is_deleted' => 0,
      'options' => ['limit' => 1000],
      'return' => ['id', 'display_name', 'contact_type'],
    ];

    $contactResult = civicrm_api3('Contact', 'get', $contactParams);

    foreach ($contactResult['values'] as $contact) {
      $influence = self::getCustomFieldValue($contact['id'], 'influence_level', rand(1, 5));
      $support = self::getCustomFieldValue($contact['id'], 'support_level', rand(1, 5));

      // Apply filters
      if ($influence >= $influenceFilter && $support >= $supportFilter) {
        $contacts[] = [
          'id' => $contact['id'],
          'name' => $contact['display_name'],
          'type' => $contact['contact_type'],
          'influence' => $influence,
          'support' => $support,
          'group' => self::getInfluenceGroup($influence),
        ];
      }
    }

    // Get relationships
    $relationshipParams = [
      'sequential' => 1,
      'is_active' => 1,
      'options' => ['limit' => 0],
    ];

    if (!empty($relationshipTypes)) {
      $relationshipParams['relationship_type_id'] = ['IN' => $relationshipTypes];
    }

    $relationshipResult = civicrm_api3('Relationship', 'get', $relationshipParams);

    foreach ($relationshipResult['values'] as $relationship) {
      // Only include relationships where both contacts are in our filtered set
      $contactIds = array_column($contacts, 'id');
      if (in_array($relationship['contact_id_a'], $contactIds) &&
        in_array($relationship['contact_id_b'], $contactIds)) {

        $relationships[] = [
          'source' => $relationship['contact_id_a'],
          'target' => $relationship['contact_id_b'],
          'type' => self::getRelationshipTypeName($relationship['relationship_type_id']),
          'strength' => rand(1, 3), // Could be based on custom field
        ];
      }
    }

    return [
      'nodes' => $contacts,
      'links' => $relationships,
      'stats' => self::calculateStats($contacts, $relationships),
    ];
  }

  /**
   * Export network data to CSV
   */
  public static function exportToCSV($params = []) {
    $data = self::getNetworkData($params);

    $csvData = [];
    $csvData[] = ['Name', 'Type', 'Influence', 'Support', 'Connections'];

    foreach ($data['nodes'] as $node) {
      $connections = 0;
      foreach ($data['links'] as $link) {
        if ($link['source'] == $node['id'] || $link['target'] == $node['id']) {
          $connections++;
        }
      }

      $csvData[] = [
        $node['name'],
        $node['type'],
        $node['influence'],
        $node['support'],
        $connections,
      ];
    }

    return $csvData;
  }

  private static function getCustomFieldValue($contactId, $fieldName, $default = NULL) {
    // This would fetch from actual custom fields
    // For demo purposes, returning random values
    return $default ?: rand(1, 5);
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
        'return' => ['label_a_b'],
      ]);

      return $result['values'][0]['label_a_b'] ?? 'Related to';
    }
    catch (Exception $e) {
      return 'Related to';
    }
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

    return [
      'total' => $total,
      'high_influence' => $highInfluence,
      'supporters' => $supporters,
      'opposition' => $opposition,
    ];
  }
}
