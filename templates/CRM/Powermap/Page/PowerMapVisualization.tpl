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

        <!-- Relationship Types -->
        <div class="filter-group">
          <label class="filter-label">Relationship Types</label>
          <div id="relationship-types">
            <!-- Dynamically populated -->
          </div>
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
        </div>
      </div>

      <!-- Add Stakeholder Button -->
      <button class="add-stakeholder-btn" onclick="window.location.href='{crmURL p='civicrm/powermap/add' q='reset=1'}'">
        ➕ Add New Stakeholder
      </button>
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
          </div>

          <!-- Zoom Controls -->
          <button id="zoom-in" class="control-btn" title="Zoom In (Ctrl/Cmd + +)">
            🔍 Zoom In
          </button>
          <button id="zoom-out" class="control-btn" title="Zoom Out (Ctrl/Cmd + -)">
            🔍 Zoom Out
          </button>
          <button id="reset-view" class="control-btn" title="Reset View (Ctrl/Cmd + 0)">
            🎯 Reset
          </button>
          <button id="center-view" class="control-btn" title="Center View">
            📍 Center
          </button>
          <button id="export-csv" class="control-btn" title="Export to CSV">
            📊 Export
          </button>
        </div>
      </div>

      <!-- Visualization Container -->
      <div id="powermap-container"></div>
    </div>
  </div>

  <!-- Add Stakeholder Modal -->
  <div id="add-stakeholder-modal" class="powermap-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2 class="modal-title">Add New Stakeholder</h2>
        <button class="close-btn" onclick="closeModal()">&times;</button>
      </div>
      <form id="add-stakeholder-form">
        <div class="form-group">
          <label class="form-label" for="contact-select">Select Contact</label>
          <select id="contact-select" class="form-control form-select" required>
            <option value="">- Select Contact -</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="influence-select">Influence Level</label>
          <select id="influence-select" class="form-control form-select" required>
            <option value="">- Select Level -</option>
            <option value="1">1 - Low</option>
            <option value="2">2 - Medium-Low</option>
            <option value="3">3 - Medium</option>
            <option value="4">4 - High</option>
            <option value="5">5 - Very High</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="support-select">Support Level</label>
          <select id="support-select" class="form-control form-select" required>
            <option value="">- Select Level -</option>
            <option value="1">1 - Strong Opposition</option>
            <option value="2">2 - Opposition</option>
            <option value="3">3 - Neutral</option>
            <option value="4">4 - Support</option>
            <option value="5">5 - Strong Support</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="relationship-select">Relationship Type</label>
          <select id="relationship-select" class="form-control form-select">
            <option value="">- Select Type -</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="related-contact-select">Related to Contact</label>
          <select id="related-contact-select" class="form-control form-select">
            <option value="">- Select Contact -</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="notes-textarea">Notes</label>
          <textarea id="notes-textarea" class="form-control" rows="4" placeholder="Additional notes about this stakeholder..."></textarea>
        </div>

        <div class="btn-group">
          <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
          <button type="submit" class="btn btn-primary">Add Stakeholder</button>
        </div>
      </form>
    </div>
  </div>
  {literal}

  <script type="text/javascript">

    // Pass data from PHP to JavaScript
    window.powermapData = {/literal}{$contactsJson}{literal};

    // Modal functions
    function openModal() {
      document.getElementById('add-stakeholder-modal').style.display = 'block';
      loadContactsForModal();
    }

    function closeModal() {
      document.getElementById('add-stakeholder-modal').style.display = 'none';
    }

    function loadContactsForModal() {
      // Load contacts via AJAX for the modal
      CRM.api3('Contact', 'get', {
        'sequential': 1,
        'is_deleted': 0,
        'options': {'limit': 0}
      }).then(function(result) {
        const contactSelect = document.getElementById('contact-select');
        const relatedContactSelect = document.getElementById('related-contact-select');

        contactSelect.innerHTML = '<option value="">- Select Contact -</option>';
        relatedContactSelect.innerHTML = '<option value="">- Select Contact -</option>';

        result.values.forEach(function(contact) {
          const option = new Option(contact.display_name, contact.id);
          contactSelect.add(option.cloneNode(true));
          relatedContactSelect.add(option);
        });
      });

      // Load relationship types
      CRM.api3('RelationshipType', 'get', {
        'sequential': 1,
        'is_active': 1
      }).then(function(result) {
        const relationshipSelect = document.getElementById('relationship-select');
        relationshipSelect.innerHTML = '<option value="">- Select Type -</option>';

        result.values.forEach(function(type) {
          const option = new Option(type.label_a_b, type.id);
          relationshipSelect.add(option);
        });
      });
    }

    // Form submission
    document.getElementById('add-stakeholder-form').addEventListener('submit', function(e) {
      e.preventDefault();

      const formData = new FormData(this);
      const data = Object.fromEntries(formData);

      // Submit via AJAX
      CRM.api3('Contact', 'create', {
        'id': data.contactId,
        'custom_influence_level': data.influenceLevel,
        'custom_support_level': data.supportLevel,
        'custom_powermap_notes': data.notes
      }).then(function(result) {
        if (data.relationshipType && data.relatedContactId) {
          return CRM.api3('Relationship', 'create', {
            'contact_id_a': data.contactId,
            'contact_id_b': data.relatedContactId,
            'relationship_type_id': data.relationshipType,
            'is_active': 1
          });
        }
      }).then(function() {
        CRM.alert('Stakeholder added successfully', 'Success', 'success');
        closeModal();
        // Reload the visualization
        window.location.reload();
      }).catch(function(error) {
        CRM.alert('Error adding stakeholder: ' + error.error_message, 'Error', 'error');
      });
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
      const modal = document.getElementById('add-stakeholder-modal');
      if (event.target == modal) {
        closeModal();
      }
    }
  </script>
  {/literal}
