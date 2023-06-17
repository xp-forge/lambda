<?php namespace com\amazon\aws\lambda;

interface Streaming {

  /**
   * Invoke this lambda with the given event and context, streaming the
   * response using the given stream.
   *
   * @param  var $event
   * @param  com.amazon.aws.lambda.Stream $stream
   * @param  com.amazon.aws.lambda.Context $context
   * @return void
   */
  public function handle($event, $stream, $context);
}