<?php

/**
 * Class Entities
 */
class Entities extends \Bitfalls\Phalcon\Model
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     *
     */
    protected $entity;

    /**
     * @var string
     *
     */
    protected $references;

    /**
     * @var string
     *
     */
    protected $description;

    public function initialize() {
        parent::initialize();

        $this->hasMany('entity', 'TagBind', 'entity_type', array('alias' => 'binds'));
    }

    /**
     * @param $id
     * @return $this
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * @param $entity
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;
        return $this;
    }

    /**
     * @param $references
     * @return $this
     */
    public function setReferences($references)
    {
        $this->references = $references;
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
     * @return int
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Returns the value of field entity
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Returns the value of field references
     *
     * @return string
     */
    public function getReferences()
    {
        return $this->references;
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
