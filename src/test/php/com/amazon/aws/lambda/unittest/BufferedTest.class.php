<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{Context, Lambda};
use lang\IllegalStateException;
use test\{Assert, Test, Values};

class BufferedTest extends RuntimeTest {
  use TestContext;

  /**
   * Invokes a lambda and returns the response
   * 
   * @param  function(var, com.amazon.aws.lambda.Context): void
   * @return string
   */
  private function invoke($lambda) {
    return $this->runtime
      ->buffered()
      ->invoke($lambda, null, new Context($this->headers, $this->environment))
      ->readData()
    ;
  }

  /**
   * Lambda implementations for `response` test.
   *
   * @return iterable
   */
  private function implementations() {
    yield [function($event, $context) {
      return ['test' => true];
    }];
    yield [new class() implements Lambda {
      public function process($event, $context) {
        return ['test' => true];
      }
    }];
  }

  #[Test]
  public function can_create() {
    $this->runtime->buffered();
  }

  #[Test, Values(from: 'implementations')]
  public function response($lambda) {
    $response= $this->invoke($this->runtime->invokeable($lambda)->callable);

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
}