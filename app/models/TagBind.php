<?php

/**
 * Class TagBind
 */
class TagBind extends \Bitfalls\Phalcon\Model
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
    protected $tag;

    /**
     * @var string
     *
     */
    protected $entity_type;

    /**
     * @var string
     *
     */
    protected $entity_id;

    public function initialize() {
        parent::initialize();

        $this->belongsTo('tag', 'Tags', 'id');

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
     * @param $tag
     * @return $this
     */
    public function setTag($tag)
    {
        $this->tag = $tag;
        return $this;
    }

    /**
     * @param $entity_type
     * @return $this
     */
    public function setEntityType($entity_type)
    {
        $this->entity_type = $entity_type;
        return $this;
    }

    /**
     * @param $entity_id
     * @return $this
     */
    public function setEntityId($entity_id)
    {
        $this->entity_id = $entity_id;
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
     * Returns the value of field tag
     *
     * @return integer
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Returns the value of field entity_type
     *
     * @return string
     */
    public function getEntityType()
    {
        return $this->entity_type;
    }

    /**
     * Returns the value of field entity_id
     *
     * @return string
     */
    public function getEntityId()
    {
        return $this->entity_id;
    }

}
