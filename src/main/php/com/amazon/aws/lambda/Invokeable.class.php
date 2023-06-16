<?php namespace com\amazon\aws\lambda;

class Invokeable {
  public $callable, $invokeMode;

  public function __construct($callable, $invokeMode) {
    $this->callable= $callable;
    $this->invokeMode= $invokeMode;
  }

  /**
   * Returns the invoke mode for the API
   *
   * @param  com.amazon.aws.lambda.RuntimeApi
   * @return com.amazon.aws.lambda.InvokeMode
   */
  public function mode($api) {
    return new $this->invokeMode($api);
  }
}