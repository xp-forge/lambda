<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\Environment;
use com\amazon\aws\{Credentials, ServiceEndpoint};
use io\streams\{MemoryOutputStream, StringWriter};
use io\{File, Files, Path};
use lang\ElementNotFoundException;
use lang\Environment as System;
use test\{Assert, Expect, Test};
use util\Properties;

class EnvironmentTest {

  #[Test]
  public function can_create() {
    new Environment('.');
  }

  #[Test]
  public function taskroot() {
    Assert::equals(new Path('.'), (new Environment('.'))->taskroot());
  }

  #[Test]
  public function path() {
    Assert::equals(new Path('./etc'), (new Environment('.'))->path('etc'));
  }

  #[Test]
  public function tempDir_prefers_using_environment() {
    Assert::equals(new Path('tmp'), (new Environment('.', null, ['TEMP' => 'tmp']))->tempDir());
  }

  #[Test]
  public function tempDir_falls_back_to_sys_get_temp_dir() {
    Assert::equals(new Path(sys_get_temp_dir()), (new Environment('.', null, []))->tempDir());
  }

  #[Test]
  public function variable() {
    Assert::equals('true', (new Environment('.', null, ['TEST' => 'true']))->variable('TEST'));
  }

  #[Test]
  public function non_existant_variable() {
    Assert::null((new Environment('.', null, []))->variable('TEST'));
  }

  #[Test]
  public function not_local_by_default() {
    Assert::false((new Environment('.', null, []))->local());
  }

  #[Test]
  public function local() {
    Assert::true((new Environment('.', null, ['AWS_LOCAL' => true]))->local());
  }

  #[Test]
  public function credentials() {
    $env= [
      'AWS_ACCESS_KEY_ID'     => 'KEY',
      'AWS_SECRET_ACCESS_KEY' => 'SECRET',
    ];

    Assert::equals(
      new Credentials('KEY', 'SECRET'),
      (new Environment('.', null, $env))->credentials()
    );
  }

  #[Test]
  public function credentials_with_session() {
    $env= [
      'AWS_ACCESS_KEY_ID'     => 'KEY',
      'AWS_SECRET_ACCESS_KEY' => 'SECRET',
      'AWS_SESSION_TOKEN'     => 'SESSION',
    ];

    Assert::equals(
      new Credentials('KEY', 'SECRET', 'SESSION'),
      (new Environment('.', null, $env))->credentials()
    );
  }

  #[Test]
  public function endpoint() {
    Assert::equals(
      'id.execute-api.us-east-1.amazonaws.com',
      (new Environment('.', null, ['AWS_ACCESS_KEY_ID' => '*', 'AWS_SECRET_ACCESS_KEY' => '*']))
        ->endpoint('execute-api')
        ->in('us-east-1')
        ->using('id')
        ->domain()
    );
  }

  #[Test]
  public function trace() {
    $stream= new MemoryOutputStream();

    $env= new Environment('.', new StringWriter($stream));
    $env->trace('Test');

    Assert::equals("Test\n", $stream->bytes());
  }

  #[Test]
  public function properties() {
    $temp= System::tempDir();
    $ini= new File($temp, 'test.ini');
    
    try {
      Files::write($ini, 'key=value');

      Assert::equals(
        ['key' => 'value'],
        (new Environment($temp))->properties('test')->readSection(null)
      );
    } finally {
      $ini->unlink();
    }
  }

  #[Test, Expect(class: ElementNotFoundException::class, message: '/Cannot find properties "test"/')]
  public function non_existant_properties() {
    (new Environment('.'))->properties('test');
  }
}