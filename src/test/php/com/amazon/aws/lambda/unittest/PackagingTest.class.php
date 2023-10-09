<?php namespace com\amazon\aws\lambda\unittest;

use io\archive\zip\{ZipFile, ZipIterator};
use io\streams\MemoryOutputStream;
use io\{File, Files, Folder, Path};
use lang\Environment;
use test\verify\Runtime;
use test\{After, Assert, Test, Values};
use util\cmd\Console;
use xp\lambda\{PackageLambda, Sources};

class PackagingTest {
  private $cleanup= [];

  /** Creates a new temporary folder */
  private function tempDir(): Folder {
    $this->cleanup[]= $f= new Folder(Environment::tempDir(), uniqid());
    $f->create();
    return $f;
  }

  /** Creates files and directory from given definitions */
  private function create(array $definitions, Folder $folder= null): Path {
    $folder ?? $folder= $this->tempDir();

    // Create sources from definitions
    foreach ($definitions as $name => $definition) {
      switch ($definition[0]) {
        case Sources::IS_FILE:
          Files::write(new File($folder, $name), $definition[1]);
          break;

        case Sources::IS_FOLDER:
          (new Folder($folder, $name))->create($definition[1]);
          break;

        case Sources::IS_LINK:
          symlink($definition[1], new Path($folder, $name));
          break;
      }
    }

    return new Path($folder);
  }

  /** Creates package from given sources */
  private function package(Sources $sources): ZipIterator {

    // Run packaging command
    $target= new Path($this->tempDir(), 'test.zip');
    $out= Console::$out->stream();
    Console::$out->redirect(new MemoryOutputStream());
    try {
      $cmd= new PackageLambda($target, $sources);
      $cmd->run();
    } finally {
      Console::$out->redirect($out);
    }

    // Remember to close the archive
    return ZipFile::open($target)->iterator();
  }

  #[After]
  private function cleanup() {
    foreach ($this->cleanup as $folder) {
      $folder->unlink();
    }
  }

  #[Test]
  public function single_file() {
    $zip= $this->package(new Sources($this->create(['file.txt' => [Sources::IS_FILE, 'Test']]), ['file.txt']));

    $file= $zip->next();
    Assert::equals('file.txt', $file->getName());
    Assert::equals(4, $file->getSize());
    Assert::false($zip->hasNext());
  }

  #[Test]
  public function single_directory() {
    $zip= $this->package(new Sources($this->create(['src' => [Sources::IS_FOLDER, 0755]]), ['src']));

    $dir= $zip->next();
    Assert::equals('src/', $dir->getName());
    Assert::true($dir->isDirectory());
    Assert::false($zip->hasNext());
  }

  #[Test]
  public function file_inside_directory() {
    $path= $this->create([
      'src'          => [Sources::IS_FOLDER, 0755],
      'src/file.txt' => [Sources::IS_FILE, 'Test']
    ]);
    $zip= $this->package(new Sources($path, ['src']));

    $dir= $zip->next();
    Assert::equals('src/', $dir->getName());
    Assert::true($dir->isDirectory());

    $file= $zip->next();
    Assert::equals('src/file.txt', $file->getName());
    Assert::equals(4, $file->getSize());

    Assert::false($zip->hasNext());
  }

  #[Test, Values(['src/test', 'src/it'])]
  public function test_sources_ignored($test) {
    $path= $this->create([
      'src'          => [Sources::IS_FOLDER, 0755],
      $test          => [Sources::IS_FOLDER, 0755],
      $test.'/t.sh'  => [Sources::IS_FILE, '#!/bin/sh ...'],
      'src/file.txt' => [Sources::IS_FILE, 'Test']
    ]);
    $zip= $this->package(new Sources($path, ['src']));

    $dir= $zip->next();
    Assert::equals('src/', $dir->getName());
    Assert::true($dir->isDirectory());

    $file= $zip->next();
    Assert::equals('src/file.txt', $file->getName());
    Assert::equals(4, $file->getSize());

    Assert::false($zip->hasNext());
  }

  #[Test, Runtime(os: 'Linux'), Values(['../../core', '%s/core'])]
  public function link_inside_directory($target) {
    $tempDir= $this->tempDir();

    $link= sprintf($target, rtrim($tempDir->getURI(), DIRECTORY_SEPARATOR));
    $path= $this->create([
      'core/'                => [Sources::IS_FOLDER, 0755],
      'core/composer.json'   => [Sources::IS_FILE, '{"require":{"php":">=7.0"}}'],
      'project'              => [Sources::IS_FOLDER, 0755],
      'project/src'          => [Sources::IS_FOLDER, 0755],
      'project/src/file.txt' => [Sources::IS_FILE, 'Test'],
      'project/lib'          => [Sources::IS_FOLDER, 0755],
      'project/lib/core'     => [Sources::IS_LINK, $link],
    ], $tempDir);
    $zip= $this->package(new Sources(new Path($path, 'project'), ['src', 'lib']));

    $dir= $zip->next();
    Assert::equals('src/', $dir->getName());
    Assert::true($dir->isDirectory());

    $file= $zip->next();
    Assert::equals('src/file.txt', $file->getName());
    Assert::equals(4, $file->getSize());

    $lib= $zip->next();
    Assert::equals('lib/', $lib->getName());
    Assert::true($lib->isDirectory());

    $core= $zip->next();
    Assert::equals('lib/core/', $core->getName());
    Assert::true($core->isDirectory());

    $composer= $zip->next();
    Assert::equals('lib/core/composer.json', $composer->getName());
    Assert::equals(27, $composer->getSize());

    Assert::false($zip->hasNext());
  }

  #[Test, Runtime(os: 'Linux'), Values(['../../libs/inc.pth', '%s/libs/inc.pth'])]
  public function link_to_file($target) {
    $tempDir= $this->tempDir();

    $link= sprintf($target, rtrim($tempDir->getURI(), DIRECTORY_SEPARATOR));
    $path= $this->create([
      'libs/'                => [Sources::IS_FOLDER, 0755],
      'libs/inc.pth'         => [Sources::IS_FILE, 'src/main/php'],
      'project'              => [Sources::IS_FOLDER, 0755],
      'project/src'          => [Sources::IS_FOLDER, 0755],
      'project/src/file.txt' => [Sources::IS_FILE, 'Test'],
      'project/lib'          => [Sources::IS_FOLDER, 0755],
      'project/lib/inc.pth'  => [Sources::IS_LINK, $link],
    ], $tempDir);
    $zip= $this->package(new Sources(new Path($path, 'project'), ['src', 'lib']));

    $dir= $zip->next();
    Assert::equals('src/', $dir->getName());
    Assert::true($dir->isDirectory());

    $file= $zip->next();
    Assert::equals('src/file.txt', $file->getName());
    Assert::equals(4, $file->getSize());

    $lib= $zip->next();
    Assert::equals('lib/', $lib->getName());
    Assert::true($lib->isDirectory());

    $path= $zip->next();
    Assert::equals('lib/inc.pth', $path->getName());
    Assert::equals(12, $path->getSize());

    Assert::false($zip->hasNext());
  }
}