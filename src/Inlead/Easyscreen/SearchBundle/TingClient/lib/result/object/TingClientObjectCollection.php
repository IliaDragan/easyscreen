<?php
namespace Inlead\Easyscreen\SearchBundle\TingClient\lib\result\object;

class TingClientObjectCollection {
	public $objects;
	
  public function __construct($objects = array()) {
  	$this->objects = $objects;
  }
}

