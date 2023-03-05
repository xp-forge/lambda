<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\{Context, Environment, Handler, Lambda};
use lang\IllegalArgumentException;
use test\{Assert, Expect, Test};

class HandlerTest {
  private $headers= [
    'Lambda-Runtime-Aws-Request-Id'       => ['3e1afeb0-cde4-1d0e-c3c0-66b15046bb88'],
    'Lambda-Runtime-Invoked-Function-Arn' => ['arn:aws:lambda:us-east-1:1185465369:function:test'],
    'Lambda-Runtime-Trace-Id'             => ['Root=1-dc99d00f-c079a84d433534434534ef0d;Parent=91ed514f1e5c03b2;Sampled=1'],
  ];

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

  #[Test]
  public function return_function() {
    $fixture= new class(new Environment('.')) extends Handler {
      public function target() {
        return function($event, $context) { return 'Test'; };
      }
    };
    Assert::equals('Test', ($fixture->lambda())(null, new Context($this->headers, [])));
  }

  #[Test]
  public function return_lambda() {
    $fixture= new class(new Environment('.')) extends Handler {
      public function target() {
        return new class() implements Lambda {
          public function process($event, $context) { return 'Test'; }
        };
      }
    };
    Assert::equals('Test', ($fixture->lambda())(null, new Context($this->headers, [])));
  }

  #[Test, Expect(IllegalArgumentException::class)]
  public function cannot_return_null() {
    $fixture= new class(new Environment('.')) extends Handler {
      public function target() {
        return null;
      }
    };
    $fixture->lambda();
  }
}