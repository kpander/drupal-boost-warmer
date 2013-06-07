README.txt
==========
BOOST WARMER is a module that visits all pages defined in your sitemap.xml file
that haven't been cached by Boost. By running on a regular basis as a cron job,
it ensures that expired pages are re-cached so the chance of an anonymous user
visiting an uncached page is minimized.


WHY USE THIS MODULE?
====================
Using Boost is great for performance, but it's only beneficial if a user visits
a page that's already been cached by Boost and hasn't expired yet. This module 
tries to keep the cache warm for every page a user can visit on your site.

While similar to Boost Crawler (included with Boost), it has the following 
differences:
- It will check all pages defined in sitemap.xml
- It will check any pages defined by hook_boost_warmer_get_urls()
- It will check a list of manually-defined pages
- It does not require or use the HTTPRL library

The Boost Crawler module uses the HTTPRL library for page 
requests (with good reasons related to performance). However, this will not 
function on some servers, which is one reason to use this module instead.


HOW IT WORKS
============
When the path /boost-warmer/crawl is requested, this is what happens:

1. A list of URLs to crawl is generated. This includes all urls found in
   /sitemap.xml (if it exists), all urls entered in the admin/settings page
   for this module, and all urls defined by third-party modules and returned
   through hook_boost_warmer_get_urls().

2. Boost is given a chance to expire any statically cached html pages that are
   no longer valid.
   
3. The first 5 urls that haven't been statically cached by Boost are given
   page requests, causing them to be cached by Boost. (The number of urls is
   configurable in the admin/settings page.)


INSTALLATION
============
After installation in Drupal, this module will do nothing.

You must add a cron event to initiate the crawler at a regular interval. The 
following crontab example will crawl for pages to Boost every 10 minutes:

*/10 * * * * /usr/bin/wget -O - -q -t 1 http://YOUR-SITE.COM/boost-warmer/crawl


CONFIGURATION
=============
This module can be configured by setting variables in your settings.php file.
Alternately, you can enable the Boost Warmer UI module to configure it via 
Drupal at the path: /admin/config/system/boost-warmer

Available configuration variables include:

  // Define the maximum number of page requests to execute each time Boost 
  // Warmer is called.
  // Default value = 5
  $conf['boost_warmer_max_requests'] = 10;

  // Should we log Boost Warmer activity to watchdog()? Note: This will 
  // increase database activity if you're running Boost Warmer frequently and
  // are using the Database Logging (dblog) module.
  // Allowed values = 1 (Yes, log the activity) or 0 (No, don't log activity)
  // Default value = 0
  $conf['boost_warmer_use_watchdog'] = 1;

  // Define a specific string for the 'user agent' when requesting pages. This
  // allows you to set something specific that you can filter for (or exclude)
  // when reviewing server or analytics logs.
  // Default value = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13"
  $conf['boost_warmer_user_agent'] = "Boost Warmer agent";

  // Define a set of paths that Boost Warmer should include in the full list of
  // urls it will crawl.
  $paths = array(
    'my-test-page',
    'contact',
    'contact/submission-received',
  );
  $conf['boost_warmer_urls_static'] = implode("\n", $paths);


CREDITS
=======
The initial idea comes from the shell script found here:
  http://dominiquedecooman.com/use-varnish-xmlsitemap-cron-and-bash-warm-cache-fast-pages

The page request (curl) implementation was derived from the code here:
  http://drupal.org/node/1916906


AUTHOR/MAINTAINER
=================
Kendall Anderson <dailyphotography at gmail DOT com>
http://invisiblethreads.com


CHANGELOG
=========
v1.0, 2013-05-02
- initial development


TODO
====
- see http://drupal.org/node/1938360 for another user who might use this
- add apikey for /boost-warmer/crawl to prevent DOS abuse
