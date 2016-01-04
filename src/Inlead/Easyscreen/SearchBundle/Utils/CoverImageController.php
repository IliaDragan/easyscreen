<?php

namespace Inlead\Easyscreen\SearchBundle\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Inlead\Easyscreen\SearchBundle\AddiClient\AdditionalInformationService as AdditionalInformationService;

define('ADDI_WSDL_URL', 'http://moreinfo.addi.dk/2.1/');
define('ADDI_USERNAME', 'netpunkt');
define('ADDI_PASSWORD', 'byspaste');
define('ADDI_GROUP', '733000');

class CoverImageController
{
    public function getCoverImage($faustNumbers)
    {
        $fs = new Filesystem();
        $covers = array();

        $addi = new AdditionalInformationService(ADDI_WSDL_URL, ADDI_USERNAME, ADDI_GROUP, ADDI_PASSWORD);
        $response = $addi->getByFaustNumber($faustNumbers);

        foreach ($faustNumbers as $faust) {
            if (isset($response[$faust])) {
                foreach ($response[$faust] as $prop => $value) {
                    $localFilename = $this->cacheCover($value, $this->getImageFilename($faust . $prop));
                    $covers[$faust][$prop] = 'http://'.$_SERVER['HTTP_HOST'].'/web/covers/'.$localFilename;
                }
            }
        }

        return $covers;
    }

    private function getImageFilename($originalName)
    {
        return sha1($originalName) . '.jpeg';
    }

    /**
    * Save cover from Addi on local storage.
    *
    * @param $url
    * @param $newfname
    *
    * @return mixed
    */
    private function cacheCover($url, $newfname)
    {
        $destination_folder = '../web/covers/';
        $file = fopen($url, 'rb');
        if ($file) {
            $newf = fopen($destination_folder . $newfname, 'wb');
            if ($newf) {
                while (! feof($file)) {
                    fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                }
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
