<?php
namespace CJ;

class HttpClient {
	
    public static function sendRequest(string $url, string $method='POST', array|string $data = [], array $headers = [], int $maxRetries=1) {
        $attempt = 0;
        do {
            $response = self::executeCurl($url, $method, $data, $headers);
            $attempt++;
        } while ($response === false && $attempt < $maxRetries);
        if ($response === false) {
            throw new \Exception('HTTP request failed after ' . $maxRetries . ' attempts to '.$url.' by '.$method);
        }
        return $response;
    }

    private static function executeCurl(string $url, string $method, array|string $data, array $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (!empty($data)) {
            if ($method === 'POST' || $method === 'PUT') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response !== false ? $response : false;
    }
	
}