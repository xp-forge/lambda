<?php namespace com\amazon\aws\lambda;

interface Lambda {

  /**
   * Invoke this lambda with the given event and context. May return any
   * value and raise any exception.
   *
   * @param  var $event
   * @param  com.amazon.aws.lambda.Context $context
   * @return var
   */
  public function process($event, $context);
}