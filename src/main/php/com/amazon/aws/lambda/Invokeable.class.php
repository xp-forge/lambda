<?php namespace com\amazon\aws\lambda;

class Invokeable {
  private $callable, $mode;

  /**
   * Create a new invokeable
   *
   * @param  callable $callable
   * @param  com.amazon.aws.lambda.InvokeMode $mode
   */
  public function __construct($callable, InvokeMode $mode) {
    $this->callable= $callable;
    $this->mode= $mode;
  }

  /**
   * Invoke this instance
   *
   * @param  var $event
   * @param  com.amazon.aws.lambda.Context $context
   * @return var
   */
  public function invoke($event, Context $context) {
    return $this->mode->invoke($this->callable, $event, $context);
  }
}