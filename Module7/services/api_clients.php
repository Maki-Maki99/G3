<?php

function api_get($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    $res = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return ["ok" => false, "error" => $err, "url" => $url];
    }

    return ["ok" => true, "data" => json_decode($res, true), "url" => $url];
}

function fetch_all_modules($endpoints) {
    $results = [];
    foreach ($endpoints as $key => $url) {
        $results[$key] = api_get($url);
    }
    return $results;
}
