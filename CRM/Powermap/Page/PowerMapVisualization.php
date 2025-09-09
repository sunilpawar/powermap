<?php
use CRM_Powermap_ExtensionUtil as E;

class CRM_Powermap_Page_PowerMapVisualization extends CRM_Core_Page {

  public function run() {
    // Add CSS and JS resources
    /*
    CRM_Core_Resources::singleton()
      ->addScriptFile('civicrm', 'bower_components/d3-3.5.x/d3.min.js', 100);
    */

    CRM_Core_Resources::singleton()
      ->addScriptFile('com.skvare.powermap', 'js/d3.v4.js', 100);
    /*
    CRM_Core_Resources::singleton()
      ->addScriptFile('com.skvare.powermap', 'js/d3-force.js', 110);
    */
    CRM_Core_Resources::singleton()
      ->addScriptFile('com.skvare.powermap', 'js/powermap-visualization.js', 110)
      ->addStyleFile('com.skvare.powermap', 'css/powermap.css');

    // Set page title
    //$this->setTitle(ts('PowerMap - Network Visualization'));

    // Assign template variables
    $this->assign('pageTitle', ts('PowerMap Visualization'));

    // Get contact data for initial load
    $contacts = $this->getContactsWithRelationships();
    //echo '<pre>'; print_r($contacts); echo '</pre>';exit;
    $this->assign('contactsJson', json_encode($contacts));

    parent::run();
  }

  /**
   * Get contacts with their relationships for network visualization
   */
  private function getContactsWithRelationships() {
    $contacts = [];
    $relationships = [];

    // Get all contacts
    $contactResult = civicrm_api3('Contact', 'get', [
      'sequential' => 1,
      'is_deleted' => 0,
      'options' => ['limit' => 100],
      'return' => ['id', 'display_name', 'contact_type', 'contact_sub_type'],
    ]);

    foreach ($contactResult['values'] as $contact) {
      $contacts[] = [
        'id' => $contact['id'],
        'name' => $contact['display_name'],
        'type' => $contact['contact_type'],
        'influence' => $this->getInfluenceLevel($contact['id']),
        'support' => $this->getSupportLevel($contact['id']),
      ];
    }

    // Get relationships
    $relationshipResult = civicrm_api3('Relationship', 'get', [
      'sequential' => 1,
      'is_active' => 1,
      'options' => ['limit' => 0],
      'return' => ['id', 'contact_id_a', 'contact_id_b', 'relationship_type_id'],
    ]);

    foreach ($relationshipResult['values'] as $relationship) {
      $relationships[] = [
        'id' => $relationship['id'],
        'source' => $relationship['contact_id_a'],
        'target' => $relationship['contact_id_b'],
        'type' => $this->getRelationshipTypeName($relationship['relationship_type_id']),
      ];
    }

    return [
      'nodes' => $contacts,
      'links' => $relationships,
    ];
  }

  /**
   * Get influence level for a contact (from custom field or default)
   */
  private function getInfluenceLevel($contactId) {
    // Try to get from custom field first
    try {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $contactId,
        'return' => ['custom_influence_level'], // Assuming custom field exists
      ]);

      if (!empty($result['values'][0]['custom_influence_level'])) {
        return (int)$result['values'][0]['custom_influence_level'];
      }
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
  private function getSupportLevel($contactId) {
    // Try to get from custom field first
    try {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $contactId,
        'return' => ['custom_support_level'], // Assuming custom field exists
      ]);

      if (!empty($result['values'][0]['custom_support_level'])) {
        return (int)$result['values'][0]['custom_support_level'];
      }
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
