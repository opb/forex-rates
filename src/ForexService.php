<?php namespace Opb\Forex;

use DateTime;
use Money\Money;
use Money\Currency;
use Money\CurrencyPair;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Contracts\Cache\Repository as Cache;

class ForexService{

    use DispatchesJobs;

    const RATE_CACHE_KEY_PREFIX = 'forex_rates:';
    const BACKUP_CACHE_KEY_PREFIX = 'forex_rates_backup:';

    protected $rates = [];

    protected $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    public function getRate(Currency $from, Currency $to, DateTime $date = null)
    {
        $rates = $this->getRatesFromStore($date);
        $dollarFrom = $from->getName() == 'USD'? 1.0 : $rates[$from->getName()];
        $dollarTo = $to->getName() == 'USD'? 1.0 : $rates[$to->getName()];

        $rate = $dollarTo / $dollarFrom; 

        return new CurrencyPair($from, $to, $rate);
    }

    public function convert(Money $from, Currency $to, DateTime $date = null)
    {
        $pair = $this->getRate($from->getCurrency(), $to);

        return $pair->convert($from);
    }

    protected function getRatesFromStore(DateTime $date = null)
    {
        $rateKey = $date ? $date->format('Y-m-d') : 'live';

        // 1. try to get form this object
        if(isset($this->rates[$rateKey])){
            return $this->rates[$rateKey];
        }

        // 2. get from main cache
        if($rates = $this->cache->get(self::RATE_CACHE_KEY_PREFIX.$rateKey)){
            $this->rates[$rateKey] = $rates;
            return $rates;
        }

        // 3. not in main cache any more. get from backup cache and dispatch refetch job
        $this->dispatch(new Jobs\RefetchRatesFromApi($date));

        if($rates = $this->cache->get(self::BACKUP_CACHE_KEY_PREFIX.$rateKey)){
            $this->rates[$rateKey] = $rates;
            return $rates;
        }

        throw new ForexException('No Exchange Rate sources were available');
    }

}
