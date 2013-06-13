<?php

use Phalcon\Mvc\Model\Validator\Email;

/**
 * Class Contacts
 */
class Contacts extends \Bitfalls\Phalcon\Model
{

    use \Bitfalls\Traits\TimeAware;

    /**
     * @var integer
     *
     */
    protected $id;

    /**
     * @var string
     *
     */
    protected $email;

    /**
     * @var integer
     *
     */
    protected $user_id;

    /**
     * @var integer
     *
     */
    protected $activated;

    /**
     * @var string
     *
     */
    protected $activated_on;

    /**
     * @var string
     *
     */
    protected $activation_key;

    /**
     * @var string
     *
     */
    protected $created_on;

    public function initialize()
    {
        parent::initialize();
        $this->hasOne('user_id', 'Users', 'id', array('alias' => 'user'));
        $this->belongsTo('id', 'Users', 'main_contact_id', array('alias' => 'mainFor'));
    }

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
     * @param $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param $user_id
     * @return $this
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
        return $this;
    }

    /**
     * @param $activated
     * @return $this
     */
    public function setActivated($activated)
    {
        if ($activated == 1) {
            $this->setActivatedOn($this->dateToMysqlFull());
            $this->setActivationKey(null);
        } else {
            $this->setActivatedOn(null);
        }
        $this->activated = $activated;
        return $this;
    }

    /**
     * @param $activated_on
     * @return $this
     */
    public function setActivatedOn($activated_on)
    {
        $this->activated_on = $activated_on;
        return $this;
    }

    /**
     * @param $activation_key
     * @return $this
     */
    public function setActivationKey($activation_key)
    {
        $this->activation_key = $activation_key;
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
     * Returns the value of field email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
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
     * Returns the value of field activated
     *
     * @return integer
     */
    public function getActivated()
    {
        return $this->activated;
    }

    /**
     * Returns the value of field activation_key
     *
     * @return string
     */
    public function getActivationKey()
    {
        return $this->activation_key;
    }

    /**
     * Validations and business logic
     */
    public function validation()
    {
        $this->validate(new Email(array(
            "field" => "email",
            "required" => true
        )));
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }

}
