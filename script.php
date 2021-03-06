<?php

function get($url)
{
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $httpCode = (int)42;
    while ($httpCode != 200) // crappy, but that's life bro
    {
        if ($httpCode != 42)
            sleep(60);
        $res = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    }
    return $res;
}

define('ENDPOINT', 'http://api.openweathermap.org/data/2.5/forecast');
$cityId = trim(file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'paris'));
$APIKey = trim(file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'apikey'));

// Forging API call URL
$url = sprintf('%s?id=%s&APPID=%s&units=metric', ENDPOINT, $cityId, $APIKey);
// Calling the API and decoding the return
$jsonData = json_decode(get($url), true);
// parsing data to make sms
$lowestTemp = (float)1000;
$highestTemp = (float)0;
$averageTemp = (float)0;
$averageWind = (float)0;
$Rain = (float)0;
$averageClouds = (float)0;
$Snow = (float)0;
// We use data only for today's day
$today = date('d');
$iteration = 0;
foreach ($jsonData['list'] as $data)
{
    if (date('d', $data['dt']) === $today)
    {
        if ($lowestTemp > $data['main']['temp'])
            $lowestTemp = $data['main']['temp'];
        if ($highestTemp < $data['main']['temp'])
            $highestTemp = $data['main']['temp'];
        $averageTemp += $data['main']['temp'];
        $averageWind += $data['wind']['speed'];
        if (isset($data['rain']['3h']))
            $Rain += $data['rain']['3h'];
        if (isset($data['snow']['3h']))
            $Snow += $data['snow']['3h'];
        $averageClouds += $data['clouds']['all'];
    }
    else break;
    $iteration++;
}

if ($iteration === 0)
{
    die();
}
$averageTemp /= $iteration;
$averageWind /= $iteration;
// Wind is meter/s, we want km/h
$averageWind *= (float)3.6;
$averageClouds /= $iteration;

if ($Snow > 0)
    $sms = sprintf('LT: %.2f, HT: %.2f, AT: %.2f, R: %.2f, S: %.2f, AC: %.2f, AW: %.2f', $lowestTemp, $highestTemp, $averageTemp, $Rain, $Snow, $averageClouds, $averageWind);
else
    $sms = sprintf('LT: %.2f, HT: %.2f, AT: %.2f, R: %.2f, AC: %.2f, AW: %.2f', $lowestTemp, $highestTemp, $averageTemp, $Rain, $averageClouds, $averageWind);
`sms $sms`;
