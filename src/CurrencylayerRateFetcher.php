<?php namespace Opb\Forex;

use DateTime;
use GuzzleHttp\Client;
use Opb\Forex\Contracts\RateFetcher;
use Illuminate\Contracts\Config\Repository as Config;

class CurrencylayerRateFetcher implements RateFetcher{

    const HTTP_TIMEOUT = 3;
    const API_URL_BASE = 'http://www.apilayer.net/api';

    protected $guzzle;
    protected $config;

    public function __construct(Client $guzzle, Config $config)
    {
        $this->guzzle = $guzzle;
        $this->config = $config['services.currencylayer'];
    }

    public function getUsdRates(DateTime $date = null)
    {
        $rawRates = $date ? $this->fetchHistoricalRawRates($date) : $this->fetchLiveRawRates();

        return $this->formatRates($rawRates);
    }

    protected function fetchLiveRawRates()
    {
        $url = self::API_URL_BASE.'/live?access_key='.$this->config['access_key'];

        return $this->fetchRates($url);
    }

    protected function fetchHistoricalRawRates(DateTime $date)
    {
        $dateString = $date->format('Y-m-d');
        $url = self::API_URL_BASE."/historical?date={$dateString}&access_key=".$this->config['access_key'];

        return $this->fetchRates($url);
    }

    protected function fetchRates($url)
    {
        $response = $this->guzzle->get($url, ['timeout' => self::HTTP_TIMEOUT]);

        return $response->json();
    }

    protected function formatRates($rawRates)
    {
        if(!isset($rawRates['quotes'])){
            throw new ForexException('Response from API in unexpected format: '.json_encode($rawRates));
        }

        $rawRates = $rawRates['quotes'];
        $output = [];

        foreach($rawRates as $curr => $rate){
            $m = [];
            if(preg_match('/USD(\w\w\w)/', $curr, $m)){
                $output[strtoupper($m[1])] = $rate;
            }
        }

        return $output;
    }
}