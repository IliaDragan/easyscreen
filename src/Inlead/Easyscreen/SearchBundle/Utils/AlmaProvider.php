<?php
/**
 * @file
 */

namespace Inlead\Easyscreen\SearchBundle\Utils;

use Inlead\Easyscreen\SearchBundle\AlmaClient\AlmaClient;

class AlmaProvider
{
    const ALMA_BASE_URL = 'https://roar.roskildebib.dk:8070/alma/';

    private $instance;

    private function getInstance()
    {
        if (empty($this->instance)) {
            $this->instance = new AlmaClient(self::ALMA_BASE_URL);
        }

        return $this->instance;
    }

    public function getAvailability(array $items)
    {
        $availability = $this->getInstance()->get_availability(implode(',', $items));

        return $availability;
    }

    public function getRecordDetail(array $items)
    {
        $detail = $this->getInstance()->catalogue_record_detail(implode(',', $items));

        return $detail;
    }

    public function getReservationBranches()
    {
        $branches = $this->getInstance()->get_branches();

        return $branches;
    }
}