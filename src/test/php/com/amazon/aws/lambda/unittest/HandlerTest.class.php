<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{Environment, Handler};
use test\{Assert, Test};

class HandlerTest {

  #[Test]
  public function can_create() {
    new class(new Environment('.')) extends Handler {
      public function target() { /* TBI */ }
    };
  }

  #[Test]
  public function environment_accessor() {
    $env= new Environment('.');
    $fixture= new class($env) extends Handler {
      public function target() { /* TBI */ }
    };
    Assert::equals($env, $fixture->environment());
  }
}