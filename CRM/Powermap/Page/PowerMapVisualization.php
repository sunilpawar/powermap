<?php
use CRM_Powermap_ExtensionUtil as E;

class CRM_Powermap_Page_PowerMapVisualization extends CRM_Core_Page {

  public function run() {
    // Add CSS and JS resources
    $groups = ['' => ts('- select group -')] + CRM_Core_PseudoConstant::nestedGroup();
    $this->assign('groups', $groups);
    CRM_Core_Resources::singleton()
      ->addScriptFile('com.skvare.powermap', 'js/d3.v4.js', 100);

    CRM_Core_Resources::singleton()
      ->addScriptFile('com.skvare.powermap', 'js/powermap-visualization.js', 110)
      ->addStyleFile('com.skvare.powermap', 'css/powermap.css');

    // Assign template variables
    $this->assign('pageTitle', ts('PowerMap Visualization'));

    // Get contact data for initial load
    $groupID = NULL;
    $onlyRelationship = FALSE;
    if (!empty($_REQUEST['group_id'])) {
      $groupID = $_REQUEST['group_id'];
    }
    if (!empty($_REQUEST['only_relationship'])) {
      $onlyRelationship = TRUE;
    }
    $contacts = $this->getContactsWithRelationships($groupID, $onlyRelationship);
    $this->assign('contactsJson', json_encode($contacts));

    parent::run();
  }

  /**
   * Get contacts with their relationships for network visualization
   */
  private function getContactsWithRelationships($groupID = NULL, $onlyRelationship = FALSE) {
    $contacts = [];
    $relationships = [];

    // Get relationships
    if (!empty($groupID)) {
      $result = Civi\Api4\GroupContact::get()
        ->addWhere('group_id', '=', $groupID)    // your group ID
        ->addWhere('status', '=', 'Added')  // optional, only current members
        ->execute()->getArrayCopy();

      $groupContactIds = array_column($result, 'contact_id');
    }
    if (empty($groupContactIds)) {
      $groupContactIds = [2864];
    }
    $contactIds = $groupContactIds;
    $contactIds = array_combine($contactIds, $contactIds);
    // Get all contacts
    [$contacts, $contactStrengthLevels] = $this->getContactDetails($contactIds);

    // Get relationships
    [$relationships, $relationshipContacts] = $this->getContactRelationships($contactIds, $contactStrengthLevels);
    $missingContact = [];
    foreach ($relationshipContacts as $contactRel) {
      if (!array_key_exists($contactRel, $contacts)) {
        $missingContact[] = $contactRel;
      }
    }
    if (!empty($missingContact)) {
      [$contactsMissing, $contactStrengthLevels] = $this->getContactDetails($missingContact);
      $contacts = array_merge($contacts, $contactsMissing);
    }

    if ($onlyRelationship) {
      // Filter contacts to only those with relationships
      // Generate the code get only contact from $contacts which are having
      // present in $relationshipContacts array.
      $contacts = array_filter($contacts, function ($contact) use ($relationshipContacts) {
        return in_array($contact['id'], $relationshipContacts);
      });
      $contacts = array_values($contacts);
    }

    return [
      'nodes' => $contacts,
      'links' => $relationships,
    ];
  }

  private function getContactRelationships($contactIDs, $contactStrengthLevels) {
    $relationships = [];
    $relationshipResult = \Civi\Api4\Relationship::get(TRUE)
      ->addSelect('contact_id_a', 'contact_id_b', 'relationship_type.label_a_b')
      ->addJoin('RelationshipType AS relationship_type', 'INNER')
      ->addWhere('contact_id_a', 'IN', $contactIDs)
      ->addClause('OR', ['contact_id_b', 'IN', $contactIDs])
      ->addWhere('is_active', '=', TRUE)
      ->execute()->getArrayCopy();
    $relationshipContacts = [];
    foreach ($relationshipResult as $relationship) {
      if ($relationship['contact_id_a'] == $relationship['contact_id_b']) {
        continue; // Skip self-relationships
      }
      $relationshipContacts[$relationship['contact_id_a']] = $relationship['contact_id_a'];
      $relationshipContacts[$relationship['contact_id_b']] = $relationship['contact_id_b'];
      // Determine link strength based on the higher strength level of the two contacts
      if (array_key_exists($relationship['contact_id_a'], $contactStrengthLevels)) {
        $contactSourceStrength = $contactStrengthLevels[$relationship['contact_id_a']];
      }
      else {
        $contactSourceStrength = $this->getStrengthLevel($relationship['contact_id_a']);
      }
      if (array_key_exists($relationship['contact_id_b'], $contactStrengthLevels)) {
        $contactTargetStrength = $contactStrengthLevels[$relationship['contact_id_b']];
      }
      else {
        $contactTargetStrength = $this->getStrengthLevel($relationship['contact_id_b']);
      }

      $contactLinkStrength = max($contactSourceStrength, $contactTargetStrength);
      $relationships[] = [
        'source' => $relationship['contact_id_a'],
        'target' => $relationship['contact_id_b'],
        'type' => $relationship['relationship_type.label_a_b'],
        'strength' => $contactLinkStrength,
      ];
    }
    return [$relationships, $relationshipContacts];

  }

  private function getContactDetails($contactIDs) {
    $influenceLevelInfo = CRM_Powermap_API_PowerMap::getCustomFieldInfo('influence_level');
    $supportLevelInfo = CRM_Powermap_API_PowerMap::getCustomFieldInfo('support_level');
    $relationshipStrengthLevelInfo = CRM_Powermap_API_PowerMap::getCustomFieldInfo('relationship_strength');

    $contactParams = [
      'sequential' => 1,
      'is_deleted' => 0,
      'options' => ['limit' => 1000],
      'id' => ['IN' => $contactIDs],
      'return' => ['id', 'display_name', 'contact_type', 'contact_sub_type',
        $influenceLevelInfo['column_field'],
        $supportLevelInfo['column_field'],
        $relationshipStrengthLevelInfo['column_field'],
      ],
    ];
    $contactResult = civicrm_api3('Contact', 'get', $contactParams);
    $contactStrengthLevels = [];
    foreach ($contactResult['values'] as $contact) {
      $customInfo = [
        'influence_level' => !empty($contact[$influenceLevelInfo['column_field']]) ? (int)$contact[$influenceLevelInfo['column_field']] : 1,
        'support_level' => !empty($contact[$supportLevelInfo['column_field']]) ? (int)$contact[$supportLevelInfo['column_field']] : 1,
        'strength_level' => !empty($contact[$relationshipStrengthLevelInfo['column_field']]) ? (int)$contact[$relationshipStrengthLevelInfo['column_field']] : 1,
      ];
      $contactStrengthLevels[$contact['id']] = $customInfo['strength_level'];
      $contacts[$contact['id']] = [
        'id' => $contact['id'],
        'name' => $contact['display_name'],
        'type' => $contact['contact_type'],
        'influence' => $customInfo['influence_level'],
        'support' => $customInfo['support_level'],
      ];
    }
    return [$contacts, $contactStrengthLevels];
  }

  /**
   * Get influence level for a contact (from custom field or default)
   */
  private function getContactCustomInfo($contactId) {
    $influenceLevelInfo = CRM_Powermap_API_PowerMap::getCustomFieldInfo('influence_level');
    $supportLevelInfo = CRM_Powermap_API_PowerMap::getCustomFieldInfo('support_level');
    // Try to get from custom field first
    try {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $contactId,
        'return' => [
          $influenceLevelInfo['column_field'],
          $supportLevelInfo['column_field'],
        ],

      ]);
      $result = [
        'influence_level' => !empty($result['values'][0][$influenceLevelInfo['column_field']]) ? (int)$result['values'][0][$influenceLevelInfo['column_field']] : 1,
        'support_level' => !empty($result['values'][0][$supportLevelInfo['column_field']]) ? (int)$result['values'][0][$supportLevelInfo['column_field']] : 1,
      ];
      return $result;
    }
    catch (Exception $e) {
      // Custom field doesn't exist, use default
    }

    // Default random influence level for demo
    return rand(1, 5);
  }

  /**
   * Get support level for a contact
   */
  private function getStrengthLevel($contactId) {
    //return rand(1, 3);
    // Try to get from custom field first
    $relationshipStrengthLevelInfo = CRM_Powermap_API_PowerMap::getCustomFieldInfo('relationship_strength');
    try {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $contactId,
        'return' => [$relationshipStrengthLevelInfo['column_field']], // Assuming custom field exists
      ]);

      return !empty($result['values'][0][$relationshipStrengthLevelInfo['column_field']]) ?
        (int)$result['values'][0][$relationshipStrengthLevelInfo['column_field']] : 1;
    }
    catch (Exception $e) {
      // Custom field doesn't exist, use default
    }

    // Default random support level for demo
    return rand(1, 5);
  }

  /**
   * Get relationship type name
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
    }
    catch (Exception $e) {
      // Return default
    }

    return 'Related to';
  }
}
