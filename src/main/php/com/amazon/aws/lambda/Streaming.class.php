<?php namespace com\amazon\aws\lambda;

use lang\IllegalStateException;
use peer\http\{HttpConnection, HttpRequest};

/**
 * Lambda response streaming
 *
 * @test com.amazon.aws.lambda.unittest.StreamingTest 
 * @see   https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html#runtimes-custom-response-streaming
 */
class Streaming {
  private $conn, $request;
  private $response= null;
  private $stream= null;
  
  public function __construct(HttpConnection $conn) {
    $this->conn= $conn;

    $this->request= $conn->create(new HttpRequest());
    $this->request->setMethod('POST');
    $this->request->setHeader('Lambda-Runtime-Function-Response-Mode', 'streaming');
    $this->request->setHeader('Transfer-Encoding', 'chunked');
  }

  /**
   * Uses given mime type
   *
   * @param  string $mime
   * @return void
   * @throws lang.IllegalStateException
   */
  public function use($mime) {
    if ($this->response) throw new IllegalStateException('Streaming ended');

    $this->request->setHeader('Content-Type', $mime);
  }

  /**
   * Writes to and flushes the stream
   *
   * @param  string $bytes
   * @return void
   * @throws lang.IllegalStateException
   */
  public function write($bytes) {
    if ($this->response) throw new IllegalStateException('Streaming ended');

    $this->stream ?? $this->stream= $this->conn->open($this->request);
    $this->stream->write($bytes);
    $this->stream->flush();
  }

  /**
   * Ends this response stream
   *
   * @return void
   */
  public function end() {
    if ($this->response) {
      // Already ended
    } else if ($this->stream) {
      $this->response= $this->conn->finish($this->stream);
      $this->response->closeStream();
    } else {
      $this->response= $this->conn->send($this->request);
      $this->response->closeStream();
    }
  }

  public function invoke($lambda, $event, $context) {
    try {
      $lambda($event, $context, $this);
    } finally {
      $this->end(); // Ensure response is sent and stream is closed
    }

    return $this->response;
  }
}