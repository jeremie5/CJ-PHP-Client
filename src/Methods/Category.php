<?php
namespace CJ;

class Category {
	
    public static function getAll() {
        $response = \CJ\CJClient::createRequest($endpoint="product/getCategory");
		return $response;
    }

}