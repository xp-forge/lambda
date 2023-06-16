<?php namespace com\amazon\aws\lambda;

use Throwable as Any;
use peer\http\{HttpConnection, HttpRequest, RequestData};
use text\json\Json;

/**
 * Lambda buffered response
 *
 * @test com.amazon.aws.lambda.unittest.BufferedTest 
 */
class Buffered extends InvokeMode {
  private $conn;

  /** Creates a new streaming response directed at the given HTTP endpoint */
  public function __construct(HttpConnection $conn) {
    $this->conn= $conn;
  }

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
      $target= '/response';
    } catch (Any $e) {
      $result= self::error($e);
      $target= '/error';
    }

    $request= $this->conn->create(new HttpRequest());
    $request->setMethod('POST');
    $request->setHeader('Content-Type', 'application/json');
    $request->setTarget(rtrim($request->target, '/').$target);
    $request->setParameters(new RequestData(Json::of($result)));

    return $this->conn->send($request);
  }
}