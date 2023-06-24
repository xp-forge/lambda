<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\Handler;
use io\streams\{StringWriter, MemoryOutputStream};
use lang\{ClassLoader, IllegalArgumentException, ClassNotFoundException};
use test\{Assert, Before, Expect, Test};
use xp\lambda\AwsRunner;

class AwsRunnerTest {
  private $impl;

  #[Before]
  public function impl() {
    $this->impl= ClassLoader::defineClass('AwsRunnerTest_Handler', Handler::class, [], [
      'target' => function() {
        return function($event, $context) { /* NOOP */ };
      }
    ]);
    $this->writer= new StringWriter(new MemoryOutputStream());
  }

  #[Test]
  public function handler_determined_from_environment() {
    $handler= AwsRunner::handler(['_HANDLER' => $this->impl->getName()], $this->writer);
    Assert::instance($this->impl, $handler);
  }

  #[Test, Expect(class: IllegalArgumentException::class, message: 'Class util.Date is not a lambda handler')]
  public function handler_must_extend_base_class() {
    AwsRunner::handler(['_HANDLER' => 'util.Date'], $this->writer);
  }

  #[Test, Expect(class: ClassNotFoundException::class, message: 'Class "" could not be found')]
  public function without_handler() {
    AwsRunner::handler([], $this->writer);
  }
}