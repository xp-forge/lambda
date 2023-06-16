<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\RuntimeApi;
use lang\{IllegalArgumentException, IllegalStateException};
use test\{Assert, Before, Test};

class ExceptionTest {
  private $runtime;

  #[Before]
  public function runtime() {
    $this->runtime= new RuntimeApi('test');
  }

  #[Test]
  public function includes_errorMessage() {
    Assert::equals(
      'Test',
      $this->runtime->error(new IllegalArgumentException('Test'))['errorMessage']
    );
  }

  #[Test]
  public function includes_errorType() {
    Assert::equals(
      'lang.IllegalArgumentException',
      $this->runtime->error(new IllegalArgumentException('Test'))['errorType']
    );
  }

  #[Test]
  public function includes_stackTrace() {
    Assert::true(in_array(
      'Exception lang.IllegalArgumentException (Test)',
      $this->runtime->error(new IllegalArgumentException('Test'))['stackTrace']
    ));
  }

  #[Test]
  public function includes_cause() {
    Assert::true(in_array(
      'Exception lang.IllegalStateException (Cause)',
      $this->runtime->error(new IllegalArgumentException('Test', new IllegalStateException('Cause')))['stackTrace']
    ));
  }
}