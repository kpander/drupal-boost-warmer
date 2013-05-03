README.txt
==========
BOOST WARMER is a module that visits all pages defined in your sitemap.xml file
that haven't been cached by Boost. By running on a regular basis as a cron job,
it ensures that expired pages are re-cached so the chance of an anonymous user
visiting an uncached page is minimized.

While similar to Boost Crawler, it has the following differences:
- It will check all pages defined in sitemap.xml
- It will check any pages defined by hook_boost_warmer_get_urls()
- It does not require or use the HTTPRL library

The Boost Crawler module (included with Boost) uses the HTTPRL library for page 
requests (with good reasons related to performance). However, this will not 
function on some servers, which is one reason to use this module instead.


INSTALLATION
============
You'll need to add a cron event to initiate the crawler at a regular interval.
The following crontab example will crawl uncached pages every 10 minutes:

*/10 * * * * /usr/bin/wget -O - -q -t 1 http://your-site.com/boost-warmer/crawl


DETAILS
=======
@todo COMPLETE THIS DOCUMENTATION

Events that we care about:

1. hook_cron() (Drupal's general cron event)
  
   This is where we add any additional elements to sitemap.xml if necessary,
   and generate the sitemap.dynamic.txt file to provide additional urls
   to boost that may not exist in sitemap.xml.

2. boost_warmer_page_crawl()

   Loading this page causes pages that aren't currently boosted, to be
   requested (thereby warming the boost cache for them).
 






AUTHOR/MAINTAINER
=================
Kendall Anderson <dailyphotography at gmail DOT com>
http://invisiblethreads.com


CREDITS
=======
The initial idea comes from the shell script found here:
  http://dominiquedecooman.com/use-varnish-xmlsitemap-cron-and-bash-warm-cache-fast-pages

The page request (curl) implementation was derived from the code here:
  http://drupal.org/node/1916906



CHANGELOG
=========
v1.0, 2013-05-02
----------------
- initial development
