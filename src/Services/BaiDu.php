<?php

namespace Torann\GeoIP\Services;

use Exception;
use Illuminate\Support\Facades\Log;
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

        // Verify response status
        if ($json->status !== 0) {
            throw new Exception('Request failed (' . $json->message . ')');
        }

        $address = explode("|", $json->address);

        try {
            $province = ucwords(implode('', pinyin($address[1], PINYIN_DEFAULT)));
        } catch (Exception $e) {
            $province = $address[1];
        }

        return $this->hydrate([
            'ip' => $ip,
            'iso_code' => $address[0],
            'country' => '中国',
            'city' => $address[2],
            'state' => $province,
            'state_name' => $address[1],
            'postal_code' => $json->content->address_detail->city_code,
            'lat' => $json->content->point->y,
            'lon' => $json->content->point->x,
            'timezone' => 'Asia/Shanghai',
            'continent' => 'Asia',
            'currency' => 'RMB',
        ]);
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