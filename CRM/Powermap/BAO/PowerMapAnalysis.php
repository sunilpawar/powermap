<?php

class CRM_Powermap_BAO_PowerMapAnalysis extends CRM_Core_DAO {

  /**
   * Analyze network centrality for all contacts
   */
  public static function calculateNetworkCentrality($contactIds = NULL) {
    $relationships = self::getNetworkRelationships($contactIds);
    $centrality = [];

    // Calculate degree centrality (number of connections)
    foreach ($relationships as $rel) {
      $sourceId = $rel['contact_id_a'];
      $targetId = $rel['contact_id_b'];

      if (!isset($centrality[$sourceId])) {
        $centrality[$sourceId] = ['degree' => 0, 'betweenness' => 0, 'closeness' => 0];
      }
      if (!isset($centrality[$targetId])) {
        $centrality[$targetId] = ['degree' => 0, 'betweenness' => 0, 'closeness' => 0];
      }

      $centrality[$sourceId]['degree']++;
      $centrality[$targetId]['degree']++;
    }

    // Calculate betweenness centrality (simplified)
    $centrality = self::calculateBetweennessCentrality($relationships, $centrality);

    return $centrality;
  }

  /**
   * Calculate influence score based on multiple factors
   */
  public static function calculateInfluenceScore($contactId) {
    $factors = [];

    // Factor 1: Network centrality
    $centrality = self::calculateNetworkCentrality([$contactId]);
    $factors['centrality'] = isset($centrality[$contactId]) ? $centrality[$contactId]['degree'] : 0;

    // Factor 2: Custom influence level
    $customInfluence = self::getCustomFieldValue($contactId, 'influence_level');
    $factors['custom_influence'] = $customInfluence ? $customInfluence : 3;

    // Factor 3: Relationship strength (average)
    $factors['relationship_strength'] = self::getAverageRelationshipStrength($contactId);

    // Factor 4: Contact type weight
    $contactType = self::getContactType($contactId);
    $factors['type_weight'] = self::getContactTypeWeight($contactType);

    // Calculate weighted score
    $weights = [
      'centrality' => 0.3,
      'custom_influence' => 0.4,
      'relationship_strength' => 0.2,
      'type_weight' => 0.1,
    ];

    $score = 0;
    foreach ($factors as $factor => $value) {
      $score += $value * $weights[$factor];
    }

    return min(5, max(1, round($score, 2)));
  }

  /**
   * Identify key influencers in the network
   */
  public static function identifyKeyInfluencers($limit = 10) {
    $contacts = self::getAllNetworkContacts();
    $influencers = [];

    foreach ($contacts as $contact) {
      $score = self::calculateInfluenceScore($contact['id']);
      $influencers[] = [
        'contact_id' => $contact['id'],
        'name' => $contact['display_name'],
        'influence_score' => $score,
        'connections' => self::getConnectionCount($contact['id']),
      ];
    }

    // Sort by influence score
    usort($influencers, function ($a, $b) {
      return $b['influence_score'] <=> $a['influence_score'];
    });

    return array_slice($influencers, 0, $limit);
  }

  /**
   * Find shortest path between two contacts
   */
  public static function findShortestPath($sourceId, $targetId) {
    $relationships = self::getNetworkRelationships();
    $graph = self::buildGraph($relationships);

    return self::dijkstraPath($graph, $sourceId, $targetId);
  }

  /**
   * Detect communities/clusters in the network
   */
  public static function detectCommunities() {
    $relationships = self::getNetworkRelationships();
    $communities = [];

    // Simple community detection based on modularity
    $graph = self::buildGraph($relationships);
    $communities = self::modularityClustering($graph);

    return $communities;
  }

  /**
   * Generate network statistics
   */
  public static function getNetworkStatistics() {
    $relationships = self::getNetworkRelationships();
    $contacts = self::getAllNetworkContacts();

    $stats = [
      'total_nodes' => count($contacts),
      'total_edges' => count($relationships),
      'density' => self::calculateNetworkDensity($contacts, $relationships),
      'average_degree' => self::calculateAverageDegree($contacts, $relationships),
      'clustering_coefficient' => self::calculateClusteringCoefficient($relationships),
      'diameter' => self::calculateNetworkDiameter($relationships),
    ];

    return $stats;
  }

  // Helper methods

  private static function getNetworkRelationships($contactIds = NULL) {
    $params = [
      'sequential' => 1,
      'is_active' => 1,
      'options' => ['limit' => 0],
    ];

    if ($contactIds) {
      $params['contact_id_a'] = ['IN' => $contactIds];
      $params['contact_id_b'] = ['IN' => $contactIds];
    }

    try {
      $result = civicrm_api3('Relationship', 'get', $params);
      return $result['values'];
    }
    catch (Exception $e) {
      return [];
    }
  }

  private static function getAllNetworkContacts() {
    try {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'is_deleted' => 0,
        'options' => ['limit' => 0],
        'return' => ['id', 'display_name', 'contact_type'],
      ]);
      return $result['values'];
    }
    catch (Exception $e) {
      return [];
    }
  }

  private static function getCustomFieldValue($contactId, $fieldName) {
    try {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $contactId,
        'return' => ['custom_' . $fieldName],
      ]);

      if (!empty($result['values'][0]['custom_' . $fieldName])) {
        return $result['values'][0]['custom_' . $fieldName];
      }
    }
    catch (Exception $e) {
      // Field doesn't exist or other error
    }

    return NULL;
  }

  private static function getAverageRelationshipStrength($contactId) {
    $relationships = self::getNetworkRelationships([$contactId]);
    $strengths = [];

    foreach ($relationships as $rel) {
      if ($rel['contact_id_a'] == $contactId || $rel['contact_id_b'] == $contactId) {
        // Get relationship strength from custom field or default to 2
        $strength = self::getCustomFieldValue($rel['id'], 'relationship_strength') ?: 2;
        $strengths[] = $strength;
      }
    }

    return !empty($strengths) ? array_sum($strengths) / count($strengths) : 2;
  }

  private static function getContactType($contactId) {
    try {
      $result = civicrm_api3('Contact', 'get', [
        'sequential' => 1,
        'id' => $contactId,
        'return' => ['contact_type'],
      ]);

      return $result['values'][0]['contact_type'] ?? 'Individual';
    }
    catch (Exception $e) {
      return 'Individual';
    }
  }

  private static function getContactTypeWeight($contactType) {
    $weights = [
      'Individual' => 1.0,
      'Organization' => 1.5,
      'Household' => 0.8,
    ];

    return $weights[$contactType] ?? 1.0;
  }

  private static function getConnectionCount($contactId) {
    $relationships = self::getNetworkRelationships([$contactId]);
    return count($relationships);
  }

  private static function calculateBetweennessCentrality($relationships, $centrality) {
    // Simplified betweenness centrality calculation
    // In a full implementation, this would use Floyd-Warshall or similar algorithms
    $graph = self::buildGraph($relationships);

    foreach ($centrality as $nodeId => &$metrics) {
      $betweenness = 0;

      // Calculate paths that go through this node
      foreach ($graph as $sourceId => $targets) {
        foreach ($targets as $targetId => $weight) {
          if ($sourceId != $nodeId && $targetId != $nodeId) {
            // Check if shortest path goes through this node
            $directPath = isset($graph[$sourceId][$targetId]) ? 1 : INF;
            $throughNodePath = 2; // Simplified: assume 2-hop path through node

            if ($throughNodePath < $directPath) {
              $betweenness += 1;
            }
          }
        }
      }

      $metrics['betweenness'] = $betweenness;
    }

    return $centrality;
  }

  private static function buildGraph($relationships) {
    $graph = [];

    foreach ($relationships as $rel) {
      $sourceId = $rel['contact_id_a'];
      $targetId = $rel['contact_id_b'];

      if (!isset($graph[$sourceId])) {
        $graph[$sourceId] = [];
      }
      if (!isset($graph[$targetId])) {
        $graph[$targetId] = [];
      }

      $graph[$sourceId][$targetId] = 1;
      $graph[$targetId][$sourceId] = 1; // Undirected graph
    }

    return $graph;
  }

  private static function dijkstraPath($graph, $sourceId, $targetId) {
    // Simplified Dijkstra implementation
    $distances = [];
    $previous = [];
    $unvisited = [];

    foreach ($graph as $nodeId => $neighbors) {
      $distances[$nodeId] = INF;
      $previous[$nodeId] = NULL;
      $unvisited[$nodeId] = TRUE;
    }

    $distances[$sourceId] = 0;

    while (!empty($unvisited)) {
      $currentNode = NULL;
      $minDistance = INF;

      foreach ($unvisited as $nodeId => $unused) {
        if ($distances[$nodeId] < $minDistance) {
          $minDistance = $distances[$nodeId];
          $currentNode = $nodeId;
        }
      }

      if ($currentNode === NULL || $currentNode == $targetId) {
        break;
      }

      unset($unvisited[$currentNode]);

      if (isset($graph[$currentNode])) {
        foreach ($graph[$currentNode] as $neighbor => $weight) {
          if (isset($unvisited[$neighbor])) {
            $alt = $distances[$currentNode] + $weight;
            if ($alt < $distances[$neighbor]) {
              $distances[$neighbor] = $alt;
              $previous[$neighbor] = $currentNode;
            }
          }
        }
      }
    }

    // Reconstruct path
    $path = [];
    $current = $targetId;

    while ($current !== NULL) {
      array_unshift($path, $current);
      $current = $previous[$current];
    }

    return $path[0] == $sourceId ? $path : [];
  }

  private static function calculateNetworkDensity($contacts, $relationships) {
    $nodeCount = count($contacts);
    $edgeCount = count($relationships);
    $maxEdges = $nodeCount * ($nodeCount - 1) / 2;

    return $maxEdges > 0 ? $edgeCount / $maxEdges : 0;
  }

  private static function calculateAverageDegree($contacts, $relationships) {
    $nodeCount = count($contacts);
    $edgeCount = count($relationships);

    return $nodeCount > 0 ? (2 * $edgeCount) / $nodeCount : 0;
  }

  private static function calculateClusteringCoefficient($relationships) {
    // Simplified clustering coefficient calculation
    $graph = self::buildGraph($relationships);
    $clusteringSum = 0;
    $nodeCount = 0;

    foreach ($graph as $nodeId => $neighbors) {
      $neighborCount = count($neighbors);
      if ($neighborCount < 2) {
        continue;
      }

      $edgesBetweenNeighbors = 0;
      $maxPossibleEdges = $neighborCount * ($neighborCount - 1) / 2;

      foreach ($neighbors as $neighbor1 => $weight1) {
        foreach ($neighbors as $neighbor2 => $weight2) {
          if ($neighbor1 < $neighbor2 && isset($graph[$neighbor1][$neighbor2])) {
            $edgesBetweenNeighbors++;
          }
        }
      }

      $clusteringSum += $maxPossibleEdges > 0 ? $edgesBetweenNeighbors / $maxPossibleEdges : 0;
      $nodeCount++;
    }

    return $nodeCount > 0 ? $clusteringSum / $nodeCount : 0;
  }

  private static function calculateNetworkDiameter($relationships) {
    // Simplified diameter calculation (maximum shortest path)
    $graph = self::buildGraph($relationships);
    $maxDistance = 0;

    $nodeIds = array_keys($graph);

    for ($i = 0; $i < count($nodeIds); $i++) {
      for ($j = $i + 1; $j < count($nodeIds); $j++) {
        $path = self::dijkstraPath($graph, $nodeIds[$i], $nodeIds[$j]);
        $distance = count($path) - 1;
        $maxDistance = max($maxDistance, $distance);
      }
    }

    return $maxDistance;
  }

  private static function modularityClustering($graph) {
    // Simplified community detection
    // In a real implementation, this would use Louvain or similar algorithms
    $communities = [];
    $visited = [];
    $communityId = 0;

    foreach ($graph as $nodeId => $neighbors) {
      if (!isset($visited[$nodeId])) {
        $community = self::depthFirstSearch($graph, $nodeId, $visited);
        $communities[$communityId] = $community;
        $communityId++;
      }
    }

    return $communities;
  }

  private static function depthFirstSearch($graph, $startNode, &$visited) {
    $community = [];
    $stack = [$startNode];

    while (!empty($stack)) {
      $node = array_pop($stack);

      if (!isset($visited[$node])) {
        $visited[$node] = TRUE;
        $community[] = $node;

        if (isset($graph[$node])) {
          foreach ($graph[$node] as $neighbor => $weight) {
            if (!isset($visited[$neighbor])) {
              $stack[] = $neighbor;
            }
          }
        }
      }
    }

    return $community;
  }
}
