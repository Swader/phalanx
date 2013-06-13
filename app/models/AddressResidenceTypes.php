<?php

/**
 * Class AddressResidenceTypes
 */
class AddressResidenceTypes extends \Bitfalls\Phalcon\Model
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
    protected $slug;

    /**
     * @var string
     *
     */
    protected $name;

    /**
     * @var string
     *
     */
    protected $description;

    /**
     * @return array
     */
    public static function getCachedPairs()
    {
        $sKey = 'address_residence_types_pairs_slug_name';
        if (!apc_exists($sKey)) {
            $aAll = AddressResidenceTypes::find(array('order' => 'name ASC'));
            $aPairs = array();
            /** @var AddressResidenceTypes $oEntity */
            foreach ($aAll as $oEntity) {
                $aPairs[$oEntity->getSlug()] = $oEntity->getName();
            }
            apc_store($sKey, $aPairs);
        }
        return apc_fetch($sKey);
    }

    /**
     * @param null $data
     * @param null $whitelist
     * @return bool|void
     */
    public function save($data = null, $whitelist = null) {
        if (apc_exists('address_residence_types_pairs_slug_name')) {
            apc_delete('address_residence_types_pairs_slug_name');
        }
        parent::save($data, $whitelist);
        self::getCachedPairs();
    }

    public function initialize() {
        parent::initialize();

        $this->hasMany('slug', 'AddressBook', 'residence_type', array('alias' => 'addresses'));
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
     * @param $slug
     * @return $this
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * @param $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @param $description
     * @return $this
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
     * Returns the value of field slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Returns the value of field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the value of field description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

}
