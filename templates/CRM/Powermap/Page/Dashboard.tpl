{* Power Mapping Dashboard Template *}
<div class="crm-powermap-dashboard">
  <div class="crm-submit-buttons">
    <a href="{crmURL p='civicrm/powermap/manage' q='action=add&reset=1'}" class="button">
      <span><i class="crm-i fa-plus"></i> {ts}Create New Power Map{/ts}</span>
    </a>
    <a href="{crmURL p='civicrm/contact/search/advanced' q='reset=1'}" class="button">
      <span><i class="crm-i fa-search"></i> {ts}Find Stakeholders{/ts}</span>
    </a>
  </div>

  <div class="crm-powermap-filters">
    <div class="crm-section">
      <div class="label">
        <label for="campaign-filter">{ts}Campaign{/ts}</label>
      </div>
      <div class="content">
        <select id="campaign-filter" class="crm-select2">
          <option value="">{ts}- All Campaigns -{/ts}</option>
          {foreach from=$campaigns item=campaign}
            <option value="{$campaign.id}">{$campaign.title}</option>
          {/foreach}
        </select>
      </div>
    </div>

    <div class="crm-section">
      <div class="label">
        <label for="type-filter">{ts}Stakeholder Type{/ts}</label>
      </div>
      <div class="content">
        <select id="type-filter" class="crm-select2" multiple="multiple">
          <option value="politician">{ts}Politicians{/ts}</option>
          <option value="media">{ts}Media Contacts{/ts}</option>
          <option value="donor">{ts}Major Donors{/ts}</option>
          <option value="community_leader">{ts}Community Leaders{/ts}</option>
          <option value="business">{ts}Business Executives{/ts}</option>
          <option value="expert">{ts}Experts{/ts}</option>
          <option value="activist">{ts}Activists{/ts}</option>
        </select>
      </div>
    </div>

    <div class="crm-section">
      <div class="label">
        <label for="quadrant-filter">{ts}Quadrant{/ts}</label>
      </div>
      <div class="content">
        <select id="quadrant-filter" class="crm-select2">
          <option value="">{ts}- All Quadrants -{/ts}</option>
          <option value="champions">{ts}Champions (High Influence + Support){/ts}</option>
          <option value="targets">{ts}Targets (High Influence + Low Support){/ts}</option>
          <option value="grassroots">{ts}Grassroots (Low Influence + High Support){/ts}</option>
          <option value="monitor">{ts}Monitor (Low Influence + Low Support){/ts}</option>
        </select>
      </div>
    </div>
  </div>

  <div class="crm-powermap-stats">
    <div class="crm-powermap-stat-box">
      <div class="stat-number" id="total-stakeholders">{$totalStakeholders}</div>
      <div class="stat-label">{ts}Total Stakeholders{/ts}</div>
    </div>
    <div class="crm-powermap-stat-box champions">
      <div class="stat-number" id="champions-count">0</div>
      <div class="stat-label">{ts}Champions{/ts}</div>
    </div>
    <div class="crm-powermap-stat-box targets">
      <div class="stat-number" id="targets-count">0</div>
      <div class="stat-label">{ts}Targets{/ts}</div>
    </div>
    <div class="crm-powermap-stat-box grassroots">
      <div class="stat-number" id="grassroots-count">0</div>
      <div class="stat-label">{ts}Grassroots{/ts}</div>
    </div>
  </div>

  <div class="crm-powermap-container">
    <div class="crm-powermap-sidebar">
      <h3>{ts}Stakeholder List{/ts}</h3>
      <div id="stakeholder-search">
        <input type="text" placeholder="{ts}Search stakeholders...{/ts}" id="stakeholder-search-input">
      </div>
      <div id="stakeholder-list"></div>

      <div class="crm-powermap-legend">
        <h4>{ts}Legend{/ts}</h4>
        <div class="legend-item">
          <div class="legend-color high-influence"></div>
          <span>{ts}High Influence{/ts}</span>
        </div>
        <div class="legend-item">
          <div class="legend-color medium-influence"></div>
          <span>{ts}Medium Influence{/ts}</span>
        </div>
        <div class="legend-item">
          <div class="legend-color low-influence"></div>
          <span>{ts}Low Influence{/ts}</span>
        </div>
        <div class="legend-item">
          <div class="legend-indicator supporter"></div>
          <span>{ts}Supporter{/ts}</span>
        </div>
        <div class="legend-item">
          <div class="legend-indicator opponent"></div>
          <span>{ts}Opponent{/ts}</span>
        </div>
      </div>
    </div>

    <div class="crm-powermap-main">
      <div class="crm-powermap-toolbar">
        <button id="export-map" class="button">{ts}Export Map{/ts}</button>
        <button id="save-view" class="button">{ts}Save View{/ts}</button>
        <button id="full-screen" class="button">{ts}Full Screen{/ts}</button>
      </div>

      <div id="power-map-visualization"></div>
    </div>
  </div>
</div>

<script type="text/template" id="stakeholder-template">
  <div class="stakeholder-item" data-contact-id="<%= id %>">
    <div class="stakeholder-name"><%= name %></div>
    <div class="stakeholder-details">
      <%= influence_level %> Influence • <%= support_level %> • <%= stakeholder_type %>
    </div>
    <div class="stakeholder-quadrant <%= quadrant %>">
      <%= quadrant.charAt(0).toUpperCase() + quadrant.slice(1) %>
    </div>
  </div>
</script>
