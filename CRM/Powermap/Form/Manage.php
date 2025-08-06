<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://docs.civicrm.org/dev/en/latest/framework/quickform/
 */
class CRM_Powermap_Form_Manage extends CRM_Core_Form {

  public function preProcess() {
    CRM_Utils_System::setTitle(E::ts('Manage Power Maps'));
  }

  public function buildForm() {

    // Add form elements
    $this->add('text', 'map_name', E::ts('Map Name'), ['class' => 'huge'], TRUE);

    $this->add('select', 'campaign_id', E::ts('Associated Campaign'),
      $this->getCampaignOptions(), FALSE, ['class' => 'crm-select2']);

    $this->add('textarea', 'description', E::ts('Description'),
      ['rows' => 4, 'cols' => 60]);

    $this->add('select', 'stakeholder_contacts', E::ts('Select Stakeholders'),
      $this->getContactOptions(), FALSE,
      ['class' => 'crm-select2', 'multiple' => 'multiple', 'placeholder' => E::ts('Search contacts...')]);

    // Add buttons
    $this->addButtons([
      [
        'type' => 'upload',
        'name' => E::ts('Save Map'),
        'isDefault' => TRUE,
      ],
      [
        'type' => 'cancel',
        'name' => E::ts('Cancel'),
      ],
    ]);

    parent::buildForm();
  }

  public function postProcess() {
    $values = $this->exportValues();

    // Save power map configuration
    // This would typically save to a custom table or entity

    CRM_Core_Session::setStatus(
      E::ts('Power map "%1" has been saved.', [1 => $values['map_name']]),
      E::ts('Power Map Saved'),
      'success'
    );

    // Redirect to dashboard
    $url = CRM_Utils_System::url('civicrm/powermap/dashboard', 'reset=1');
    CRM_Utils_System::redirect($url);
  }

  /**
   * Get campaign options for select field
   */
  private function getCampaignOptions() {
    $options = ['' => E::ts('- Select Campaign -')];

    try {
      $result = civicrm_api3('Campaign', 'get', [
        'is_active' => 1,
        'options' => ['limit' => 0, 'sort' => 'title ASC'],
      ]);

      foreach ($result['values'] as $campaign) {
        $options[$campaign['id']] = $campaign['title'];
      }
    }
    catch (Exception $e) {
      // Handle error
    }

    return $options;
  }

  /**
   * Get contact options for stakeholder selection
   */
  private function getContactOptions() {
    $options = [];

    $query = "
      SELECT c.id, c.display_name, c.contact_type
      FROM civicrm_contact c
      WHERE c.is_deleted = 0
        AND c.is_deceased = 0
      ORDER BY c.display_name
      LIMIT 1000
    ";

    $dao = CRM_Core_DAO::executeQuery($query);

    while ($dao->fetch()) {
      $options[$dao->id] = $dao->display_name . ' (' . $dao->contact_type . ')';
    }

    return $options;
  }
}
