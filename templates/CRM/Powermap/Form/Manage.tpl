{* templates/CRM/Powermap/Form/Manage.tpl - Complete Power Map Management Template *}

<div class="crm-powermap-manage-form crm-block crm-form-block">

  {* Page Header *}
  <div class="crm-powermap-header">
    <div class="crm-powermap-title">
      <h1>{if $action eq 1}{ts}Create New Power Map{/ts}{elseif $action eq 2}{ts}Edit Power Map{/ts}{else}{ts}Manage Power Maps{/ts}{/if}</h1>
      <div class="crm-powermap-breadcrumb">
        <a href="{crmURL p='civicrm/powermap/dashboard' q='reset=1'}">{ts}Dashboard{/ts}</a> &raquo;
        <span>{ts}Manage Maps{/ts}</span>
      </div>
    </div>

    <div class="crm-powermap-actions">
      {if $action neq 1}
        <a href="{crmURL p='civicrm/powermap/manage' q='action=add&reset=1'}" class="button crm-button">
          <span><i class="crm-i fa-plus-circle"></i> {ts}Create New Map{/ts}</span>
        </a>
      {/if}
      <a href="{crmURL p='civicrm/powermap/dashboard' q='reset=1'}" class="button crm-button">
        <span><i class="crm-i fa-arrow-circle-left"></i> {ts}Back to Dashboard{/ts}</span>
      </a>
    </div>
  </div>

  {if $action eq 1 or $action eq 2}
    {* Create/Edit Form *}
    <div class="crm-powermap-form-container">

      {* Basic Information Section *}
      <fieldset class="crm-powermap-basic-info">
        <legend>{ts}Basic Information{/ts}</legend>

        <div class="crm-section">
          <div class="label">{$form.map_name.label}</div>
          <div class="content">
            {$form.map_name.html}
            <div class="description">{ts}Enter a descriptive name for this power map{/ts}</div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.campaign_id.label}</div>
          <div class="content">
            {$form.campaign_id.html}
            <div class="description">{ts}Associate this power map with a specific campaign (optional){/ts}</div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.description.label}</div>
          <div class="content">
            {$form.description.html}
            <div class="description">{ts}Provide additional context or notes about this power map{/ts}</div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section">
          <div class="label">{$form.is_active.label}</div>
          <div class="content">
            {$form.is_active.html}
            <div class="description">{ts}Uncheck to hide this power map from the dashboard{/ts}</div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* Stakeholder Selection Section *}
      <fieldset class="crm-powermap-stakeholder-selection">
        <legend>{ts}Stakeholder Selection{/ts}</legend>

        <div class="crm-section">
          <div class="label">{ts}Selection Method{/ts}</div>
          <div class="content">
            <div class="crm-powermap-selection-tabs">
              <ul class="crm-tabs-list">
                <li class="crm-tab active" data-tab="manual">
                  <a href="#manual-selection">{ts}Manual Selection{/ts}</a>
                </li>
                <li class="crm-tab" data-tab="smart-group">
                  <a href="#smart-group-selection">{ts}Smart Group{/ts}</a>
                </li>
                <li class="crm-tab" data-tab="advanced-search">
                  <a href="#advanced-search-selection">{ts}Advanced Search{/ts}</a>
                </li>
                <li class="crm-tab" data-tab="bulk-import">
                  <a href="#bulk-import-selection">{ts}Bulk Import{/ts}</a>
                </li>
              </ul>
            </div>
          </div>
          <div class="clear"></div>
        </div>

        {* Manual Selection Tab *}
        <div id="manual-selection" class="crm-powermap-tab-content active">
          <div class="crm-section">
            <div class="label">{$form.stakeholder_contacts.label}</div>
            <div class="content">
              {$form.stakeholder_contacts.html}
              <div class="description">{ts}Search and select individual contacts to include in this power map{/ts}</div>
            </div>
            <div class="clear"></div>
          </div>

          <div class="crm-section">
            <div class="label">{ts}Quick Filters{/ts}</div>
            <div class="content">
              <div class="crm-powermap-quick-filters">
                <button type="button" class="crm-button stakeholder-filter" data-type="politician">
                  <i class="crm-i fa-university"></i> {ts}Politicians{/ts}
                </button>
                <button type="button" class="crm-button stakeholder-filter" data-type="media">
                  <i class="crm-i fa-newspaper-o"></i> {ts}Media Contacts{/ts}
                </button>
                <button type="button" class="crm-button stakeholder-filter" data-type="donor">
                  <i class="crm-i fa-dollar"></i> {ts}Major Donors{/ts}
                </button>
                <button type="button" class="crm-button stakeholder-filter" data-type="community_leader">
                  <i class="crm-i fa-users"></i> {ts}Community Leaders{/ts}
                </button>
                <button type="button" class="crm-button stakeholder-filter" data-type="business">
                  <i class="crm-i fa-briefcase"></i> {ts}Business Leaders{/ts}
                </button>
              </div>
            </div>
            <div class="clear"></div>
          </div>
        </div>

        {* Smart Group Selection Tab *}
        <div id="smart-group-selection" class="crm-powermap-tab-content">
          <div class="crm-section">
            <div class="label">{ts}Select Smart Group{/ts}</div>
            <div class="content">
              <select name="smart_group_id" id="smart-group-select" class="crm-select2 huge">
                <option value="">{ts}- Select Smart Group -{/ts}</option>
                {foreach from=$smartGroups item=group}
                  <option value="{$group.id}">{$group.title} ({$group.count} {ts}contacts{/ts})</option>
                {/foreach}
              </select>
              <div class="description">{ts}Select an existing smart group to automatically include its members{/ts}</div>
            </div>
            <div class="clear"></div>
          </div>

          <div class="crm-section">
            <div class="label">{ts}Auto-Update{/ts}</div>
            <div class="content">
              <input type="checkbox" name="smart_group_auto_update" id="smart-group-auto-update" value="1" />
              <label for="smart-group-auto-update">{ts}Automatically update power map when smart group membership changes{/ts}</label>
              <div class="description">{ts}When enabled, contacts will be automatically added/removed from this power map based on smart group membership{/ts}</div>
            </div>
            <div class="clear"></div>
          </div>
        </div>

        {* Advanced Search Selection Tab *}
        <div id="advanced-search-selection" class="crm-powermap-tab-content">
          <div class="crm-section">
            <div class="content">
              <div class="crm-powermap-search-builder">
                <div class="search-criteria">
                  <h4>{ts}Search Criteria{/ts}</h4>

                  <div class="criteria-row">
                    <select name="search_field_1" class="search-field">
                      <option value="">{ts}- Select Field -{/ts}</option>
                      <option value="contact_type">{ts}Contact Type{/ts}</option>
                      <option value="contact_sub_type">{ts}Contact Sub Type{/ts}</option>
                      <option value="group_id">{ts}Group Membership{/ts}</option>
                      <option value="tag_id">{ts}Tag{/ts}</option>
                      <option value="city">{ts}City{/ts}</option>
                      <option value="state_province">{ts}State/Province{/ts}</option>
                      <option value="country">{ts}Country{/ts}</option>
                      <option value="employer">{ts}Current Employer{/ts}</option>
                      <option value="job_title">{ts}Job Title{/ts}</option>
                    </select>

                    <select name="search_operator_1" class="search-operator">
                      <option value="=">{ts}Equals{/ts}</option>
                      <option value="LIKE">{ts}Contains{/ts}</option>
                      <option value="IN">{ts}Is one of{/ts}</option>
                      <option value="NOT IN">{ts}Is not one of{/ts}</option>
                    </select>

                    <input type="text" name="search_value_1" class="search-value" placeholder="{ts}Enter value{/ts}" />

                    <button type="button" class="crm-button add-criteria">
                      <i class="crm-i fa-plus"></i> {ts}Add{/ts}
                    </button>
                  </div>
                </div>

                <div class="search-actions">
                  <button type="button" class="crm-button preview-search">
                    <i class="crm-i fa-eye"></i> {ts}Preview Results{/ts}
                  </button>
                  <button type="button" class="crm-button save-search">
                    <i class="crm-i fa-save"></i> {ts}Save as Smart Group{/ts}
                  </button>
                </div>

                <div class="search-results" id="search-preview" style="display: none;">
                  <h4>{ts}Search Results{/ts} (<span id="search-count">0</span> {ts}contacts{/ts})</h4>
                  <div class="results-list" id="search-results-list">
                    {* Results will be populated via AJAX *}
                  </div>
                </div>
              </div>
            </div>
            <div class="clear"></div>
          </div>
        </div>

        {* Bulk Import Selection Tab *}
        <div id="bulk-import-selection" class="crm-powermap-tab-content">
          <div class="crm-section">
            <div class="label">{ts}Import Method{/ts}</div>
            <div class="content">
              <div class="crm-powermap-import-options">
                <label class="radio-option">
                  <input type="radio" name="import_method" value="csv" checked />
                  <span>{ts}Upload CSV File{/ts}</span>
                  <div class="option-description">{ts}Upload a CSV file with contact IDs or names{/ts}</div>
                </label>

                <label class="radio-option">
                  <input type="radio" name="import_method" value="paste" />
                  <span>{ts}Paste Contact List{/ts}</span>
                  <div class="option-description">{ts}Paste a list of contact names or IDs{/ts}</div>
                </label>

                <label class="radio-option">
                  <input type="radio" name="import_method" value="existing_map" />
                  <span>{ts}Copy from Existing Map{/ts}</span>
                  <div class="option-description">{ts}Copy stakeholders from another power map{/ts}</div>
                </label>
              </div>
            </div>
            <div class="clear"></div>
          </div>

          <div class="import-method-content">
            {* CSV Upload *}
            <div id="csv-import" class="import-content active">
              <div class="crm-section">
                <div class="label">{ts}CSV File{/ts}</div>
                <div class="content">
                  <input type="file" name="csv_file" id="csv-file-input" accept=".csv" />
                  <div class="description">
                    {ts}CSV should contain columns: contact_id, first_name, last_name, or display_name{/ts}
                    <br /><a href="#" id="download-csv-template">{ts}Download CSV Template{/ts}</a>
                  </div>
                </div>
                <div class="clear"></div>
              </div>

              <div class="crm-section">
                <div class="label">{ts}Import Options{/ts}</div>
                <div class="content">
                  <label><input type="checkbox" name="csv_has_header" checked /> {ts}First row contains headers{/ts}</label>
                  <br />
                  <label><input type="checkbox" name="csv_create_missing" /> {ts}Create new contacts for unmatched entries{/ts}</label>
                </div>
                <div class="clear"></div>
              </div>
            </div>

            {* Paste Content *}
            <div id="paste-import" class="import-content">
              <div class="crm-section">
                <div class="label">{ts}Contact List{/ts}</div>
                <div class="content">
                  <textarea name="paste_contacts" rows="10" cols="60" placeholder="{ts}Paste contact names or IDs, one per line{/ts}"></textarea>
                  <div class="description">{ts}Enter contact names, email addresses, or contact IDs (one per line){/ts}</div>
                </div>
                <div class="clear"></div>
              </div>
            </div>

            {* Copy from Existing Map *}
            <div id="existing-map-import" class="import-content">
              <div class="crm-section">
                <div class="label">{ts}Source Power Map{/ts}</div>
                <div class="content">
                  <select name="source_map_id" class="crm-select2 huge">
                    <option value="">{ts}- Select Power Map -{/ts}</option>
                    {foreach from=$existingMaps item=map}
                      <option value="{$map.id}">{$map.name} ({$map.stakeholder_count} {ts}stakeholders{/ts})</option>
                    {/foreach}
                  </select>
                  <div class="description">{ts}All stakeholders from the selected map will be copied to this new map{/ts}</div>
                </div>
                <div class="clear"></div>
              </div>

              <div class="crm-section">
                <div class="label">{ts}Copy Options{/ts}</div>
                <div class="content">
                  <label><input type="checkbox" name="copy_assessments" checked /> {ts}Copy assessment data (influence/support levels){/ts}</label>
                  <br />
                  <label><input type="checkbox" name="copy_notes" /> {ts}Copy assessment notes{/ts}</label>
                </div>
                <div class="clear"></div>
              </div>
            </div>
          </div>
        </div>
      </fieldset>

      {* Assessment Configuration Section *}
      <fieldset class="crm-powermap-assessment-config">
        <legend>{ts}Assessment Configuration{/ts}</legend>

        <div class="crm-section">
          <div class="label">{ts}Default Assessment Values{/ts}</div>
          <div class="content">
            <div class="assessment-defaults">
              <div class="default-field">
                <label>{ts}Default Influence Level{/ts}</label>
                <select name="default_influence_level">
                  <option value="">{ts}- No Default -{/ts}</option>
                  <option value="low">{ts}Low{/ts}</option>
                  <option value="medium" selected>{ts}Medium{/ts}</option>
                  <option value="high">{ts}High{/ts}</option>
                </select>
              </div>

              <div class="default-field">
                <label>{ts}Default Support Level{/ts}</label>
                <select name="default_support_level">
                  <option value="">{ts}- No Default -{/ts}</option>
                  <option value="strong_opposition">{ts}Strong Opposition{/ts}</option>
                  <option value="opposition">{ts}Opposition{/ts}</option>
                  <option value="neutral" selected>{ts}Neutral{/ts}</option>
                  <option value="support">{ts}Support{/ts}</option>
                  <option value="strong_support">{ts}Strong Support{/ts}</option>
                </select>
              </div>

              <div class="default-field">
                <label>{ts}Default Priority{/ts}</label>
                <select name="default_engagement_priority">
                  <option value="low">{ts}Low Priority{/ts}</option>
                  <option value="medium" selected>{ts}Medium Priority{/ts}</option>
                  <option value="high">{ts}High Priority{/ts}</option>
                </select>
              </div>
            </div>
            <div class="description">{ts}These values will be automatically assigned to newly added stakeholders{/ts}</div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section">
          <div class="label">{ts}Assessment Reminders{/ts}</div>
          <div class="content">
            <label><input type="checkbox" name="enable_reminders" value="1" /> {ts}Enable periodic assessment reminders{/ts}</label>
            <div class="reminder-options" style="display: none; margin-top: 10px;">
              <label>{ts}Reminder Frequency:{/ts}</label>
              <select name="reminder_frequency">
                <option value="monthly">{ts}Monthly{/ts}</option>
                <option value="quarterly" selected>{ts}Quarterly{/ts}</option>
                <option value="biannually">{ts}Bi-annually{/ts}</option>
                <option value="annually">{ts}Annually{/ts}</option>
              </select>
            </div>
            <div class="description">{ts}Automatically create reminder activities for stakeholder reassessment{/ts}</div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* Visualization Settings Section *}
      <fieldset class="crm-powermap-viz-settings">
        <legend>{ts}Visualization Settings{/ts}</legend>

        <div class="crm-section">
          <div class="label">{ts}Map Layout{/ts}</div>
          <div class="content">
            <div class="layout-options">
              <label class="radio-option">
                <input type="radio" name="map_layout" value="quadrant" checked />
                <span>{ts}Quadrant Layout{/ts}</span>
                <div class="option-description">{ts}Traditional 2x2 quadrant power mapping{/ts}</div>
              </label>

              <label class="radio-option">
                <input type="radio" name="map_layout" value="bubble" />
                <span>{ts}Bubble Chart{/ts}</span>
                <div class="option-description">{ts}Stakeholder size represents influence level{/ts}</div>
              </label>

              <label class="radio-option">
                <input type="radio" name="map_layout" value="network" />
                <span>{ts}Network View{/ts}</span>
                <div class="option-description">{ts}Show relationships between stakeholders{/ts}</div>
              </label>
            </div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section">
          <div class="label">{ts}Display Options{/ts}</div>
          <div class="content">
            <div class="display-checkboxes">
              <label><input type="checkbox" name="show_names" checked /> {ts}Show stakeholder names{/ts}</label>
              <label><input type="checkbox" name="show_organizations" /> {ts}Show organization names{/ts}</label>
              <label><input type="checkbox" name="show_quadrant_labels" checked /> {ts}Show quadrant labels{/ts}</label>
              <label><input type="checkbox" name="show_grid_lines" checked /> {ts}Show grid lines{/ts}</label>
              <label><input type="checkbox" name="enable_drag_drop" checked /> {ts}Enable drag & drop positioning{/ts}</label>
            </div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="crm-section">
          <div class="label">{ts}Color Scheme{/ts}</div>
          <div class="content">
            <select name="color_scheme">
              <option value="default" selected>{ts}Default (Red/Orange/Green){/ts}</option>
              <option value="blue">{ts}Blue Gradient{/ts}</option>
              <option value="purple">{ts}Purple Gradient{/ts}</option>
              <option value="custom">{ts}Custom Colors{/ts}</option>
            </select>
            <div class="custom-colors" style="display: none; margin-top: 10px;">
              <div class="color-picker">
                <label>{ts}High Influence:{/ts}</label>
                <input type="color" name="high_influence_color" value="#dc3545" />
              </div>
              <div class="color-picker">
                <label>{ts}Medium Influence:{/ts}</label>
                <input type="color" name="medium_influence_color" value="#fd7e14" />
              </div>
              <div class="color-picker">
                <label>{ts}Low Influence:{/ts}</label>
                <input type="color" name="low_influence_color" value="#20c997" />
              </div>
            </div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* Access Control Section *}
      <fieldset class="crm-powermap-access-control">
        <legend>{ts}Access Control{/ts}</legend>

        <div class="crm-section">
          <div class="label">{ts}Visibility{/ts}</div>
          <div class="content">
            <div class="visibility-options">
              <label class="radio-option">
                <input type="radio" name="visibility" value="public" checked />
                <span>{ts}Public{/ts}</span>
                <div class="option-description">{ts}Visible to all users with power mapping access{/ts}</div>
              </label>

              <label class="radio-option">
                <input type="radio" name="visibility" value="private" />
                <span>{ts}Private{/ts}</span>
                <div class="option-description">{ts}Visible only to you and users you specify{/ts}</div>
              </label>

              <label class="radio-option">
                <input type="radio" name="visibility" value="group" />
                <span>{ts}Group Access{/ts}</span>
                <div class="option-description">{ts}Visible to members of selected groups{/ts}</div>
              </label>
            </div>
          </div>
          <div class="clear"></div>
        </div>

        <div class="access-options" style="display: none;">
          <div class="crm-section">
            <div class="label">{ts}Authorized Users{/ts}</div>
            <div class="content">
              <select name="authorized_users[]" multiple class="crm-select2 huge">
                <option value="">{ts}- Select Users -{/ts}</option>
                {foreach from=$users item=user}
                  <option value="{$user.contact_id}">{$user.display_name} ({$user.email})</option>
                {/foreach}
              </select>
            </div>
            <div class="clear"></div>
          </div>

          <div class="crm-section">
            <div class="label">{ts}Authorized Groups{/ts}</div>
            <div class="content">
              <select name="authorized_groups[]" multiple class="crm-select2 huge">
                <option value="">{ts}- Select Groups -{/ts}</option>
                {foreach from=$groups item=group}
                  <option value="{$group.id}">{$group.title}</option>
                {/foreach}
              </select>
            </div>
            <div class="clear"></div>
          </div>
        </div>

        <div class="crm-section">
          <div class="label">{ts}Editing Permissions{/ts}</div>
          <div class="content">
            <div class="permission-options">
              <label><input type="checkbox" name="allow_stakeholder_addition" checked /> {ts}Allow others to add stakeholders{/ts}</label>
              <label><input type="checkbox" name="allow_assessment_editing" checked /> {ts}Allow others to edit assessments{/ts}</label>
              <label><input type="checkbox" name="allow_export" checked /> {ts}Allow others to export data{/ts}</label>
            </div>
          </div>
          <div class="clear"></div>
        </div>
      </fieldset>

      {* Form Buttons *}
      <div class="crm-submit-buttons">
        {include file="CRM/common/formButtons.tpl" location="bottom"}

        <span class="crm-button crm-button-type-next">
          <button type="button" id="save-and-continue" class="crm-form-submit">
            <span>{if $action eq 1}{ts}Create & Add Stakeholders{/ts}{else}{ts}Save & Continue{/ts}{/if}</span>
          </button>
        </span>

        <span class="crm-button crm-button-type-back">
          <button type="button" id="preview-map" class="crm-form-submit">
            <span><i class="crm-i fa-eye"></i> {ts}Preview Map{/ts}</span>
          </button>
        </span>
      </div>
    </div>

  {else}
    {* List View - Show existing power maps *}
    <div class="crm-powermap-list-container">

      {* Search and Filter Bar *}
      <div class="crm-powermap-list-filters">
        <div class="filter-section">
          <input type="text" id="map-search" placeholder="{ts}Search power maps...{/ts}" />

          <select id="campaign-filter" class="crm-select2">
            <option value="">{ts}- All Campaigns -{/ts}</option>
            {foreach from=$campaigns item=campaign}
              <option value="{$campaign.id}">{$campaign.title}</option>
            {/foreach}
          </select>

          <select id="status-filter">
            <option value="">{ts}- All Status -{/ts}</option>
            <option value="1">{ts}Active{/ts}</option>
            <option value="0">{ts}Inactive{/ts}</option>
          </select>

          <select id="owner-filter" class="crm-select2">
            <option value="">{ts}- All Owners -{/ts}</option>
            <option value="mine">{ts}My Maps{/ts}</option>
            {foreach from=$users item=user}
              <option value="{$user.contact_id}">{$user.display_name}</option>
            {/foreach}
          </select>

          <button type="button" id="clear-filters" class="crm-button">
            <i class="crm-i fa-times"></i> {ts}Clear{/ts}
          </button>
        </div>

        <div class="view-options">
          <div class="view-toggle">
            <button type="button" class="view-btn active" data-view="grid">
              <i class="crm-i fa-th-large"></i>
            </button>
            <button type="button" class="view-btn" data-view="list">
              <i class="crm-i fa-list"></i>
            </button>
          </div>

          <select id="sort-by">
            <option value="created_date_desc">{ts}Newest First{/ts}</option>
            <option value="created_date_asc">{ts}Oldest First{/ts}</option>
            <option value="name_asc">{ts}Name A-Z{/ts}</option>
            <option value="name_desc">{ts}Name Z-A{/ts}</option>
            <option value="stakeholders_desc">{ts}Most Stakeholders{/ts}</option>
          </select>
        </div>
      </div>

      {* Power Maps Grid/List *}
      <div class="crm-powermap-grid" id="powermap-grid">
        {foreach from=$powerMaps item=map}
          <div class="powermap-card" data-id="{$map.id}" data-campaign="{$map.campaign_id}" data-status="{$map.is_active}" data-owner="{$map.created_id}">
            <div class="card-header">
              <div class="card-title">
                <h3><a href="{crmURL p='civicrm/powermap/dashboard' q="reset=1&map_id=`$map.id`"}">{$map.name}</a></h3>
                <div class="card-meta">
                  {if $map.campaign_title}
                    <span class="campaign-tag">
                      <i class="crm-i fa-bullhorn"></i> {$map.campaign_title}
                    </span>
                  {/if}
                  <span class="created-date">
                    <i class="crm-i fa-calendar"></i> {$map.created_date|date_format:"%b %e, %Y"}
                  </span>
                </div>
              </div>

              <div class="card-actions">
                <div class="dropdown">
                  <button class="dropdown-toggle" type="button">
                    <i class="crm-i fa-ellipsis-v"></i>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a href="{crmURL p='civicrm/powermap/dashboard' q="reset=1&map_id=`$map.id`"}"><i class="crm-i fa-eye"></i> {ts}View{/ts}</a></li>
                    <li><a href="{crmURL p='civicrm/powermap/manage' q="action=update&id=`$map.id`&reset=1"}"><i class="crm-i fa-edit"></i> {ts}Edit{/ts}</a></li>
                    <li><a href="#" class="duplicate-map" data-id="{$map.id}"><i class="crm-i fa-copy"></i> {ts}Duplicate{/ts}</a></li>
                    <li><a href="#" class="export-map" data-id="{$map.id}"><i class="crm-i fa-download"></i> {ts}Export{/ts}</a></li>
                    <li class="divider"></li>
                    <li><a href="#" class="archive-map" data-id="{$map.id}"><i class="crm-i fa-archive"></i> {if $map.is_active}{ts}Archive{/ts}{else}{ts}Restore{/ts}{/if}</a></li>
                    <li><a href="#" class="delete-map" data-id="{$map.id}"><i class="crm-i fa-trash"></i> {ts}Delete{/ts}</a></li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="card-body">
              {if $map.description}
                <p class="map-description">{$map.description|truncate:120}</p>
              {/if}

              <div class="map-stats">
                <div class="stat-item">
                  <span class="stat-number">{$map.stakeholder_count}</span>
                  <span class="stat-label">{ts}Stakeholders{/ts}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number">{$map.champions_count}</span>
                  <span class="stat-label champions">{ts}Champions{/ts}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number">{$map.targets_count}</span>
                  <span class="stat-label targets">{ts}Targets{/ts}</span>
                </div>
                <div class="stat-item">
                  <span class="stat-number">{$map.last_updated|date_format:"%b %e"}</span>
                  <span class="stat-label">{ts}Last Updated{/ts}</span>
                </div>
              </div>

              <div class="map-preview">
                <div class="mini-chart" data-stakeholders='{$map.stakeholders_json}'>
                  {* Mini visualization will be rendered here via JavaScript *}
                </div>
              </div>
            </div>

            <div class="card-footer">
              <div class="owner-info">
                <i class="crm-i fa-user"></i>
                <span>{$map.created_by_name}</span>
              </div>

              <div class="status-indicator {if $map.is_active}active{else}inactive{/if}">
                {if $map.is_active}{ts}Active{/ts}{else}{ts}Inactive{/ts}{/if}
              </div>
            </div>
          </div>
          {foreachelse}
          <div class="crm-powermap-empty-state">
            <div class="empty-icon">
              <i class="crm-i fa-map-o"></i>
            </div>
            <h3>{ts}No Power Maps Found{/ts}</h3>
            <p>{ts}Get started by creating your first power map to visualize stakeholder relationships and influence.{/ts}</p>
            <a href="{crmURL p='civicrm/powermap/manage' q='action=add&reset=1'}" class="crm-button crm-button-type-next">
              <span><i class="crm-i fa-plus"></i> {ts}Create Your First Power Map{/ts}</span>
            </a>
          </div>
        {/foreach}
      </div>

      {* Pagination *}
      {if $pager}
        <div class="crm-powermap-pagination">
          {$pager->_response.titleTop}
          {$pager->_response.links}
        </div>
      {/if}
    </div>

  {/if}
</div>

{* JavaScript for form functionality *}
<script type="text/javascript">
  {literal}
  CRM.$(function($) {

    // Tab switching
    $('.crm-powermap-selection-tabs .crm-tab').click(function(e) {
      e.preventDefault();
      var tabId = $(this).data('tab');

      // Update active tab
      $('.crm-powermap-selection-tabs .crm-tab').removeClass('active');
      $(this).addClass('active');

      // Show corresponding content
      $('.crm-powermap-tab-content').removeClass('active');
      $('#' + tabId + '-selection').addClass('active');
    });

    // Import method switching
    $('input[name="import_method"]').change(function() {
      var method = $(this).val();
      $('.import-content').removeClass('active');
      $('#' + method + '-import').addClass('active');
    });

    // Visibility option handling
    $('input[name="visibility"]').change(function() {
      var visibility = $(this).val();
      if (visibility === 'private' || visibility === 'group') {
        $('.access-options').show();
      } else {
        $('.access-options').hide();
      }
    });

    // Color scheme handling
    $('select[name="color_scheme"]').change(function() {
      if ($(this).val() === 'custom') {
        $('.custom-colors').show();
      } else {
        $('.custom-colors').hide();
      }
    });

    // Enable reminders handling
    $('input[name="enable_reminders"]').change(function() {
      if ($(this).is(':checked')) {
        $('.reminder-options').show();
      } else {
        $('.reminder-options').hide();
      }
    });

    // Stakeholder filter buttons
    $('.stakeholder-filter').click(function() {
      var type = $(this).data('type');
      // Add logic to filter stakeholder selection dropdown
      // This would integrate with the stakeholder_contacts select2 field
    });

    // Preview search functionality
    $('.preview-search').click(function() {
      // Collect search criteria
      var criteria = [];
      $('.criteria-row').each(function() {
        var field = $(this).find('.search-field').val();
        var operator = $(this).find('.search-operator').val();
        var value = $(this).find('.search-value').val();

        if (field && value) {
          criteria.push({
            field: field,
            operator: operator,
            value: value
          });
        }
      });

      if (criteria.length > 0) {
        // Make API call to preview results
        CRM.api3('Contact', 'get', {
          // Build API parameters from criteria
        }).done(function(result) {
          $('#search-count').text(result.count);
          // Populate results list
          var resultsList = $('#search-results-list');
          resultsList.empty();

          $.each(result.values, function(id, contact) {
            resultsList.append(
              '<div class="search-result-item">' +
              '<input type="checkbox" name="search_results[]" value="' + contact.id + '" checked /> ' +
              contact.display_name + ' (' + contact.contact_type + ')' +
              '</div>'
            );
          });

          $('#search-preview').show();
        });
      }
    });

// Add criteria row
    $('.add-criteria').click(function() {
      var newRow = $('.criteria-row').first().clone();
      newRow.find('input, select').val('');
      newRow.find('.add-criteria').removeClass('add-criteria').addClass('remove-criteria')
        .html('<i class="crm-i fa-minus"></i> {/literal}{ts}Remove{/ts}{literal}');
      $('.search-criteria').append(newRow);
    });

    // Remove criteria row
    $(document).on('click', '.remove-criteria', function() {
      $(this).closest('.criteria-row').remove();
    });

    // CSV template download
    $('#download-csv-template').click(function(e) {
      e.preventDefault();
      var csvContent = "contact_id,first_name,last_name,email,organization\n";
      csvContent += "123,John,Smith,john@example.com,ACME Corp\n";
      csvContent += "456,Jane,Doe,jane@example.com,XYZ Foundation\n";

      var blob = new Blob([csvContent], { type: 'text/csv' });
      var url = window.URL.createObjectURL(blob);
      var a = document.createElement('a');
      a.href = url;
      a.download = 'powermap_import_template.csv';
      a.click();
      window.URL.revokeObjectURL(url);
    });

    // Smart group selection
    $('#smart-group-select').change(function() {
      var groupId = $(this).val();
      if (groupId) {
        // Load group members preview
        CRM.api3('Contact', 'get', {
          group: groupId,
          options: { limit: 10 }
        }).done(function(result) {
          // Show preview of group members
          var preview = '<div class="group-preview"><h4>Group Members Preview:</h4>';
          $.each(result.values, function(id, contact) {
            preview += '<div>' + contact.display_name + '</div>';
          });
          if (result.count > 10) {
            preview += '<div>... and ' + (result.count - 10) + ' more</div>';
          }
          preview += '</div>';

          $('#smart-group-select').parent().append(preview);
        });
      }
    });

    // Save and continue button
    $('#save-and-continue').click(function() {
      // Set a flag to indicate we want to continue to stakeholder management
      $('<input>').attr({
        type: 'hidden',
        name: 'continue_to_stakeholders',
        value: '1'
      }).appendTo('form');

      $('form').submit();
    });

    // Preview map button
    $('#preview-map').click(function() {
      // Open preview in a modal or new tab
      var formData = $('form').serialize();

      CRM.confirm({
        title: '{/literal}{ts escape="js"}Preview Power Map{/ts}{literal}',
        message: '<div id="map-preview-container" style="height: 400px; width: 100%;"></div>',
        options: {
          no: '{/literal}{ts escape="js"}Close{/ts}{literal}'
        },
        width: 800,
        height: 500
      }).on('crmLoad', function() {
        // Initialize a mini power map visualization
        // This would use the same PowerMap class but in preview mode
      });
    });

    // List view functionality
    if ($('#powermap-grid').length) {

      // View toggle
      $('.view-btn').click(function() {
        var view = $(this).data('view');
        $('.view-btn').removeClass('active');
        $(this).addClass('active');

        if (view === 'list') {
          $('#powermap-grid').removeClass('grid-view').addClass('list-view');
        } else {
          $('#powermap-grid').removeClass('list-view').addClass('grid-view');
        }
      });

      // Search functionality
      $('#map-search').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.powermap-card').each(function() {
          var cardText = $(this).text().toLowerCase();
          if (cardText.indexOf(searchTerm) > -1) {
            $(this).show();
          } else {
            $(this).hide();
          }
        });
      });

      // Filter functionality
      $('#campaign-filter, #status-filter, #owner-filter').change(function() {
        applyListFilters();
      });

      function applyListFilters() {
        var campaignFilter = $('#campaign-filter').val();
        var statusFilter = $('#status-filter').val();
        var ownerFilter = $('#owner-filter').val();
        var currentUserId = {/literal}{$currentUserId|default:0}{literal};

        $('.powermap-card').each(function() {
          var show = true;
          var $card = $(this);

          if (campaignFilter && $card.data('campaign') != campaignFilter) {
            show = false;
          }

          if (statusFilter !== '' && $card.data('status') != statusFilter) {
            show = false;
          }

          if (ownerFilter) {
            if (ownerFilter === 'mine' && $card.data('owner') != currentUserId) {
              show = false;
            } else if (ownerFilter !== 'mine' && $card.data('owner') != ownerFilter) {
              show = false;
            }
          }

          if (show) {
            $card.show();
          } else {
            $card.hide();
          }
        });
      }

      // Clear filters
      $('#clear-filters').click(function() {
        $('#map-search').val('');
        $('#campaign-filter, #status-filter, #owner-filter').val('').trigger('change');
        $('.powermap-card').show();
      });

      // Sort functionality
      $('#sort-by').change(function() {
        var sortBy = $(this).val();
        var $cards = $('.powermap-card');
        var $container = $('#powermap-grid');

        $cards.sort(function(a, b) {
          var aVal, bVal;

          switch(sortBy) {
            case 'name_asc':
              aVal = $(a).find('.card-title h3').text().toLowerCase();
              bVal = $(b).find('.card-title h3').text().toLowerCase();
              return aVal < bVal ? -1 : aVal > bVal ? 1 : 0;

            case 'name_desc':
              aVal = $(a).find('.card-title h3').text().toLowerCase();
              bVal = $(b).find('.card-title h3').text().toLowerCase();
              return aVal > bVal ? -1 : aVal < bVal ? 1 : 0;

            case 'created_date_asc':
              aVal = new Date($(a).find('.created-date').text());
              bVal = new Date($(b).find('.created-date').text());
              return aVal - bVal;

            case 'created_date_desc':
              aVal = new Date($(a).find('.created-date').text());
              bVal = new Date($(b).find('.created-date').text());
              return bVal - aVal;

            case 'stakeholders_desc':
              aVal = parseInt($(a).find('.stat-item:first .stat-number').text());
              bVal = parseInt($(b).find('.stat-item:first .stat-number').text());
              return bVal - aVal;

            default:
              return 0;
          }
        });

        $cards.detach().appendTo($container);
      });

      // Card action handlers
      $('.duplicate-map').click(function(e) {
        e.preventDefault();
        var mapId = $(this).data('id');

        CRM.confirm({
          title: '{/literal}{ts escape="js"}Duplicate Power Map{/ts}{literal}',
          message: '{/literal}{ts escape="js"}This will create a copy of the power map with all stakeholders and assessments. What would you like to name the new map?{/ts}{literal}',
          options: {
            yes: '{/literal}{ts escape="js"}Duplicate{/ts}{literal}',
            no: '{/literal}{ts escape="js"}Cancel{/ts}{literal}'
          }
        }).on('crmConfirm:yes', function() {
          // API call to duplicate map
          CRM.api3('Powermap', 'duplicate', {
            id: mapId
          }).done(function(result) {
            location.reload();
          });
        });
      });

      $('.export-map').click(function(e) {
        e.preventDefault();
        var mapId = $(this).data('id');

        // Show export dialog
        var exportDialog = '{/literal}<div class="export-options">' +
          '<div class="form-group">' +
          '<label>{ts escape="js"}Format:{/ts}</label>' +
          '<select id="export-format-modal">' +
          '<option value="json">{ts escape="js"}JSON{/ts}</option>' +
          '<option value="csv">{ts escape="js"}CSV{/ts}</option>' +
          '<option value="pdf">{ts escape="js"}PDF Report{/ts}</option>' +
          '</select>' +
          '</div>' +
          '</div>{literal}';

        CRM.confirm({
          title: '{/literal}{ts escape="js"}Export Power Map{/ts}{literal}',
          message: exportDialog,
          options: {
            yes: '{/literal}{ts escape="js"}Export{/ts}{literal}',
            no: '{/literal}{ts escape="js"}Cancel{/ts}{literal}'
          }
        }).on('crmConfirm:yes', function() {
          var format = $('#export-format-modal').val();

          CRM.api3('Powermap', 'export', {
            id: mapId,
            format: format
          }).done(function(result) {
            if (result.file_path) {
              window.location = result.file_path;
            }
          });
        });
      });

      $('.archive-map').click(function(e) {
        e.preventDefault();
        var mapId = $(this).data('id');
        var $card = $(this).closest('.powermap-card');
        var isActive = $card.data('status') == 1;

        var action = isActive ? 'archive' : 'restore';
        var message = isActive ?
          '{/literal}{ts escape="js"}Are you sure you want to archive this power map? It will be hidden from the main dashboard.{/ts}{literal}' :
          '{/literal}{ts escape="js"}Are you sure you want to restore this power map? It will be visible on the main dashboard.{/ts}{literal}';

        CRM.confirm({
          title: action.charAt(0).toUpperCase() + action.slice(1) + ' Power Map',
          message: message
        }).on('crmConfirm:yes', function() {
          CRM.api3('PowermapConfig', 'create', {
            id: mapId,
            is_active: isActive ? 0 : 1
          }).done(function(result) {
            location.reload();
          });
        });
      });

      $('.delete-map').click(function(e) {
        e.preventDefault();
        var mapId = $(this).data('id');

        CRM.confirm({
          title: '{/literal}{ts escape="js"}Delete Power Map{/ts}{literal}',
          message: '{/literal}{ts escape="js"}Are you sure you want to permanently delete this power map? This action cannot be undone.{/ts}{literal}',
          options: {
            yes: '{/literal}{ts escape="js"}Delete{/ts}{literal}',
            no: '{/literal}{ts escape="js"}Cancel{/ts}{literal}'
          }
        }).on('crmConfirm:yes', function() {
          CRM.api3('PowermapConfig', 'delete', {
            id: mapId
          }).done(function(result) {
            $('.powermap-card[data-id="' + mapId + '"]').fadeOut(function() {
              $(this).remove();

              // Check if no cards left
              if ($('.powermap-card:visible').length === 0) {
                location.reload();
              }
            });
          });
        });
      });

      // Dropdown menus
      $('.dropdown-toggle').click(function(e) {
        e.preventDefault();
        e.stopPropagation();

        // Close other dropdowns
        $('.dropdown-menu').removeClass('show');

        // Toggle this dropdown
        $(this).next('.dropdown-menu').toggleClass('show');
      });

      // Close dropdowns when clicking outside
      $(document).click(function() {
        $('.dropdown-menu').removeClass('show');
      });

      // Initialize mini charts for each power map card
      $('.mini-chart').each(function() {
        var $this = $(this);
        var stakeholders = $this.data('stakeholders');

        if (stakeholders && stakeholders.length > 0) {
          // Create a simple mini visualization
          createMiniChart($this[0], stakeholders);
        }
      });

      function createMiniChart(container, stakeholders) {
        var width = 100;
        var height = 60;
        var margin = 5;

        var svg = d3.select(container)
          .append('svg')
          .attr('width', width)
          .attr('height', height);

        var xScale = d3.scaleLinear()
          .domain([-2, 2])
          .range([margin, width - margin]);

        var yScale = d3.scaleLinear()
          .domain([0, 3])
          .range([height - margin, margin]);

        var colorScale = d3.scaleOrdinal()
          .domain(['low', 'medium', 'high'])
          .range(['#20c997', '#fd7e14', '#dc3545']);

        // Add background quadrants
        svg.append('rect')
          .attr('x', width/2)
          .attr('y', 0)
          .attr('width', width/2)
          .attr('height', height/2)
          .attr('fill', '#d4edda')
          .attr('opacity', 0.3);

        svg.append('rect')
          .attr('x', 0)
          .attr('y', 0)
          .attr('width', width/2)
          .attr('height', height/2)
          .attr('fill', '#f8d7da')
          .attr('opacity', 0.3);

        // Add stakeholder dots
        svg.selectAll('.mini-dot')
          .data(stakeholders.slice(0, 20)) // Limit to 20 for performance
          .enter()
          .append('circle')
          .attr('class', 'mini-dot')
          .attr('cx', d => xScale(d.support_score || 0))
          .attr('cy', d => yScale(d.influence_score || 1))
          .attr('r', 2)
          .attr('fill', d => colorScale(d.influence_level || 'low'))
          .attr('opacity', 0.7);
      }
    }

    // Initialize Select2 dropdowns
    $('.crm-select2').select2({
      width: '100%',
      placeholder: function() {
        return $(this).attr('placeholder') || $(this).find('option:first').text();
      },
      allowClear: true
    });

    // Initialize stakeholder contacts select2 with AJAX
    $('#stakeholder_contacts').select2({
      width: '100%',
      placeholder: '{/literal}{ts escape="js"}Search and select contacts...{/ts}{literal}',
      allowClear: true,
      multiple: true,
      minimumInputLength: 2,
      ajax: {
        url: CRM.url('civicrm/ajax/rest'),
        dataType: 'json',
        delay: 250,
        data: function(params) {
          return {
            entity: 'Contact',
            action: 'get',
            json: JSON.stringify({
              sequential: 1,
              display_name: { 'LIKE': '%' + params.term + '%' },
              options: { limit: 25, sort: 'display_name ASC' },
              return: ['id', 'display_name', 'contact_type', 'email']
            })
          };
        },
        processResults: function(data) {
          var results = [];
          if (data.values) {
            $.each(data.values, function(index, contact) {
              results.push({
                id: contact.id,
                text: contact.display_name + ' (' + contact.contact_type + ')' + (contact.email ? ' - ' + contact.email : '')
              });
            });
          }
          return { results: results };
        }
      }
    });
  });
  {/literal}
</script>
