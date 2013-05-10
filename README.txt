README.txt
==========
BOOST WARMER is a module that visits all pages defined in your sitemap.xml file
that haven't been cached by Boost. By running on a regular basis as a cron job,
it ensures that expired pages are re-cached so the chance of an anonymous user
visiting an uncached page is minimized.


WHY USE THIS MODULE?
====================
Using Boost is great for performance, but it's only beneficial if a user visits
a page that's already been cached by Boost. (And that the cached page hasn't
expired yet). This module tries to keep the cache warm for every page a user
can visit on your site.

While similar to Boost Crawler (included with Boost), it has the following differences:
- It will check all pages defined in sitemap.xml
- It will check any pages defined by hook_boost_warmer_get_urls()
- It will check a list of manually-defined pages
- It does not require or use the HTTPRL library

The Boost Crawler module uses the HTTPRL library for page 
requests (with good reasons related to performance). However, this will not 
function on some servers, which is one reason to use this module instead.


HOW IT WORKS
============
When the path /boost-warmer/crawl is requested:

1. A list of URLs to crawl is generated. This includes all urls returned from
   /sitemap.xml (if it exists), all urls entered in the admin/settings page
   for this module, and all urls defined by third-party modules and returned
   through hook_boost_warmer_get_urls().

2. Boost is given a chance to expire any statically cached html pages that are
   no longer valid.
   
3. The first 10 urls that haven't been statically cached by Boost are given
   page requests, causing them to be cached by Boost. (The number of urls is
   configurable in the admin/settings page.)


INSTALLATION
============
For this module to work, you'll need to add a cron event to initiate the 
crawler at a regular interval. The following crontab example will crawl 
for pages to Boost every 10 minutes:

*/10 * * * * /usr/bin/wget -O - -q -t 1 http://YOUR-SITE.COM/boost-warmer/crawl


CONFIGURATION
=============
After installing the module, go to /admin/config/system/boost-warmer to change
the module settings.


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
- in admin/settings page, provide link to 'crawl all pages now'
