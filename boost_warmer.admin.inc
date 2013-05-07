<?php
/**
 * @file
 * Admin settings for the boost warmer module.
 */

function boost_warmer_admin_settings() {
  $form = array();

  $msg = "@todo: created dynamically with results of hook_boost_warmer_get_urls but we should also allow for a static list that can be updated...";


  $form[BOOST_WARMER_VAR_MAX_REQUESTS] = array(
    '#title'          => 'Maximum number of page requests per execution',
    '#type'           => 'textfield',
    '#default_value'  => variable_get(BOOST_WARMER_VAR_MAX_REQUESTS, BOOST_WARMER_DEFAULT_MAX_REQUESTS),
  );


  $form[BOOST_WARMER_VAR_URLS_STATIC] = array(
    '#title'          => "Additional paths to test for Boost cache expiry",
    '#type'           => 'textarea',
    '#description'    => "These should be paths that do not exist in sitemap.xml, nor are identified by hook_boost_warmer_get_urls().<br><br>One per line. Do not including leading '/' or http://.",
    '#default_value'  => variable_get(BOOST_WARMER_VAR_URLS_STATIC, ''),
  );


  $form[BOOST_WARMER_VAR_USE_WATCHDOG] = array(
    '#title'          => "Should page requests be logged via watchdog?",
    '#type'           => 'checkbox',
    '#description'    => "This can quickly fill up your database. Only enable this if you know what you're doing!",
    '#default_value'  => variable_get(BOOST_WARMER_VAR_USE_WATCHDOG, BOOST_WARMER_DEFAULT_USE_WATCHDOG),
  );

  return system_settings_form($form);
}
