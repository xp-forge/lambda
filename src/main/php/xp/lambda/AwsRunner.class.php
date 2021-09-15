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
   * Reads a value from the given input stream
   *
   * @param  io.streams.InputStream
   * @return var
   */
  private static function read($in) {
    return Json::read(new StreamInput($in));
  }

  /**
   * Marshals a value 
   *
   * @param  var $value
   * @return string
   */
  private static function value($value) {
    return Json::of($value);
  }

  /**
   * Marshals an error according to the AWS specification.
   *
   * @param  Throwable $e
   * @return string
   */
  private static function error($e) {
    $error= ['errorMessage' => $e->getMessage(), 'errorType' => nameof($e), 'stackTrace' => []];

    $t= XPException::wrap($e);
    do {
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

    return Json::of($error);
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
        new RequestData(self::error($t)),
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
        $event= 0 === $context->payloadLength ? null : self::read($r->in());

        $type= 'response';
        $response= self::value($lambda($event, $context));
      } catch (Throwable $t) {
        $type= 'error';
        $response= self::error($t);
      }

      self::endpoint($environment, "invocation/{$context->awsRequestId}/{$type}")->post(
        new RequestData($response),
        ['Content-Type' => 'application/json']
      );
    } while (true);

    return 0;
  }
}