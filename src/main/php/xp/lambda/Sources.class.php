<?php namespace xp\lambda;

use IteratorAggregate, Traversable;
use io\Path;

/** Returns a unique list of sources */
class Sources implements IteratorAggregate {
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