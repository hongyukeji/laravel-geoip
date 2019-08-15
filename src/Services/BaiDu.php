<?php

namespace Torann\GeoIP\Services;

use Exception;
use Torann\GeoIP\Support\HttpClient;
use Torann\GeoIP\Services\AbstractService;

class BaiDu extends AbstractService
{
    /**
     * Http client instance.
     *
     * @var HttpClient
     */
    protected $client;

    /**
     * The "booting" method of the service.
     *
     * @return void
     */
    public function boot()
    {
        $this->client = new HttpClient([
            'base_uri' => 'https://api.map.baidu.com/location/ip',
            'query' => [
                'ak' => $this->config('key'),
            ],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function locate($ip)
    {
        // Get data from client
        $data = $this->client->get('', [
            'ip' => $ip,
        ]);

        // Verify server response
        if ($this->client->getErrors() !== null) {
            throw new Exception('Request failed (' . $this->client->getErrors() . ')');
        }

        // Parse body content
        $json = json_decode($data[0]);

        $address = explode("|", $json->address);

        return [
            'ip' => $ip,
            'iso_code' => $address[0],
            'country' => $address[1],
            'city' => $address[2],
            'state' => $json->state ?? '',
            'state_name' => $json->state_name ?? '',
            'postal_code' => $json->content->city_code ?? '',
            'lat' => $json->point->y,
            'lon' => $json->point->x,
            'timezone' => $json->timezone ?? '',
            'continent' => $json->continent ?? '',
        ];
    }

    /**
     * Update function for service.
     *
     * @return string
     */
    public function update()
    {
        // Optional artisan command line update method
    }
}