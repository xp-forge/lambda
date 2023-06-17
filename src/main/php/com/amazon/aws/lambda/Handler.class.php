<?php namespace com\amazon\aws\lambda;

use ReflectionFunction;
use lang\IllegalArgumentException;

/**
 * Base class for lambda handlers. Subclasses overwrite the `target` method,
 * perform initialization there and finally return the invokeable lambda.
 *
 * @test  com.amazon.aws.lambda.unittest.HandlerTest
 */
abstract class Handler {
  protected $environment;

  /** Creates a new handler with a given environment */
  public function __construct(Environment $environment) {
    $this->environment= $environment;
  }

  /** @return com.amazon.aws.lambda.Environment */
  public function environment() { return $this->environment; }

  /** @return com.amazon.aws.lambda.Lambda|com.amazon.aws.lambda.Streaming|callable */
  public abstract function target();
}
