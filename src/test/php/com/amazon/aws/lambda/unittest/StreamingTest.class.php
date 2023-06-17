<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\Context;
use io\streams\MemoryInputStream;
use lang\IllegalStateException;
use test\{Assert, Expect, Test};

class StreamingTest extends RuntimeTest {
  use TestContext;

  /**
   * Invokes a lambda and returns the response
   * 
   * @param  function(var, com.amazon.aws.lambda.Context, com.amazon.aws.lambda.Streaming): void
   * @return string
   */
  private function invoke($lambda) {
    return $this->runtime
      ->streaming()
      ->invoke($lambda, null, new Context($this->headers, $this->environment))
      ->readData()
    ;
  }

  #[Test]
  public function can_create() {
    $this->runtime->streaming();
  }

  #[Test]
  public function noop() {
    $response= $this->invoke(function($event, $context, $stream) {
      // NOOP
    });

    Assert::equals(
      "POST /2018-06-01/runtime/invocation/3e1afeb0-cde4-1d0e-c3c0-66b15046bb88/response HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Lambda-Runtime-Function-Response-Mode: streaming\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n",
      $response
    );
  }

  #[Test]
  public function reports_exceptions_before_streaming_via_error() {
    $response= $this->invoke(function($event, $context, $stream) {
      throw new IllegalStateException('Test');
    });

    $headersAt= strpos($response, "\r\n\r\n") + 4;
    Assert::equals(
      "POST /2018-06-01/runtime/invocation/3e1afeb0-cde4-1d0e-c3c0-66b15046bb88/error HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: application/json\r\n".
      "Content-Length: ".(strlen($response) - $headersAt)."\r\n".
      "\r\n",
      substr($response, 0, $headersAt)
    );
  }

  #[Test]
  public function write_event_stream() {
    $response= $this->invoke(function($event, $context, $stream) {
      $stream->use('text/event-stream');
      $stream->write("data: One\n\n");
      $stream->write("data: Two\n\n");
    });

    Assert::equals(
      "POST /2018-06-01/runtime/invocation/3e1afeb0-cde4-1d0e-c3c0-66b15046bb88/response HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: text/event-stream\r\n".
      "Lambda-Runtime-Function-Response-Mode: streaming\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n".
      "data: One\n\ndata: Two\n\n",
      $response
    );
  }

  #[Test]
  public function transmit() {
    $response= $this->invoke(function($event, $context, $stream) {
      $stream->transmit(new MemoryInputStream('{"test":true}'), 'application/json');
    });

    Assert::equals(
      "POST /2018-06-01/runtime/invocation/3e1afeb0-cde4-1d0e-c3c0-66b15046bb88/response HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: application/json\r\n".
      "Lambda-Runtime-Function-Response-Mode: streaming\r\n".
      "Transfer-Encoding: chunked\r\n".
      "\r\n".
      "{\"test\":true}",
      $response
    );
  }

  #[Test, Expect(IllegalStateException::class)]
  public function writing_after_end() {
    $this->invoke(function($event, $context, $stream) {
      $stream->end();
      $stream->write('Test');
    });
  }

  #[Test, Expect(IllegalStateException::class)]
  public function changing_mime_type_after_end() {
    $this->invoke(function($event, $context, $stream) {
      $stream->end();
      $stream->use('text/plain');
    });
  }
}