<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\Environment;
use io\streams\{StringWriter, MemoryOutputStream};
use io\{Path, File, Files};
use lang\ElementNotFoundException;
use lang\Environment as System;
use unittest\{Assert, Test};
use util\Properties;

class EnvironmentTest {

  #[Test]
  public function can_create() {
    new Environment('.');
  }

  #[Test]
  public function path() {
    Assert::equals(new Path('./etc'), (new Environment('.'))->path('etc'));
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

  #[Test, Expect(class: ElementNotFoundException::class, withMessage: '/Cannot find properties "test"/')]
  public function non_existant_properties() {
    (new Environment('.'))->properties('test');
  }
}
