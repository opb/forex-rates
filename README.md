## Forex Rate Fetcher

Get Forex rates and store in cache. Docs aren't great. If you're interested in using, let me know and I can flesh these out a bit.

### Setup

1. install via composer:
	```
	composer require opb/forex-rates
	```
2.  Bind a `RateFetcher` implementation into the IoC. We provide one for [Currencylayer](http://www.currencylayer.com). You'll probably want to do this in your `AppServiceProvider`:

	```
	// app/Providers/AppServiceProvider.php
	
	    $this->app->bind(
            'Opb\Forex\Contracts\RateFetcher',
            'Opb\Forex\CurrencylayerRateFetcher'
        );
   ```
3. For Currencylayer, you need to set your `access_key` in your `services.php` config file. If you write your own, or use a different provider, this will be different:

	```
	// services.php
	'currencylayer' => [
	
	    'access_key' => 'YourAccessKeyHere',
	    
	],
	```
4. To use, simply make use of the ForexService class and run `getRate()` to get the rate between two currencies, or `convert` to convery an amount of a certain currency into another currency. Makes extensive use of the `mathiasverraes/money` money package.

### Examples

1. Get a rate. Returns a `CurrencyPair` object:

	```
	$forex = app('Opb\Forex\ForexService');
	$gbp = new Money\Currency('GBP');
	$usd = new Money\Currency('USD');
	$rate = $forex->getRate($gbp, $usd);
	var_dump($rate);
	
	class Money\CurrencyPair#709 (3) {
        private $baseCurrency =>
        class Money\Currency#690 (1) {
            private $name =>
            string(3) "GBP"
        }
        private $counterCurrency =>
        class Money\Currency#660 (1) {
            private $name =>
            string(3) "USD"
        }
        private $ratio =>
        double(1.5634111755758)
    }
	```
2. Convert from an amount of `Money` (in cents/pence) to another `Currency`

	```
	$forex = app('Opb\Forex\ForexService');
	$from = new Money\Money(10000, new Money\Currency('GBP'));
	$to = new Money\Currency('USD');
	$result = $forex->convert($from, $to);
	var_dump($result);
	
    class Money\Money#681 (2) {
        private $amount =>
        int(15611)
        private $currency =>
        class Money\Currency#706 (1) {
            private $name =>
            string(3) "USD"
        }
    }
	```
3. You can optionally supply a `DateTime` object as the third parameter to `getRate()` and `convert()` and this will attempt to get the rate for that given day.
	
