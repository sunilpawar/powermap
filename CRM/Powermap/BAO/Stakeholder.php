<?php

class CRM_Powermap_BAO_Stakeholder extends CRM_Core_DAO {

  /**
   * Get stakeholder influence score as numeric value
   */
  public static function getInfluenceScore($contactId) {
    $customField = self::getCustomFieldInfo('influence_level');
    if (!$customField) {
      return 0;
    }

    $query = "
      SELECT {$customField['column_name']} as influence_level
      FROM {$customField['table_name']}
      WHERE entity_id = %1
    ";

    $params = [1 => [$contactId, 'Integer']];
    $influenceLevel = CRM_Core_DAO::singleValueQuery($query, $params);

    // Convert to numeric score
    $scores = ['high' => 3, 'medium' => 2, 'low' => 1];
    return isset($scores[$influenceLevel]) ? $scores[$influenceLevel] : 0;
  }

  /**
   * Get stakeholder support score as numeric value
   */
  public static function getSupportScore($contactId) {
    $customField = self::getCustomFieldInfo('support_level');
    if (!$customField) {
      return 0;
    }

    $query = "
      SELECT {$customField['column_name']} as support_level
      FROM {$customField['table_name']}
      WHERE entity_id = %1
    ";

    $params = [1 => [$contactId, 'Integer']];
    $supportLevel = CRM_Core_DAO::singleValueQuery($query, $params);

    // Convert to numeric score
    $scores = [
      'strong_support' => 2,
      'support' => 1,
      'neutral' => 0,
      'opposition' => -1,
      'strong_opposition' => -2
    ];
    return isset($scores[$supportLevel]) ? $scores[$supportLevel] : 0;
  }

  /**
   * Get custom field information
   */
  private static function getCustomFieldInfo($fieldName) {
    static $cache = [];

    if (isset($cache[$fieldName])) {
      return $cache[$fieldName];
    }

    try {
      $result = civicrm_api3('CustomField', 'getsingle', [
        'name' => $fieldName,
      ]);

      $groupResult = civicrm_api3('CustomGroup', 'getsingle', [
        'id' => $result['custom_group_id'],
      ]);

      $cache[$fieldName] = [
        'table_name' => $groupResult['table_name'],
        'column_name' => $result['column_name'],
      ];

      return $cache[$fieldName];
    }
    catch (Exception $e) {
      return NULL;
    }
  }

  /**
   * Get strategic quadrant for a stakeholder
   */
  public static function getStrategicQuadrant($contactId) {
    $influence = self::getInfluenceScore($contactId);
    $support = self::getSupportScore($contactId);

    if ($influence >= 2 && $support >= 1) {
      return 'champions'; // High influence, high support
    }
    elseif ($influence >= 2 && $support <= 0) {
      return 'targets'; // High influence, low support
    }
    elseif ($influence <= 1 && $support >= 1) {
      return 'grassroots'; // Low influence, high support
    }
    else {
      return 'monitor'; // Low influence, low support
    }
  }

  /**
   * Get engagement strategy for a stakeholder
   */
  public static function getEngagementStrategy($contactId) {
    $quadrant = self::getStrategicQuadrant($contactId);

    $strategies = [
      'champions' => [
        'strategy' => 'Leverage and empower',
        'actions' => ['Give platforms', 'Provide resources', 'Offer recognition'],
        'priority' => 'high'
      ],
      'targets' => [
        'strategy' => 'Persuade and convert',
        'actions' => ['Direct lobbying', 'Relationship building', 'Education'],
        'priority' => 'high'
      ],
      'grassroots' => [
        'strategy' => 'Mobilize and amplify',
        'actions' => ['Volunteer recruitment', 'Social media', 'Testimonials'],
        'priority' => 'medium'
      ],
      'monitor' => [
        'strategy' => 'Monitor but don\'t prioritize',
        'actions' => ['Information sharing', 'Long-term cultivation'],
        'priority' => 'low'
      ]
    ];

    return $strategies[$quadrant];
  }
}
