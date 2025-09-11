{*
  PowerMap Visualization Template

  This template provides the complete user interface for the PowerMap visualization system.
  It includes advanced filtering, group selection, network statistics, and interactive controls.

  Key Features:
  - Responsive sidebar with filters and statistics
  - Advanced group selection with relationship filtering
  - Real-time network statistics display
  - Interactive zoom, pan, and search controls
  - Export functionality (CSV, PNG)
  - Modal dialogs for detailed contact information
  - Accessibility features and keyboard shortcuts
  - Dark mode support
  - Performance optimizations

  Template Variables:
  - $pageTitle: Page title for display
  - $groups: Available CiviCRM groups for filtering
  - $contactsJson: JSON-encoded network data for visualization
  - $currentGroupId: Currently selected group ID (optional)
  - $currentContact: Currently selected contact ID (optional)
  - $onlyRelationship: Whether relationship-only mode is active

  @author PowerMap Team
  @since 1.0.0
  @updated 2025
*}

{* Main container with flexbox layout for responsive design *}
<div class="powermap-container" role="main" aria-label="PowerMap Network Visualization">

  {* ============================================
      SIDEBAR: Filters, Statistics, and Controls
      ============================================ *}
  <aside class="powermap-sidebar" role="complementary" aria-label="Network Statistics and Filters">

    {* Network Statistics Dashboard *}
    <section class="sidebar-section" id="network-statistics">
      <header class="section-title" role="heading" aria-level="2">
        <span class="section-icon" aria-hidden="true">📊</span>
        <span>Network Statistics</span>
      </header>

      {* Real-time statistics grid *}
      <div class="stats-grid" role="group" aria-label="Network Statistics">
        <div class="stat-card" data-metric="total">
          <div class="stat-number" id="stat-total" aria-live="polite">0</div>
          <div class="stat-label">Total Stakeholders</div>
        </div>
        <div class="stat-card" data-metric="influence">
          <div class="stat-number" id="stat-influence" aria-live="polite">0</div>
          <div class="stat-label">High Influence</div>
        </div>
        <div class="stat-card" data-metric="supporters">
          <div class="stat-number" id="stat-supporters" aria-live="polite">0</div>
          <div class="stat-label">Strong Supporters</div>
        </div>
        <div class="stat-card" data-metric="opposition">
          <div class="stat-number" id="stat-opposition" aria-live="polite">0</div>
          <div class="stat-label">Opposition</div>
        </div>
      </div>
    </section>

    {* Advanced Filtering Section *}
    <section class="sidebar-section" id="network-filters">
      <header class="section-title" role="heading" aria-level="2">
        <span class="section-icon" aria-hidden="true">🔍</span>
        <span>Advanced Filters</span>
      </header>

      {* Influence Level Filter *}
      <div class="filter-group" role="group" aria-labelledby="influence-filter-label">
        <label id="influence-filter-label" class="filter-label" for="influence-slider">
          Minimum Influence Level
        </label>
        <input type="range" id="influence-slider" class="filter-slider" min="1" max="5" value="1" aria-describedby="influence-description" data-filter="influence"/>
        <div class="slider-value" id="influence-description">
          Level: <span id="influence-value" aria-live="polite">1</span>
        </div>
        <div class="filter-help">
          <small>Filter contacts by their influence level (1=Low, 5=Very High)</small>
        </div>
      </div>

      {* Support Level Filter *}
      <div class="filter-group" role="group" aria-labelledby="support-filter-label">
        <label id="support-filter-label" class="filter-label" for="support-slider">
          Minimum Support Level
        </label>
        <input type="range" id="support-slider" class="filter-slider" min="1" max="5" value="1" aria-describedby="support-description" data-filter="support"/>
        <div class="slider-value" id="support-description">
          Level: <span id="support-value" aria-live="polite">1</span>
        </div>
        <div class="filter-help">
          <small>Filter contacts by their support level (1=Opposition, 5=Strong Support)</small>
        </div>
      </div>

      {* Relationship Depth Filter *}
      <div class="filter-group" role="group" aria-labelledby="depth-filter-label">
        <label id="depth-filter-label" class="filter-label" for="relationship-depth-slider">
          Minimum Relationship Depth
        </label>
        <input type="range" id="relationship-depth-slider" class="filter-slider" min="0" max="10" value="0" aria-describedby="depth-description" data-filter="depth"/>
        <div class="slider-value" id="depth-description">
          Connections: <span id="relationship-depth-value" aria-live="polite">0</span>+
        </div>
        <div class="filter-help">
          <small>Show only contacts with minimum number of relationships</small>
        </div>
      </div>

      {* Relationship Types Filter *}
      <div class="filter-group" role="group" aria-labelledby="relationship-types-label">
        <label id="relationship-types-label" class="filter-label">
          Relationship Types
        </label>

        {* Bulk selection controls *}
        <div class="filter-controls" role="group" aria-label="Relationship Type Bulk Actions">
          <button type="button" id="select-all-relationships" class="filter-btn" aria-label="Select all relationship types">
            Select All
          </button>
          <button type="button" id="deselect-all-relationships" class="filter-btn" aria-label="Deselect all relationship types">
            Clear All
          </button>
        </div>

        {* Dynamic relationship type checkboxes container *}
        <div id="relationship-types" class="relationship-types-container" role="group" aria-label="Available relationship types" aria-describedby="relationship-types-help">
          {* Populated dynamically by JavaScript *}
        </div>
        <div id="relationship-types-help" class="filter-help">
          <small>Select which relationship types to display in the network</small>
        </div>
      </div>

      {* Filter Reset Button *}
      <div class="filter-group">
        <button type="button" id="reset-filters" class="reset-filters-btn" aria-label="Reset all filters to default values">
          <span aria-hidden="true">🔄</span> Reset All Filters
        </button>
      </div>
    </section>

    {* Visualization Legend *}
    <section class="sidebar-section" id="visualization-legend">
      <header class="section-title" role="heading" aria-level="2">
        <span class="section-icon" aria-hidden="true">🎨</span>
        <span>Legend</span>
      </header>

      <div class="legend" role="group" aria-label="Visualization Legend">
        {* Influence level colors *}
        <div class="legend-category">
          <h4 class="legend-category-title">Influence Levels</h4>
          <div class="legend-item">
            <div class="legend-color" style="background-color: #ff4757;" aria-hidden="true"></div>
            <div class="legend-label">High Influence (4-5)</div>
          </div>
          <div class="legend-item">
            <div class="legend-color" style="background-color: #ffa502;" aria-hidden="true"></div>
            <div class="legend-label">Medium Influence (3)</div>
          </div>
          <div class="legend-item">
            <div class="legend-color" style="background-color: #2ed573;" aria-hidden="true"></div>
            <div class="legend-label">Low Influence (1-2)</div>
          </div>
        </div>

        <div class="legend-separator" aria-hidden="true"></div>

        {* Relationship indicators *}
        <div class="legend-category">
          <h4 class="legend-category-title">Relationships</h4>
          <div class="legend-item">
            <div class="legend-arrow" style="background-color: #999; height: 2px; width: 20px;" aria-hidden="true"></div>
            <div class="legend-label">Connection (thickness = strength)</div>
          </div>
          <div class="legend-item">
            <div class="legend-arrow" aria-hidden="true">→</div>
            <div class="legend-label">Relationship Direction</div>
          </div>
        </div>
      </div>
    </section>

    {* Action Buttons *}
    <section class="sidebar-section" id="action-buttons">
      <div class="action-buttons-container">
        {* Add New Stakeholder Button *}
        <button type="button" class="add-stakeholder-btn" onclick="window.location.href='{crmURL p='civicrm/powermap/add' q='reset=1'}'" aria-label="Add new stakeholder to the network">
          <span aria-hidden="true">➕</span> Add New Stakeholder
        </button>

        {* Network Analysis Button *}
        <button type="button" id="analyze-network-btn" class="analyze-network-btn" aria-label="Perform advanced network analysis">
          <span aria-hidden="true">📈</span> Analyze Network
        </button>
      </div>
    </section>
  </aside>

  {* ============================================
      MAIN CONTENT: Visualization and Controls
      ============================================ *}
  <main class="powermap-main" role="main">

    {* Header with Title and Primary Controls *}
    <header class="powermap-header" role="banner">
      <div class="header-left">
        <h1 class="powermap-title">{$pageTitle}</h1>
        <button id="dark-mode-toggle" class="control-btn">Toggle Dark Mode</button>
        {* Group Selection Form - Enhanced with better UX *}
        <div class="group-selection-container">
          <form method="GET" class="group-selection-form" role="search" aria-label="Group Selection">
            <div class="form-row">
              <div class="form-group">
                <label for="group_id" class="group-label">Select Group:</label>
                <select name="group_id" id="group_id" class="crm-form-select group-select" aria-describedby="group-help" data-current-value="{$currentGroupId|default:''}">
                  <option value="">All Contacts</option>
                  {foreach from=$groups key=val item=group}
                    {if $val != ''}
                      <option value="{$val}" {if $currentGroupId == $val}selected{/if}>
                        {$group} (#{$val})
                      </option>
                    {/if}
                  {/foreach}
                </select>
                <div id="group-help" class="field-help">
                  <small>Select a CiviCRM group to focus the analysis</small>
                </div>
              </div>

              <div class="form-group">
                <div class="checkbox-container">
                  <input type="checkbox" id="only_relationship" name="only_relationship" {if $onlyRelationship}checked{/if} aria-describedby="relationship-help"/>
                  <label for="only_relationship">
                    Show only contacts with relationships
                  </label>
                </div>
                <div id="relationship-help" class="field-help">
                  <small>Hide isolated contacts with no relationships</small>
                </div>
              </div>

              <div class="form-group">
                <div class="label">
                  <label for="contact_id">{ts}Contact{/ts}</label>
                </div>
                <div class="content">
                  <input type="text"
                         id="contact_id"
                         name="contact_id"
                         class="crm-form-entityref form-control"
                         data-api-entity="Contact"
                         data-select-params='{literal}{"multiple":true}{/literal}'
                         data-api-params='{literal}{"extra":["email","phone"]}{/literal}'
                         data-select-prompt="- Select Contact -"
                         data-create-links="1"
                         placeholder="{ts}Start typing contact name...{/ts}"
                         aria-describedby="contact-help" />
                </div>
                <div id="contact-help" class="field-help">
                  <small>Select specific contacts to highlight in the network</small>
                </div>
              </div>

              <div class="form-group">
                <button type="submit" name="generate_group_powermap" id="generate_group_powermap" class="btn btn-primary generate-btn" aria-label="Generate PowerMap for selected group">
                  <span class="btn-icon" aria-hidden="true">🎯</span>
                  Generate PowerMap
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      {* Primary Controls Panel *}
      <div class="powermap-controls" role="toolbar" aria-label="Visualization Controls">

        {* Search Controls *}
        <div class="search-container" role="search">
          <label for="search-input" class="sr-only">Search stakeholders</label>
          <div class="search-input-container">
            <span class="search-icon" aria-hidden="true">🔍</span>
            <input type="text" id="search-input" class="search-input" placeholder="Search stakeholders..." aria-describedby="search-help" autocomplete="off"/>
            <button type="button" id="clear-search" class="clear-search-btn" title="Clear search" aria-label="Clear search" style="display: none;">
              <span aria-hidden="true">✕</span>
            </button>
          </div>
          <div id="search-help" class="sr-only">
            Type to search for stakeholders by name. Use Ctrl+F to focus this field.
          </div>
        </div>

        {* View Controls *}
        <div class="view-controls" role="group" aria-label="View Controls">
          <button type="button" id="zoom-in" class="control-btn" title="Zoom In (Ctrl/Cmd + +)" aria-label="Zoom in to the visualization">
            <span class="btn-icon" aria-hidden="true">🔍+</span>
            <span class="btn-text">Zoom In</span>
          </button>

          <button type="button" id="zoom-out" class="control-btn" title="Zoom Out (Ctrl/Cmd + -)" aria-label="Zoom out from the visualization">
            <span class="btn-icon" aria-hidden="true">🔍-</span>
            <span class="btn-text">Zoom Out</span>
          </button>

          <button type="button" id="reset-view" class="control-btn" title="Reset View (Ctrl/Cmd + 0)" aria-label="Reset zoom and pan to default view">
            <span class="btn-icon" aria-hidden="true">🎯</span>
            <span class="btn-text">Reset</span>
          </button>

          <button type="button" id="center-view" class="control-btn" title="Center View" aria-label="Center the visualization on all visible nodes">
            <span class="btn-icon" aria-hidden="true">📍</span>
            <span class="btn-text">Center</span>
          </button>
        </div>

        {* Export Controls *}
        <div class="export-controls" role="group" aria-label="Export Controls">
          <button type="button" id="export-csv" class="control-btn" title="Export network data to CSV" aria-label="Export network data as CSV file">
            <span class="btn-icon" aria-hidden="true">📊</span>
            <span class="btn-text">Export CSV</span>
          </button>

          <button type="button" id="export-png" class="control-btn" title="Export visualization as image" aria-label="Export visualization as PNG image">
            <span class="btn-icon" aria-hidden="true">🖼️</span>
            <span class="btn-text">Export PNG</span>
          </button>

          <button type="button" id="fullscreen-btn" class="control-btn" title="Toggle fullscreen mode" aria-label="Toggle fullscreen visualization">
            <span class="btn-icon" aria-hidden="true">⛶</span>
            <span class="btn-text">Fullscreen</span>
          </button>
        </div>
      </div>
    </header>

    {* Quick Statistics Bar *}
    <div class="quick-stats-bar" role="complementary" aria-label="Current View Statistics">
      <div class="quick-stat" data-stat="filtered">
        <span class="quick-stat-label">Filtered:</span>
        <span id="filtered-count" class="quick-stat-value" aria-live="polite">0</span>
      </div>
      <div class="quick-stat" data-stat="relationships">
        <span class="quick-stat-label">Relationships:</span>
        <span id="relationships-count" class="quick-stat-value" aria-live="polite">0</span>
      </div>
      <div class="quick-stat" data-stat="influence">
        <span class="quick-stat-label">Avg Influence:</span>
        <span id="avg-influence" class="quick-stat-value" aria-live="polite">0</span>
      </div>
      <div class="quick-stat" data-stat="density">
        <span class="quick-stat-label">Network Density:</span>
        <span id="network-density" class="quick-stat-value" aria-live="polite">0%</span>
      </div>

      {* Filter Status Indicator *}
      <div class="filter-status" id="filter-status" style="display: none;">
        <span class="filter-status-icon" aria-hidden="true">🔍</span>
        <span class="filter-status-text">Filters Active</span>
        <button type="button" class="filter-status-clear" onclick="window.powermapController?.resetFilters()" aria-label="Clear all active filters">
          Clear
        </button>
      </div>
    </div>

    {* Main Visualization Container *}
    <div id="powermap-container" class="visualization-container" role="img" aria-label="PowerMap Network Visualization" aria-describedby="visualization-instructions" tabindex="0">
      {* Instructions for screen readers and keyboard users *}
      <div id="visualization-instructions" class="sr-only">
        Interactive network visualization. Use mouse to pan and zoom, or use keyboard shortcuts:
        Ctrl+Plus to zoom in, Ctrl+Minus to zoom out, Ctrl+0 to reset view.
        Click on nodes to see details and highlight connections.
      </div>
    </div>

    {* Loading Overlay with Progress Indication *}
    <div id="loading-overlay" class="loading-overlay" style="display: none;" aria-hidden="true">
      <div class="loading-spinner">
        <div class="spinner" aria-hidden="true"></div>
        <div class="loading-text" aria-live="polite">Loading network data...</div>
        <div class="loading-progress">
          <div class="progress-bar">
            <div class="progress-fill" id="loading-progress-fill"></div>
          </div>
          <div class="progress-text" id="loading-progress-text">Initializing...</div>
        </div>
      </div>
    </div>

    {* Error State Display *}
    <div id="error-overlay" class="error-overlay" style="display: none;" role="alert">
      <div class="error-content">
        <div class="error-icon" aria-hidden="true">⚠️</div>
        <h3 class="error-title">PowerMap Loading Error</h3>
        <p class="error-message" id="error-message-text">
          There was an error loading the PowerMap data. Please try refreshing the page or contact your system administrator.
        </p>
        <div class="error-actions">
          <button type="button" class="btn btn-primary" onclick="window.location.reload()">
            Refresh Page
          </button>
          <button type="button" class="btn btn-secondary" onclick="window.powermapController?.loadDemoData()">
            Load Demo Data
          </button>
        </div>
      </div>
    </div>
  </main>

  {* ============================================
      MODAL DIALOGS
      ============================================ *}

  {* Contact Details Modal *}
  <div id="contact-details-modal" class="powermap-modal" role="dialog" aria-labelledby="contact-modal-title" aria-describedby="contact-modal-description" aria-hidden="true">
    <div class="modal-backdrop" onclick="closeContactModal()" aria-label="Close modal"></div>
    <div class="modal-content">
      <header class="modal-header">
        <h2 id="contact-modal-title" class="modal-title">Contact Details</h2>
        <button type="button" class="close-btn" onclick="closeContactModal()" aria-label="Close contact details modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </header>
      <div id="contact-details-content" class="modal-body" role="document">
        {* Dynamically populated by JavaScript *}
      </div>
    </div>
  </div>

  <div id="network-analysis-modal" class="powermap-modal" aria-hidden="true">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Network Analysis</h2>
        <button class="close-btn" onclick="window.powermapController.closeAnalysisModal()" aria-label="Close network analysis modal">&times;</button>
      </div>
      <div id="network-analysis-content" class="modal-body"></div>
    </div>
  </div>

  {* Network Analysis Modal *}
  <div id="network-analysis-modal" class="powermap-modal" role="dialog" aria-labelledby="analysis-modal-title" aria-hidden="true">
    <div class="modal-backdrop" onclick="closeAnalysisModal()" aria-label="Close modal"></div>
    <div class="modal-content large">
      <header class="modal-header">
        <h2 id="analysis-modal-title" class="modal-title">Network Analysis</h2>
        <button type="button" class="close-btn" onclick="closeAnalysisModal()" aria-label="Close network analysis modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </header>
      <div id="network-analysis-content" class="modal-body">
        {* Dynamically populated by JavaScript *}
      </div>
    </div>
  </div>

  {* Help/Instructions Modal *}
  <div id="help-modal" class="powermap-modal" role="dialog" aria-labelledby="help-modal-title" aria-hidden="true">
    <div class="modal-backdrop" onclick="closeHelpModal()" aria-label="Close modal"></div>
    <div class="modal-content">
      <header class="modal-header">
        <h2 id="help-modal-title" class="modal-title">PowerMap Help</h2>
        <button type="button" class="close-btn" onclick="closeHelpModal()" aria-label="Close help modal">
          <span aria-hidden="true">&times;</span>
        </button>
      </header>
      <div class="modal-body">
        <div class="help-content">
          <section class="help-section">
            <h3>Getting Started</h3>
            <ul>
              <li>Select a group from the dropdown to focus your analysis</li>
              <li>Use filters to narrow down the network view</li>
              <li>Click and drag to pan around the visualization</li>
              <li>Use mouse wheel or zoom buttons to zoom in/out</li>
            </ul>
          </section>

          <section class="help-section">
            <h3>Keyboard Shortcuts</h3>
            <dl class="keyboard-shortcuts">
              <dt>Ctrl/Cmd + +</dt><dd>Zoom in</dd>
              <dt>Ctrl/Cmd + -</dt><dd>Zoom out</dd>
              <dt>Ctrl/Cmd + 0</dt><dd>Reset view</dd>
              <dt>Ctrl/Cmd + F</dt><dd>Focus search</dd>
              <dt>Esc</dt><dd>Clear search or close modals</dd>
              <dt>F1</dt><dd>Open help</dd>
            </dl>
          </section>

          <section class="help-section">
            <h3>Understanding the Visualization</h3>
            <ul>
              <li><strong>Node Size:</strong> Represents influence level</li>
              <li><strong>Node Color:</strong> Indicates influence category</li>
              <li><strong>Line Thickness:</strong> Shows relationship strength</li>
              <li><strong>Arrows:</strong> Indicate relationship direction</li>
            </ul>
          </section>
        </div>
      </div>
    </div>
  </div>
</div>

{* Help Button (Floating) *}
<button type="button" id="help-button" class="help-button" onclick="openHelpModal()" aria-label="Open PowerMap help and instructions" title="Help & Instructions">
  <span aria-hidden="true">❓</span>
</button>

{* ============================================
    JAVASCRIPT INITIALIZATION
    ============================================ *}
{literal}
<script type="text/javascript">
  document.body.classList.toggle('dark-mode');
  // Initialize Select2 for group dropdown with enhanced features
  CRM.$(document).ready(function($) {
    $('#group_id').select2({
      placeholder: 'Select a group...',
      allowClear: true,
      width: '100%',
      templateResult: function(option) {
        if (!option.id) return option.text;

        // Extract group ID from text for display
        const match = option.text.match(/\(#(\d+)\)$/);
        const groupId = match ? match[1] : '';
        const name = option.text.replace(/\s*\(#\d+\)$/, '');

        return $('<span>').text(name).append(
                $('<small>').text(' (ID: ' + groupId + ')').css('color', '#666')
        );
      }
    });

    {/literal}{if !empty($currentContact)}{literal}
    var currentContact = '{/literal}{$currentContact}{literal}';
    CRM.$('#contact_id').val(currentContact).trigger('change');
    {/literal}{/if}{literal}

    {/literal}{if !empty($onlyRelationship)}{literal}
    CRM.$('#only_relationship').prop('checked', true);
    {/literal}{/if}{literal}

    // Auto-submit form when group selection changes
    $('#group_id').on('change', function() {
      if ($(this).val() !== '') {
        $(this).closest('form').submit();
      }
    });
  });

  // Pass data from PHP to JavaScript with error handling
  try {
    window.powermapData = {/literal}{$contactsJson}{literal};
    console.log('PowerMap data loaded:', window.powermapData);
  } catch (error) {
    console.error('Error parsing PowerMap data:', error);
    window.powermapData = { nodes: [], links: [], metadata: { error: true } };
  }

  // Global modal management functions
  function closeContactModal() {
    const modal = document.getElementById('contact-details-modal');
    if (modal) {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
    }
    if (window.powermapController) {
      window.powermapController.closeContactModal();
    }
  }

  function showContactDetails(contactId) {
    if (window.powermapController) {
      window.powermapController.showContactDetails(contactId);
    }
  }

  function closeAnalysisModal() {
    const modal = document.getElementById('network-analysis-modal');
    if (modal) {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
    }
  }

  function openAnalysisModal() {
    const modal = document.getElementById('network-analysis-modal');
    if (modal) {
      modal.style.display = 'block';
      modal.setAttribute('aria-hidden', 'false');
    }
    if (window.powermapController) {
      window.powermapController.showNetworkAnalysis();
    }
  }

  function closeHelpModal() {
    const modal = document.getElementById('help-modal');
    if (modal) {
      modal.style.display = 'none';
      modal.setAttribute('aria-hidden', 'true');
    }
  }

  function openHelpModal() {
    const modal = document.getElementById('help-modal');
    if (modal) {
      modal.style.display = 'block';
      modal.setAttribute('aria-hidden', 'false');
      // Focus the modal for accessibility
      modal.querySelector('.modal-content').focus();
    }
  }

  // Enhanced initialization with progress tracking
  document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing PowerMap...');

    // Show loading overlay
    const loadingOverlay = document.getElementById('loading-overlay');
    if (loadingOverlay) {
      loadingOverlay.style.display = 'flex';
    }

    // Simulate loading progress
    let progress = 0;
    const progressInterval = setInterval(() => {
      progress += Math.random() * 20;
      if (progress > 90) progress = 90;

      const progressFill = document.getElementById('loading-progress-fill');
      const progressText = document.getElementById('loading-progress-text');

      if (progressFill) progressFill.style.width = progress + '%';
      if (progressText) {
        if (progress < 30) progressText.textContent = 'Loading data...';
        else if (progress < 60) progressText.textContent = 'Processing relationships...';
        else if (progress < 90) progressText.textContent = 'Initializing visualization...';
      }

      if (progress >= 90) {
        clearInterval(progressInterval);
      }
    }, 100);

    // Initialize PowerMap with error handling
    setTimeout(() => {
      try {
        // Complete progress
        const progressFill = document.getElementById('loading-progress-fill');
        const progressText = document.getElementById('loading-progress-text');
        if (progressFill) progressFill.style.width = '100%';
        if (progressText) progressText.textContent = 'Complete!';

        // Initialize controller
        window.powermapController = new EnhancedPowerMapController();

        // Make controller available globally for debugging
        window.PowerMapController = EnhancedPowerMapController;

        // Hide loading overlay
        setTimeout(() => {
          if (loadingOverlay) {
            loadingOverlay.style.display = 'none';
          }
        }, 500);

        console.log('PowerMap Controller initialized and available globally');

        // Initialize additional features
        initializeAdditionalFeatures();

      } catch (error) {
        console.error('Error initializing PowerMap:', error);
        showErrorState(error);
      }
    }, 200);
  });
  // In the DOMContentLoaded listener
  /*
  const sliders = document.querySelectorAll('.filter-slider');
  sliders.forEach(slider => {
    slider.addEventListener('input', debounce(() => {
      window.powermapController.applyFilters();
    }, 300));
  });
  */
  // Initialize additional features and event handlers
  function initializeAdditionalFeatures() {
    // Fullscreen functionality
    const fullscreenBtn = document.getElementById('fullscreen-btn');
    if (fullscreenBtn) {
      fullscreenBtn.addEventListener('click', toggleFullscreen);
    }

    // Network analysis button
    const analyzeBtn = document.getElementById('analyze-network-btn');
    if (analyzeBtn) {
      analyzeBtn.addEventListener('click', openAnalysisModal);
    }

    // Keyboard shortcuts for accessibility
    document.addEventListener('keydown', function(event) {
      // Help modal shortcut
      if (event.key === 'F1' || (event.key === '?' && !isInputFocused())) {
        event.preventDefault();
        openHelpModal();
      }

      // Escape key handling
      if (event.key === 'Escape') {
        closeAllModals();
      }
    });

    // Auto-save user preferences
    initializeUserPreferences();

    // Initialize tooltips and help text
    initializeTooltips();
  }

  // Fullscreen functionality
  function toggleFullscreen() {
    const container = document.querySelector('.powermap-container');
    const btn = document.getElementById('fullscreen-btn');

    if (!document.fullscreenElement) {
      container.requestFullscreen().then(() => {
        container.classList.add('fullscreen-mode');
        btn.querySelector('.btn-text').textContent = 'Exit Fullscreen';
        btn.setAttribute('title', 'Exit fullscreen mode');

        // Resize visualization for fullscreen
        if (window.powermapController && window.powermapController.visualization) {
          setTimeout(() => {
            window.powermapController.visualization.resize();
          }, 100);
        }
      }).catch(err => {
        console.error('Error enabling fullscreen:', err);
      });
    } else {
      document.exitFullscreen().then(() => {
        container.classList.remove('fullscreen-mode');
        btn.querySelector('.btn-text').textContent = 'Fullscreen';
        btn.setAttribute('title', 'Toggle fullscreen mode');

        // Resize visualization back to normal
        if (window.powermapController && window.powermapController.visualization) {
          setTimeout(() => {
            window.powermapController.visualization.resize();
          }, 100);
        }
      });
    }
  }

  // Error state management with user-friendly messaging
  function showErrorState(error) {
    const loadingOverlay = document.getElementById('loading-overlay');
    const errorOverlay = document.getElementById('error-overlay');
    const errorMessage = document.getElementById('error-message-text');

    if (loadingOverlay) loadingOverlay.style.display = 'none';

    if (errorOverlay) {
      errorOverlay.style.display = 'flex';
      errorOverlay.setAttribute('aria-hidden', 'false');
    }

    if (errorMessage) {
      errorMessage.textContent = error.message ||
              'There was an error loading the PowerMap data. Please try refreshing the page or contact your system administrator.';
    }

    // Log detailed error for debugging
    console.error('PowerMap Error Details:', {
      message: error.message,
      stack: error.stack,
      data: window.powermapData
    });
  }

  // User preferences management
  function initializeUserPreferences() {
    const preferences = {
      defaultZoomLevel: 1,
      showTooltips: true,
      autoCenter: true,
      theme: 'default'
    };

    // Load saved preferences
    try {
      const saved = localStorage.getItem('powermap-preferences');
      if (saved) {
        Object.assign(preferences, JSON.parse(saved));
      }
    } catch (error) {
      console.warn('Could not load user preferences:', error);
    }

    // Apply preferences
    window.powermapPreferences = preferences;
  }

  // Tooltip initialization
  function initializeTooltips() {
    // Add tooltip functionality to control buttons
    const buttons = document.querySelectorAll('.control-btn, .filter-btn');
    buttons.forEach(btn => {
      if (!btn.title) return;

      btn.addEventListener('mouseenter', showTooltip);
      btn.addEventListener('mouseleave', hideTooltip);
      btn.addEventListener('focus', showTooltip);
      btn.addEventListener('blur', hideTooltip);
    });
  }

  function showTooltip(event) {
    if (!window.powermapPreferences?.showTooltips) return;

    const button = event.target;
    const tooltip = document.createElement('div');
    tooltip.className = 'custom-tooltip';
    tooltip.textContent = button.title;
    tooltip.id = 'custom-tooltip';

    document.body.appendChild(tooltip);

    const rect = button.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.bottom + 5 + 'px';
  }

  function hideTooltip() {
    const tooltip = document.getElementById('custom-tooltip');
    if (tooltip) {
      tooltip.remove();
    }
  }

  // Utility functions
  function isInputFocused() {
    const activeElement = document.activeElement;
    return activeElement && (
            activeElement.tagName === 'INPUT' ||
            activeElement.tagName === 'TEXTAREA' ||
            activeElement.tagName === 'SELECT' ||
            activeElement.contentEditable === 'true'
    );
  }

  function closeAllModals() {
    closeContactModal();
    closeAnalysisModal();
    closeHelpModal();
  }

  // Handle page visibility changes to optimize performance
  document.addEventListener('visibilitychange', function() {
    if (window.powermapController && window.powermapController.visualization) {
      if (document.hidden) {
        // Pause simulation when tab is not visible
        window.powermapController.visualization.simulation?.stop();
      } else {
        // Resume simulation when tab becomes visible
        window.powermapController.visualization.simulation?.restart();
      }
    }
  });

  // Handle window resize with debouncing
  let resizeTimeout;
  window.addEventListener('resize', function() {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
      if (window.powermapController && window.powermapController.visualization) {
        window.powermapController.visualization.resize();
      }
    }, 250);
  });

  // Performance monitoring
  if (window.performance && window.performance.mark) {
    window.performance.mark('powermap-init-start');

    window.addEventListener('load', function() {
      window.performance.mark('powermap-init-end');
      window.performance.measure(
              'powermap-initialization',
              'powermap-init-start',
              'powermap-init-end'
      );

      const measure = window.performance.getEntriesByName('powermap-initialization')[0];
      console.log('PowerMap initialization took:', measure.duration.toFixed(2), 'ms');
    });
  }

  // Analytics and usage tracking (optional)
  function trackUserAction(action, details = {}) {
    // Implement your analytics tracking here
    console.log('User Action:', action, details);

    // Example: Send to analytics service
    // if (window.analytics) {
    //   window.analytics.track('PowerMap Action', {
    //     action: action,
    //     ...details,
    //     timestamp: Date.now()
    //   });
    // }
  }

  // Export tracking function for use by controller
  window.trackPowerMapAction = trackUserAction;
</script>
{/literal}
