<?php

/**
 * Class AddressBook
 */
class AddressBook extends \Bitfalls\Phalcon\Model
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
    protected $first_name;

    /**
     * @var string
     *
     */
    protected $last_name;

    /**
     * @var integer
     *
     */
    protected $city;

    /**
     * @var string
     *
     */
    protected $street;

    /**
     * @var string
     *
     */
    protected $zip;

    /**
     * @var integer
     *
     */
    protected $user_id;

    /**
     * @var integer
     *
     */
    protected $is_def;

    /**
     * @var string
     *
     */
    protected $residence_type;

    /** @var string */
    protected $additional_info;

    /**
     * @var string
     *
     */
    protected $phone;

    public function initialize() {
        parent::initialize();

        $this->hasOne('residence_type', 'AddressResidenceTypes', 'slug', array('alias' => 'type'));
        $this->belongsTo('user_id', 'Users', 'id', array('alias' => 'user'));

        $this->hasMany('id', 'Orders', 'shipping_address');

        $this->belongsTo('city', 'Cities', 'id', array('alias' => 'oCity'));
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
     * @param $sInfo
     * @return $this
     */
    public function setAdditionalInfo($sInfo) {
        $this->additional_info = $sInfo;
        return $this;
    }

    /**
     * @return string
     */
    public function getAdditionalInfo() {
        return $this->additional_info;
    }

    /**
     * @param $first_name
     * @return $this
     */
    public function setFirstName($first_name)
    {
        $this->first_name = $first_name;
        return $this;
    }

    /**
     * @param $last_name
     * @return $this
     */
    public function setLastName($last_name)
    {
        $this->last_name = $last_name;
        return $this;
    }

    /**
     * @param $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @param $street
     * @return $this
     */
    public function setStreet($street)
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @param $zip
     * @return $this
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
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
     * @param $is_def
     * @return $this
     */
    public function setIsDef($is_def)
    {
        $this->is_def = $is_def;
        return $this;
    }

    /**
     * @param $residence_type
     * @return $this
     */
    public function setResidenceType($residence_type)
    {
        $this->residence_type = $residence_type;
        return $this;
    }

    /**
     * @param $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
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
     * Returns the value of field first_name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Returns the value of field last_name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Returns the value of field city
     *
     * @return integer
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Returns the value of field street
     *
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * Returns the value of field zip
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
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
     * Returns the value of field is_def
     *
     * @return integer
     */
    public function getIsDef()
    {
        return $this->is_def;
    }

    /**
     * Returns the value of field residence_type
     *
     * @return string
     */
    public function getResidenceType()
    {
        return $this->residence_type;
    }

    /**
     * Returns the value of field phone
     *
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

}
