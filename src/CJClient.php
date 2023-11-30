<?php
namespace CJ;

class CJClient{
	
    private $email;
    private $password;
	
	private $access_token;
	
	public $max_network_retries;
	
	private $httpClient;

	const API_BASE_URL="https://developers.cjdropshipping.com/api2.0/v1";

    public function __construct(string $email, string $password){
        if (version_compare(PHP_VERSION, '8.0.0', '<')) {
            throw new \Exception("CJCLient requires PHP version 8.0 or higher.");
        }
        $this->email = $email;
        $this->password = $password;
		$this->max_network_retries=3;
		$this->httpClient = new HttpClient();
		$this->getAccessToken();
    }
	
    private function getAccessToken() {
        $tokenFile = __DIR__ . '/AccessToken.json';
        if (file_exists($tokenFile)) {
            $tokenData = json_decode(file_get_contents($tokenFile), true);
            $expiryDate = new \DateTime($tokenData['accessTokenExpiryDate']);
            $now = new \DateTime();
            if ($expiryDate > $now) {
                $this->access_token = $tokenData['accessToken'];
                return;
            }
        }
        $this->requestNewAccessToken();
    }

    private function requestNewAccessToken() {
        $url = self::API_BASE_URL . '/authentication/getAccessToken';
        $headers = ['Content-Type' => 'application/json'];
        $data = json_encode(['email' => $this->email, 'password' => $this->password]);
        $response = $this->httpClient->sendRequest($url, 'POST', $data, $headers);
        if ($response === false) {
            throw new \Exception('Failed to get new access token');
        }
        $decodedResponse = json_decode($response, true);
        if ($decodedResponse['code'] == 200 && $decodedResponse['result']) {
            $tokenData = $decodedResponse['data'];
            file_put_contents(__DIR__ . '/AccessToken.json', json_encode($tokenData));
            $this->access_token = $tokenData['accessToken'];
        }
		else
		{
            throw new \Exception('Failed to authenticate with CJ API');
        }
    }
	
    public function createRequest(string $endpoint_path, $method='POST', array $payload, string|null|bool $queue = null, callable|null $callback = null) {
        $url = self::API_BASE_URL . '/' . $endpoint_path;
        $headers = [
            'CJ-Access-Token' => $this->access_token,
            'Content-Type' => 'application/json'
        ];
        $data = json_encode($payload);
        if ($data === false) {
            throw new \Exception('Failed to encode payload to JSON');
        }
        $response = $this->httpClient->sendRequest($url, $method, $data, $headers, $this->max_network_retries);
        if ($response === false) {
            throw new \Exception('CURL request failed after ' . $this->max_network_retries . ' attempts');
        }
        $decodedResponse = json_decode($response, true);
        if (null === $decodedResponse) {
            throw new \Exception('Invalid JSON response');
        }
        return $this->processResponse($decodedResponse);
    }
	
    private function processResponse(array $response) {
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
