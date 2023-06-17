<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{Context, Buffered, RuntimeApi};
use lang\IllegalStateException;
use test\{Assert, Before, Test};

class BufferedTest {
  use TestContext;

  private $runtime;

  /**
   * Invokes a lambda and returns the response
   * 
   * @param  function(var, com.amazon.aws.lambda.Context, com.amazon.aws.lambda.Streaming): void
   * @return string
   */
  private function invoke($lambda) {
    return $this->runtime
      ->buffered()
      ->invoke($lambda, null, new Context($this->headers, $this->environment))
      ->readData()
    ;
  }

  #[Before]
  public function runtime() {
    $this->runtime= new RuntimeApi(new TestConnection());
  }

  #[Test]
  public function can_create() {
    $this->runtime->buffered();
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
      "Content-Length: 836\r\n".
      "\r\n",
      substr($response, 0, strpos($response, "\r\n\r\n") + 4)
    );
  }
}