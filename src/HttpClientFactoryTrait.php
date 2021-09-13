<?php
namespace DoubleBreak\Spapi;

trait HttpClientFactoryTrait {
  private function createHttpClient($config)
  {
    $httpConfig = $this->config['http'] ?? [];
    $httpConfig = array_merge($httpConfig, $config);
    $ClientFactory = new \Hyperf\Guzzle\ClientFactory();
    $client = $ClientFactory->create($httpConfig);
    return $client;
  }

}
