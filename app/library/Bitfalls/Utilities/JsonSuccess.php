<?php

namespace Bitfalls\Utilities;

/**
 * Class JsonSuccess
 * @package Bitfalls\Utilities
 */
class JsonSuccess extends JsonResponse
{

    /**  */
    public function __construct() {
        parent::__construct();
        $this->responseObject->status = 'success';
    }

    /**
     * @return JsonResponse
     */
    public static function getInstance() {
        return new self();
    }

}