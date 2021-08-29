<?php namespace com\amazon\aws\lambda\unittest;

use com\amazon\aws\lambda\Environment;
use io\streams\{StringWriter, MemoryOutputStream};
use io\{Path, File, Files};
use lang\ElementNotFoundException;
use lang\Environment as System;
use unittest\{Assert, After, Before, Test};
use util\Properties;

class EnvironmentTest {
  private $temp= [];

  #[Before]
  public function cleanTemp() {
    foreach (['TEMP', 'TMP', 'TMPDIR', 'TEMPDIR'] as $variable) {
      $this->temp[$variable]= $_ENV[$variable] ?? null;
      unset($_ENV[$variable]);
    }
  }

  #[After]
  public function restoreTemp() {
    $_ENV += $this->temp;
  }

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
    $_ENV['TEMP']= 'tmp';
    Assert::equals(new Path('tmp'), (new Environment('.'))->tempDir());
  }

  #[Test]
  public function tempDir_falls_back_to_sys_get_temp_dir() {
    unset($_ENV['TEMP']);
    Assert::equals(new Path(sys_get_temp_dir()), (new Environment('.'))->tempDir());
  }

  #[Test]
  public function variable() {
    $_ENV['TEST']= 'true';
    Assert::equals('true', (new Environment('.'))->variable('TEST'));
  }

  #[Test]
  public function non_existant_variable() {
    unset($_ENV['TEST']);
    Assert::null((new Environment('.'))->variable('TEST'));
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
