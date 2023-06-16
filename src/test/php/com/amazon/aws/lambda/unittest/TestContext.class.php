<?php namespace com\amazon\aws\lambda\unittest;

trait TestContext {
  private $headers= [
    'Lambda-Runtime-Aws-Request-Id'       => ['3e1afeb0-cde4-1d0e-c3c0-66b15046bb88'],
    'Lambda-Runtime-Invoked-Function-Arn' => ['arn:aws:lambda:us-east-1:1185465369:function:test'],
    'Lambda-Runtime-Trace-Id'             => ['Root=1-dc99d00f-c079a84d433534434534ef0d;Parent=91ed514f1e5c03b2;Sampled=1'],
    'Lambda-Runtime-Client-Context'       => null,
    'Lambda-Runtime-Cognito-Identity'     => null,
    'Lambda-Runtime-Deadline-Ms'          => null,
  ];
  private $environment= [
    'AWS_LAMBDA_FUNCTION_NAME'        => 'test',
    'AWS_LAMBDA_FUNCTION_VERSION'     => '$LATEST',
    'AWS_LAMBDA_FUNCTION_MEMORY_SIZE' => 1536,
    'AWS_LAMBDA_LOG_GROUP_NAME'       => '/aws/lambda/test',
    'AWS_LAMBDA_LOG_STREAM_NAME'      => '2021/08/27/[$LATEST]17c13f63daf4a2a178be63f5531f77cc',
    'AWS_REGION'                      => 'us-east-1',
  ];
}