<?php
namespace CJ;

class HttpClient {

	public function __construct() {
		
	}

    public function sendRequest($url, $method, $data = [], $headers = [], $maxRetries) {
        $attempt = 0;
        do {
            $response = $this->executeCurl($url, $method, $data, $headers);
            $attempt++;
        } while ($response === false && $attempt < $maxRetries);
        if ($response === false) {
            throw new Exception('HTTP request failed after ' . $maxRetries . ' attempts');
        }
        return $response;
    }

    private function executeCurl($url, $method, $data, $headers) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        if (!empty($data)) {
            if ($method === 'POST' || $method === 'PUT') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }
        $response = curl_exec($ch);
        curl_close($ch);
        return $response !== false ? $response : false;
    }
	
}