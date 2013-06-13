<?php


class Errors extends \Bitfalls\Phalcon\Model
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
    protected $url;

    /**
     * @var integer
     *
     */
    protected $user_id;

    /**
     * @var string
     *
     */
    protected $stack_trace;

    /**
     * @var string
     *
     */
    protected $details;

    /**
     * @var string
     *
     */
    protected $type;

    /**
     * @var integer
     *
     */
    protected $code;


    /**
     * Method to set the value of field id
     *
     * @param integer $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Method to set the value of field url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Method to set the value of field user_id
     *
     * @param integer $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * Method to set the value of field stack_trace
     *
     * @param string $stack_trace
     */
    public function setStackTrace($stack_trace)
    {
        $this->stack_trace = $stack_trace;
    }

    /**
     * Method to set the value of field details
     *
     * @param string $details
     */
    public function setDetails($details)
    {
        $this->details = $details;
    }

    /**
     * Method to set the value of field type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Method to set the value of field code
     *
     * @param integer $code
     */
    public function setCode($code)
    {
        $this->code = $code;
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
     * Returns the value of field url
     *
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Returns the value of field user_id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Returns the value of field stack_trace
     *
     * @return string
     */
    public function getStackTrace()
    {
        return $this->stack_trace;
    }

    /**
     * Returns the value of field details
     *
     * @return string
     */
    public function getDetails()
    {
        return $this->details;
    }

    /**
     * Returns the value of field type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the value of field code
     *
     * @return integer
     */
    public function getCode()
    {
        return $this->code;
    }

}
