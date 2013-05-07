<?php

/**
 * @file
 * This file contains no working PHP code; it exists to provide additional
 * documentation for doxygen as well as to document hooks in the standard
 * Drupal manner.
 */


/**
 * @defgroup boost_warmer Boost Warmer module integrations.
 *
 * Module integrations with the boost_warmer module.
 */

/**
 * @defgroup boost_warmer_hooks Boost Warmer's hooks
 * @{
 * Hooks that can be implemented by other modules in order to extend 
 * boost_warmer.
 */

/**
 * Specify urls that should be checked by boost_warmer when looking for expired
 * or non-cached pages.
 *
 * @return
 *   An array of paths or aliases, not including the domain.
 */
function hook_boost_warmer_get_urls() {
  return array(
    'contact-us',
    'about/welcome',
  );
}

/**
 * @}
 */
