
<?php

define("UMMAH_API_URL", "https://ummahapi.com");

function callUmmahAPI($endpoint)
{
    $url = UMMAH_API_URL . $endpoint;

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => [
            "Accept: application/json"
        ]
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);

    curl_close($curl);

    if ($response === false) {
        return [
            "success" => false,
            "error" => "Erreur de connexion : " . $error
        ];
    }

    $result = json_decode($response, true);

    if ($httpCode !== 200 || !is_array($result)) {
        return [
            "success" => false,
            "error" => "Erreur API. Code HTTP : " . $httpCode
        ];
    }

    if (isset($result["success"]) && $result["success"] === false) {
        return [
            "success" => false,
            "error" => $result["error"] ?? "Erreur UmmahAPI."
        ];
    }

    return [
        "success" => true,
        "data" => $result["data"] ?? $result
    ];
}
