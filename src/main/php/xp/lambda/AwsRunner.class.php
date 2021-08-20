<?php namespace xp\lambda;

use Throwable;
use com\amazon\aws\lambda\{Context, Environment};
use io\IOException;
use lang\{XPClass, XPException};
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
   * Returns the lambda handler class using the `_HANDLER` environment
   * variable.
   *
   * @return lang.XPClass
   */
  private static function handler() {
    return XPClass::forName($_ENV['_HANDLER']);
  }

  /**
   * Returns a lambda API endpoint using the `AWS_LAMBDA_RUNTIME_API`
   * environment variable.
   *
   * @see    https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html
   * @param  string $path
   * @return peer.http.HttpConnection
   */
  private static function endpoint($path) {
    $c= new HttpConnection("http://{$_ENV['AWS_LAMBDA_RUNTIME_API']}/2018-06-01/runtime/{$path}");

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

    // Initialization
    try {
      $lambda= self::handler()->newInstance(new Environment($_ENV['LAMBDA_TASK_ROOT'], Console::$out))->lambda();
    } catch (Throwable $t) {
      self::endpoint('init/error')->post(
        new RequestData(self::error($t)),
        ['Content-Type' => 'application/json']
      );
      return 1;
    }

    // Process events using the lambda runtime interface
    do {
      try {
        $r= self::endpoint('invocation/next')->get();
      } catch (IOException $e) {
        Console::$err->writeLine($e);
        break;
      }

      $context= new Context($r->headers());
      try {
        $type= 'response';
        $response= self::value($lambda($context->payload ? self::read($r->in()) : null, $context));
      } catch (Throwable $t) {
        $type= 'error';
        $response= self::error($t);
      }

      self::endpoint("invocation/{$context->awsRequestId}/{$type}")->post(
        new RequestData($response),
        ['Content-Type' => 'application/json']
      );
    } while (true);

    return 0;
  }
}