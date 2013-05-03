<?php
/**
 * @file
 * Code to crawl urls found in sitemap.xml and sitemap.dynamic.txt, that don't
 * have rendered boost file already.
 *
 * Most of this was derived from code found here:
 * http://drupal.org/node/1916906
 */


class BoostWarmer {

  protected $base_sitemap;
  protected $max_requests;

  protected $urls = array();

  protected $user;
  protected $password;


  function __construct() {
    // Define location of sitemap.xml.
    $this->base_sitemap = variable_get('xmlsitemap_base_url', '') . "/sitemap.xml";

    // Define maximum number of url requests per run.
    $this->max_requests = 5;


    $this->user = '';
    $this->password = '';


    // Get all possible urls to crawl (from combining sitemap.xml and the
    // files/sitemap.dynamic.txt files).
    $this->getUrls();
  }


  /**
   * Get all URLs to crawl.
   */
  private function getUrls() {
    $this->getUrlsFromSitemap();
    $this->getUrlsFromDynamicList();
    $this->urls = array_unique($this->urls);
    dpm($this->urls, 'all urls to crawl');
  }

  /**
   * Get URLs in sitemap.xml.
   */
  private function getUrlsFromSitemap() {
    // Retrieve URLs from sitemap.xml.
    $ch = curl_init();
    if (!empty($this->password)) {
      curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->password);
    }
    curl_setopt($ch, CURLOPT_URL, $this->base_sitemap);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);

 
    // Get urls from xml.
    $xml_file_list = new SimpleXMLElement($data);

    foreach ($xml_file_list->url as $xml_file_url_list) {
      $this->urls[] = (string) $xml_file_url_list->loc;
    }
  }

  /**
   * Get URLs in files/sitemap.dynamic.txt.
   */
  private function getUrlsFromDynamicList() {
    $file = boost_warmer_get_filename();
    if (!file_exists($file)) {
      return;
    }

    $items  = file($file);
    $urls   = array();

    for ($i=0; $i<count($items); $i++) {
      $url = trim($items[$i]);
      if (!empty($url)) {
        $this->urls[] = $url;
      }
    }
  }





  /**
   * For each url that doesn't have a rendered boost static html file, request
   * it, to cause boost to render that page.
   *
   * Abort after we reach the maximum number of page requests per session.
   */
  public function crawl() {
    $requested_urls = array();

    // Check each url to see if it's been 'boosted' yet.
    foreach ($this->urls as $url) {
      // If we've already requested the maximum number of urls in this pass,
      // stop the process.
      if (count($requested_urls) >= $this->max_requests) {
        break;
      };

      // Ask Boost to generate a name for the cached filename. This should take
      // into consideration all boost variables automatically, as it uses
      // Boost itself to generate the filename.
      $boost      = boost_transform_url($url);
      $temp_file  = DRUPAL_ROOT . '/' . $boost['filename'];
      $temp_file .= '.' . variable_get('boost_extension_texthtml', 'html');

#      $temp_file = $this->base_path . substr($url, 7) ."." . variable_get('boost_extension_texthtml', 'html');
#      $temp_file = str_replace('?', variable_get('boost_char', '_'), $temp_file);
      dpm("look for file: $temp_file");

      if (!(file_exists($temp_file))) {
        dpm("not found");
        // We don't have a rendered boost file for this url. Request the page
        // in order to generate the static html file.
        #drupal_set_message("REQUEST: $url");
        $requested_urls[] = preg_replace("/^http:\/\/[^\/]+\//", '', $url);
        $this->requestUrl($url);
      }
      else {
        dpm("found");
        // We already have a rendered boost file for this url. Ignore it.
        #drupal_set_message("<em>Ignore: $url</em>");
      }
    }

    // Log a record of the pages we requested.
    $message  = "Requested %count urls: " . implode(', ', $requested_urls);
    $vars     = array('%count' => count($requested_urls));
    watchdog('boost_warmer', $message, $vars, WATCHDOG_INFO);
  }



  /**
   * Request the given URL. This will cause boost to render the page to a
   * static html file, thereby 'warming' the cache for this url.
   */
  private function requestUrl($url) {
    $ch = curl_init();
    if (!empty($this->password)) {
      curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->password);
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $data = curl_exec($ch);
    curl_close($ch);
  }



} ### end of class
