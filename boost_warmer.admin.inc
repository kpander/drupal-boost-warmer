<?php
/**
 * @file
 * Admin settings for the boost warmer module.
 */

function boost_warmer_admin_settings() {
  $form = array();


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


  $form[BOOST_WARMER_VAR_USER_AGENT] = array(
    '#title'          => "User agent to request pages with",
    '#type'           => 'textfield',
    '#description'    => "If your site expects certain types of browsers, you'll want to set this to something typical, e.g.:<br>" . BOOST_WARMER_DEFAULT_USER_AGENT . "<br><br>However, if you want to be able to filter for and ignore boost_warmer page requests (e.g., in your analytics tool) set this to something custom, e.g.,<br>boost_warmer",
    '#default_value'  => variable_get(BOOST_WARMER_VAR_USER_AGENT, BOOST_WARMER_DEFAULT_USER_AGENT),
  );


  return system_settings_form($form);
}
