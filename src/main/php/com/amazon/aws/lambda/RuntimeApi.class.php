<?php namespace com\amazon\aws\lambda;

use lang\Throwable;
use peer\http\{HttpConnection, HttpRequest, RequestData};
use text\json\Json;

/**
 * Runtime API
 *
 * @see   https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html
 */
class RuntimeApi {
  private $conn, $version;

  public function __construct($endpoint, $version= '2018-06-01') {
    $this->conn= $endpoint instanceof HttpConnection
      ? $endpoint
      : new HttpConnection("http://{$endpoint}")
    ;
    $this->version= $version;

    // Use a 15 minute timeout, this is the maximum lambda runtime, see
    // https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-limits.html
    $this->conn->setTimeout(900);
  }

  /**
   * Marshals an exception according to the AWS specification.
   *
   * @param  Throwable $e
   * @return [:var]
   */
  public function error($e) {
    $error= ['errorMessage' => $e->getMessage(), 'errorType' => nameof($e), 'stackTrace' => []];

    $t= Throwable::wrap($e);
    do {
      $error['stackTrace'][]= $t->compoundMessage();
      foreach ($t->getStackTrace() as $e) {
        $error['stackTrace'][]= sprintf(
          '%s::%s(...) (line %d of %s)%s',
          strtr($e->class, '\\', '.') ?: '<main>',
          $e->method,
          $e->line,
          $e->file ? basename($e->file) : '',
          $e->message ? ' - '.$e->message : ''
        );
      }
    } while ($t= $t->getCause());

    return $error;
  }

  /**
   * Creates a POST request for a given endpoint
   * 
   * @param  string $endpoint
   * @return peer.http.HttpRequest
   */
  public function request($endpoint) {
    $request= $this->conn->create(new HttpRequest());
    $request->setMethod('POST');
    $request->setTarget("/{$this->version}/runtime/{$endpoint}");
    return $request;
  }

  /**
   * Starts a stream for a given request
   * 
   * @param  peer.http.HttpRequest
   * @return peer.http.HttpOutputStream
   */
  public function stream($request) {
    return $this->conn->open($request);
  }

  /**
   * Finishes a stream for a given request
   * 
   * @param  peer.http.HttpOutputStream
   * @return peer.http.HttpResponse
   */
  public function finish($stream) {
    return $this->conn->finish($stream);
  }

  /**
   * Receives from a given endpoint
   * 
   * @param  string $endpoint
   * @return peer.http.HttpResponse
   */
  public function receive($endpoint) {
    $request= $this->conn->create(new HttpRequest());
    $request->setMethod('GET');
    $request->setTarget("/{$this->version}/runtime/{$endpoint}");
    return $this->conn->send($request);
  }

  /**
   * Sends result to a given endpoint
   * 
   * @param  string $endpoint
   * @param  var $result
   * @return peer.http.HttpResponse
   */
  public function send($endpoint, $result) {
    $request= $this->conn->create(new HttpRequest());
    $request->setMethod('POST');
    $request->setTarget("/{$this->version}/runtime/{$endpoint}");
    $request->setHeader('Content-Type', 'application/json');
    $request->setParameters(new RequestData(Json::of($result)));
    return $this->conn->send($request);
  }

  /**
   * Report an error to a given endpoint
   * 
   * @param  string $endpoint
   * @param  var $result
   * @return peer.http.HttpResponse
   */
  public function report($endpoint, $exception) {
    return $this->send($endpoint, $this->error($exception));
  }
}