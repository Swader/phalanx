<?php

namespace Bitfalls\Utilities;

/**
 * Class JsonResponse
 * @package Bitfalls\Utilities
 */
class JsonResponse {

    /** @var \stdClass */
    protected $responseObject = null;

    /**  */
    public function __construct() {
        $this->responseObject = new \stdClass();
    }

    /**
     * @param bool $bAngularFix
     * Setting AngularFix to true will prefix the returned value with ")]}',\n", as per a JSON vulnerability problem: http://docs.angularjs.org/api/ng.$http
     * Note that this will probably break JSON parsing on the client side if Angular is NOT being used, thus the parameter defaults to false.
     */
    public function raise($bAngularFix = false) {
        if (!isset($this->responseObject->status)) {
            $this->responseObject->status = 'success';
        }
        if (!isset($this->responseObject->message)) {
            $this->setMessage('Success!');
        }
        die((($bAngularFix) ? ")]}',\n" : '').json_encode($this->responseObject));
    }

    /**
     * @param $sMessage
     * @return $this
     */
    public function setMessage($sMessage) {
        $this->responseObject->message = $sMessage;
        return $this;
    }

    /**
     * @param $mResult
     * @return $this
     */
    public function setResult($mResult) {
        $this->responseObject->result = $mResult;
        return $this;
    }

}