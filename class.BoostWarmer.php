<?php
/**
 * @file
 * Code to crawl urls found in sitemap.xml, from hook_boost_warmer_get_urls()
 * and from the static list of paths.
 *
 * Most of this was derived from code found here:
 * http://drupal.org/node/1916906
 */


class BoostWarmer {

  protected $max_requests;
  protected $user_agent;

  protected $url_base = '';
  protected $urls = array();

  protected $user;
  protected $password;


  function __construct($options) {
    // Define maximum number of url requests per run.
    $this->max_requests = $options->max_requests;

    // Define the CURL user agent.
    $this->user_agent = $options->user_agent;


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
    $this->url_base = $GLOBALS['base_url'] . '/';

    $this->getUrlsFromSitemap();
    $this->getUrlsFromHook();
    $this->getUrlsFromStaticList();
    $this->urls = array_unique($this->urls);
    #dpm($this->urls, 'all urls to crawl');
  }

  /**
   * Get URLs in sitemap.xml.
   */
  private function getUrlsFromSitemap() {
    // Retrieve URLs from sitemap.xml, if it exists.
    $url  = $this->url_base . 'sitemap.xml';
    $data = trim($this->requestUrl($url));
    if (empty($data)) {
      return;
    }
 
    // Get urls from xml.
    $xml_file_list = new SimpleXMLElement($data);

    foreach ($xml_file_list->url as $xml_file_url_list) {
      $this->urls[] = (string) $xml_file_url_list->loc;
    }
  }

  /**
   * Get URLs defined by hook_boost_warmer_get_urls().
   */
  private function getUrlsFromHook() {
    $urls = variable_get(BOOST_WARMER_VAR_URLS_HOOK, array());

    for ($i=0; $i<count($urls); $i++) {
      $url = trim($urls[$i]);
      if (!empty($url)) {
        $this->urls[] = $this->url_base . $url;
      }
    }
  }

  /**
   * Get URLs defined in the admin/settings page.
   */
  private function getUrlsFromStaticList() {
    $text = trim(variable_get(BOOST_WARMER_VAR_URLS_STATIC, ''));
    if (empty($text)) {
      return;
    }

    $urls = explode("\n", $text);
    for ($i = 0; $i<count($urls); $i++) {
      $url = trim($urls[$i]);

      if (!empty($url)) {
        $this->urls[] = $this->url_base . $url;
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

    // Check each url to see if it's been processed by Boost yet.
    foreach ($this->urls as $url) {
      // If we've already requested the maximum number of urls in this pass,
      // stop the process.
      if (count($requested_urls) >= $this->max_requests) {
        break;
      };

      // Ask Boost for the statically cached filename. This will take into
      // consideration all Boost variables automatically, as it uses Boost
      // itself to generate the filename.
      $boost      = boost_transform_url($url);
      $temp_file  = DRUPAL_ROOT . '/' . $boost['filename'];
      $temp_file .= '.' . variable_get('boost_extension_texthtml', 'html');

      #dpm("look for file: $temp_file");

      if (file_exists($temp_file)) {
        // We already have a rendered static html file for this url. Because
        // we already called boost_cron() prior to this crawl event, that means
        // the cached file hasn't expired yet and is valid.
        //
        // Ignore this url.
        #drupal_set_message("<em>Ignore: $url</em>");
      }
      else {
        // This url hasn't been statically cached by Boost yet, or it's expired
        // recently. Request the page so Boost can build the static html file.
        #drupal_set_message("REQUEST: $url");
        $requested_urls[] = preg_replace("/^https?:\/\/[^\/]+\//", '', $url);
        $this->requestUrl($url);
      }
    }

    // Return the list of requested urls.
    return $requested_urls;
  }



  /**
   * Request the given URL. This will cause boost to render the page to a
   * static html file, thereby 'warming' the cache for this url.
   *
   * @todo implement http auth for both curl and stream
   */
  private function requestUrl($url) {
    // Use curl if it's present. Otherwise, we use file_get_contents().
    if (function_exists('curl_exec')) {
      $ch = curl_init();
#      if (!empty($this->password)) {
#        curl_setopt($ch, CURLOPT_USERPWD, $this->user . ':' . $this->password);
#      }
      curl_setopt($ch, CURLOPT_URL, $url);
      curl_setopt($ch, CURLOPT_USERAGENT, $this->user_agent);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
      $data = curl_exec($ch);
      curl_close($ch);
    }
    else {
      // Create a stream.
      $opts = array(
        'http' => array(
          'method'  => "GET",
          'header'  => "Accept-language: en\r\n",# . "Cookie: foo=bar\r\n",
        ),
      );
      $context  = stream_context_create($opts);
      $data     = file_get_contents($url, false, $context);
    }

    return $data;
  }



} ### end of class
