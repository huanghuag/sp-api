<?php
namespace DoubleBreak\Spapi;

use GuzzleHttp\Psr7\Query;

class Client {

  use HttpClientFactoryTrait;

  protected $credentials;
  protected $config;
  protected $signer;
  protected $lastHttpResponse = null;
  public function __construct(array $credentials = [], array $config = [])
  {
    $this->credentials = $credentials;
    $this->config = $config;
    $this->signer = new Signer(); //Should be injected :(
  }


  private function normalizeHeaders($headers)
  {
    return $result = array_combine(
       array_map(function($header) { return strtolower($header); }, array_keys($headers)),
       $headers
    );

  }

  private function headerValue($header)
  {
      return \GuzzleHttp\Psr7\Header::parse($header)[0];
      
  }

  public function send($uri, $requestOptions = [])
  {
    $requestOptions['headers'] = $requestOptions['headers'] ?? [];
    $requestOptions['headers']['accept'] = 'application/json';
    $requestOptions['headers'] = $this->normalizeHeaders($requestOptions['headers']);


    //Prepare for signing
    $signOptions = [
      'service' => 'execute-api',
      'access_token' => $this->credentials['access_token'],
      'access_key' => $this->credentials['sts_credentials']['access_key'],
      'secret_key' =>  $this->credentials['sts_credentials']['secret_key'],
      'security_token' =>  $this->credentials['sts_credentials']['session_token'],
      'region' =>  $this->config['region'] ?? null,
      'host' => $this->config['host'],
      'uri' =>  $uri,
      'method' => $requestOptions['method']
    ];

    if (isset($requestOptions['query'])) {
      $query = $requestOptions['query'];
      ksort($query);
      $signOptions['query_string'] =  $this->build_query($query);
    }

    if (isset($requestOptions['form_params'])) {
      ksort($requestOptions['form_params']);
      $signOptions['payload'] = $this->build_query($requestOptions['form_params']);
    }

    if (isset($requestOptions['json'])) {
      ksort($requestOptions['json']);
      $signOptions['payload'] = json_encode($requestOptions['json']);
    }

    //Sign
    $requestOptions = $this->signer->sign($requestOptions, $signOptions);

    //Prep client and send the request
    $client = $this->createHttpClient([
      'base_uri' => 'https://' . $this->config['host']
    ]);

    try {
      $this->lastHttpResponse = null;
      $method = $requestOptions['method'];
      unset($requestOptions['method']);
      $response = $client->request($method, $uri, $requestOptions);
      $this->lastHttpResponse = $response;
      return json_decode($response->getBody(), true);
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      $this->lastHttpResponse = $e->getResponse();
      throw $e;
    }

  }

  public function getLastHttpResponse()
  {
    return $this->lastHttpResponse;
  }
    /**
     * Build a query string from an array of key value pairs.
     *
     * This function can use the return value of `parse_query()` to build a query
     * string. This function does not modify the provided keys when an array is
     * encountered (like `http_build_query()` would).
     *
     * @param array     $params   Query string parameters.
     * @param int|false $encoding Set to false to not encode, PHP_QUERY_RFC3986
     *                            to encode using RFC3986, or PHP_QUERY_RFC1738
     *                            to encode using RFC1738.
     *
     * @return string
     *
     * @deprecated build_query will be removed in guzzlehttp/psr7:2.0. Use Query::build instead.
     */
    private function build_query(array $params, $encoding = PHP_QUERY_RFC3986)
    {
        return Query::build($params, $encoding);
    }

}
