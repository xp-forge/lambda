<?php namespace com\amazon\aws\lambda;

use Throwable;

/**
 * Lambda buffered response
 *
 * @test com.amazon.aws.lambda.unittest.BufferedTest 
 */
class Buffered extends InvokeMode {

  /**
   * Invokes the given lambda
   *
   * @param  callable $lambda
   * @param  var $event
   * @param  com.amazon.aws.lambda.Context $context
   * @return peer.HttpResponse
   */
  public function invoke($lambda, $event, $context) {
    try {
      $result= $lambda($event, $context);
      return $this->api->send("invocation/{$context->awsRequestId}/response", $result);
    } catch (Throwable $t) {
      return $this->api->report("invocation/{$context->awsRequestId}/error", $t);
    }
  }
}