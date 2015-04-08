<?php

namespace Inlead\Easyscreen\SearchBundle\TingClient\lib\log;

/**
 * Dummy logger which does nothing
 */
class TingClientVoidLogger extends TingClientLogger {
  protected function doLog($message, $severity) {
    //Do nothing
  }
}

