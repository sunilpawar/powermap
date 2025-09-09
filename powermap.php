<?php

require_once 'powermap.civix.php';

use CRM_Powermap_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function powermap_civicrm_config(&$config) {
  _powermap_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function powermap_civicrm_install() {
  _powermap_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function powermap_civicrm_enable() {
  _powermap_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_navigationMenu().
 */
function powermap_civicrm_navigationMenu(&$menu) {
  _powermap_civix_insert_navigation_menu($menu, 'Contacts', [
    'label' => E::ts('PowerMap'),
    'name' => 'powermap',
    'url' => 'civicrm/powermap',
    'permission' => 'access CiviCRM',
    'operator' => 'OR',
    'separator' => 0,
    'icon' => 'crm-i fa-sitemap',
  ]);
  _powermap_civix_navigationMenu($menu);
}

/**
 * Implements hook_civicrm_permission().
 */
function powermap_civicrm_permission(&$permissions) {
  $permissions['access powermap'] = [
    'label' => E::ts('PowerMap: Access PowerMap'),
    'description' => E::ts('Grants access to view and use PowerMap visualization'),
  ];
  $permissions['edit powermap'] = [
    'label' => E::ts('PowerMap: Edit PowerMap'),
    'description' => E::ts('Grants permission to create and modify power map data'),
  ];
}

/**
 * Implements hook_civicrm_angularModules().
 */
function powermap_civicrm_angularModules(&$angularModules) {
  $angularModules['crmPowermap'] = [
    'ext' => 'com.skvare.powermap',
    'js' => ['ang/crmPowermap.js', 'ang/crmPowermap/*.js'],
    'css' => ['ang/crmPowermap.css'],
    'partials' => ['ang/crmPowermap'],
    'requires' => ['crmUi', 'crmUtil', 'ngRoute'],
  ];
}
