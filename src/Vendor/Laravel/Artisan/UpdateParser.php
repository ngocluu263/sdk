<?php namespace PragmaRX\SDK\Vendor\Laravel\Artisan;

use PragmaRX\SDK\Support\Config;
use UAParser\Util\Converter;
use UAParser\Util\Fetcher;

class UpdateParser extends Base {

    /**
     * Command name.
     *
     * @var string
     */
    protected $name = 'sdk:updateparser';

    /**
     * Command description.
     *
     * @var string
     */
    protected $description = 'Update uaparser regexes';

	/**
	 * @var
	 */
	private $config;

	public function __construct(Config $config)
	{
		parent::__construct();

		$this->config = $config;
	}

    /**
     * Execute the command.
     *
     * @return void
     */
    public function fire()
    {
	    $this->config->set('enabled', false);

        $fetcher = new Fetcher();

        $converter = new Converter(base_path().'/vendor/pragmarx/ua-parser/php/resources/');

        $converter->convertString($fetcher->fetch(), false);
    }

}
