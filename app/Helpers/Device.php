<?php

namespace App\Helpers;

use DeviceDetector\ClientHints;
use DeviceDetector\DeviceDetector;
use DeviceDetector\Parser\Device\AbstractDeviceParser;
use DeviceDetector\Cache\LaravelCache;
use Illuminate\Support\Facades\Http;

class Device
{
    public static function name()
    {
        AbstractDeviceParser::setVersionTruncation(AbstractDeviceParser::VERSION_TRUNCATION_NONE);
        $userAgent = request()->userAgent();
        $clientHints = ClientHints::factory($_SERVER);

        $dd = new DeviceDetector($userAgent, $clientHints);
        $dd->setCache(new LaravelCache());
        $dd->skipBotDetection();
        $dd->parse();

        $parts = [
            $dd->getBrandName(),
            $dd->getModel(),
            $dd->getOs()["name"] ?? "",
            $dd->getClient()["name"] ?? "",
            $dd->getDeviceName()
        ];

        return implode(" ", array_filter($parts));
    }

    public static function tokenName()
{
    $country = "Unknown Country";
    $city = "Unknown City";
    $lat = "Unknown Latitude";
    $lon = "Unknown Longitude";
    $timezone = "Unknown Timezone";

    // Use a simple device name instead of the full detection
    $deviceName = request()->userAgent() ?? "Unknown Device"; 
    
    // $ip = request()->ip();
    $ip = "195.46.25.220";

    $res = Http::get("http://ip-api.com/json/$ip?fields=status,country,city,lat,lon,timezone");
    if ($res->json()['status'] == 'success') {
        $country = $res->json()['country'];
        $city = $res->json()['city'];
        $lat = $res->json()['lat'];
        $lon = $res->json()['lon'];
        $timezone = $res->json()['timezone'];
    }
    
    return "$deviceName, $city $country, TZ:$timezone, POINT($lat, $lon)";
}
}
