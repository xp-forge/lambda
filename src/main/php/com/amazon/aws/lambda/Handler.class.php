<?php namespace com\amazon\aws\lambda;

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

  /** @return com.amazon.aws.lambda.Lambda|callable */
  public abstract function target();

  /** @return callable */
  public final function lambda() {
    $target= $this->target();
    if ($target instanceof Lambda) {
      return [$target, 'process'];
    } else if (is_callable($target)) {
      return $target;
    } else {
      throw new IllegalArgumentException('Expected either a callable or a Lambda instance, have '.typeof($target));
    }
  }
}
