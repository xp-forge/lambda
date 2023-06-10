<?php namespace xp\lambda;

use com\amazon\aws\lambda\{Environment, Context};
use lang\{XPClass, Throwable};
use util\UUID;
use util\cmd\Console;

/**
 * Run lambdas locally
 *
 * @see https://docs.aws.amazon.com/de_de/lambda/latest/dg/runtimes-api.html
 */
class RunLambda {
  const TRACE_ID= 'Root=1-5bef4de7-ad49b0e87f6ef6c87fc2e700;Parent=9a9197af755a6419;Sampled=1';

  private $impl, $length, $event;

  /**
   * Creates a new `run` subcommand
   *
   * @param  string $handler
   * @param  ?string $event
   * @throws lang.ClassLoadingException
   */
  public function __construct($handler, $event= null) {
    $this->impl= XPClass::forName($handler);
    if (null === $event) {
      $this->length= 0;
      $this->event= null;
    } else {
      $this->length= strlen($event);
      $this->event= json_decode($event, true);
    }    
  }

  /** Runs this command */
  public function run(): int {
    $name= $this->impl->getSimpleName();
    $region= getenv('AWS_REGION') ?: 'test-local-1';
    $context= new Context(
      [
        'Lambda-Runtime-Aws-Request-Id'       => [UUID::randomUUID()->hashCode()],
        'Lambda-Runtime-Invoked-Function-Arn' => ["arn:aws:lambda:{$region}:123456789012:function:{$name}"],
        'Lambda-Runtime-Trace-Id'             => [self::TRACE_ID],
        'Lambda-Runtime-Deadline-Ms'          => [(time() + 900) * 1000],
        'Content-Length'                      => [$this->length],
      ],
      $_ENV + ['AWS_LAMBDA_FUNCTION_NAME' => $name, 'AWS_REGION' => $region]
    );
    
    try {
      $lambda= $this->impl->newInstance(new Environment(getcwd(), Console::$out, []))->target();
      $result= $lambda instanceof Lambda ? $lambda->process($this->event, $context) : $lambda($this->event, $context);
      Console::$out->writeLine($result);
      return 0;
    } catch (Throwable $e) {
      Console::$err->writeLine($e);
      return 1;
    }
  }
}