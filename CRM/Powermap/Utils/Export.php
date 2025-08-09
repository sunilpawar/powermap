<?php

/**
 * Utility class for Power Mapping data export
 */
class CRM_Powermap_Utils_Export {

  /**
   * Export power map data in various formats
   */
  public static function exportPowerMap($mapId, $format = 'csv', $options = []) {
    $data = self::getPowerMapData($mapId, $options);

    switch (strtolower($format)) {
      case 'csv':
        return self::exportAsCSV($data, $options);
      case 'json':
        return self::exportAsJSON($data, $options);
      case 'pdf':
        return self::exportAsPDF($data, $options);
      case 'excel':
        return self::exportAsExcel($data, $options);
      default:
        throw new Exception('Unsupported export format: ' . $format);
    }
  }

  /**
   * Get power map data for export
   */
  private static function getPowerMapData($mapId, $options = []) {
    $data = [
      'map_info' => [],
      'stakeholders' => [],
      'summary' => []
    ];

    // Get map information (if we have a maps table)
    $data['map_info'] = [
      'id' => $mapId,
      'name' => $options['map_name'] ?? 'Power Map',
      'export_date' => date('Y-m-d H:i:s'),
      'exported_by' => CRM_Core_Session::getLoggedInContactDisplayName()
    ];

    // Get stakeholder data
    $customFields = self::getCustomFieldInfo();
    if (empty($customFields)) {
      return $data;
    }

    $query = "
      SELECT
        c.id,
        c.display_name,
        c.contact_type,
        c.contact_sub_type,
        e.email,
        p.phone,
        a.street_address,
        a.city,
        a.state_province_id,
        a.country_id,
        org.display_name as organization_name,
        pm.{$customFields['influence_column']} as influence_level,
        pm.{$customFields['support_column']} as support_level,
        pm.{$customFields['priority_column']} as engagement_priority,
        pm.{$customFields['type_column']} as stakeholder_type,
        pm.{$customFields['authority_column']} as decision_authority,
        pm.{$customFields['notes_column']} as assessment_notes,
        pm.{$customFields['date_column']} as last_assessment_date
      FROM civicrm_contact c
      LEFT JOIN {$customFields['table_name']} pm ON pm.entity_id = c.id
      LEFT JOIN civicrm_email e ON e.contact_id = c.id AND e.is_primary = 1
      LEFT JOIN civicrm_phone p ON p.contact_id = c.id AND p.is_primary = 1
      LEFT JOIN civicrm_address a ON a.contact_id = c.id AND a.is_primary = 1
      LEFT JOIN civicrm_contact org ON org.id = c.employer_id
      WHERE c.is_deleted = 0
        AND pm.{$customFields['influence_column']} IS NOT NULL
    ";

    // Apply filters
    $params = [];
    $whereConditions = [];
    $paramIndex = 1;

    if (!empty($options['campaign_id'])) {
      $whereConditions[] = "EXISTS (
        SELECT 1 FROM civicrm_campaign_contact cc
        WHERE cc.contact_id = c.id AND cc.campaign_id = %{$paramIndex}
      )";
      $params[$paramIndex] = [$options['campaign_id'], 'Integer'];
      $paramIndex++;
    }

    if (!empty($options['stakeholder_type'])) {
      $whereConditions[] = "pm.{$customFields['type_column']} LIKE %{$paramIndex}";
      $params[$paramIndex] = ['%' . $options['stakeholder_type'] . '%', 'String'];
      $paramIndex++;
    }

    if (!empty($whereConditions)) {
      $query .= ' AND ' . implode(' AND ', $whereConditions);
    }

    $query .= ' ORDER BY c.display_name';

    $dao = CRM_Core_DAO::executeQuery($query, $params);

    $summary = ['total' => 0, 'champions' => 0, 'targets' => 0, 'grassroots' => 0, 'monitor' => 0];

    while ($dao->fetch()) {
      $influenceScore = CRM_Powermap_BAO_Stakeholder::getInfluenceScore($dao->id);
      $supportScore = CRM_Powermap_BAO_Stakeholder::getSupportScore($dao->id);
      $quadrant = CRM_Powermap_BAO_Stakeholder::getStrategicQuadrant($dao->id);
      $strategy = CRM_Powermap_BAO_Stakeholder::getEngagementStrategy($dao->id);

      $stakeholder = [
        'id' => $dao->id,
        'name' => $dao->display_name,
        'contact_type' => $dao->contact_type,
        'contact_sub_type' => $dao->contact_sub_type,
        'email' => $dao->email,
        'phone' => $dao->phone,
        'address' => trim(implode(', ', array_filter([
          $dao->street_address,
          $dao->city,
          self::getStateProvinceName($dao->state_province_id),
          self::getCountryName($dao->country_id)
        ]))),
        'organization' => $dao->organization_name,
        'influence_level' => $dao->influence_level,
        'support_level' => $dao->support_level,
        'engagement_priority' => $dao->engagement_priority,
        'stakeholder_type' => $dao->stakeholder_type,
        'decision_authority' => $dao->decision_authority,
        'assessment_notes' => $dao->assessment_notes,
        'last_assessment_date' => $dao->last_assessment_date,
        'influence_score' => $influenceScore,
        'support_score' => $supportScore,
        'quadrant' => $quadrant,
        'strategy' => $strategy['strategy'],
        'priority' => $strategy['priority']
      ];

      $data['stakeholders'][] = $stakeholder;
      $summary['total']++;
      $summary[$quadrant]++;
    }

    $data['summary'] = $summary;

    return $data;
  }

  /**
   * Export as CSV
   */
  private static function exportAsCSV($data, $options = []) {
    $filename = 'powermap_export_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = CRM_Utils_File::tempdir() . '/' . $filename;

    $file = fopen($filepath, 'w');
    if (!$file) {
      throw new Exception('Could not create CSV file');
    }

    // Write BOM for UTF-8
    fwrite($file, "\xEF\xBB\xBF");

    // Headers
    $headers = [
      'ID',
      'Name',
      'Contact Type',
      'Email',
      'Phone',
      'Organization',
      'Influence Level',
      'Support Level',
      'Quadrant',
      'Engagement Priority',
      'Stakeholder Type',
      'Decision Authority',
      'Engagement Strategy',
      'Last Assessment Date'
    ];

    if (!empty($options['include_contact_details'])) {
      $headers[] = 'Address';
      $headers[] = 'Contact Sub Type';
    }

    if (!empty($options['include_scores'])) {
      $headers[] = 'Influence Score';
      $headers[] = 'Support Score';
    }

    if (!empty($options['include_notes'])) {
      $headers[] = 'Assessment Notes';
    }

    fputcsv($file, $headers);

    // Data rows
    foreach ($data['stakeholders'] as $stakeholder) {
      $row = [
        $stakeholder['id'],
        $stakeholder['name'],
        $stakeholder['contact_type'],
        $stakeholder['email'],
        $stakeholder['phone'],
        $stakeholder['organization'],
        $stakeholder['influence_level'],
        $stakeholder['support_level'],
        ucfirst($stakeholder['quadrant']),
        $stakeholder['engagement_priority'],
        $stakeholder['stakeholder_type'],
        $stakeholder['decision_authority'],
        $stakeholder['strategy'],
        $stakeholder['last_assessment_date']
      ];

      if (!empty($options['include_contact_details'])) {
        $row[] = $stakeholder['address'];
        $row[] = $stakeholder['contact_sub_type'];
      }

      if (!empty($options['include_scores'])) {
        $row[] = $stakeholder['influence_score'];
        $row[] = $stakeholder['support_score'];
      }

      if (!empty($options['include_notes'])) {
        $row[] = strip_tags($stakeholder['assessment_notes']);
      }

      fputcsv($file, $row);
    }

    fclose($file);

    return [
      'file_path' => $filepath,
      'filename' => $filename,
      'mime_type' => 'text/csv'
    ];
  }

  /**
   * Export as JSON
   */
  private static function exportAsJSON($data, $options = []) {
    $filename = 'powermap_export_' . date('Y-m-d_H-i-s') . '.json';
    $filepath = CRM_Utils_File::tempdir() . '/' . $filename;

    $exportData = [
      'export_info' => [
        'format' => 'json',
        'version' => '1.0',
        'exported_at' => date('c'),
        'exported_by' => CRM_Core_Session::getLoggedInContactDisplayName()
      ],
      'map_info' => $data['map_info'],
      'summary' => $data['summary'],
      'stakeholders' => $data['stakeholders']
    ];

    $json = json_encode($exportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

    if (file_put_contents($filepath, $json) === FALSE) {
      throw new Exception('Could not create JSON file');
    }

    return [
      'file_path' => $filepath,
      'filename' => $filename,
      'mime_type' => 'application/json'
    ];
  }

  /**
   * Export as PDF
   */
  private static function exportAsPDF($data, $options = []) {
    $filename = 'powermap_report_' . date('Y-m-d_H-i-s') . '.pdf';
    $filepath = CRM_Utils_File::tempdir() . '/' . $filename;

    // Generate HTML content
    $html = self::generatePDFHTML($data, $options);

    // Use CiviCRM's PDF generation
    $pdf = CRM_Utils_PDF_Utils::html2pdf($html, $filename, TRUE, [
      'orientation' => 'P',
      'unit' => 'mm',
      'format' => 'A4',
      'margin_left' => 15,
      'margin_right' => 15,
      'margin_top' => 15,
      'margin_bottom' => 15
    ]);

    file_put_contents($filepath, $pdf);

    return [
      'file_path' => $filepath,
      'filename' => $filename,
      'mime_type' => 'application/pdf'
    ];
  }

  /**
   * Generate HTML for PDF export
   */
  private static function generatePDFHTML($data, $options = []) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Power Mapping Report</title>
        <style>
            body { font-family: Arial, sans-serif; font-size: 12px; }
            .header { text-align: center; margin-bottom: 30px; }
            .summary { margin-bottom: 30px; }
            .summary-grid { display: table; width: 100%; }
            .summary-item { display: table-cell; text-align: center; padding: 10px; }
            .stat-number { font-size: 24px; font-weight: bold; }
            .stat-label { font-size: 14px; color: #666; }
            .stakeholder-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            .stakeholder-table th, .stakeholder-table td {
                border: 1px solid #ddd; padding: 8px; text-align: left;
            }
            .stakeholder-table th { background-color: #f5f5f5; font-weight: bold; }
            .quadrant-champions { background-color: #d4edda; }
            .quadrant-targets { background-color: #f8d7da; }
            .quadrant-grassroots { background-color: #d1ecf1; }
            .quadrant-monitor { background-color: #e2e3e5; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Power Mapping Report</h1>
            <p>Generated on ' . date('F j, Y \a\t g:i A') . '</p>
            <p>Exported by: ' . CRM_Core_Session::getLoggedInContactDisplayName() . '</p>
        </div>

        <div class="summary">
            <h2>Summary</h2>
            <div class="summary-grid">
                <div class="summary-item">
                    <div class="stat-number">' . $data['summary']['total'] . '</div>
                    <div class="stat-label">Total Stakeholders</div>
                </div>
                <div class="summary-item">
                    <div class="stat-number">' . $data['summary']['champions'] . '</div>
                    <div class="stat-label">Champions</div>
                </div>
                <div class="summary-item">
                    <div class="stat-number">' . $data['summary']['targets'] . '</div>
                    <div class="stat-label">Targets</div>
                </div>
                <div class="summary-item">
                    <div class="stat-number">' . $data['summary']['grassroots'] . '</div>
                    <div class="stat-label">Grassroots</div>
                </div>
                <div class="summary-item">
                    <div class="stat-number">' . $data['summary']['monitor'] . '</div>
                    <div class="stat-label">Monitor</div>
                </div>
            </div>
        </div>

        <h2>Stakeholder Details</h2>
        <table class="stakeholder-table">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Quadrant</th>
                    <th>Influence</th>
                    <th>Support</th>
                    <th>Priority</th>
                    <th>Type</th>
                    <th>Strategy</th>
                </tr>
            </thead>
            <tbody>';

    foreach ($data['stakeholders'] as $stakeholder) {
      $html .= '
                <tr class="quadrant-' . $stakeholder['quadrant'] . '">
                    <td>' . htmlspecialchars($stakeholder['name']) . '</td>
                    <td>' . ucfirst($stakeholder['quadrant']) . '</td>
                    <td>' . ucfirst($stakeholder['influence_level']) . '</td>
                    <td>' . str_replace('_', ' ', ucfirst($stakeholder['support_level'])) . '</td>
                    <td>' . ucfirst($stakeholder['engagement_priority']) . '</td>
                    <td>' . str_replace('_', ' ', ucfirst($stakeholder['stakeholder_type'])) . '</td>
                    <td>' . htmlspecialchars($stakeholder['strategy']) . '</td>
                </tr>';
    }

    $html .= '
            </tbody>
        </table>
    </body>
    </html>';

    return $html;
  }

  /**
   * Export as Excel
   */
  private static function exportAsExcel($data, $options = []) {
    // This would require PHPSpreadsheet or similar library
    // For now, fall back to CSV
    return self::exportAsCSV($data, $options);
  }

  /**
   * Get custom field information
   */
  private static function getCustomFieldInfo() {
    static $cache = NULL;

    if ($cache !== NULL) {
      return $cache;
    }

    try {
      $group = civicrm_api3('CustomGroup', 'getsingle', [
        'name' => 'power_mapping_data',
      ]);

      $fields = civicrm_api3('CustomField', 'get', [
        'custom_group_id' => $group['id'],
        'options' => ['limit' => 0],
      ]);

      $cache = [
        'table_name' => $group['table_name'],
        'group_id' => $group['id']
      ];

      foreach ($fields['values'] as $field) {
        $cache[$field['name'] . '_column'] = $field['column_name'];
        $cache[$field['name'] . '_id'] = $field['id'];
      }

    }
    catch (Exception $e) {
      $cache = [];
    }

    return $cache;
  }

  /**
   * Get state/province name
   */
  private static function getStateProvinceName($stateProvinceId) {
    if (!$stateProvinceId) {
      return '';
    }

    try {
      $result = civicrm_api3('StateProvince', 'getvalue', [
        'id' => $stateProvinceId,
        'return' => 'name'
      ]);
      return $result;
    }
    catch (Exception $e) {
      return '';
    }
  }

  /**
   * Get country name
   */
  private static function getCountryName($countryId) {
    if (!$countryId) {
      return '';
    }

    try {
      $result = civicrm_api3('Country', 'getvalue', [
        'id' => $countryId,
        'return' => 'name'
      ]);
      return $result;
    }
    catch (Exception $e) {
      return '';
    }
  }

  /**
   * Create downloadable file response
   */
  public static function downloadFile($filePath, $filename, $mimeType) {
    if (!file_exists($filePath)) {
      throw new Exception('Export file not found');
    }

    // Set headers for download
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');

    // Output file
    readfile($filePath);

    // Clean up temporary file
    unlink($filePath);

    CRM_Utils_System::civiExit();
  }

  /**
   * Export stakeholder network data for visualization
   */
  public static function exportNetworkData($mapId, $options = []) {
    $data = self::getPowerMapData($mapId, $options);

    $networkData = [
      'nodes' => [],
      'links' => []
    ];

    // Convert stakeholders to nodes
    foreach ($data['stakeholders'] as $stakeholder) {
      $networkData['nodes'][] = [
        'id' => $stakeholder['id'],
        'name' => $stakeholder['name'],
        'group' => $stakeholder['quadrant'],
        'influence' => $stakeholder['influence_score'],
        'support' => $stakeholder['support_score'],
        'type' => $stakeholder['stakeholder_type']
      ];
    }

    // Add relationships as links (if relationship data available)
    $networkData['links'] = self::getStakeholderRelationships($data['stakeholders']);

    return $networkData;
  }

  /**
   * Get stakeholder relationships for network visualization
   */
  private static function getStakeholderRelationships($stakeholders) {
    $links = [];

    // This would query CiviCRM relationships if enabled
    if (Civi::settings()->get('powermap_sync_with_relationships')) {
      $contactIds = array_column($stakeholders, 'id');

      if (!empty($contactIds)) {
        $query = "
          SELECT r.contact_id_a, r.contact_id_b, rt.name_a_b as relationship_type
          FROM civicrm_relationship r
          JOIN civicrm_relationship_type rt ON rt.id = r.relationship_type_id
          WHERE r.is_active = 1
            AND r.contact_id_a IN (" . implode(',', $contactIds) . ")
            AND r.contact_id_b IN (" . implode(',', $contactIds) . ")
        ";

        $dao = CRM_Core_DAO::executeQuery($query);

        while ($dao->fetch()) {
          $links[] = [
            'source' => $dao->contact_id_a,
            'target' => $dao->contact_id_b,
            'type' => $dao->relationship_type,
            'strength' => 1 // Could be calculated based on relationship type
          ];
        }
      }
    }

    return $links;
  }
}
