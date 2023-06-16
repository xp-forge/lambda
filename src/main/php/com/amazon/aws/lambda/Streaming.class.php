<?php namespace com\amazon\aws\lambda;

use Throwable;
use io\Channel;
use io\streams\InputStream;
use lang\{IllegalStateException, IllegalArgumentException};

/**
 * Lambda response streaming
 *
 * @test com.amazon.aws.lambda.unittest.StreamedTest
 */
class Streaming extends InvokeMode implements Stream {
  private $request= null;
  private $response= null;
  private $stream= null;

  /**
   * Starts streaming the response
   *
   * @return peer.http.HttpOutputStream
   */
  private function start() {
    $this->request->setHeader('Lambda-Runtime-Function-Response-Mode', 'streaming');
    $this->request->setHeader('Transfer-Encoding', 'chunked');
    return $this->api->stream($this->request);
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

    $this->stream ?? $this->stream= $this->start();
    $this->stream->write($bytes);
    $this->stream->flush();
  }

  /**
   * Ends this response stream
   *
   * @return void
   */
  public function end() {      
    if ($this->response) return; // Already ended

    $this->stream ?? $this->stream= $this->start();
    $this->response= $this->api->finish($this->stream);
    $this->response->closeStream();
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
      $this->request= $this->api->request("invocation/{$context->awsRequestId}/response");
      $lambda($event, $context, $this);
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
}