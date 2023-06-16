<?php namespace com\amazon\aws\lambda;

use lang\Throwable;

/* @see  https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html#runtimes-custom-response-streaming */
abstract class InvokeMode {

  /**
   * Marshals an exception according to the AWS specification.
   *
   * @param  Throwable $e
   * @return [:var]
   */
  public static function error($e) {
    $error= ['errorMessage' => $e->getMessage(), 'errorType' => nameof($e), 'stackTrace' => []];

    $t= Throwable::wrap($e);
    do {
      $error['stackTrace'][]= $t->compoundMessage();
      foreach ($t->getStackTrace() as $e) {
        $error['stackTrace'][]= sprintf(
          '%s::%s(...) (line %d of %s)%s',
          strtr($e->class, '\\', '.') ?: '<main>',
          $e->method,
          $e->line,
          $e->file ? basename($e->file) : '',
          $e->message ? ' - '.$e->message : ''
        );
      }
    } while ($t= $t->getCause());

    return $error;
  }

  /**
   * Invokes the given lambda
   *
   * @param  callable $lambda
   * @param  var $event
   * @param  com.amazon.aws.lambda.Context $context
   * @return peer.HttpResponse
   */
  public abstract function invoke($lambda, $event, $context);

}