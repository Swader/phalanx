<?php

use Bitfalls\Traits\TimeAware;

/**
 * Class Users
 */
class Users extends \Bitfalls\Phalcon\Model
{
    use TimeAware;

    const PASSWORD_SALT = 'w435278tv3985t8734nt8973463b948b76t3783bv4586z45879tv629b89692';
    const HASH_SALT = '83457tn09376tv8973zt8374zt83745ztv274z5n3z57t437v5789';

    /**
     * @var integer
     *
     */
    protected $id;

    /**
     * @var string
     *
     */
    protected $username;

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
     * @var string
     *
     */
    protected $password;

    /**
     * @var string
     *
     */
    protected $created_on;

    /**
     * @var integer
     *
     */
    protected $created_by;

    /**
     * @var integer
     *
     */
    protected $type;

    /**
     * @var string
     *
     */
    protected $password_reset_hash;

    /**
     * @var integer
     *
     */
    protected $main_contact_id;

    /** @var  Users */
    protected static $oCurrent;

    public function initialize()
    {
        parent::initialize();

        /** Set Up M:M relationship with groups */
        $this->hasMany('id', 'UsersRoles', 'user_id');
        $this->hasManyThrough('UserRoles', 'UsersRoles');

        /** Set up 1;M relationship with contacts */
        $this->hasMany('id', 'Contacts', 'user_id');

        /** Set up 1:1 relationship with main contact */
        $this->hasOne('main_contact_id', 'Contacts', 'id', array('alias' => 'mainContact'));

        /** Set up creator relationship */
        $this->hasOne('created_by', 'Users', 'id', array('alias' => 'creator'));

        /** Set up settings relationship */
        $this->hasMany('id', 'Settings', 'user_id');

        /** Give addresses to user */
        $this->hasMany('id', 'AddressBook', 'user_id', array('alias' => 'addresses'));

        $this->hasMany('id', 'Orders', 'user_id');
    }

    /**
     * @return AddressBook|bool
     */
    public function getDefaultAddress()
    {
        return AddressBook::findFirst(array('user_id = :id: AND is_def = 1', 'bind' => array('id' => $this->getId())));
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
     * @param $username
     * @return $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
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
     * @param $password
     * @return $this
     */
    public function setPassword($password)
    {
        $this->password = self::hashPassword($password, $this->getUsername());
        return $this;
    }

    /**
     * @param $sPassword
     * @param $sUsername
     * @return string
     */
    public static function hashPassword($sPassword, $sUsername)
    {
        return hash('sha512', self::PASSWORD_SALT . $sUsername);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getLoginSessionHash()
    {
        if (!$this->getId() || !$this->getUsername() || !$this->getPassword()) {
            throw new \Exception('Tried to get login hash on non-fetched user.');
        }
        return md5($this->getPassword() . $this->getUsername() . self::HASH_SALT);
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
     * @param $created_by
     * @return $this
     */
    public function setCreatedBy($created_by)
    {
        if ($created_by instanceof Users) {
            $created_by = $created_by->getId();
        }
        $this->created_by = $created_by;
        return $this;
    }

    /**
     * @param $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param $passwordResetHash
     * @return $this
     */
    public function setPasswordResetHash($passwordResetHash)
    {
        $this->password_reset_hash = $passwordResetHash;
        return $this;
    }

    /**
     * @param $mainContactId
     * @return $this
     */
    public function setMainContactId($mainContactId)
    {
        $this->main_contact_id = $mainContactId;
        return $this;
    }

    /**
     * @param Contacts $oContact
     * @return bool
     * @throws Exception
     */
    public function setContactAsMain(Contacts $oContact) {
        if ($oContact->mainFor->getId() != $this->getId()) {
            if ($oContact->mainFor) {
                throw new \Exception('Contact '.$oContact->getId().' is already main for another user. Cannot comply.');
            } else {
                if (!$this->setMainContactId($oContact->getId())->save()) {
                    throw new \Exception('Unable to set contact '.$oContact->getId().' as main for '.$this->getId().': '.$this->getMessages(true));
                }
            }
        }
        return true;
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
     * Returns the value of field username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
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
     * Returns the value of field password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Returns the value of field created_by
     *
     * @return integer
     */
    public function getCreatedBy()
    {
        return $this->created_by;
    }

    /**
     * Returns the value of field type
     *
     * @return integer
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns the value of field password_reset_hash
     *
     * @return string
     */
    public function getPasswordResetHash()
    {
        return $this->password_reset_hash;
    }

    /**
     * Returns the value of field main_contact_id
     *
     * @return integer
     */
    public function getMainContactId()
    {
        return $this->main_contact_id;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->getFirstName() . ' ' . $this->getLastName();
    }

    /**
     * @param Users $oUser
     */
    public static function setCurrent(Users $oUser)
    {
        self::$oCurrent = $oUser;
    }

    /**
     * @return Users
     */
    public static function getCurrent()
    {
        return self::$oCurrent;
    }

    /**
     * @param $mRole
     * @return bool
     */
    public function hasSelfRole($mRole)
    {
        if (!is_string($mRole)) {
            /** @var UserRoles $mRole */
            $mRole = $mRole->getSlug();
        }
        return true;
    }


}