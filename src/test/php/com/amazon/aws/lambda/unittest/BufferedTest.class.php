<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{Context, Buffered, RuntimeApi};
use lang\IllegalStateException;
use test\{Assert, Test};

class BufferedTest {
  use TestContext;

  /**
   * Invokes a lambda and returns the response
   * 
   * @param  function(var, com.amazon.aws.lambda.Context, com.amazon.aws.lambda.Streaming): void
   * @return string
   */
  private function invoke($lambda) {
    $stream= new Buffered(new RuntimeApi(new TestConnection()));
    return $stream->invoke($lambda, null, new Context($this->headers, $this->environment))->readData();
  }

  #[Test]
  public function can_create() {
    new Buffered(new RuntimeApi(new TestConnection()));
  }

  #[Test]
  public function response() {
    $response= $this->invoke(function($event, $context) {
      return ['test' => true];
    });

    Assert::equals(
      "POST /2018-06-01/runtime/invocation/3e1afeb0-cde4-1d0e-c3c0-66b15046bb88/response HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: application/json\r\n".
      "Content-Length: 13\r\n".
      "\r\n".
      "{\"test\":true}",
      $response
    );
  }

  #[Test]
  public function error() {
    $response= $this->invoke(function($event, $context) {
      throw new IllegalStateException('Test');
    });

    Assert::equals(
      "POST /2018-06-01/runtime/invocation/3e1afeb0-cde4-1d0e-c3c0-66b15046bb88/error HTTP/1.1\r\n".
      "Connection: close\r\n".
      "Host: test\r\n".
      "Content-Type: application/json\r\n".
      "Content-Length: 712\r\n".
      "\r\n",
      substr($response, 0, strpos($response, "\r\n\r\n") + 4)
    );
  }
}