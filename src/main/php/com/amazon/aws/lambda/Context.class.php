<?php namespace com\amazon\aws\lambda;

use io\streams\InputStream;
use lang\Throwable;
use lang\Value;
use text\json\{Json, StreamInput};

/**
 * Context object
 *
 * @test com.amazon.aws.lambda.unittest.ContextTest
 * @see  https://docs.aws.amazon.com/lambda/latest/dg/nodejs-context.html
 */
class Context implements Value {
  public $awsRequestId, $invokedFunctionArn, $traceId, $clientContext, $cognitoIdentity, $deadline;
  public $functionName, $functionVersion, $memoryLimitInMB, $logGroupName, $logStreamName, $region;
  public $payloadLength;

  /**
   * Creates a new invocation from given request headers. Extracts well-known
   * headers and make them accessible via members.
   *
   * @see  https://docs.aws.amazon.com/lambda/latest/dg/runtimes-api.html
   */
  public function __construct(array $headers, array $environment= []) {
    $this->awsRequestId= $headers['Lambda-Runtime-Aws-Request-Id'][0];
    $this->invokedFunctionArn= $headers['Lambda-Runtime-Invoked-Function-Arn'][0];
    $this->traceId= $headers['Lambda-Runtime-Trace-Id'][0] ?? null;
    $this->deadline= $headers['Lambda-Runtime-Deadline-Ms'][0] ?? null;
    $this->clientContext= $headers['Lambda-Runtime-Client-Context'][0] ?? null;
    $this->cognitoIdentity= $headers['Lambda-Runtime-Cognito-Identity'][0] ?? null;
    $this->functionName= $environment['AWS_LAMBDA_FUNCTION_NAME'] ?? 'test';
    $this->functionVersion= $environment['AWS_LAMBDA_FUNCTION_VERSION'] ?? '$LATEST';
    $this->memoryLimitInMB= $environment['AWS_LAMBDA_FUNCTION_MEMORY_SIZE'] ?? '1536';
    $this->region= $environment['AWS_REGION'] ?? 'us-east-1';
    $this->logGroupName= $environment['AWS_LAMBDA_LOG_GROUP_NAME'] ?? null;
    $this->logStreamName= $environment['AWS_LAMBDA_LOG_STREAM_NAME'] ?? null;
    $this->payloadLength= cast($headers['Content-Length'][0] ?? null, '?int');
  }

  /**
   * Returns remaining time in seconds, or NULL if no deadline was set.
   *
   * @param  ?float $now If omitted, uses `microtime()`
   * @return ?float
   */
  public function remainingTime($now= null) {
    return null === $this->deadline
      ? null
      : ($this->deadline / 1000) - ($now ?? microtime(true))
    ;
  }

  /** @return string */
  public function toString() {
    return sprintf(
      "%s(awsRequestId: %s, payloadLength: %s)@{\n".
      "  [invokedFunctionArn] %s\n".
      "  [traceId           ] %s\n".
      "  [deadline          ] %s\n".
      "  [functionName      ] %s\n".
      "  [functionVersion   ] %s\n".
      "  [memoryLimitInMB   ] %s\n".
      "  [logGroupName      ] %s\n".
      "  [logStreamName     ] %s\n".
      "  [region            ] %s\n".
      "  [clientContext     ] %s\n".
      "  [cognitoIdentity   ] %s\n".
      "}\n",
      nameof($this),
      $this->awsRequestId,
      null === $this->payloadLength ? '(unknown)' : $this->payloadLength.' bytes',
      $this->invokedFunctionArn,
      $this->traceId ?? '(null)',
      $this->deadline ?? '(null)',
      $this->functionName,
      $this->functionVersion,
      $this->memoryLimitInMB,
      $this->logGroupName ?? '(null)',
      $this->logStreamName ?? '(null)',
      $this->region,
      $this->clientContext ?? '(null)',
      $this->cognitoIdentity ?? '(null)'
    );
  }

  /** @return string */
  public function hashCode() { return spl_object_hash($this); }

  /**
   * Comparison
   *
   * @param  var $value
   * @return int
   */
  public function compareTo($value) {
    return $this <=> $value;
  }
}