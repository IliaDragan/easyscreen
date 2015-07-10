<?php

namespace Inlead\Easyscreen\SearchBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Inlead\Easyscreen\SearchBundle\AddiClient\AdditionalInformationService as AdditionalInformationService;


define('ADDI_WSDL_URL', 'http://moreinfo.addi.dk/2.1/');
define('ADDI_USERNAME', 'netpunkt');
define('ADDI_PASSWORD', 'byspaste');
define('ADDI_GROUP', '733000');
class CoverImageController extends Controller
{
  public function getCoverImage($faustNumbers) {
    $fs = new Filesystem();
    $covers = array();

    //Check for existing cached covers;
    foreach ($faustNumbers as &$faust) {
      $faustHashFileName = md5($faust) . '.jpg';
      if ($fs->exists('covers/' . $faustHashFileName)) {
        // @todo return a link; Achtung dirty code here.
        // $request = Request::createFromGlobals();
        $covers[$faust] = 'http://' . $_SERVER['HTTP_HOST'] . '/web/covers/' . $faustNumbers;
      } else {
        unset($faust);
      }
    }
    // Fetch non-cached covers from Addi.
    $addi = new AdditionalInformationService(ADDI_WSDL_URL, ADDI_USERNAME, ADDI_GROUP, ADDI_PASSWORD);
    $response = $addi->getByFaustNumber($faustNumbers);

    foreach($response as $faust=>$addiObj) {
      if (isset($addiObj)) {
        $faustHashFileName = md5($faust) . '.jpg';
        $covers[$faust] = 'http://' . $_SERVER['HTTP_HOST'] . '/web/covers/' . $this->cacheCover($addiObj->detailUrl, $faustHashFileName);
      }
    }
    return $covers;
  }

  /**
   * Save cover from Addi on local storage.
   * @param $url
   * @param $newfname
   * @return mixed
   */
  private function cacheCover($url, $newfname) {
    $destination_folder = './covers/';
    $file = fopen ($url, "rb");
    if ($file) {
      $newf = fopen ($destination_folder . $newfname, "wb");
      if ($newf)
        while(!feof($file)) {
          fwrite($newf, fread($file, 1024 * 8 ), 1024 * 8 );
        }
    }
    if ($file) {
      fclose($file);
    }
    if ($newf) {
      fclose($newf);
    }
    return $newfname;
  }
}
