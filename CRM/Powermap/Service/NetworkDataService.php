<?php

/**
 * Common PowerMap Service for reusable network data operations
 *
 * This service class contains all the common logic for fetching and processing
 * network data that can be shared between API endpoints and page controllers.
 */
class CRM_Powermap_Service_NetworkDataService {

  const DEFAULT_CONTACT_LIMIT = 1000;
  const DEFAULT_INFLUENCE_LEVEL = 1;
  const DEFAULT_SUPPORT_LEVEL = 1;
  const DEFAULT_STRENGTH_LEVEL = 1;
  const FALLBACK_CONTACT_ID = 1;

  /**
   * Cache for custom field information
   * @var array
   */
  private static $customFieldCache = [];

  /**
   * Cache for contact data
   * @var array
   */
  private static $contactDataCache = [];

  /**
   * Get network data with comprehensive filtering support
   *
   * @param array $params Parameters including:
   *   - group_id: Filter by CiviCRM group
   *   - contact_id: Specific contact IDs (array or comma-separated string)
   *   - only_relationship: Show only contacts with relationships
   *   - influence_min: Minimum influence level
   *   - support_min: Minimum support level
   *   - relationship_types: Array of relationship type IDs to include
   * @return array Network data structure
   */
  public static function getNetworkData($params = []) {
    try {
      // Process and validate parameters
      $processedParams = self::processParameters($params);

      // Get contact IDs based on filters
      $contactIds = self::getFilteredContactIds($processedParams);

      // Get contact details with custom field values
      [$contacts, $contactStrengthLevels] = self::getContactDetails($contactIds);

      // Get relationships between contacts
      [$relationships, $relationshipContactIds] = self::getContactRelationships(
        $contactIds,
        $contactStrengthLevels,
        $processedParams
      );

      // Add missing contacts referenced in relationships
      $contacts = self::addMissingRelationshipContacts($contacts, $relationshipContactIds);

      // Filter contacts if relationship-only mode is enabled
      if ($processedParams['only_relationship']) {
        $contacts = self::filterContactsWithRelationships($contacts, $relationshipContactIds);
      }

      // Calculate network statistics
      $stats = self::calculateNetworkStats($contacts, $relationships);

      return [
        'nodes' => array_values($contacts),
        'links' => $relationships,
        'stats' => $stats,
        'metadata' => [
          'total_contacts' => count($contacts),
          'total_relationships' => count($relationships),
          'filters_applied' => $processedParams,
          'generated_at' => date('Y-m-d H:i:s'),
        ],
      ];

    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('NetworkDataService Error: ' . $e->getMessage());
      return self::getDemoData();
    }
  }

  /**
   * Process and validate input parameters
   *
   * @param array $params Raw parameters
   * @return array Processed parameters
   */
  private static function processParameters($params) {
    $processed = [
      'group_id' => !empty($params['group_id']) ? (int)$params['group_id'] : NULL,
      'only_relationship' => !empty($params['only_relationship']),
      'influence_min' => isset($params['influence_min']) ? (int)$params['influence_min'] : 1,
      'support_min' => isset($params['support_min']) ? (int)$params['support_min'] : 1,
      'relationship_types' => [],
      'contact_id' => [],
    ];

    // Process contact_id parameter (can be array or comma-separated string)
    if (!empty($params['contact_id'])) {
      if (is_array($params['contact_id'])) {
        $processed['contact_id'] = array_map('intval', $params['contact_id']);
      } elseif (is_string($params['contact_id'])) {
        $processed['contact_id'] = array_map('intval', explode(',', $params['contact_id']));
      } else {
        $processed['contact_id'] = [(int)$params['contact_id']];
      }
      $processed['contact_id'] = array_filter($processed['contact_id']);
    }

    // Process relationship_types parameter
    if (!empty($params['relationship_types'])) {
      if (is_array($params['relationship_types'])) {
        $processed['relationship_types'] = array_map('intval', $params['relationship_types']);
      } elseif (is_string($params['relationship_types'])) {
        $processed['relationship_types'] = array_map('intval', explode(',', $params['relationship_types']));
      }
    }

    return $processed;
  }

  /**
   * Get filtered contact IDs based on parameters
   *
   * @param array $params Processed parameters
   * @return array Contact IDs
   */
  private static function getFilteredContactIds($params) {
    // If specific contact IDs provided, use those
    if (!empty($params['contact_id'])) {
      return $params['contact_id'];
    }

    // If group ID provided, get contacts from group
    if (!empty($params['group_id'])) {
      return self::getContactIdsFromGroup($params['group_id']);
    }

    // Default fallback
    return [self::FALLBACK_CONTACT_ID];
  }

  /**
   * Get contact IDs from specified group
   *
   * @param int $groupID Group ID
   * @return array Contact IDs
   */
  private static function getContactIdsFromGroup($groupID) {
    try {
      $groupContacts = \Civi\Api4\GroupContact::get()
        ->addSelect('contact_id')
        ->addWhere('group_id', '=', $groupID)
        ->addWhere('status', '=', 'Added')
        ->execute();

      $contactIds = [];
      foreach ($groupContacts as $groupContact) {
        $contactIds[] = $groupContact['contact_id'];
      }

      return !empty($contactIds) ? $contactIds : [self::FALLBACK_CONTACT_ID];

    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting group contacts: ' . $e->getMessage());
      return [self::FALLBACK_CONTACT_ID];
    }
  }

  /**
   * Get detailed contact information including custom field values
   *
   * @param array $contactIds Contact IDs to retrieve
   * @return array [$contacts, $contactStrengthLevels]
   */
  private static function getContactDetails($contactIds) {
    if (empty($contactIds)) {
      return [[], []];
    }

    $customFields = self::getCustomFieldInfo();

    $contactParams = [
      'sequential' => 1,
      'is_deleted' => 0,
      'options' => ['limit' => self::DEFAULT_CONTACT_LIMIT],
      'id' => ['IN' => $contactIds],
      'return' => array_merge(
        ['id', 'display_name', 'contact_type', 'contact_sub_type'],
        array_column($customFields, 'column_field')
      ),
    ];

    try {
      $contactResult = civicrm_api3('Contact', 'get', $contactParams);

      $contacts = [];
      $contactStrengthLevels = [];

      foreach ($contactResult['values'] as $contact) {
        $customValues = self::extractCustomFieldValues($contact, $customFields);

        $contacts[$contact['id']] = [
          'id' => (int) $contact['id'],
          'name' => $contact['display_name'] ?: 'Contact ' . $contact['id'],
          'type' => $contact['contact_type'] ?: 'Individual',
          'subtype' => $contact['contact_sub_type'] ?? '',
          'influence' => $customValues['influence_level'],
          'support' => $customValues['support_level'],
          'group' => self::getInfluenceGroup($customValues['influence_level']),
          'notes' => '', // Could be extended to include notes
        ];

        $contactStrengthLevels[$contact['id']] = $customValues['strength_level'];
      }

      return [$contacts, $contactStrengthLevels];

    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting contact details: ' . $e->getMessage());
      return [[], []];
    }
  }

  /**
   * Get relationships between contacts
   *
   * @param array $contactIds Base contact IDs
   * @param array $contactStrengthLevels Strength levels mapping
   * @param array $params Parameters including relationship type filters
   * @return array [$relationships, $relationshipContactIds]
   */
  private static function getContactRelationships($contactIds, $contactStrengthLevels, $params = []) {
    if (empty($contactIds)) {
      return [[], []];
    }

    try {
      $relationshipQuery = \Civi\Api4\Relationship::get(TRUE)
        ->addSelect('id', 'contact_id_a', 'contact_id_b', 'relationship_type_id', 'relationship_type.label_a_b')
        ->addJoin('RelationshipType AS relationship_type', 'INNER')
        ->addClause('OR', ['contact_id_a', 'IN', $contactIds], ['contact_id_b', 'IN', $contactIds])
        ->addWhere('is_active', '=', TRUE);

      // Apply relationship type filter if specified
      if (!empty($params['relationship_types'])) {
        $relationshipQuery->addWhere('relationship_type_id', 'IN', $params['relationship_types']);
      }

      $relationshipResult = $relationshipQuery->execute()->getArrayCopy();

      $relationships = [];
      $relationshipContactIds = [];

      foreach ($relationshipResult as $relationship) {
        if ($relationship['contact_id_a'] == $relationship['contact_id_b']) {
          continue; // Skip self-relationships
        }

        $sourceId = (int) $relationship['contact_id_a'];
        $targetId = (int) $relationship['contact_id_b'];

        $relationshipContactIds[$sourceId] = $sourceId;
        $relationshipContactIds[$targetId] = $targetId;

        $linkStrength = self::calculateLinkStrength($sourceId, $targetId, $contactStrengthLevels);

        $relationships[] = [
          'id' => (int) $relationship['id'],
          'source' => $sourceId,
          'target' => $targetId,
          'type' => $relationship['relationship_type.label_a_b'] ?: 'Related to',
          'strength' => $linkStrength,
          'start_date' => '',
          'end_date' => '',
        ];
      }

      return [$relationships, $relationshipContactIds];

    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting relationships: ' . $e->getMessage());
      return [[], []];
    }
  }

  /**
   * Add missing contacts referenced in relationships
   *
   * @param array $existingContacts Current contacts
   * @param array $relationshipContactIds All contact IDs from relationships
   * @return array Complete contact data
   */
  private static function addMissingRelationshipContacts($existingContacts, $relationshipContactIds) {
    $missingContactIds = [];
    foreach ($relationshipContactIds as $contactId) {
      if (!array_key_exists($contactId, $existingContacts)) {
        $missingContactIds[] = $contactId;
      }
    }

    if (!empty($missingContactIds)) {
      [$missingContacts, $strengthLevels] = self::getContactDetails($missingContactIds);
      $existingContacts = array_merge($existingContacts, $missingContacts);
    }

    return $existingContacts;
  }

  /**
   * Filter contacts to only those with relationships
   *
   * @param array $contacts All contacts
   * @param array $relationshipContactIds Contact IDs with relationships
   * @return array Filtered contacts
   */
  private static function filterContactsWithRelationships($contacts, $relationshipContactIds) {
    return array_filter($contacts, function($contact) use ($relationshipContactIds) {
      return array_key_exists($contact['id'], $relationshipContactIds);
    });
  }

  /**
   * Calculate link strength between two contacts
   *
   * @param int $sourceId Source contact ID
   * @param int $targetId Target contact ID
   * @param array $contactStrengthLevels Cached strength levels
   * @return int Link strength
   */
  private static function calculateLinkStrength($sourceId, $targetId, $contactStrengthLevels) {
    $sourceStrength = $contactStrengthLevels[$sourceId] ?? self::getContactStrengthLevel($sourceId);
    $targetStrength = $contactStrengthLevels[$targetId] ?? self::getContactStrengthLevel($targetId);
    return max($sourceStrength, $targetStrength);
  }

  /**
   * Get individual contact's strength level
   *
   * @param int $contactId Contact ID
   * @return int Strength level
   */
  private static function getContactStrengthLevel($contactId) {
    if (isset(self::$contactDataCache[$contactId]['strength'])) {
      return self::$contactDataCache[$contactId]['strength'];
    }

    try {
      $customFields = self::getCustomFieldInfo();
      $strengthField = $customFields['relationship_strength']['column_field'];

      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $contactId,
        'return' => [$strengthField],
      ]);

      $strength = self::DEFAULT_STRENGTH_LEVEL;
      if (!empty($result['values'][0][$strengthField])) {
        $strength = (int) $result['values'][0][$strengthField];
      }

      self::$contactDataCache[$contactId]['strength'] = $strength;
      return $strength;

    } catch (Exception $e) {
      return self::DEFAULT_STRENGTH_LEVEL;
    }
  }

  /**
   * Get cached custom field information
   *
   * @return array Custom field information
   */
  private static function getCustomFieldInfo() {
    if (empty(self::$customFieldCache)) {
      $fieldNames = ['influence_level', 'support_level', 'relationship_strength'];

      foreach ($fieldNames as $fieldName) {
        try {
          self::$customFieldCache[$fieldName] = CRM_Powermap_API_PowerMap::getCustomFieldInfo($fieldName);
        } catch (Exception $e) {
          self::$customFieldCache[$fieldName] = [
            'column_field' => 'custom_' . $fieldName,
            'exists' => FALSE,
          ];
        }
      }
    }

    return self::$customFieldCache;
  }

  /**
   * Extract custom field values from contact data
   *
   * @param array $contact Contact data
   * @param array $customFields Custom field information
   * @return array Extracted values
   */
  private static function extractCustomFieldValues($contact, $customFields) {
    return [
      'influence_level' => self::getCustomFieldValue(
        $contact,
        $customFields['influence_level'],
        self::DEFAULT_INFLUENCE_LEVEL
      ),
      'support_level' => self::getCustomFieldValue(
        $contact,
        $customFields['support_level'],
        self::DEFAULT_SUPPORT_LEVEL
      ),
      'strength_level' => self::getCustomFieldValue(
        $contact,
        $customFields['relationship_strength'],
        self::DEFAULT_STRENGTH_LEVEL
      ),
    ];
  }

  /**
   * Get custom field value with fallback
   *
   * @param array $contact Contact data
   * @param array $fieldInfo Field information
   * @param int $default Default value
   * @return int Field value or default
   */
  private static function getCustomFieldValue($contact, $fieldInfo, $default) {
    $columnField = $fieldInfo['column_field'] ?? null;

    if ($columnField && !empty($contact[$columnField])) {
      return (int) $contact[$columnField];
    }

    return $default;
  }

  /**
   * Get influence group classification
   *
   * @param int $influence Influence level
   * @return string Group classification
   */
  private static function getInfluenceGroup($influence) {
    if ($influence >= 4) return 'high';
    if ($influence >= 3) return 'medium';
    return 'low';
  }

  /**
   * Calculate network statistics
   *
   * @param array $contacts Contact data
   * @param array $relationships Relationship data
   * @return array Statistics
   */
  private static function calculateNetworkStats($contacts, $relationships) {
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

    $avgInfluence = $total > 0 ? array_sum(array_column($contacts, 'influence')) / $total : 0;
    $avgSupport = $total > 0 ? array_sum(array_column($contacts, 'support')) / $total : 0;

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
   * Export network data to CSV format
   *
   * @param array $params Parameters for filtering
   * @return array CSV data
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
   * Get demo data when no real data is available
   *
   * @return array Demo network data
   */
  private static function getDemoData() {
    return [
      'nodes' => [
        ['id' => 1, 'name' => 'John Smith', 'type' => 'Individual', 'subtype' => '', 'influence' => 5, 'support' => 4, 'group' => 'high', 'notes' => 'Key decision maker'],
        ['id' => 2, 'name' => 'Mary Johnson', 'type' => 'Individual', 'subtype' => '', 'influence' => 4, 'support' => 5, 'group' => 'high', 'notes' => 'Strong supporter'],
        ['id' => 3, 'name' => 'Tech Corporation', 'type' => 'Organization', 'subtype' => '', 'influence' => 3, 'support' => 2, 'group' => 'medium', 'notes' => 'Potential opposition'],
        ['id' => 4, 'name' => 'Community Group', 'type' => 'Organization', 'subtype' => '', 'influence' => 2, 'support' => 5, 'group' => 'low', 'notes' => 'Grassroots support'],
        ['id' => 5, 'name' => 'City Council', 'type' => 'Organization', 'subtype' => '', 'influence' => 5, 'support' => 3, 'group' => 'high', 'notes' => 'Regulatory authority'],
      ],
      'links' => [
        ['id' => 1, 'source' => 1, 'target' => 2, 'type' => 'Colleague', 'strength' => 2, 'start_date' => '', 'end_date' => ''],
        ['id' => 2, 'source' => 2, 'target' => 3, 'type' => 'Advisor', 'strength' => 3, 'start_date' => '', 'end_date' => ''],
        ['id' => 3, 'source' => 1, 'target' => 4, 'type' => 'Member', 'strength' => 1, 'start_date' => '', 'end_date' => ''],
        ['id' => 4, 'source' => 3, 'target' => 5, 'type' => 'Reports To', 'strength' => 2, 'start_date' => '', 'end_date' => ''],
        ['id' => 5, 'source' => 4, 'target' => 5, 'type' => 'Advocate', 'strength' => 1, 'start_date' => '', 'end_date' => ''],
      ],
      'stats' => ['total' => 5, 'high_influence' => 3, 'supporters' => 2, 'opposition' => 1],
      'metadata' => ['total_contacts' => 5, 'filtered_contacts' => 5, 'total_relationships' => 5, 'is_demo_data' => TRUE]
    ];
  }
}