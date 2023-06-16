<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\InvokeMode;
use lang\{IllegalArgumentException, IllegalStateException};
use test\{Assert, Test};

class ExceptionTest {

  #[Test]
  public function includes_errorMessage() {
    Assert::equals(
      'Test',
      InvokeMode::error(new IllegalArgumentException('Test'))['errorMessage']
    );
  }

  #[Test]
  public function includes_errorType() {
    Assert::equals(
      'lang.IllegalArgumentException',
      InvokeMode::error(new IllegalArgumentException('Test'))['errorType']
    );
  }

  #[Test]
  public function includes_stackTrace() {
    Assert::true(in_array(
      'Exception lang.IllegalArgumentException (Test)',
      InvokeMode::error(new IllegalArgumentException('Test'))['stackTrace']
    ));
  }

  #[Test]
  public function includes_cause() {
    Assert::true(in_array(
      'Exception lang.IllegalStateException (Cause)',
      InvokeMode::error(new IllegalArgumentException('Test', new IllegalStateException('Cause')))['stackTrace']
    ));
  }
}