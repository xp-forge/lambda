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

  /** @return com.amazon.aws.lambda.Lambda|com.amazon.aws.lambda.XXX|callable */
  public abstract function target();

  /**
   * Returns an invokeable
   *
   * @param  com.amazon.aws.lambda.RuntimeApi
   * @return com.amazon.aws.lambda.Invokeable
   * @throws lang.IllegalArgumentException
   */
  public final function invokeable($api) {
    $target= $this->target();
    if ($target instanceof Lambda) {
      return new Invokeable([$target, 'process'], $api->buffered());
    } else if ($target instanceof XXX) {
      return new Invokeable([$target, 'yyy'], $api->streaming());
    } else if (is_callable($target)) {
      $n= (new ReflectionFunction($target))->getNumberOfParameters();
      return new Invokeable($target, $n < 3 ? $api->buffered() : $api->streaming());
    } else {
      throw new IllegalArgumentException('Expected callable|Lambda|XXX, have '.typeof($target));
    }
  }
}
