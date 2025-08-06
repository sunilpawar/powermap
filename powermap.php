<?php

require_once 'powermap.civix.php';

use CRM_Powermap_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function powermap_civicrm_config(&$config): void {
  _powermap_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function powermap_civicrm_install(): void {
  _powermap_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function powermap_civicrm_enable(): void {
  _powermap_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function powermap_civicrm_navigationMenu(&$menu) {
  _powermap_civix_insert_navigation_menu($menu, 'Contacts', [
    'label' => E::ts('Power Mapping'),
    'name' => 'power_mapping',
    'url' => 'civicrm/powermap/dashboard',
    'permission' => 'access power mapping',
    'operator' => 'OR',
    'separator' => 0,
  ]);

  _powermap_civix_insert_navigation_menu($menu, 'Contacts/power_mapping', [
    'label' => E::ts('Dashboard'),
    'name' => 'powermap_dashboard',
    'url' => 'civicrm/powermap/dashboard',
    'permission' => 'access power mapping',
  ]);

  _powermap_civix_insert_navigation_menu($menu, 'Contacts/power_mapping', [
    'label' => E::ts('Manage Maps'),
    'name' => 'powermap_manage',
    'url' => 'civicrm/powermap/manage',
    'permission' => 'edit power mapping',
  ]);

  _powermap_civix_insert_navigation_menu($menu, 'Contacts/power_mapping', [
    'label' => E::ts('Settings'),
    'name' => 'powermap_settings',
    'url' => 'civicrm/powermap/settings',
    'permission' => 'administer power mapping',
  ]);

  _powermap_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_permission().
 */
function powermap_civicrm_permission(&$permissions) {
  $permissions += [
    'access power mapping' => [
      'label' => E::ts('Access Power Mapping'),
      'description' => E::ts('View power mapping dashboards and visualizations'),
    ],
    'edit power mapping' => [
      'label' => E::ts('Edit Power Mapping'),
      'description' => E::ts('Create and modify power mapping data'),
    ],
    'administer power mapping' => [
      'label' => E::ts('Administer Power Mapping'),
      'description' => E::ts('Configure power mapping settings and permissions'),
    ],
  ];
}
