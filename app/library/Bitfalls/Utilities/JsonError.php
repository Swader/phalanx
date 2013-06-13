<?php

namespace Bitfalls\Utilities;

/**
 * Class JsonError
 * @package Bitfalls\Utilities
 */
class JsonError extends JsonResponse
{

    /**  */
    public function __construct() {
        parent::__construct();
        $this->responseObject->status = 'error';
    }

    /**
     * @return JsonResponse
     */
    public static function getInstance() {
        return new self();
    }

}