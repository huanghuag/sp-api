<?php
namespace app\api\library;
use DoubleBreak\Spapi\TokenStorageInterface;

class TokenStorage implements TokenStorageInterface {

    private $tokenArray;
  public function __construct()
  {

  }


  public function getToken($key): ?array
  {
    $content = $this->tokenArray;

    if ($content != '') {
      return $content[$key] ?? null;
    }
    return null;
  }


  public function storeToken($key, $value)
  {

    $this->tokenArray[$key] = $value;
    return true;
  }
}
