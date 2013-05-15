<?php
/**
 * @file
 * Code to crawl urls found in sitemap.xml, from hook_boost_warmer_get_urls()
 * and from the static list of paths.
 *
 * In many ways this is a refactor of the idea at http://drupal.org/node/1916906
 * with some additional functionality.
 */


class BoostWarmer {

  // Define the required configuration options. Specifically:
  // $config->max_requests = Maximum number of url requests per run.
  // $config->user_agent = The user agent to use when requesting pages via curl.
  protected $config;

  // Defines the url to the root of the site e.g., "http://domain.com/"
  // This is used for defining our paths to crawl as absolute urls.
  protected $urlBase;

  // Stores the full list of urls to crawl.
  protected $queue = array();


  /**
   * Constructor.
   */
  protected function __construct($config) {
    if (empty($config->auth_user) || empty($config->auth_pass)) {
      unset($config->auth_user);
      unset($config->auth_pass);
    }
    $this->config = $config;
  }


  /**
   * Crawl urls that don't currently have a valid Boost static html file.
   *
   * Abort after we reach the maximum number of page requests per session.
   */
  public function crawl() {
    // Get the queue of urls to crawl, or refresh the queue if it's empty.
    $this->queue = variable_get(BOOST_WARMER_VAR_QUEUE, array());
    if (!count($this->queue)) {
      $this->getUrls();
      // dpm($this->queue, 'refreshed queue with all urls to crawl');
    }

    $requested_urls = array();

    // Check each url to see if it's been processed by Boost yet.
    while (count($this->queue)) {
      // If we've already requested the maximum number of urls in this pass,
      // stop the process.
      if (count($requested_urls) >= $this->config->max_requests) {
        break;
      };

      $url = array_shift($this->queue);

      // Ask Boost for the statically cached filename. This will take into
      // consideration all Boost variables automatically, as it uses Boost
      // itself to generate the filename.
      $boost      = boost_transform_url($url);
      $temp_file  = DRUPAL_ROOT . '/' . $boost['filename'];
      $temp_file .= '.' . variable_get('boost_extension_texthtml', 'html');

      // dpm("look for file: $temp_file");
      if (file_exists($temp_file)) {
        // We already have a rendered static html file for this url. Because
        // we already called boost_cron() prior to this crawl event, that means
        // the cached file hasn't expired yet and is valid.
        //
        // Ignore this url.
        // drupal_set_message("<em>Ignore: $url</em>");
      }
      else {
        // This url hasn't been statically cached by Boost yet, or it's expired
        // recently. Request the page so Boost can build the static html file.
        // drupal_set_message("REQUEST: $url");
        $requested_urls[] = preg_replace("/^https?:\/\/[^\/]+\//", '', $url);
        $this->requestUrl($url);
      }
    }

    // Save the revised queue.
    variable_set(BOOST_WARMER_VAR_QUEUE, $this->queue);

    // Return the list of requested urls.
    return $requested_urls;
  }


  /**
   * Get all possible urls to crawl.
   *
   * This combines urls from by combining sitemap.xml, anything returned from
   * calling hook_boost_warmer_get_urls(), and the static list of xmls added
   * via the module settings page.
   */
  protected function getUrls() {
    $this->urlBase = $GLOBALS['base_url'] . '/';

    $this->getUrlsFromSitemap();
    $this->getUrlsFromHook();
    $this->getUrlsFromStaticList();
    $this->queue = array_unique($this->queue);
  }

  /**
   * Get URLs in sitemap.xml.
   */
  protected function getUrlsFromSitemap() {
    // Retrieve URLs from sitemap.xml, if it exists.
    $url  = $this->urlBase . 'sitemap.xml';
    $data = trim($this->requestUrl($url));
    if (empty($data)) {
      return;
    }

    // Get urls from xml (if we were given valid xml data).
    if ($this->isXML($data)) {
      $xml_file_list = new SimpleXMLElement($data);

      foreach ($xml_file_list->url as $xml_file_url_list) {
        $this->queue[] = (string) $xml_file_url_list->loc;
      }
    }
  }


  /**
   * Get URLs defined by hook_boost_warmer_get_urls().
   */
  protected function getUrlsFromHook() {
    $urls = variable_get(BOOST_WARMER_VAR_URLS_HOOK, array());

    for ($i = 0; $i < count($urls); $i++) {
      $url = trim($urls[$i]);
      if (!empty($url)) {
        $this->queue[] = $this->urlBase . $url;
      }
    }
  }

  /**
   * Get URLs defined in the admin/settings page.
   */
  protected function getUrlsFromStaticList() {
    $text = trim(variable_get(BOOST_WARMER_VAR_URLS_STATIC, ''));
    if (empty($text)) {
      return;
    }

    $urls = explode("\n", $text);
    for ($i = 0; $i < count($urls); $i++) {
      $url = trim($urls[$i]);

      if (!empty($url)) {
        $this->queue[] = $this->urlBase . $url;
      }
    }
  }


  /**
   * Request the given URL. 
   *
   * This will cause boost to render the page to a static html file, thereby 
   * 'warming' the cache for this url.
   */
  protected function requestUrl($url) {
    // Use curl if it's present. Otherwise, default to file_get_contents().
    if (function_exists('curl_exec')) {
      return $this->requestUrlCurl($url);
    }
    else {
      return $this->requestUrlStream($url);
    }
  }

  /**
   * Load a page via curl and return the page contents.
   * 
   * @return string
   *   Returns the html returned by the page request.
   *
   * @todo test user agent string is being used
   */
  protected function requestUrlCurl($url) {
    $ch = curl_init();

    // If we've provide http authentication credentials for requesting pages,
    // use them.
    if (isset($this->config->auth_user)) {
      curl_setopt($ch, CURLOPT_USERPWD, trim($this->config->auth_user) . ':' . trim($this->config->auth_pass));
    }

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->config->user_agent);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    $data = curl_exec($ch);
    curl_close($ch);

    return $data;
  }

  /**
   * Load a page via file_get_contents and return the page contents.
   *
   * @return string
   *   Returns the html returned by the page request.
   *
   * @todo test httpauth requests for stream method
   * @todo test user agent string is being used
   */
  protected function requestUrlStream($url) {
    // Create a stream.
    $headers = array(
      "Accept-language: en",
    );
    if (isset($this->config->auth_user)) {
      $headers[] = "Authorization: Basic " . base64_encode($this->config->auth_user . ':' . $this->config->auth_pass);
    }
    $opts = array(
      'http' => array(
        'method'      => "GET",
        'header'      => $headers,
        'user_agent'  => $this->config->user_agent,
      ),
    );
    $context = stream_context_create($opts);
    return file_get_contents($url, FALSE, $context);
  }


  /**
   * Confirm the given xml data is valid.
   *
   * This helps us avoid throwing an error when we use SimpleXMLElement().
   *
   * @see http://ca3.php.net/manual/en/class.simplexmlelement.php#107869
   */
  protected function isXML($data) {
    libxml_use_internal_errors(TRUE);

    $doc = new DOMDocument('1.0', 'utf-8');
    $doc->loadXML($data);

    $errors = libxml_get_errors();

    if (empty($errors)) {
      return TRUE;
    }

    $error = $errors[0];
    if ($error->level < 3) {
      return TRUE;
    }

    return FALSE;
  }

}
