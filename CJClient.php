<?php
namespace CJ;

class CJClient{
	
    private $email;
    private $password;
	
	private static $access_token;
	public static $max_network_retries;
	private static $httpClient;

	const API_BASE_URL="https://developers.cjdropshipping.com/api2.0/v1";

    public function __construct(string $email, string $password){
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            throw new \Exception("CJCLient requires PHP version 8.0 or higher.");
        }
        $this->email = $email;
        $this->password = $password;
		self::$max_network_retries = 3;
		$this->getAccessToken();
    }
	
    private function getAccessToken() {
        $tokenFile = __DIR__ . '/AccessToken.json';
		if (!is_writable($tokenFile)) {
			throw new \Exception('Cannot write to '.$tokenFile);
		}
		if (!is_readable($tokenFile)) {
			throw new \Exception('Cannot read from '.$tokenFile);
		}
        if (file_exists($tokenFile)) {
            $tokenData = json_decode(file_get_contents($tokenFile), true);
            $expiryDate = new \DateTime($tokenData['accessTokenExpiryDate']);
            $now = new \DateTime();
            if ($expiryDate > $now) {
               return self::$access_token = $tokenData['accessToken'];
            }
        }
        $this->requestNewAccessToken();
    }

    private function requestNewAccessToken() {
        $url = self::API_BASE_URL . '/authentication/getAccessToken';
        $headers = ['Content-Type' => 'application/json'];
        $data = json_encode(['email' => $this->email, 'password' => $this->password]);
        $response = \CJ\HttpClient::sendRequest($url, 'POST', $data, $headers, self::$max_network_retries);
        if ($response === false) {
            throw new \Exception('Failed to get new access token');
        }
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse['code'] == 200 && $decodedResponse['result']) {
            $tokenData = $decodedResponse['data'];
            file_put_contents(__DIR__ . '/AccessToken.json', json_encode($tokenData));
            self::$access_token = $tokenData['accessToken'];
        }
		else
		{
            throw new \Exception('Failed to authenticate with CJ API, error '.$decodedResponse['code']);
        }
    }
	
    public static function createRequest(string $endpoint_path, $method='POST', ?array $payload=null, callable|null $callback = null) {
		if(!isset(self::$access_token)){
			throw new \Exception('CJClient class not initialized');
		}
        $url = self::API_BASE_URL . '/' . $endpoint_path;
        $headers = [
            'CJ-Access-Token' => self::$access_token,
            'Content-Type' => 'application/json'
        ];
		if(!is_null($payload)){
			$data = json_encode($payload);
			if ($data === false) {
				throw new \Exception('Failed to encode payload to JSON');
			}
        }
        $response =  \CJ\HttpClient::sendRequest($url, $method, $data, $headers, self::$max_network_retries);
        if ($response === false) {
            throw new \Exception('CURL request failed after ' . self::$max_network_retries . ' attempts');
        }
        $decodedResponse = json_decode($response, true);
        if (null === $decodedResponse) {
            throw new \Exception('Invalid JSON response');
        }
        return self::processResponse($decodedResponse);
    }
	
    private static function processResponse(array $response) {
        if ($response['code'] == 200) {
            return [
                'status' => 'success',
                'message' => $response['message'],
                'data' => $response['data']
            ];
        } else {
            return [
                'status' => 'failed',
                'message' => $response['message'],
                'code' => $response['code']
            ];
        }
    }

}