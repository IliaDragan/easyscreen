<?php

namespace Inlead\Easyscreen\SearchBundle\AddiClient;

/**
 * @file
 * AdditionalInformation class.
 */ 

class AdditionalInformation {

  public $thumbnailUrl;
  public $detailUrl;

  public function __construct($thumbnail_url, $detail_url) {
    $this->thumbnailUrl = $thumbnail_url;
    $this->detailUrl = $detail_url;
  }

}
