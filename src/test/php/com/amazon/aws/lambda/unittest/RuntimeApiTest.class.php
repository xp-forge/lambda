<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{Context, RuntimeApi, Lambda, Stream, Streaming};
use lang\IllegalArgumentException;
use test\{Assert, Expect, Test};

class RuntimeApiTest {
  use TestContext;

  #[Test]
  public function can_create() {
    new RuntimeApi('test');
  }

  #[Test]
  public function default_version() {
    Assert::equals('2018-06-01', (new RuntimeApi('test'))->version);
  }

  #[Test]
  public function latest_version() {
    Assert::equals('latest', (new RuntimeApi('test', 'latest'))->version);
  }

  #[Test]
  public function endpoint_url() {
    $runtime= new RuntimeApi('localhost:9000');
    Assert::equals('http://localhost:9000/', $runtime->conn->getUrl()->getCanonicalURL());
  }

  #[Test]
  public function endpoint_timeout_is_15_minutes() {
    $runtime= new RuntimeApi('localhost:9000');
    Assert::equals(15 * 60, $runtime->conn->getTimeout());
  }

  #[Test]
  public function return_lambda_function() {
    $invokeable= (new RuntimeApi('localhost:9000'))->invokeable(function($event, $context) {
      return 'Test';
    });

    Assert::equals('Test', ($invokeable->callable)(null, new Context($this->headers, [])));
  }

  #[Test]
  public function return_lambda() {
    $invokeable= (new RuntimeApi('localhost:9000'))->invokeable(new class() implements Lambda {
      public function process($event, $context) { return 'Test'; }
    });

    Assert::equals('Test', ($invokeable->callable)(null, new Context($this->headers, [])));
  }

  #[Test]
  public function return_streaming_function() {
    $invokeable= (new RuntimeApi('localhost:9000'))->invokeable(function($event, $stream, $context) {
      $stream->write('Test');
    });

    $stream= new TestStream();
    ($invokeable->callable)(null, $stream, new Context($this->headers, []));
    Assert::equals('Test', $stream->written);
  }

  #[Test]
  public function return_streaming() {
    $invokeable= (new RuntimeApi('localhost:9000'))->invokeable(new class() implements Streaming {
      public function handle($event, $stream, $context) { $stream->write('Test'); }
    });

    $stream= new TestStream();
    ($invokeable->callable)(null, $stream, new Context($this->headers, []));
    Assert::equals('Test', $stream->written);
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function cannot_return_null() {
    (new RuntimeApi('localhost:9000'))->invokeable(null);
  }
}