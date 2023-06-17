<?php namespace com\amazon\aws\lambda;

use ReflectionFunction, Throwable as Any;
use io\Channel;
use io\streams\InputStream;
use lang\{Throwable, IllegalStateException, IllegalArgumentException};
use peer\http\{HttpConnection, HttpRequest, RequestData};
use text\json\Json;

/**
 * Runtime API
 *
 * @test  com.amazon.aws.lambda.unittest.RuntimeApiTest
 * @test  com.amazon.aws.lambda.unittest.ExceptionTest
 * @test  com.amazon.aws.lambda.unittest.BufferedTest 
 * @test  com.amazon.aws.lambda.unittest.StreamedTest
 * @see   https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html
 */
class RuntimeApi {
  public $conn, $version;

  /**
   * Creates a new runtime API instance.
   *
   * Uses a 15 minute timeout, which is the maximum lambda runtime, see
   * https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-limits.html
   *
   * @param  string|peer.HttpConnection $endpoint
   * @param  string $version
   */
  public function __construct($endpoint, $version= '2018-06-01') {
    $this->conn= $endpoint instanceof HttpConnection
      ? $endpoint
      : new HttpConnection("http://{$endpoint}")
    ;
    $this->conn->setTimeout(900);
    $this->version= $version;
  }

  /** Returns the buffered invoke mode */
  public function buffered(): InvokeMode {
    return new class($this, 'BUFFERED') extends InvokeMode {
      public function invoke($lambda, $event, $context) {
        try {
          $result= $lambda($event, $context);
          return $this->api->send("invocation/{$context->awsRequestId}/response", $result);
        } catch (Any $e) {
          return $this->api->report("invocation/{$context->awsRequestId}/error", $e);
        }
      }
    };
  }

  /** Returns the streaming invoke mode */
  public function streaming(): InvokeMode {
    return new class($this, 'RESPONSE_STREAM') extends InvokeMode implements Stream {
      private $request= null;
      private $response= null;
      private $stream= null;

      private function start() {
        $this->request->setHeader('Lambda-Runtime-Function-Response-Mode', 'streaming');
        $this->request->setHeader('Transfer-Encoding', 'chunked');
        return $this->api->conn->open($this->request);
      }

      public function transmit($source, $mimeType= null) {
        if ($this->response) throw new IllegalStateException('Streaming ended');

        if ($source instanceof InputStream) {
          $in= $source;
        } else if ($source instanceof Channel) {
          $in= $source->in();
        } else {
          throw new IllegalArgumentException('Expected either a channel or an input stream, have '.typeof($source));
        }

        if (null !== $mimeType) {
          $this->request->setHeader('Content-Type', $mimeType);
        }

        $this->stream ?? $this->stream= $this->start();
        try {
          while ($in->available()) {
            $this->stream->write($in->read());
            $this->stream->flush();
          }
        } finally {
          $in->close();
          $this->end();
        }
      }

      public function use($mimeType) {
        if ($this->response) throw new IllegalStateException('Streaming ended');

        $this->request->setHeader('Content-Type', $mimeType);
      }

      public function write($bytes) {
        if ($this->response) throw new IllegalStateException('Streaming ended');

        $this->stream ?? $this->stream= $this->start();
        $this->stream->write($bytes);
        $this->stream->flush();
      }

      public function end() {      
        if ($this->response) return; // Already ended

        $this->stream ?? $this->stream= $this->start();
        $this->response= $this->api->conn->finish($this->stream);
        $this->response->closeStream();
      }

      public function invoke($lambda, $event, $context) {
        try {
          $this->request= $this->api->request('POST', "invocation/{$context->awsRequestId}/response");
          $lambda($event, $this, $context);
          $this->end();
          return $this->response;
        } catch (Throwable $t) {

          // We can only report errors before starting to stream.
          if (null === $this->stream) {
            return $this->api->report("invocation/{$context->awsRequestId}/error", $t);
          }

          // TODO: Use HTTP trailers to report back errors
          $this->end();
          throw $t;
        }
      }
    };
  }

  /**
   * Returns an invokeable
   *
   * @param  callable|com.amazon.aws.lambda.Lambda|com.amazon.aws.lambda.Streaming $target
   * @return com.amazon.aws.lambda.Invokeable
   * @throws lang.IllegalArgumentException
   */
  public final function invokeable($target) {
    if ($target instanceof Lambda) {
      return new Invokeable([$target, 'process'], $this->buffered());
    } else if ($target instanceof Streaming) {
      return new Invokeable([$target, 'handle'], $this->streaming());
    } else if (is_callable($target)) {
      $n= (new ReflectionFunction($target))->getNumberOfParameters();
      return new Invokeable($target, $n < 3 ? $this->buffered() : $this->streaming());
    } else {
      throw new IllegalArgumentException('Expected callable|Lambda|Streaming, have '.typeof($target));
    }
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
   * Creates a request for a given endpoint
   * 
   * @param  string $method
   * @param  string $endpoint
   * @return peer.http.HttpRequest
   */
  public function request($method, $endpoint) {
    $request= $this->conn->create(new HttpRequest());
    $request->setMethod($method);
    $request->setTarget("/{$this->version}/runtime/{$endpoint}");
    return $request;
  }

  /**
   * Receives from a given endpoint
   * 
   * @param  string $endpoint
   * @return peer.http.HttpResponse
   */
  public function receive($endpoint) {
    return $this->conn->send($this->request('GET', $endpoint));
  }

  /**
   * Sends result to a given endpoint
   * 
   * @param  string $endpoint
   * @param  var $result
   * @return peer.http.HttpResponse
   */
  public function send($endpoint, $result) {
    $request= $this->request('POST', $endpoint);
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