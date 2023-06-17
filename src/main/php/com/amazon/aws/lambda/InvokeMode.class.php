<?php namespace com\amazon\aws\lambda;

use lang\Value;
use util\Comparison;

/** @see https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html#runtimes-custom-response-streaming */
abstract class InvokeMode implements Value {
  use Comparison;

  protected $api, $identifier;

  /** Creates a new invoke mode instance */
  public function __construct(RuntimeApi $api, $identifier) {
    $this->api= $api;
    $this->identifier= $identifier;
  }

  /**
   * Invokes the given lambda
   *
   * @param  callable $lambda
   * @param  var $event
   * @param  com.amazon.aws.lambda.Context $context
   * @return peer.HttpResponse
   */
  public abstract function invoke($lambda, $event, $context);

  /** @return string */
  public function toString() {
    return strtr(self::class, '\\', '.').'<'.$this->identifier.'>';
  }
}