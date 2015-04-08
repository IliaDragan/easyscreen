<?php

namespace Inlead\Easyscreen\SearchBundle\TingClient\lib;

use Inlead\Easyscreen\SearchBundle\TingClient\lib\adapter\TingClientRequestAdapter as TingClientRequestAdapter;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\log\TingClientLogger as TingClientLogger;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\log\TingClientVoidLogger as TingClientVoidLogger;
use Inlead\Easyscreen\SearchBundle\TingClient\lib\request\TingClientRequest as TingClientRequest;

class TingClient {
  /**
   * @var TingClientLogger
   */
  private $logger;

  /**
   * @var TingClientRequestAdapter
   */
  private $requestAdapter;

  function __construct(TingClientRequestAdapter $requestAdapter, TingClientLogger $logger = NULL) {
    $this->logger = (isset($logger)) ? $logger : new TingClientVoidLogger();
    $this->requestAdapter = $requestAdapter;
    $this->requestAdapter->setLogger($this->logger);
  }

  function execute(TingClientRequest $request) {
    return $request->execute($this->requestAdapter);
  }
}

