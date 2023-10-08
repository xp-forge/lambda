<?php namespace xp\lambda;

use io\Path;
use io\archive\zip\{ZipFile, ZipArchiveWriter, ZipDirEntry, ZipFileEntry, Compression};
use io\streams\StreamTransfer;
use util\cmd\Console;

/** @test com.amazon.aws.lambda.unittest.PackagingTest */
class PackageLambda {
  const COMPRESSION_THRESHOLD= 24;

  // See https://www.php.net/manual/en/function.fileperms.php
  const IS_LINK= 0120000;
  const IS_FILE= 0100000;
  const IS_FOLDER= 0040000;

  private $target, $sources, $exclude, $compression;

  public function __construct(Path $target, Sources $sources, string $exclude= '#(^|/)(\..+|src/test|src/it)(/|$)#') {
    $this->target= $target;
    $this->sources= $sources;
    $this->exclude= $exclude;

    // Choose best compression algorithm based on available extensions.
    if (extension_loaded('zlib')) {
      $this->compression= Compression::$GZ;
    } else if (extension_loaded('bz2')) {
      $this->compression= Compression::$BZ;
    } else {
      $this->compression= Compression::$NONE;
    }
  }

  /** Adds ZIP file entries */
  private function add(ZipArchiveWriter $zip, Path $path, Path $base, $prefix= '') {
    if (preg_match($this->exclude, $path->toString('/'))) return;

    $stat= lstat($path);
    $relative= $prefix.$path->relativeTo($base);

    // Handle the following file types:
    // - Links: Resolve, then handle link targets
    // - Files: Add to ZIP
    // - Folders: Recursively add all subfolders and files therein
    if (self::IS_LINK === ($stat['mode'] & self::IS_LINK)) {
      $resolved= new Path(readlink($path));
      $base= $resolved->isFile() ? new Path(dirname($resolved)) : $resolved;
      yield from $this->add($zip, $resolved, $base, $relative.DIRECTORY_SEPARATOR);
    } else if (self::IS_FILE === ($stat['mode'] & self::IS_FILE)) {
      $file= $zip->add(new ZipFileEntry($relative));

      // See https://stackoverflow.com/questions/46716095/minimum-file-size-for-compression-algorithms
      if (filesize($path) > self::COMPRESSION_THRESHOLD) {
        $file->setCompression($this->compression, 9);
      }
      (new StreamTransfer($path->asFile()->in(), $file->out()))->transferAll();
      yield $file;
    } else if (self::IS_FOLDER === ($stat['mode'] & self::IS_FOLDER)) {
      yield $zip->add(new ZipDirEntry($relative));
      foreach ($path->asFolder()->entries() as $entry) {
        yield from $this->add($zip, $entry, $base, $prefix);
      }
    }
  }

  public function run(): int {
    Console::writeLine('[+] Creating ', $this->target, ' (compression: ', $this->compression, ')');
    $z= ZipFile::create($this->target->asFile()->out());

    $sources= iterator_to_array($this->sources);
    $total= sizeof($sources) + 1;
    foreach ($sources as $i => $source) {
      Console::writef("\e[34m => [%d/%d] ", $i + 1, $total);
      $entries= 0;
      foreach ($this->add($z, new Path($source), $this->sources->base) as $entry) {
        $entries++;
        Console::writef('%-60s %4d%s', substr($entry->getName(), -60), $entries, str_repeat("\x08", 65));
      }
      Console::writeLine("\e[0m");
    }

    $file= $z->add(new ZipFileEntry('class.pth'));
    $file->out()->write(preg_replace($this->exclude.'m', '?$1', file_get_contents('class.pth')));
    Console::writeLinef("\e[34m => [%1\$d/%1\$d] class.pth\e[0m", $total);

    $z->close();
    Console::writeLine();
    Console::writeLine('Wrote ', number_format(filesize($this->target)), ' bytes');
    return 0;
  }
}