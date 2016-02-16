<?php
/**
 * @file
 */

namespace Inlead\Easyscreen\SearchBundle\Utils;

use Symfony\Component\Filesystem\Filesystem;
use Inlead\Easyscreen\SearchBundle\AddiClient\AdditionalInformationService as AdditionalInformationService;

class CoverImageController
{
    const ADDI_WSDL_URL = 'http://moreinfo.addi.dk/2.1/';
    const ADDI_USERNAME = 'netpunkt';
    const ADDI_PASSWORD = 'kvupoglo';
    const ADDI_GROUP = '786000';

    public function getCoverImage($faustNumbers)
    {
        $fs = new Filesystem();
        $covers = array();

        $addi = new AdditionalInformationService(self::ADDI_WSDL_URL, self::ADDI_USERNAME, self::ADDI_GROUP, self::ADDI_PASSWORD);
        $response = $addi->getByFaustNumber($faustNumbers);

        foreach ($faustNumbers as $faust) {
            if (isset($response[$faust])) {
                foreach ($response[$faust] as $prop => $value) {
                    $localFilename = $this->getImageFilename($faust . $prop);
                    $this->cacheCover($fs, $value, $localFilename);
                    $covers[$faust][$prop] = 'http://' . $_SERVER['HTTP_HOST'] . '/web/covers/' . $localFilename;
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
    * @param Filesystem $fs
    * @param string $source
    * @param string $target
    */
    private function cacheCover(Filesystem $fs, $source, $target)
    {
        $coverDir = '../web/covers/';
        $coverPath = $coverDir . $target;

        if (!$fs->exists($coverPath)) {
            $coverContents = @file_get_contents($source);

            if (!empty($coverContents)) {
                $fs->dumpFile($coverPath, $coverContents);
            }
        }
    }
}
