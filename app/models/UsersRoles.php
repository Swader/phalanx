<?php

use Bitfalls\Utilities\Stringer;

class UsersRoles extends \Bitfalls\Phalcon\Model
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
    protected $role_slug;

    /**
     * @var integer
     *
     */
    protected $user_id;

    public function initialize()
    {
        parent::initialize();

        /** Set up relationship with Roles and Users */
        $this->belongsTo('role_slug', 'UserRoles', 'slug');
        $this->belongsTo('user_id', 'Users', 'id');
    }


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
     * Method to set the value of field role_slug
     *
     * @param string $role_slug
     */
    public function setRoleSlug($role_slug)
    {
        $this->role_slug = $role_slug;
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
     * Returns the value of field id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Returns the value of field role_slug
     *
     * @return string
     */
    public function getRoleSlug()
    {
        return $this->role_slug;
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

}
