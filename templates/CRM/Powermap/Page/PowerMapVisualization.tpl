<div class="powermap-container">
  <!-- Sidebar -->
  <div class="powermap-sidebar">
    <!-- Stats Cards -->
    <div class="sidebar-section">
      <div class="section-title">
        📊 Network Statistics
      </div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-number" id="stat-total">0</div>
          <div class="stat-label">Total Stakeholders</div>
        </div>
        <div class="stat-card">
          <div class="stat-number" id="stat-influence">0</div>
          <div class="stat-label">High Influence</div>
        </div>
        <div class="stat-card">
          <div class="stat-number" id="stat-supporters">0</div>
          <div class="stat-label">Strong Supporters</div>
        </div>
        <div class="stat-card">
          <div class="stat-number" id="stat-opposition">0</div>
          <div class="stat-label">Opposition</div>
        </div>
      </div>

      <!-- Filters -->
      <div class="sidebar-section">
        <div class="section-title">
          🔍 Filters
        </div>

        <!-- Influence Level Filter -->
        <div class="filter-group">
          <label class="filter-label">Minimum Influence Level</label>
          <input type="range" id="influence-slider" class="filter-slider" min="1" max="5" value="1">
          <div class="slider-value">Level: <span id="influence-value">1</span></div>
        </div>

        <!-- Support Level Filter -->
        <div class="filter-group">
          <label class="filter-label">Minimum Support Level</label>
          <input type="range" id="support-slider" class="filter-slider" min="1" max="5" value="1">
          <div class="slider-value">Level: <span id="support-value">1</span></div>
        </div>

        <!-- Relationship Depth Filter -->
        <div class="filter-group">
          <label class="filter-label">Minimum Relationship Depth</label>
          <input type="range" id="relationship-depth-slider" class="filter-slider" min="0" max="10" value="0">
          <div class="slider-value">Connections: <span id="relationship-depth-value">0</span>+</div>
          <div class="filter-description">Filter by minimum number of relationships</div>
        </div>

        <!-- Relationship Types -->
        <div class="filter-group">
          <label class="filter-label">Relationship Types</label>
          <div class="filter-controls">
            <button id="select-all-relationships" class="filter-btn">Select All</button>
            <button id="deselect-all-relationships" class="filter-btn">Clear All</button>
          </div>
          <div id="relationship-types" class="relationship-types-container">
            <!-- Dynamically populated -->
          </div>
        </div>

        <!-- Reset Filters Button -->
        <div class="filter-group">
          <button id="reset-filters" class="reset-filters-btn">🔄 Reset All Filters</button>
        </div>
      </div>

      <!-- Legend -->
      <div class="sidebar-section">
        <div class="section-title">
          🎨 Legend
        </div>
        <div class="legend">
          <div class="legend-item">
            <div class="legend-color" style="background-color: #ff4757;"></div>
            <div class="legend-label">High Influence (4-5)</div>
          </div>
          <div class="legend-item">
            <div class="legend-color" style="background-color: #ffa502;"></div>
            <div class="legend-label">Medium Influence (3)</div>
          </div>
          <div class="legend-item">
            <div class="legend-color" style="background-color: #2ed573;"></div>
            <div class="legend-label">Low Influence (1-2)</div>
          </div>
          <div class="legend-separator"></div>
          <div class="legend-item">
            <div class="legend-line" style="background-color: #999; height: 2px; width: 20px;"></div>
            <div class="legend-label">Relationship (thickness = strength)</div>
          </div>
        </div>
      </div>
      <!-- Add Stakeholder Button -->
      <button class="add-stakeholder-btn" onclick="window.location.href='{crmURL p='civicrm/powermap/add' q='reset=1'}'">
        ➕ Add New Stakeholder
      </button>
    </div>
  </div>
  <!-- Main Content -->
  <div class="powermap-main">
    <!-- Header -->
    <div class="powermap-header">
      <h1 class="powermap-title">{$pageTitle}</h1>
      <div class="powermap-controls">
        <!-- Search -->
        <div class="search-container">
          <span class="search-icon">🔍</span>
          <input type="text" id="search-input" class="search-input" placeholder="Search stakeholders...">
          <button id="clear-search" class="clear-search-btn" title="Clear Search">✕</button>
        </div>

        <!-- View Controls -->
        <div class="view-controls">
          <!-- Zoom Controls -->
          <button id="zoom-in" class="control-btn" title="Zoom In (Ctrl/Cmd + +)">
            🔍+ Zoom In
          </button>
          <button id="zoom-out" class="control-btn" title="Zoom Out (Ctrl/Cmd + -)">
            🔍- Zoom Out
          </button>
          <button id="reset-view" class="control-btn" title="Reset View (Ctrl/Cmd + 0)">
            🎯 Reset
          </button>
          <button id="center-view" class="control-btn" title="Center View">
            📍 Center
          </button>
        </div>

        <!-- Export Controls -->
        <div class="export-controls">
          <button id="export-csv" class="control-btn" title="Export to CSV">
            📊 Export CSV
          </button>
          <button id="export-png" class="control-btn" title="Export as Image">
            🖼️ Export PNG
          </button>
        </div>
      </div>
    </div>

    <!-- Quick Stats Bar -->
    <div class="quick-stats-bar">
      <div class="quick-stat">
        <span class="quick-stat-label">Filtered:</span>
        <span id="filtered-count" class="quick-stat-value">0</span>
      </div>
      <div class="quick-stat">
        <span class="quick-stat-label">Relationships:</span>
        <span id="relationships-count" class="quick-stat-value">0</span>
      </div>
      <div class="quick-stat">
        <span class="quick-stat-label">Avg Influence:</span>
        <span id="avg-influence" class="quick-stat-value">0</span>
      </div>
      <div class="quick-stat">
        <span class="quick-stat-label">Network Density:</span>
        <span id="network-density" class="quick-stat-value">0%</span>
      </div>
    </div>

    <!-- Visualization Container -->
    <div id="powermap-container"></div>

    <!-- Loading Overlay -->
    <div id="loading-overlay" class="loading-overlay" style="display: none;">
      <div class="loading-spinner">
        <div class="spinner"></div>
        <div class="loading-text">Loading network data...</div>
      </div>
    </div>
  </div>
  <!-- Contact Details Modal -->
  <div id="contact-details-modal" class="powermap-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Contact Details</h2>
        <button class="close-btn" onclick="closeContactModal()">&times;</button>
      </div>
      <div id="contact-details-content">
        <!-- Dynamically populated -->
      </div>
    </div>
  </div>

  {literal}
  <script type="text/javascript">
    // Pass data from PHP to JavaScript
    window.powermapData = {/literal}{$contactsJson}{literal};

    // Global functions for modal and external access
    function closeContactModal() {
      if (window.powermapController) {
        window.powermapController.closeContactModal();
      }
    }

    function showContactDetails(contactId) {
      if (window.powermapController) {
        window.powermapController.showContactDetails(contactId);
      }
    }

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
      console.log('DOM loaded, initializing PowerMap...');

      // Wait a bit for any dynamic content to load
      setTimeout(() => {
        window.powermapController = new EnhancedPowerMapController();

        // Make controller available globally for debugging
        window.PowerMapController = EnhancedPowerMapController;

        console.log('PowerMap Controller initialized and available globally');
      }, 100);
    });

    // Handle page visibility changes to pause/resume animations
    document.addEventListener('visibilitychange', function() {
      if (window.powermapController && window.powermapController.visualization) {
        if (document.hidden) {
          // Pause simulation when tab is not visible
          window.powermapController.visualization.simulation.stop();
        } else {
          // Resume simulation when tab becomes visible
          window.powermapController.visualization.simulation.restart();
        }
      }
    });
  </script>
  {/literal}
