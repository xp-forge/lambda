<?php namespace xp\lambda;

use io\Path;
use io\archive\zip\{ZipFile, ZipDirEntry, ZipFileEntry, Compression};
use io\streams\StreamTransfer;
use util\cmd\Console;

class PackageLambda {
  const COMPRESSION_THRESHOLD = 24;

  private $target, $sources, $base, $exclude, $compression;

  public function __construct(Path $target, Path $base, array $sources, string $exclude= '#/(\..+|src/test|src/it)(/|$)#') {
    $this->target= $target;
    $this->base= $base->asRealpath();
    $this->sources= $sources;
    $this->exclude= $exclude;
    $this->compression= extension_loaded('zlib') ? Compression::$GZ : Compression::$NONE;
  }

  /** Returns ZIP file entries */
  private function entries(Path $path) {
    if (preg_match($this->exclude, $path->toString('/'))) return;

    $relative= trim(str_replace($this->base, '', $path), DIRECTORY_SEPARATOR);
    if ($path->isFile()) {
      yield function($z) use($relative, $path) {
        $file= $z->add(new ZipFileEntry($relative));

        // See https://stackoverflow.com/questions/46716095/minimum-file-size-for-compression-algorithms
        if (filesize($path) > self::COMPRESSION_THRESHOLD) {
          $file->setCompression($this->compression, 9);
        }
        (new StreamTransfer($path->asFile()->in(), $file->out()))->transferAll();
        return $file;
      };
    } else {
      yield function($z) use($relative) { return $z->add(new ZipDirEntry($relative)); };
      foreach ($path->asFolder()->entries() as $entry) {
        yield from $this->entries($entry);
      }
    }
  }

  public function run(): int {
    Console::writeLine('[+] Creating ', $this->target, ' (compression: ', $this->compression, ')');
    $z= ZipFile::create($this->target->asFile()->out());
    foreach ($this->sources as $i => $source) {
      Console::writef("\e[34m => [%d/%d] ", $i + 1, sizeof($this->sources) + 1);
      $entries= 0;
      foreach ($this->entries(new Path($source)) as $action) {
        $entry= $action($z);
        $entries++;
        Console::writef('%-60s %4d%s', substr($entry->getName(), -60), $entries, str_repeat("\x08", 65));
      }
      Console::writeLine("\e[0m");
    }

    $file= $z->add(new ZipFileEntry('class.pth'));
    $file->out()->write(preg_replace($this->exclude, '?$0', file_get_contents('class.pth')));
    Console::writeLinef("\e[34m => [%1\$d/%1\$d] class.pth\e[0m", sizeof($this->sources) + 1);

    $z->close();
    Console::writeLine();
    Console::writeLine('Wrote ', number_format(filesize($this->target)), ' bytes');
    return 0;
  }
}