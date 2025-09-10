<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * PowerMap Visualization Page Handler
 *
 * This class manages the main PowerMap visualization page, handling data retrieval,
 * filtering, and resource loading for the network visualization interface.
 *
 * Key Features:
 * - Group-based contact filtering
 * - Relationship-only view mode
 * - Custom field integration for influence, support, and relationship strength
 * - Optimized data structure for D3.js visualization
 *
 * @author PowerMap Team
 * @since 1.0.0
 */
class CRM_Powermap_Page_PowerMapVisualization extends CRM_Core_Page {

  /**
   * Constants for default values and configuration
   */
  const DEFAULT_CONTACT_LIMIT = 1000;
  const DEFAULT_INFLUENCE_LEVEL = 1;
  const DEFAULT_SUPPORT_LEVEL = 1;
  const DEFAULT_STRENGTH_LEVEL = 1;
  const FALLBACK_CONTACT_ID = 2864; // Used when no group contacts found

  /**
   * Cache for custom field information to avoid repeated API calls
   * @var array
   */
  private $customFieldCache = [];

  /**
   * Cache for contact data to optimize multiple relationship queries
   * @var array
   */
  private $contactDataCache = [];

  /**
   * Main page execution method
   *
   * Handles resource loading, parameter processing, and data preparation
   * for the PowerMap visualization interface.
   */
  public function run() {
    try {
      // Load required JavaScript and CSS resources
      $this->loadPageResources();

      // Process request parameters
      $params = $this->processRequestParameters();

      // Prepare template variables
      $this->prepareTemplateData($params);

      // Get and assign contact network data
      $networkData = $this->getContactsWithRelationships(
        $params['group_id'],
        $params['only_relationship']
      );
      //echo json_encode($networkData); exit;
      $this->assign('contactsJson', json_encode($networkData));

      // Call parent run method
      parent::run();

    } catch (Exception $e) {
      // Log error and provide fallback data
      CRM_Core_Error::debug_log_message('PowerMap Visualization Error: ' . $e->getMessage());
      $this->handleError($e);
    }
  }

  /**
   * Load required CSS and JavaScript resources for the page
   *
   * Loads D3.js library, PowerMap visualization scripts, and styling.
   * Resources are loaded with proper weight ordering to ensure dependencies.
   */
  private function loadPageResources() {
    $resourceManager = CRM_Core_Resources::singleton();

    // Load D3.js library first (weight 100)
    $resourceManager->addScriptFile('com.skvare.powermap', 'js/d3.v4.js', 100);

    // Load PowerMap visualization script (weight 110, after D3.js)
    $resourceManager->addScriptFile('com.skvare.powermap', 'js/powermap-visualization.js', 110);

    // Load PowerMap CSS styling
    $resourceManager->addStyleFile('com.skvare.powermap', 'css/powermap.css');
  }

  /**
   * Process and validate request parameters
   *
   * Extracts and validates URL parameters for group filtering and view modes.
   *
   * @return array Processed parameters with defaults
   */
  private function processRequestParameters() {
    return [
      'group_id' => !empty($_REQUEST['group_id']) ? (int) $_REQUEST['group_id'] : NULL,
      'only_relationship' => !empty($_REQUEST['only_relationship']),
    ];
  }

  /**
   * Prepare template data and variables
   *
   * Sets up template variables including group options and page configuration.
   *
   * @param array $params Processed request parameters
   */
  private function prepareTemplateData($params) {
    // Get available groups for filter dropdown
    $groups = ['' => ts('- select group -')] + CRM_Core_PseudoConstant::nestedGroup();
    $this->assign('groups', $groups);

    // Set page title
    $this->assign('pageTitle', ts('PowerMap Visualization'));

    // Pass current parameters to template for state management
    $this->assign('currentGroupId', $params['group_id']);
    $this->assign('onlyRelationship', $params['only_relationship']);
  }

  /**
   * Get contacts with their relationships for network visualization
   *
   * Main method that orchestrates the data gathering process:
   * 1. Gets base contact list (from group or default)
   * 2. Fetches contact details with custom field values
   * 3. Retrieves relationships between contacts
   * 4. Adds missing contacts referenced in relationships
   * 5. Filters data based on view mode (relationship-only vs all)
   *
   * @param int|null $groupID Group ID to filter contacts (optional)
   * @param bool $onlyRelationship Whether to show only contacts with relationships
   * @return array Network data structure with nodes and links
   */
  private function getContactsWithRelationships($groupID = NULL, $onlyRelationship = FALSE) {
    // Step 1: Get initial contact IDs
    $contactIds = $this->getContactIdsFromGroup($groupID);

    // Step 2: Get contact details with custom field values
    [$contacts, $contactStrengthLevels] = $this->getContactDetails($contactIds);

    // Step 3: Get relationships between contacts
    [$relationships, $relationshipContactIds] = $this->getContactRelationships(
      $contactIds,
      $contactStrengthLevels
    );

    // Step 4: Add missing contacts that are referenced in relationships
    $contacts = $this->addMissingRelationshipContacts(
      $contacts,
      $relationshipContactIds
    );

    // Step 5: Filter contacts if relationship-only mode is enabled
    if ($onlyRelationship) {
      $contacts = $this->filterContactsWithRelationships($contacts, $relationshipContactIds);
    }

    return [
      'nodes' => array_values($contacts), // Ensure sequential array for JSON
      'links' => $relationships,
      'metadata' => [
        'total_contacts' => count($contacts),
        'total_relationships' => count($relationships),
        'group_id' => $groupID,
        'relationship_only' => $onlyRelationship,
        'generated_at' => date('Y-m-d H:i:s'),
      ],
    ];
  }

  /**
   * Get contact IDs from specified group or use fallback
   *
   * @param int|null $groupID Group ID to get contacts from
   * @return array Array of contact IDs
   */
  private function getContactIdsFromGroup($groupID) {
    if (empty($groupID)) {
      // Return fallback contact ID if no group specified
      return [self::FALLBACK_CONTACT_ID];
    }

    try {
      // Use API4 for better performance and cleaner syntax
      $groupContacts = \Civi\Api4\GroupContact::get()
        ->addSelect('contact_id')
        ->addWhere('group_id', '=', $groupID)
        ->addWhere('status', '=', 'Added') // Only current group members
        ->execute();

      $contactIds = [];
      foreach ($groupContacts as $groupContact) {
        $contactIds[] = $groupContact['contact_id'];
      }

      // Return fallback if no contacts found in group
      return !empty($contactIds) ? $contactIds : [self::FALLBACK_CONTACT_ID];

    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting group contacts: ' . $e->getMessage());
      return [self::FALLBACK_CONTACT_ID];
    }
  }

  /**
   * Get detailed contact information including custom field values
   *
   * Retrieves contact basic information along with PowerMap-specific custom fields:
   * - Influence Level: How much influence the contact has (1-5)
   * - Support Level: How supportive the contact is (1-5)
   * - Relationship Strength: Default strength for relationships (1-3)
   *
   * @param array $contactIds Array of contact IDs to retrieve
   * @return array [$contacts, $contactStrengthLevels] Contacts data and strength mapping
   */
  private function getContactDetails($contactIds) {
    if (empty($contactIds)) {
      return [[], []];
    }

    // Get custom field information (cached to avoid repeated API calls)
    $customFields = $this->getCustomFieldInfo();

    // Prepare API parameters for contact retrieval
    $contactParams = [
      'sequential' => 1,
      'is_deleted' => 0,
      'options' => ['limit' => self::DEFAULT_CONTACT_LIMIT],
      'id' => ['IN' => $contactIds],
      'return' => array_merge(
        ['id', 'display_name', 'contact_type', 'contact_sub_type'],
        array_column($customFields, 'column_field') // Add custom field columns
      ),
    ];

    try {
      $contactResult = civicrm_api3('Contact', 'get', $contactParams);

      $contacts = [];
      $contactStrengthLevels = [];

      foreach ($contactResult['values'] as $contact) {
        // Extract custom field values with fallback defaults
        $customValues = $this->extractCustomFieldValues($contact, $customFields);

        // Store contact data optimized for visualization
        $contacts[$contact['id']] = [
          'id' => (int) $contact['id'],
          'name' => $contact['display_name'] ?: 'Contact ' . $contact['id'],
          'type' => $contact['contact_type'] ?: 'Individual',
          'subtype' => $contact['contact_sub_type'] ?? '',
          'influence' => $customValues['influence_level'],
          'support' => $customValues['support_level'],
        ];

        // Cache strength levels for relationship calculations
        $contactStrengthLevels[$contact['id']] = $customValues['strength_level'];
      }

      return [$contacts, $contactStrengthLevels];

    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting contact details: ' . $e->getMessage());
      return [[], []];
    }
  }

  /**
   * Get relationships between contacts with strength calculations
   *
   * Retrieves active relationships and calculates link strength based on
   * the higher strength level of the two connected contacts.
   *
   * @param array $contactIds Base contact IDs to find relationships for
   * @param array $contactStrengthLevels Mapping of contact ID to strength level
   * @return array [$relationships, $relationshipContactIds] Relationship data and all contact IDs involved
   */
  private function getContactRelationships($contactIds, $contactStrengthLevels) {
    if (empty($contactIds)) {
      return [[], []];
    }

    try {
      // Use API4 for better join performance
      $relationshipResult = \Civi\Api4\Relationship::get(TRUE)
        ->addSelect('contact_id_a', 'contact_id_b', 'relationship_type.label_a_b')
        ->addJoin('RelationshipType AS relationship_type', 'INNER')
        ->addWhere('contact_id_a', 'IN', $contactIds)
        ->addClause('OR', ['contact_id_b', 'IN', $contactIds])
        ->addWhere('is_active', '=', TRUE)
        ->execute();

      $relationships = [];
      $relationshipContactIds = [];

      foreach ($relationshipResult as $relationship) {
        // Skip self-relationships (shouldn't happen but safety check)
        if ($relationship['contact_id_a'] == $relationship['contact_id_b']) {
          continue;
        }

        $sourceId = (int) $relationship['contact_id_a'];
        $targetId = (int) $relationship['contact_id_b'];

        // Track all contacts involved in relationships
        $relationshipContactIds[$sourceId] = $sourceId;
        $relationshipContactIds[$targetId] = $targetId;

        // Calculate link strength based on contact strength levels
        $linkStrength = $this->calculateLinkStrength(
          $sourceId,
          $targetId,
          $contactStrengthLevels
        );

        // Build relationship data structure for visualization
        $relationships[] = [
          'source' => $sourceId,
          'target' => $targetId,
          'type' => $relationship['relationship_type.label_a_b'] ?: 'Related to',
          'strength' => $linkStrength,
        ];
      }

      return [$relationships, $relationshipContactIds];

    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting relationships: ' . $e->getMessage());
      return [[], []];
    }
  }

  /**
   * Calculate link strength between two contacts
   *
   * Uses the higher strength level of the two contacts involved in the relationship.
   * Falls back to individual contact queries if strength not cached.
   *
   * @param int $sourceId Source contact ID
   * @param int $targetId Target contact ID
   * @param array $contactStrengthLevels Cached strength levels
   * @return int Link strength (1-3)
   */
  private function calculateLinkStrength($sourceId, $targetId, $contactStrengthLevels) {
    // Get source contact strength
    $sourceStrength = $contactStrengthLevels[$sourceId] ?? $this->getContactStrengthLevel($sourceId);

    // Get target contact strength
    $targetStrength = $contactStrengthLevels[$targetId] ?? $this->getContactStrengthLevel($targetId);

    // Return the higher strength level (stronger connection)
    return max($sourceStrength, $targetStrength);
  }

  /**
   * Add missing contacts that are referenced in relationships
   *
   * When relationships reference contacts not in the initial contact set,
   * this method fetches their details to complete the network.
   *
   * @param array $existingContacts Current contact data
   * @param array $relationshipContactIds All contact IDs from relationships
   * @return array Complete contact data including missing contacts
   */
  private function addMissingRelationshipContacts($existingContacts, $relationshipContactIds) {
    // Find contacts referenced in relationships but not in current contact set
    $missingContactIds = [];
    foreach ($relationshipContactIds as $contactId) {
      if (!array_key_exists($contactId, $existingContacts)) {
        $missingContactIds[] = $contactId;
      }
    }

    // Fetch missing contact details if any found
    if (!empty($missingContactIds)) {
      [$missingContacts, $strengthLevels] = $this->getContactDetails($missingContactIds);
      $existingContacts = array_merge($existingContacts, $missingContacts);
    }

    return $existingContacts;
  }

  /**
   * Filter contacts to only those with relationships
   *
   * Used in relationship-only view mode to show only contacts
   * that have at least one relationship connection.
   *
   * @param array $contacts All contact data
   * @param array $relationshipContactIds Contact IDs that have relationships
   * @return array Filtered contact data
   */
  private function filterContactsWithRelationships($contacts, $relationshipContactIds) {
    return array_filter($contacts, function($contact) use ($relationshipContactIds) {
      return array_key_exists($contact['id'], $relationshipContactIds);
    });
  }

  /**
   * Get cached custom field information for PowerMap fields
   *
   * Retrieves and caches custom field metadata to avoid repeated API calls.
   * Handles the three main PowerMap custom fields:
   * - influence_level: Contact's influence rating
   * - support_level: Contact's support rating
   * - relationship_strength: Default relationship strength
   *
   * @return array Custom field information indexed by field name
   */
  private function getCustomFieldInfo() {
    if (empty($this->customFieldCache)) {
      $fieldNames = ['influence_level', 'support_level', 'relationship_strength'];

      foreach ($fieldNames as $fieldName) {
        try {
          $this->customFieldCache[$fieldName] = CRM_Powermap_API_PowerMap::getCustomFieldInfo($fieldName);
        } catch (Exception $e) {
          // Log error but continue with other fields
          CRM_Core_Error::debug_log_message("Error getting custom field info for {$fieldName}: " . $e->getMessage());

          // Provide fallback structure
          $this->customFieldCache[$fieldName] = [
            'column_field' => 'custom_' . $fieldName,
            'exists' => FALSE,
          ];
        }
      }
    }

    return $this->customFieldCache;
  }

  /**
   * Extract custom field values from contact data with fallback defaults
   *
   * @param array $contact Contact data from API
   * @param array $customFields Custom field information
   * @return array Extracted values with defaults
   */
  private function extractCustomFieldValues($contact, $customFields) {
    return [
      'influence_level' => $this->getCustomFieldValue(
        $contact,
        $customFields['influence_level'],
        self::DEFAULT_INFLUENCE_LEVEL
      ),
      'support_level' => $this->getCustomFieldValue(
        $contact,
        $customFields['support_level'],
        self::DEFAULT_SUPPORT_LEVEL
      ),
      'strength_level' => $this->getCustomFieldValue(
        $contact,
        $customFields['relationship_strength'],
        self::DEFAULT_STRENGTH_LEVEL
      ),
    ];
  }

  /**
   * Get custom field value with fallback default
   *
   * @param array $contact Contact data
   * @param array $fieldInfo Custom field information
   * @param int $default Default value if field not found
   * @return int Field value or default
   */
  private function getCustomFieldValue($contact, $fieldInfo, $default) {
    $columnField = $fieldInfo['column_field'] ?? null;

    if ($columnField && !empty($contact[$columnField])) {
      return (int) $contact[$columnField];
    }

    return $default;
  }

  /**
   * Get individual contact's strength level (used for missing contacts)
   *
   * This method is used as a fallback when a contact's strength level
   * is not available in the cached strength levels array.
   *
   * @param int $contactId Contact ID to get strength for
   * @return int Strength level (1-3)
   */
  private function getContactStrengthLevel($contactId) {
    // Check cache first to avoid duplicate queries
    if (isset($this->contactDataCache[$contactId]['strength'])) {
      return $this->contactDataCache[$contactId]['strength'];
    }

    try {
      $customFields = $this->getCustomFieldInfo();
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

      // Cache the result
      $this->contactDataCache[$contactId]['strength'] = $strength;

      return $strength;

    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message("Error getting strength level for contact {$contactId}: " . $e->getMessage());
      return self::DEFAULT_STRENGTH_LEVEL;
    }
  }

  /**
   * Handle errors gracefully with fallback data
   *
   * Provides minimal data structure to prevent page crashes
   * and logs detailed error information for debugging.
   *
   * @param Exception $e The exception that occurred
   */
  private function handleError($e) {
    // Log detailed error for debugging
    CRM_Core_Error::debug_log_message('PowerMap Visualization Error Details: ' . print_r([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
      ], TRUE));

    // Provide minimal fallback data to prevent page crash
    $fallbackData = [
      'nodes' => [],
      'links' => [],
      'metadata' => [
        'error' => TRUE,
        'message' => 'Error loading data. Please check system logs.',
        'generated_at' => date('Y-m-d H:i:s'),
      ],
    ];

    $this->assign('contactsJson', json_encode($fallbackData));
    $this->assign('pageTitle', ts('PowerMap Visualization - Error'));

    // Show user-friendly error message
    CRM_Core_Session::setStatus(
      ts('There was an error loading the PowerMap data. Please contact your system administrator.'),
      ts('PowerMap Error'),
      'error'
    );
  }

  /**
   * Legacy method for backward compatibility
   *
   * @deprecated Use getContactStrengthLevel() instead
   * @param int $contactId Contact ID
   * @return int Strength level
   */
  private function getStrengthLevel($contactId) {
    return $this->getContactStrengthLevel($contactId);
  }

  /**
   * Legacy method for backward compatibility
   *
   * @deprecated This method is no longer used in the optimized version
   * @param int $contactId Contact ID
   * @return array Custom field values
   */
  private function getContactCustomInfo($contactId) {
    // Kept for backward compatibility but not used in optimized version
    $customFields = $this->getCustomFieldInfo();

    try {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $contactId,
        'return' => [
          $customFields['influence_level']['column_field'],
          $customFields['support_level']['column_field'],
        ],
      ]);

      if (!empty($result['values'][0])) {
        return [
          'influence_level' => $this->getCustomFieldValue(
            $result['values'][0],
            $customFields['influence_level'],
            self::DEFAULT_INFLUENCE_LEVEL
          ),
          'support_level' => $this->getCustomFieldValue(
            $result['values'][0],
            $customFields['support_level'],
            self::DEFAULT_SUPPORT_LEVEL
          ),
        ];
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error in getContactCustomInfo: ' . $e->getMessage());
    }

    return [
      'influence_level' => self::DEFAULT_INFLUENCE_LEVEL,
      'support_level' => self::DEFAULT_SUPPORT_LEVEL,
    ];
  }

  /**
   * Legacy method for backward compatibility
   *
   * @deprecated This method is no longer used in the optimized version
   * @param int $relationshipTypeId Relationship type ID
   * @return string Relationship type name
   */
  private function getRelationshipTypeName($relationshipTypeId) {
    try {
      $result = civicrm_api3('RelationshipType', 'get', [
        'sequential' => 1,
        'id' => $relationshipTypeId,
        'return' => ['label_a_b'],
      ]);

      if (!empty($result['values'][0]['label_a_b'])) {
        return $result['values'][0]['label_a_b'];
      }
    } catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error getting relationship type name: ' . $e->getMessage());
    }

    return 'Related to';
  }
}