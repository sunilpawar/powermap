<div class="crm-form-block">
  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="top"}
  </div>

  <table class="form-layout">
    <tr class="crm-powermap-form-block-contact_id">
      <td class="label">{$form.contact_id.label}</td>
      <td>{$form.contact_id.html}</td>
    </tr>

    <tr class="crm-powermap-form-block-influence_level">
      <td class="label">{$form.influence_level.label}</td>
      <td>{$form.influence_level.html}
        <div class="description">Rate the stakeholder's level of influence (1=Low, 5=Very High)</div>
      </td>
    </tr>

    <tr class="crm-powermap-form-block-support_level">
      <td class="label">{$form.support_level.label}</td>
      <td>{$form.support_level.html}
        <div class="description">Rate the stakeholder's support level (1=Strong Opposition, 5=Strong Support)</div>
      </td>
    </tr>

    <tr class="crm-powermap-form-block-relationship_type">
      <td class="label">{$form.relationship_type.label}</td>
      <td>{$form.relationship_type.html}
        <div class="description">Optional: Define relationship type with another contact</div>
      </td>
    </tr>

    <tr class="crm-powermap-form-block-related_contact_id">
      <td class="label">{$form.related_contact_id.label}</td>
      <td>{$form.related_contact_id.html}
        <div class="description">Optional: Select the contact this stakeholder is related to</div>
      </td>
    </tr>

    <tr class="crm-powermap-form-block-notes">
      <td class="label">{$form.notes.label}</td>
      <td>{$form.notes.html}
        <div class="description">Additional notes about this stakeholder</div>
      </td>
    </tr>
  </table>

  <div class="crm-submit-buttons">
    {include file="CRM/common/formButtons.tpl" location="bottom"}
  </div>
</div>
