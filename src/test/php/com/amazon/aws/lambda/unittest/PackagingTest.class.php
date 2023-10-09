<?php namespace com\amazon\aws\lambda\unittest;

use io\archive\zip\{ZipFile, ZipIterator};
use io\streams\MemoryOutputStream;
use io\{File, Files, Folder, Path};
use lang\Environment;
use test\{After, Assert, Before, Test};
use util\cmd\Console;
use xp\lambda\{PackageLambda, Sources};

class PackagingTest {
  private $tempDir;

  #[Before]
  private function tempDir() {
    $this->tempDir= new Folder(Environment::tempDir(), uniqid());
    $this->tempDir->create();
  }

  #[After]
  private function cleanup(Folder $folder= null) {
    $folder ?? $folder= $this->tempDir;
    foreach ($folder->entries() as $entry) {
      switch ($m= lstat($entry)['mode'] & 0170000) {
        case PackageLambda::IS_LINK: unlink($entry); break;
        case PackageLambda::IS_FILE: $entry->asFile()->unlink(); break;
        case PackageLambda::IS_FOLDER: $this->cleanup($entry->asFolder()); break;
      }
    }
  }

  /** Creates files and directory from given definitions */
  private function create(array $definitions): Path {

    // Create sources from definitions
    foreach ($definitions as $name => $definition) {
      switch ($definition[0]) {
        case PackageLambda::IS_FILE:
          Files::write(new File($this->tempDir, $name), $definition[1]);
          break;

        case PackageLambda::IS_FOLDER:
          (new Folder($this->tempDir, $name))->create($definition[1]);
          break;

        case PackageLambda::IS_LINK:
          symlink($definition[1], new Path($this->tempDir, $name));
          break;
      }
    }

    return new Path($this->tempDir);
  }

  /** Creates package from given sources */
  private function package(Sources $sources): ZipIterator {

    // Run packaging command
    $target= new Path($this->tempDir, 'test.zip');
    $out= Console::$out->stream();
    Console::$out->redirect(new MemoryOutputStream());
    try {
      $cmd= new PackageLambda($target, $sources);
      $cmd->run();
    } finally {
      Console::$out->redirect($out);
    }

    return ZipFile::open($target)->iterator();
  }

  #[Test]
  public function single_file() {
    $zip= $this->package(new Sources($this->create(['file.txt' => [PackageLambda::IS_FILE, 'Test']]), ['file.txt']));

    $file= $zip->next();
    Assert::equals('file.txt', $file->getName());
    Assert::equals(4, $file->getSize());
    Assert::false($zip->hasNext());
  }

  #[Test]
  public function single_directory() {
    $zip= $this->package(new Sources($this->create(['src' => [PackageLambda::IS_FOLDER, 0755]]), ['src']));

    $dir= $zip->next();
    Assert::equals('src/', $dir->getName());
    Assert::true($dir->isDirectory());
    Assert::false($zip->hasNext());
  }

  #[Test]
  public function file_inside_directory() {
    $path= $this->create([
      'src'          => [PackageLambda::IS_FOLDER, 0755],
      'src/file.txt' => [PackageLambda::IS_FILE, 'Test']
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

  #[Test]
  public function link_inside_directory() {
    $path= $this->create([
      'core/'                => [PackageLambda::IS_FOLDER, 0755],
      'core/composer.json'   => [PackageLambda::IS_FILE, '{"require":{"php":">=7.0"}}'],
      'project'              => [PackageLambda::IS_FOLDER, 0755],
      'project/src'          => [PackageLambda::IS_FOLDER, 0755],
      'project/src/file.txt' => [PackageLambda::IS_FILE, 'Test'],
      'project/lib'          => [PackageLambda::IS_FOLDER, 0755],
      'project/lib/core'     => [PackageLambda::IS_LINK, '../../core'],
    ]);
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
}