<?php namespace Opb\Forex\Contracts;

use DateTime;

interface RateFetcher{

    public function getUsdRates(DateTime $date = null);

}