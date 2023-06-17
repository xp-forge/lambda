<?php namespace xp\lambda;

use com\amazon\aws\lambda\{Context, Environment, Handler, InvokeMode, Stream, RuntimeApi};
use lang\{XPClass, Throwable, IllegalArgumentException};
use util\UUID;
use util\cmd\Console;

/**
 * Run lambdas locally
 *
 * @see https://docs.aws.amazon.com/lambda/latest/dg/lambda-runtime-environment.html
 * @see https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html
 */
class RunLambda {
  const TRACE= 'Root=1-5bef4de7-ad49b0e87f6ef6c87fc2e700;Parent=9a9197af755a6419;Sampled=1';
  const REGION= 'test-local-1';

  private $impl, $events;

  /**
   * Creates a new `run` subcommand
   *
   * @param  string $handler
   * @param  string... $events
   * @throws lang.ClassLoadingException
   * @throws lang.IllegalArgumentException
   */
  public function __construct($handler= 'Handler', ... $events) {
    $this->impl= XPClass::forName($handler);
    if (!$this->impl->isSubclassOf(Handler::class)) {
      throw new IllegalArgumentException('Class '.$handler.' is not a lambda handler');
    }

    $this->events= $events ?: ['{}'];
  }

  /** Runs this command */
  public function run(): int {
    $name= $this->impl->getSimpleName();
    $region= getenv('AWS_REGION') ?: self::REGION;
    $functionArn= "arn:aws:lambda:{$region}:123456789012:function:{$name}";
    $deadlineMs= (time() + 900) * 1000;
    $variables= $_ENV + ['AWS_LAMBDA_FUNCTION_NAME' => $name, 'AWS_REGION' => $region, 'AWS_LOCAL' => true];
    $environment= new Environment(getcwd(), Console::$out, $variables);

    try {
      $lambda= $this->impl->newInstance($environment)->invokeable(new LocalRuntime(Console::$out));
    } catch (Throwable $e) {
      Console::$err->writeLine($e);
      return 127;
    }

    $status= 0;
    foreach ($this->events as $event) {
      $headers= [
        'Lambda-Runtime-Aws-Request-Id'       => [UUID::randomUUID()->hashCode()],
        'Lambda-Runtime-Invoked-Function-Arn' => [$functionArn],
        'Lambda-Runtime-Trace-Id'             => [self::TRACE],
        'Lambda-Runtime-Deadline-Ms'          => [$deadlineMs],
        'Content-Length'                      => [strlen($event)],
      ];

      try {
        $lambda->invoke(json_decode($event, true), new Context($headers, $variables));
      } catch (Throwable $e) {
        Console::$err->writeLine($e);
        $status= 1;
      }
    }
    return $status;
  }
}