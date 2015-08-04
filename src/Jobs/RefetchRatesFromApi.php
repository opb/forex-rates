<?php namespace Opb\Forex\Jobs;

use DateTime;
use Opb\Forex\ForexService;
use Opb\Forex\ForexException;
use Illuminate\Bus\Queueable;
use Opb\Forex\Contracts\RateFetcher;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Cache\Repository as Cache;

class RefetchRatesFromApi implements SelfHandling, ShouldQueue{

    use Queueable;
    use InteractsWithQueue;

    /** @var DateTime */
    protected $date;

    public function __construct(DateTime $date = null)
    {
        $this->date = $date;
    }

    public function handle(RateFetcher $source, Cache $cache)
    {
        $rates = $source->getUsdRates($this->date);

        if(!count($rates)){
            throw new ForexException('received empty array of rates');
        }

        $cacheKey = $this->date ? $this->date->format('Y-m-d') : 'live';

        // save rates for two hours to main cache
        $cache->put(ForexService::RATE_CACHE_KEY_PREFIX.$cacheKey, $rates, 120);

        // save rates indefinitely for backup cache
        $cache->forever(ForexService::BACKUP_CACHE_KEY_PREFIX.$cacheKey, $rates);
    }

}