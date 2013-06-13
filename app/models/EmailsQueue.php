<?php

/**
 * Class EmailsQueue
 */
class EmailsQueue extends \Phalcon\Mvc\Model
{

    /**
     * @var integer
     *
     */
    protected $id;

    /**
     * @var string
     *
     */
    protected $created_on;

    /**
     * @var string
     *
     */
    protected $to_be_sent_on;

    /**
     * @var string
     *
     */
    protected $sent_on;

    /**
     * @var integer
     *
     */
    protected $priority;

    /**
     * @var string
     *
     */
    protected $serialized_recipient;

    /**
     * @var string
     *
     */
    protected $serialized_sender;

    /**
     * @var string
     *
     */
    protected $headers;

    /**
     * @var integer
     *
     */
    protected $sent;

    /**
     * @var string
     *
     */
    protected $email_object;

    /**
     * @var string
     *
     */
    protected $slug;

    /**
     * @var string
     *
     */
    protected $blobhash;


    /**
     * @param $id
     * @return $this
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $created_on
     * @return $this
     */
    public function setCreatedOn($created_on)
    {
        $this->created_on = $created_on;
        return $this;
    }

    /**
     * @param $to_be_sent_on
     * @return $this
     */
    public function setToBeSentOn($to_be_sent_on)
    {
        $this->to_be_sent_on = $to_be_sent_on;
        return $this;
    }

    /**
     * @param $sent_on
     * @return $this
     */
    public function setSentOn($sent_on)
    {
        $this->sent_on = $sent_on;
        return $this;
    }

    /**
     * @param $priority
     * @return $this
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @param $serialized_recipient
     * @return $this
     */
    public function setSerializedRecipient($serialized_recipient)
    {
        $this->serialized_recipient = $serialized_recipient;
        return $this;
    }

    /**
     * @param $serialized_sender
     * @return $this
     */
    public function setSerializedSender($serialized_sender)
    {
        $this->serialized_sender = $serialized_sender;
        return $this;
    }

    /**
     * @param $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @param $sent
     * @return $this
     */
    public function setSent($sent)
    {
        $this->sent = $sent;
        return $this;
    }

    /**
     * @param $email_object
     * @return $this
     */
    public function setEmailObject($email_object)
    {
        $this->email_object = $email_object;
        return $this;
    }

    /**
     * @param $slug
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @param $blobhash
     * @return $this
     */
    public function setBlobhash($blobhash)
    {
        $this->blobhash = $blobhash;
        return $this;
    }


    /**
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field created_on
     *
     * @return string
     */
    public function getCreatedOn()
    {
        return $this->created_on;
    }

    /**
     * Returns the value of field to_be_sent_on
     *
     * @return string
     */
    public function getToBeSentOn()
    {
        return $this->to_be_sent_on;
    }

    /**
     * Returns the value of field sent_on
     *
     * @return string
     */
    public function getSentOn()
    {
        return $this->sent_on;
    }

    /**
     * Returns the value of field priority
     *
     * @return integer
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Returns the value of field serialized_recipient
     *
     * @return string
     */
    public function getSerializedRecipient()
    {
        return $this->serialized_recipient;
    }

    /**
     * Returns the value of field serialized_sender
     *
     * @return string
     */
    public function getSerializedSender()
    {
        return $this->serialized_sender;
    }

    /**
     * Returns the value of field headers
     *
     * @return string
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Returns the value of field sent
     *
     * @return integer
     */
    public function getSent()
    {
        return $this->sent;
    }

    /**
     * Returns the value of field email_object
     *
     * @return string
     */
    public function getEmailObject()
    {
        return $this->email_object;
    }

    /**
     * Returns the value of field slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Returns the value of field blobhash
     *
     * @return string
     */
    public function getBlobhash()
    {
        return $this->blobhash;
    }

}
