<?php namespace com\amazon\aws\lambda;

use lang\Value;
use util\Secret;

class Credentials implements Value {
  private $accessKey, $secretKey, $sessionToken;

  /**
   * Creates a new instance
   *
   * @param  string $accessKey
   * @param  string|util.Secret $secretKey
   * @param  ?string $sessionToken
   */
  public function __construct($accessKey, $secretKey, $sessionToken= null) {
    $this->accessKey= $accessKey;
    $this->secretKey= $secretKey instanceof Secret ? $secretKey : new Secret($secretKey);
    $this->sessionToken= $sessionToken;
  }

  /** @return string */
  public function accessKey() { return $this->accessKey; }

  /** @return util.Secret */
  public function secretKey() { return $this->secretKey; }

  /** @return ?string */
  public function sessionToken() { return $this->sessionToken; }

  /** @return string */
  public function hashCode() {
    return 'C'.sha1($this->accessKey.$this->secretKey->reveal().$this->sessionToken);
  }

  /** @return string */
  public function toString() {
    return sprintf(
      '%s(accessKey: %s, secretKey: %s%s)',
      nameof($this),
      $this->accessKey,
      str_repeat('*', strlen($this->secretKey->reveal())),
      null === $this->sessionToken ? '' : ', sessionToken: '.$this->sessionToken
    );
  }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    if (!($value instanceof self)) return 1;

    $r= $this->accessKey <=> $value->accessKey;
    if (0 !== $r) return $r;

    $r= $this->sessionToken <=> $value->sessionToken;
    if (0 !== $r) return $r;

    return $this->secretKey->equals($value->secretKey) ? 0 : 1;
  }
}