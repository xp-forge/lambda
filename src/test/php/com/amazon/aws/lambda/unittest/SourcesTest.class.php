<?php namespace com\amazon\aws\lambda\unittest;

use io\{File, Folder, Path};
use lang\Environment;
use test\{After, Assert, Test, Values};
use xp\lambda\Sources;

class SourcesTest {
  private $cleanup= [];

  /** Creates a directory given a layout */
  private function directory(array $layout): Path {
    $dir= new Folder(Environment::tempDir(), uniqid());
    $dir->create() && $this->cleanup[]= $dir;

    foreach ($layout as $name => $contents) {
      if (null === $contents) {
        $f= new Folder($dir, $name);
        $f->create();
      } else {
        $f= new File($dir, $name);
        $f->touch();
      }
    }

    return new Path($dir);
  }

  #[After]
  public function cleanup() {
    foreach ($this->cleanup as $folder) {
      $folder->unlink();
    }
  }

  #[Test]
  public function can_create() {
    new Sources(new Path('.'), []);
  }

  #[Test, Values([[['%s/test.txt']], [['%s/test.txt', '%s/test.txt', '%s/./test.txt', '%s/../%s/test.txt']]])]
  public function with_one_file($sources) {
    $dir= $this->directory(['test.txt' => 'Test']);

    Assert::equals(
      [new Path($dir, 'test.txt')],
      iterator_to_array(new Sources($dir, array_map(
        function($source) use($dir) { return sprintf($source, $dir, $dir->name()); },
        $sources
      )))
    );
  }

  #[Test, Values([[['%s/src']], [['%s/src', '%s/src', '%s/./src', '%s/../%s/src']]])]
  public function with_one_directory($sources) {
    $dir= $this->directory(['src' => null]);

    Assert::equals(
      [new Path($dir, 'src')],
      iterator_to_array(new Sources($dir, array_map(
        function($source) use($dir) { return sprintf($source, $dir, $dir->name()); },
        $sources
      )))
    );
  }

  #[Test, Values([[['%s/src', '%s/vendor', '%s/test.txt']], [['%s/src', '%s/vendor', '%s/test.txt', '%s/src', '%s/./vendor']]])]
  public function with_files_and_directories($sources) {
    $dir= $this->directory(['src' => null, 'vendor' => null, 'test.txt' => 'Test']);

    Assert::equals(
      [new Path($dir, 'src'), new Path($dir, 'vendor'), new Path($dir, 'test.txt')],
      iterator_to_array(new Sources($dir,  array_map(
        function($source) use($dir) { return sprintf($source, $dir, $dir->name()); },
        $sources
      )))
    );
  }
}