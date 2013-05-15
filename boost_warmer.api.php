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
 * Get urls for boost_warmer to check when looking for expired pages.
 *
 * @return array
 *   An array of paths or aliases (which don't include the domain).
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
