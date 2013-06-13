<?php


class RecentSearches extends \Bitfalls\Phalcon\Model
{

    /**
     * @var integer
     *
     */
    protected $id;

    /**
     * @var integer
     *
     */
    protected $user_id;

    /**
     * @var string
     *
     */
    protected $query;

    /**
     * @var string
     *
     */
    protected $created_on;


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
     * Method to set the value of field user_id
     *
     * @param integer $user_id
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
    }

    /**
     * Method to set the value of field query
     *
     * @param string $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * Method to set the value of field created_on
     *
     * @param string $created_on
     */
    public function setCreatedOn($created_on)
    {
        $this->created_on = $created_on;
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
     * Returns the value of field user_id
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Returns the value of field query
     *
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
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

}
