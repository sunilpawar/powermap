<?php
use CRM_Powermap_ExtensionUtil as E;

class CRM_Powermap_Page_Dashboard extends CRM_Core_Page {

  public function run() {
    CRM_Utils_System::setTitle(E::ts('Power Mapping Dashboard'));

    // Get power mapping data
    $stakeholders = $this->getStakeholderData();
    $campaigns = $this->getCampaignData();

    // Assign variables to template
    $this->assign('stakeholders', $stakeholders);
    $this->assign('campaigns', $campaigns);
    $this->assign('totalStakeholders', count($stakeholders));

    // Add CSS and JS resources
    CRM_Core_Resources::singleton()
      ->addStyleFile('com.yourorg.powermap', 'css/powermap.css')
      ->addScriptFile('com.yourorg.powermap', 'js/powermap.js')
      ->addScriptFile('com.yourorg.powermap', 'js/d3.v7.min.js');

    parent::run();
  }

  /**
   * Get stakeholder data for power mapping
   */
  private function getStakeholderData() {
    $query = "
      SELECT
        c.id,
        c.display_name,
        c.contact_type,
        c.contact_sub_type,
        pm_influence.influence_level_value as influence_level,
        pm_support.support_level_value as support_level,
        pm_type.stakeholder_type_value as stakeholder_type,
        pm_authority.decision_authority_value as decision_authority,
        pm_priority.engagement_priority_value as engagement_priority,
        pm_date.last_assessment_date_value as last_assessment_date,
        pm_notes.assessment_notes_value as assessment_notes,
        email.email,
        phone.phone
      FROM civicrm_contact c
      LEFT JOIN civicrm_value_power_mapping_data_1 pm ON pm.entity_id = c.id
      LEFT JOIN civicrm_email email ON email.contact_id = c.id AND email.is_primary = 1
      LEFT JOIN civicrm_phone phone ON phone.contact_id = c.id AND phone.is_primary = 1
      WHERE c.is_deleted = 0
        AND (pm.influence_level_value IS NOT NULL OR pm.support_level_value IS NOT NULL)
      ORDER BY c.display_name
    ";

    $dao = CRM_Core_DAO::executeQuery($query);
    $stakeholders = [];

    while ($dao->fetch()) {
      $stakeholders[] = [
        'id' => $dao->id,
        'name' => $dao->display_name,
        'contact_type' => $dao->contact_type,
        'influence_level' => $dao->influence_level,
        'support_level' => $dao->support_level,
        'stakeholder_type' => $dao->stakeholder_type,
        'decision_authority' => $dao->decision_authority,
        'engagement_priority' => $dao->engagement_priority,
        'last_assessment_date' => $dao->last_assessment_date,
        'assessment_notes' => $dao->assessment_notes,
        'email' => $dao->email,
        'phone' => $dao->phone,
      ];
    }

    return $stakeholders;
  }

  /**
   * Get campaign data for filtering
   */
  private function getCampaignData() {
    try {
      $result = civicrm_api3('Campaign', 'get', [
        'is_active' => 1,
        'options' => ['limit' => 0, 'sort' => 'title ASC'],
      ]);
      return $result['values'];
    }
    catch (Exception $e) {
      return [];
    }
  }
}
