<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\Context;
use unittest\{Assert, Test, Values};

class ContextTest {
  private $headers= [
    'Lambda-Runtime-Aws-Request-Id'       => ['3e1afeb0-cde4-1d0e-c3c0-66b15046bb88'],
    'Lambda-Runtime-Invoked-Function-Arn' => ['arn:aws:lambda:us-east-1:1185465369:function:test'],
    'Lambda-Runtime-Trace-Id'             => ['Root=1-dc99d00f-c079a84d433534434534ef0d;Parent=91ed514f1e5c03b2;Sampled=1'],
    'Lambda-Runtime-Client-Context'       => null,
    'Lambda-Runtime-Cognito-Identity'     => null,
    'Lambda-Runtime-Deadline-Ms'          => ['1629390182479'],
  ];
  private $environment= [
    'AWS_LAMBDA_FUNCTION_NAME'        => 'test',
    'AWS_LAMBDA_FUNCTION_VERSION'     => '$LATEST',
    'AWS_LAMBDA_FUNCTION_MEMORY_SIZE' => 1536,
    'AWS_LAMBDA_LOG_GROUP_NAME'       => '/aws/lambda/test',
    'AWS_LAMBDA_LOG_STREAM_NAME'      => '2021/08/27/[$LATEST]17c13f63daf4a2a178be63f5531f77cc',
    'AWS_REGION'                      => 'us-east-1',
  ];

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

  #[Test, Values('headers')]
  public function headers_mapped_to_fields($header, $field) {
    Assert::equals(
      $this->headers[$header][0] ?? null,
      (new Context($this->headers, $this->environment))->{$field}
    );
  }

  #[Test, Values('environment')]
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
  public function remainingTime_accessor() {
    Assert::equals(
      180.2,
      round((new Context($this->headers))->remainingTime(1629390002.279), 1)
    );
  }

  #[Test]
  public function string_representation_is_not_empty() {
    Assert::notEquals(
      '',
      (new Context($this->headers, $this->environment))->toString()
    );
  }
}