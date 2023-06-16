<?php namespace com\amazon\aws\lambda;

use io\Channel;
use io\streams\InputStream;
use lang\{IllegalStateException, IllegalArgumentException};
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
   * @param  string $mimeType
   * @return void
   * @throws lang.IllegalStateException
   */
  public function use($mimeType) {
    if ($this->response) throw new IllegalStateException('Streaming ended');

    $this->request->setHeader('Content-Type', $mimeType);
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
   * Transmits a given source to the output asynchronously.
   *
   * @param  io.Channel|io.streams.InputStream $source
   * @param  string $mimeType
   * @return void
   * @throws lang.IllegalArgumentException
   * @throws lang.IllegalStateException
   */
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

    $this->stream ?? $this->stream= $this->conn->open($this->request);
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