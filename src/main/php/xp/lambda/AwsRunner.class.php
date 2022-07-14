<?php namespace xp\lambda;

use Throwable;
use com\amazon\aws\lambda\{Context, Environment};
use io\IOException;
use lang\{XPClass, XPException, Environment as System};
use peer\http\{HttpConnection, RequestData};
use text\json\{Json, StreamInput};
use util\cmd\Console;

/**
 * Custom AWS Lambda runtimes
 *
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/runtimes-custom.html
 */
class AwsRunner {

  /**
   * Returns the lambda handler instance using the `_HANDLER` and
   * `LAMBDA_TASK_ROOT` environment variables.
   *
   * @param  [:string] $environment
   * @param  io.streams.StringWriter $writer
   * @return com.amazon.aws.lambda.Handler
   */
  private static function handler($environment, $writer) {
    return XPClass::forName($environment['_HANDLER'])->newInstance(new Environment(
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
  private static function endpoint($environment, $path) {
    $c= new HttpConnection("http://{$environment['AWS_LAMBDA_RUNTIME_API']}/2018-06-01/runtime/{$path}");

    // Use a 15 minute timeout, this is the maximum lambda runtime, see
    // https://docs.aws.amazon.com/lambda/latest/dg/gettingstarted-limits.html
    $c->setTimeout(900);
    return $c;
  }

  /**
   * Marshals an error according to the AWS specification.
   *
   * @param  Throwable $e
   * @return [:var]
   */
  public static function error($e) {
    $error= ['errorMessage' => $e->getMessage(), 'errorType' => nameof($e), 'stackTrace' => []];

    $t= XPException::wrap($e);
    do {
      $error['stackTrace'][]= $t->compoundMessage();
      foreach ($t->getStackTrace() as $e) {
        $error['stackTrace'][]= sprintf(
          '%s::%s(...) (line %d of %s)%s',
          strtr($e->class, '\\', '.') ?: '<main>',
          $e->method,
          $e->line,
          basename($e->file),
          $e->message ? ' - '.$e->message : ''
        );
      }
    } while ($t= $t->getCause());

    return $error;
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
    } catch (Throwable $t) {
      self::endpoint($environment, 'init/error')->post(
        new RequestData(Json::of(self::error($t))),
        ['Content-Type' => 'application/json']
      );
      return 1;
    }

    // Process events using the lambda runtime interface
    do {
      try {
        $r= self::endpoint($environment, 'invocation/next')->get();
      } catch (IOException $e) {
        Console::$err->writeLine($e);
        break;
      }

      $context= new Context($r->headers(), $environment);
      try {
        $event= 0 === $context->payloadLength ? null : Json::read(new StreamInput($r->in()));
        $type= 'response';
        $response= $lambda($event, $context);
      } catch (Throwable $t) {
        $type= 'error';
        $response= self::error($t);
      }

      self::endpoint($environment, "invocation/{$context->awsRequestId}/{$type}")->post(
        new RequestData(Json::of($response)),
        ['Content-Type' => 'application/json']
      );
    } while (true);

    return 0;
  }
}