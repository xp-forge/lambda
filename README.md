AWS Lambda for the XP Framework
========================================================================

[![Build status on GitHub](https://github.com/xp-forge/aws-lambda/workflows/Tests/badge.svg)](https://github.com/xp-forge/aws-lambda/actions)
[![XP Framework Module](https://raw.githubusercontent.com/xp-framework/web/master/static/xp-framework-badge.png)](https://github.com/xp-framework/core)
[![BSD Licence](https://raw.githubusercontent.com/xp-framework/web/master/static/licence-bsd.png)](https://github.com/xp-framework/core/blob/master/LICENCE.md)
[![Requires PHP 7.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-7_0plus.svg)](http://php.net/)
[![Supports PHP 8.0+](https://raw.githubusercontent.com/xp-framework/web/master/static/php-8_0plus.svg)](http://php.net/)
[![Latest Stable Version](https://poser.pugx.org/xp-forge/aws-lambda/version.png)](https://packagist.org/packages/xp-forge/aws-lambda)

Serverless infrastructure.

Example
-------

```php
use com\amazon\aws\lambda\Handler;

class Greet extends Handler {

  /** @return com.amazon.aws.lambda.Lambda|callable */
  public function target() {
    return fn($event, $context) => sprintf(
      'Hello %s from %s @ %s',
      $event['name'],
      $context->functionName,
      $context->region
    );
  }
}
```

Development
-----------
To test your lambda locally, run the following:

```bash
$ xp lambda invoke Greet '{"name":"Test"}'
START RequestId: 9ff45cda-df9b-1b8c-c21b-5fe27c8f2d24 Version: $LATEST
END RequestId: 9ff45cda-df9b-1b8c-c21b-5fe27c8f2d24
REPORT RequestId: 9ff45cda-df9b-1b8c-c21b-5fe27c8f2d24  Init Duration: 922.19 ms...

"Hello Timm from test @ us-east-1"
```

*This functionality is achived by using the great [Docker image provided by LambCI](https://github.com/lambci/docker-lambda)!*

Setup
-----
The first step is to create and publish the runtime layer:

```bash
$ xp lambda runtime > runtime.zip
$ aws lambda publish-layer-version \
  --layer-name lambda-xp-runtime \
  --zip-file fileb://./runtime.zip \
  --region us-east-1
```

...and create a role:

```bash
$ cat > /tmp/trust-policy.json
{
  "Version": "2012-10-17",
  "Statement": [{
    "Effect": "Allow",
    "Principal": {"Service": "lambda.amazonaws.com"},
    "Action": "sts:AssumeRole"
  }]
}
$ aws iam create-role \
  --role-name InvokeLambda \
  --path "/service-role/" \
  --assume-role-policy-document file:///tmp/trust-policy.json
```

After ensuring your dependencies are up-to-date using composer, create the layer:

```bash
$ zip -r greet.zip src vendor
$ aws lambda create-function \
  --function-name greet \
  --handler Greet \
  --zip-file fileb://./greet.zip \
  --runtime provided \
  --role "arn:aws:iam::XXXXXXXXXXXX:role/service-role/InvokeLambda" \
  --region us-east-1 \
  --layers "arn:aws:lambda:us-east-1:XXXXXXXXXXXX:layer:lambda-xp-runtime:1"
```

Deployment
----------
After having initially created your lambda, you can update its code as follows:

```bash
$ zip -r greet.zip src vendor
$ aws lambda update-function-code \
  --function-name greet \
  --zip-file fileb://./greet.zip \
  --publish
```

Context
-------
The context object passed to the target lambda is defined as follows:

```php
public class com.amazon.aws.lambda.Context implements lang.Value {
  public string $awsRequestId
  public string $invokedFunctionArn
  public string $traceId
  public string $clientContext
  public string $cognitoIdentity
  public string $deadline
  public string $functionName
  public string $functionVersion
  public string $memorySize
  public string $region
  public int $payload

  public function __construct(array $headers, array $environment)

  public function remainingTime(?float $microtime): float
  public function toString(): string
  public function hashCode(): string
  public function compareTo(var $value): int
}
```

See also
--------
* [What is AWS Lambda?](https://docs.aws.amazon.com/lambda/latest/dg/welcome.html)
* [Lambda runtimes](https://docs.aws.amazon.com/lambda/latest/dg/lambda-runtimes.html)
* [AWS Lambda Custom Runtime for PHP: A Practical Example](https://aws.amazon.com/de/blogs/apn/aws-lambda-custom-runtime-for-php-a-practical-example/)