<?php namespace xp\lambda;

use Throwable;
use com\amazon\aws\lambda\{Context, Environment, Handler, InvokeMode, RuntimeApi};
use io\IOException;
use lang\{XPClass, XPException, IllegalArgumentException, Environment as System};
use peer\http\{HttpConnection, RequestData};
use text\json\{Json, StreamInput};
use util\cmd\Console;

/**
 * Custom AWS Lambda runtimes
 *
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html
 * @test com.amazon.aws.lambda.unittest.AwsRunnerTest
 */
class AwsRunner {

  /**
   * Returns the lambda handler instance using the `_HANDLER` and
   * `LAMBDA_TASK_ROOT` environment variables.
   *
   * @param  [:string] $environment
   * @param  io.streams.StringWriter $writer
   * @return com.amazon.aws.lambda.Handler
   * @throws lang.ClassLoadingException
   * @throws lang.IllegalArgumentException
   */
  public static function handler($environment, $writer) {
    $impl= XPClass::forName($environment['_HANDLER'] ?? '');
    if (!$impl->isSubclassOf(Handler::class)) {
      throw new IllegalArgumentException('Class '.$impl->getName().' is not a lambda handler');
    }

    return $impl->newInstance(new Environment(
      $environment['LAMBDA_TASK_ROOT'] ?? '.',
      $writer,
      $environment
    ));
  }

  /**
   * Entry point method
   *
   * @param  string[] $args
   * @return int
   */
  public static function main($args) {
    $variables= System::variables();
    $api= new RuntimeApi($variables['AWS_LAMBDA_RUNTIME_API']);

    // Initialization
    try {
      $lambda= self::handler($variables, Console::$out)->invokeable($api);
    } catch (Throwable $t) {
      $api->report('init/error', $t);
      return 1;
    }

    // Process events using the lambda runtime interface
    do {
      try {
        $r= $api->receive('invocation/next');
        $context= new Context($r->headers(), $variables);
        $event= 0 === $context->payloadLength ? null : Json::read(new StreamInput($r->in()));

        $lambda->invoke($event, $context);
      } catch (Throwable $t) {
        Console::$err->writeLine($t);
        break;
      }
    } while (true);

    return 0;
  }
}