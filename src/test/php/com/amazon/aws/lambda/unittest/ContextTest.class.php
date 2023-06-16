<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\Context;
use test\{Assert, Test, Values};

class ContextTest {
  use TestContext;

  /** @return iterable */
  private function headers() {
    yield ['Lambda-Runtime-Aws-Request-Id', 'awsRequestId'];
    yield ['Lambda-Runtime-Invoked-Function-Arn', 'invokedFunctionArn'];
    yield ['Lambda-Runtime-Trace-Id', 'traceId'];
    yield ['Lambda-Runtime-Client-Context', 'clientContext'];
    yield ['Lambda-Runtime-Cognito-Identity', 'cognitoIdentity'];
  }

  /** @return iterable */
  private function environment() {
    yield ['AWS_LAMBDA_FUNCTION_NAME', 'functionName'];
    yield ['AWS_LAMBDA_FUNCTION_VERSION', 'functionVersion'];
    yield ['AWS_LAMBDA_FUNCTION_MEMORY_SIZE', 'memoryLimitInMB'];
    yield ['AWS_LAMBDA_LOG_GROUP_NAME', 'logGroupName'];
    yield ['AWS_LAMBDA_LOG_STREAM_NAME', 'logStreamName'];
    yield ['AWS_REGION', 'region'];
  }

  #[Test, Values(from: 'headers')]
  public function headers_mapped_to_fields($header, $field) {
    Assert::equals(
      $this->headers[$header][0] ?? null,
      (new Context($this->headers, $this->environment))->{$field}
    );
  }

  #[Test, Values(from: 'environment')]
  public function environment_mapped_to_fields($variable, $field) {
    Assert::equals(
      $this->environment[$variable] ?? null,
      (new Context($this->headers, $this->environment))->{$field}
    );
  }

  #[Test]
  public function payloadLength_unknown() {
    Assert::equals(
      null,
      (new Context($this->headers))->payloadLength
    );
  }

  #[Test]
  public function payloadLength_set() {
    Assert::equals(
      6100,
      (new Context($this->headers + ['Content-Length' => ['6100']]))->payloadLength
    );
  }

  #[Test]
  public function remainingTime_null_when_deadline_missing() {
    Assert::null((new Context($this->headers))->remainingTime());
  }

  #[Test]
  public function remainingTime_accessor() {
    $context= new Context(['Lambda-Runtime-Deadline-Ms' => ['1629390182479']] + $this->headers);
    Assert::equals(180.2, round($context->remainingTime(1629390002.279), 1));
  }

  #[Test]
  public function string_representation_is_not_empty() {
    Assert::notEquals('', (new Context($this->headers, $this->environment))->toString());
  }

  #[Test]
  public function hashcode_is_not_empty() {
    Assert::notEquals('', (new Context($this->headers, $this->environment))->hashCode());
  }

  #[Test]
  public function equals_with_same_content() {
    Assert::equals(
      new Context($this->headers, $this->environment),
      new Context($this->headers, $this->environment)
    );
  }

  #[Test]
  public function does_not_equal_different_content() {
    Assert::notEquals(
      new Context($this->headers, $this->environment),
      new Context($this->headers + ['Content-Length' => ['6100']], $this->environment)
    );
  }
}