<?php namespace com\amazon\aws\lambda;

/** @see https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html#runtimes-custom-response-streaming */
abstract class InvokeMode {
  protected $api;

  /** Creates a new invoke mode instance */
  public function __construct(RuntimeApi $api) {
    $this->api= $api;
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

}