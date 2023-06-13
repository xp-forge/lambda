<?php namespace xp\lambda;

use peer\http\{HttpConnection, HttpRequest};

/** @see https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html#runtimes-custom-response-streaming */
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
   */
  public function use($mime) {
    $this->request->setHeader('Content-Type', $mime);
  }

  /**
   * Writes to and flushes the stream
   *
   * @param  string $bytes
   * @return void
   */
  public function write($bytes) {
    $this->stream ?? $this->stream= $this->conn->open($this->request);
    $this->stream->write($bytes);
    $this->stream->flush();
  }

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