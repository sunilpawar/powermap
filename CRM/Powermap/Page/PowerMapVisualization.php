<?php

use CRM_Powermap_ExtensionUtil as E;

/**
 * PowerMap Visualization Page Handler - Updated to use NetworkDataService
 */
class CRM_Powermap_Page_PowerMapVisualization extends CRM_Core_Page {

  /**
   * Main page execution method
   */
  public function run() {
    try {
      // Load required JavaScript and CSS resources
      $this->loadPageResources();

      // Process request parameters
      $params = $this->processRequestParameters();

      // Prepare template variables
      $this->prepareTemplateData($params);

      // Get network data using the common service
      $networkData = CRM_Powermap_Service_NetworkDataService::getNetworkData($params);

      $this->assign('contactsJson', json_encode($networkData));

      // Call parent run method
      parent::run();

    }
    catch (Exception $e) {
      // Log error and provide fallback data
      CRM_Core_Error::debug_log_message('PowerMap Visualization Error: ' . $e->getMessage());
      $this->handleError($e);
    }
  }

  /**
   * Load required CSS and JavaScript resources for the page
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
   */
  private function processRequestParameters(): array {
    $contactIDs = !empty($_REQUEST['contact_id']) ? explode(',', $_REQUEST['contact_id']) : [];
    $contactIDs = array_map('trim', $contactIDs);

    return [
      'group_id' => !empty($_REQUEST['group_id']) ? (int)$_REQUEST['group_id'] : NULL,
      'only_relationship' => !empty($_REQUEST['only_relationship']),
      'contact_id' => $contactIDs,
      'influence_min' => isset($_REQUEST['influence_min']) ? (int)$_REQUEST['influence_min'] : 1,
      'support_min' => isset($_REQUEST['support_min']) ? (int)$_REQUEST['support_min'] : 1,
    ];
  }

  /**
   * Prepare template data and variables
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
    $this->assign('currentContact', implode(',', $params['contact_id']));
  }

  /**
   * Handle errors gracefully with fallback data
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
}