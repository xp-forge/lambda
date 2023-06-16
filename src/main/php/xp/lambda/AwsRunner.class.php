<?php namespace xp\lambda;

use Throwable, ReflectionFunction;
use com\amazon\aws\lambda\{Context, Environment, Handler, Streaming, Buffered, InvokeMode};
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
 * @test com.amazon.aws.lambda.unittest.ExceptionTest
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
   * Returns a lambda API endpoint using the `AWS_LAMBDA_RUNTIME_API`
   * environment variable.
   *
   * @see    https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html
   * @param  [:string] $environment
   * @param  string $path
   * @return peer.http.HttpConnection
   */
  public static function endpoint($environment, $path) {
    $c= new HttpConnection("http://{$environment['AWS_LAMBDA_RUNTIME_API']}/2018-06-01/runtime/{$path}");

    // Use a 15 minute timeout, this is the maximum lambda runtime, see
    // https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-limits.html
    $c->setTimeout(900);
    return $c;
  }

  /**
   * Entry point method
   *
   * @param  string[] $args
   * @return int
   */
  public static function main($args) {
    $environment= System::variables();

    // Initialization
    try {
      $lambda= self::handler($environment, Console::$out)->lambda();
      $stream= (new ReflectionFunction($lambda))->getNumberOfParameters() >= 3;
    } catch (Throwable $t) {
      self::endpoint($environment, 'init/error')->post(
        new RequestData(Json::of(InvokeMode::error($t))),
        ['Content-Type' => 'application/json']
      );
      return 1;
    }

    // Process events using the lambda runtime interface
    do {
      try {
        $r= self::endpoint($environment, 'invocation/next')->get();
        $context= new Context($r->headers(), $environment);
        $event= 0 === $context->payloadLength ? null : Json::read(new StreamInput($r->in()));

        $endpoint= self::endpoint($environment, "invocation/{$context->awsRequestId}");
        $invocation= $stream ? new Streaming($endpoint) : new Buffered($endpoint);
        $invocation->invoke($lambda, $event, $context);
      } catch (Throwable $t) {
        Console::$err->writeLine($e);
        break;
      }
    } while (true);

    return 0;
  }
}