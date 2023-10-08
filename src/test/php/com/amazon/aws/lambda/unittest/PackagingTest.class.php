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
  private function cleanup() {
    $this->tempDir->unlink();
  }

  /** Creates files and directory from given definitions */
  private function create(array $definitions): Path {

    // Create sources from definitions
    foreach ($definitions as $name => $definition) {
      switch ($definition[0]) {
        case 'file':
          Files::write(new File($this->tempDir, $name), $definition[1]);
          break;

        case 'dir':
          (new Folder($this->tempDir, $name))->create($definition[1]);
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
    $zip= $this->package(new Sources($this->create(['file.txt' => ['file', 'Test']]), ['file.txt']));

    $file= $zip->next();
    Assert::equals('file.txt', $file->getName());
    Assert::equals(4, $file->getSize());
    Assert::false($zip->hasNext());
  }

  #[Test]
  public function single_directory() {
    $zip= $this->package(new Sources($this->create(['src' => ['dir', 0755]]), ['src']));

    $dir= $zip->next();
    Assert::equals('src'.DIRECTORY_SEPARATOR, $dir->getName());
    Assert::true($dir->isDirectory());
    Assert::false($zip->hasNext());
  }

  #[Test]
  public function file_inside_directory() {
    $path= $this->create([
      'src'          => ['dir', 0755],
      'src/file.txt' => ['file', 'Test']
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
}