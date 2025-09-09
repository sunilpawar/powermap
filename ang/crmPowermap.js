(function(angular, $, _) {
  'use strict';

  angular.module('crmPowermap', CRM.angRequires('crmPowermap'))
    .config(['$routeProvider', function($routeProvider) {
      $routeProvider.when('/powermap', {
        controller: 'PowermapCtrl',
        templateUrl: '~/crmPowermap/PowermapView.html'
      });
    }])

    .controller('PowermapCtrl', ['$scope', 'crmApi', 'crmStatus',
      function($scope, crmApi, crmStatus) {
        $scope.data = {
          nodes: [],
          links: [],
          stats: {}
        };

        $scope.filters = {
          influenceMin: 1,
          supportMin: 1,
          relationshipTypes: [],
          searchTerm: ''
        };

        $scope.loading = true;

        // Load initial data
        $scope.loadData = function() {
          $scope.loading = true;

          crmApi('PowerMap', 'getnetworkdata', $scope.filters)
            .then(function(result) {
              $scope.data = result.values;
              $scope.loading = false;

              // Initialize visualization
              if (window.PowerMapVisualization) {
                $scope.initVisualization();
              }
            })
            .catch(function(error) {
              crmStatus({text: 'Error loading data: ' + error.error_message, type: 'error'});
              $scope.loading = false;
            });
        };

        $scope.initVisualization = function() {
          if ($scope.visualization) {
            $scope.visualization.updateData($scope.data);
          } else {
            $scope.visualization = new PowerMapVisualization('powermap-container', $scope.data);
          }
        };

        $scope.applyFilters = function() {
          $scope.loadData();
        };

        $scope.exportCSV = function() {
          crmApi('PowerMap', 'exporttocsv', $scope.filters)
            .then(function(result) {
              const csvData = result.values;
              const csvContent = csvData.map(row => row.join(',')).join('\n');

              const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
              const link = document.createElement('a');

              if (link.download !== undefined) {
                const url = URL.createObjectURL(blob);
                link.setAttribute('href', url);
                link.setAttribute('download', 'powermap-export.csv');
                link.style.visibility = 'hidden';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
              }
            });
        };

        // Initialize
        $scope.loadData();
      }
    ])

    .directive('powermapVisualization', function() {
      return {
        restrict: 'E',
        template: '<div id="powermap-container"></div>',
        scope: {
          data: '=',
          filters: '='
        },
        link: function(scope, element, attrs) {
          scope.$watch('data', function(newData) {
            if (newData && newData.nodes && newData.nodes.length > 0) {
              if (scope.visualization) {
                scope.visualization.updateData(newData);
              } else {
                scope.visualization = new PowerMapVisualization('powermap-container', newData);
              }
            }
          });

          scope.$watch('filters', function(newFilters) {
            if (scope.visualization && newFilters) {
              scope.visualization.applyFilters(newFilters);
            }
          }, true);
        }
      };
    });

})(angular, CRM.$, CRM._);
