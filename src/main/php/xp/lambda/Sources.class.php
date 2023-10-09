<?php namespace xp\lambda;

use IteratorAggregate, Traversable;
use io\Path;

/** Returns a unique list of sources */
class Sources implements IteratorAggregate {

  // See https://www.php.net/manual/en/function.fileperms.php
  const IS_LINK= 0120000;
  const IS_FILE= 0100000;
  const IS_FOLDER= 0040000;

  public $base;
  private $sources;

  public function __construct(Path $base, array $sources) {
    $this->base= $base->asRealpath();
    $this->sources= $sources;
  }

  public function getIterator(): Traversable {
    $seen= [];
    foreach ($this->sources as $source) {
      $path= ($source instanceof Path ? $source : new Path($source))->asRealpath($this->base);

      $key= $path->hashCode(); 
      if (isset($seen[$key])) continue;

      yield $path;
      $seen[$key]= true;
    }
  }
}