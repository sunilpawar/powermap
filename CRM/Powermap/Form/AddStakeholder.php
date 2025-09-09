<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Powermap_Form_AddStakeholder extends CRM_Core_Form {

  public function buildForm() {
    $this->setTitle(ts('Add New Stakeholder'));

    // Contact selection
    /*
    $this->add('entityref', 'contact_id', ts('Select Contact'), [
      'entity' => 'Contact',
      'api' => [
        'params' => ['is_deleted' => 0],
      ],
    ], TRUE);
    */

    $this->addEntityRef('contact_id', E::ts('Select Contact'), [
      'entity' => 'Contact',
      'multiple' => TRUE,
      'placeholder' => E::ts('Search and select contacts...'),
      'select' => ['minimumInputLength' => 0],
      'api' => [
        'params' => [
          'contact_type' => ['IN' => ['Individual', 'Organization']],
          'is_deleted' => 0
        ],
        'extra' => ['contact_type']
      ]
    ]);

    // Influence level
    $this->add('select', 'influence_level', ts('Influence Level'), [
      '' => ts('- Select -'),
      1 => ts('1 - Low'),
      2 => ts('2 - Medium-Low'),
      3 => ts('3 - Medium'),
      4 => ts('4 - High'),
      5 => ts('5 - Very High'),
    ], TRUE);

    // Support level
    $this->add('select', 'support_level', ts('Support Level'), [
      '' => ts('- Select -'),
      1 => ts('1 - Strong Opposition'),
      2 => ts('2 - Opposition'),
      3 => ts('3 - Neutral'),
      4 => ts('4 - Support'),
      5 => ts('5 - Strong Support'),
    ], TRUE);

    // Relationship type
    $relationshipTypes = $this->getRelationshipTypes();
    $this->add('select', 'relationship_type', ts('Relationship Type'), $relationshipTypes, TRUE);

    // Related to contact
    $this->addEntityRef('related_contact_id', E::ts('Related to Contact'), [
      'entity' => 'Contact',
      'multiple' => TRUE,
      'placeholder' => E::ts('Search and select contacts...'),
      'select' => ['minimumInputLength' => 0],
      'api' => [
        'params' => [
          'contact_type' => ['IN' => ['Individual', 'Organization']],
          'is_deleted' => 0
        ],
        'extra' => ['contact_type']
      ]
    ]);
    // Notes
    $this->add('textarea', 'notes', ts('Notes'), [
      'rows' => 4,
      'cols' => 60,
    ]);

    $this->addButtons([
      [
        'type' => 'submit',
        'name' => ts('Add Stakeholder'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => ts('Cancel'),
      ],
    ]);

    parent::buildForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    // Create or update custom fields for influence and support levels
    $this->updateContactCustomFields($values['contact_id'], $values);

    // Create relationship if specified
    if (!empty($values['related_contact_id'])) {
      $this->createRelationship($values);
    }

    CRM_Core_Session::setStatus(ts('Stakeholder added successfully'), ts('Success'), 'success');

    // Redirect back to PowerMap
    $url = CRM_Utils_System::url('civicrm/powermap', 'reset=1');
    CRM_Utils_System::redirect($url);

    parent::postProcess();
  }

  private function getRelationshipTypes() {
    $types = ['' => ts('- Select -')];

    try {
      $result = civicrm_api3('RelationshipType', 'get', [
        'sequential' => 1,
        'is_active' => 1,
        'options' => ['limit' => 0],
      ]);

      foreach ($result['values'] as $type) {
        $types[$type['id']] = $type['label_a_b'];
      }
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error fetching relationship types: ' . $e->getMessage());
    }

    return $types;
  }

  private function updateContactCustomFields($contactId, $values) {
    // This would typically use custom fields created during extension installation
    // For now, we'll store in contact custom data
    try {
      // You would need to create these custom fields during installation
      civicrm_api3('Contact', 'create', [
        'id' => $contactId,
        'custom_influence_level' => $values['influence_level'],
        'custom_support_level' => $values['support_level'],
        'custom_powermap_notes' => $values['notes'],
      ]);
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error updating contact custom fields: ' . $e->getMessage());
    }
  }

  private function createRelationship($values) {
    try {
      civicrm_api3('Relationship', 'create', [
        'contact_id_a' => $values['contact_id'],
        'contact_id_b' => $values['related_contact_id'],
        'relationship_type_id' => $values['relationship_type'],
        'is_active' => 1,
      ]);
    }
    catch (Exception $e) {
      CRM_Core_Error::debug_log_message('Error creating relationship: ' . $e->getMessage());
    }
  }
}
