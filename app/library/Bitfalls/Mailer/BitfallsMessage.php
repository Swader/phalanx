<?php

namespace Bitfalls\Mailer;

/**
 * Class BitfallsMessage
 * @package Bitfalls\Mailer
 */
class BitfallsMessage extends \Swift_Message
{

    /** @var string */
    protected $sSlug;

    /**
     * @param $sSlug
     * @return $this
     */
    public function setSlug($sSlug)
    {
        $this->sSlug = $sSlug;
        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->sSlug;
    }

    /**
     * @param null $subject
     * @param null $body
     * @param null $contentType
     * @param null $charset
     */
    public function __construct($subject = null, $body = null, $contentType = null, $charset = null)
    {
        parent::__construct($subject, $body, $contentType, $charset);
    }

    /**
     * @param null $subject
     * @param null $body
     * @param null $contentType
     * @param null $charset
     * @return BitfallsMessage|\Swift_Message
     */
    public static function newInstance($subject = null, $body = null, $contentType = null, $charset = null)
    {
        return new BitfallsMessage($subject, $body, $contentType, $charset);
    }

    /**
     * @return string
     */
    public function calculateBlobhash()
    {
        return md5(
            $this->getBody()
                . $this->getSlug()
                . serialize($this->getTo())
                . serialize($this->getFrom())
                . $this->getSubject()
        );
    }

}