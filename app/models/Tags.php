<?php

/**
 * Class Tags
 */
class Tags extends \Bitfalls\Phalcon\Model
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
    protected $tag;

    /**
     * @var integer
     *
     */
    protected $parent;

    /**
     * @var integer
     *
     */
    protected $tag_type;

    /**
     * @var string
     *
     */
    protected $description;

    public function initialize() {
        parent::initialize();

        $this->hasOne('tag_type', 'TagTypes', 'id', array('alias' => 'type'));
        $this->hasOne('parent', 'Tags', 'id', array('alias' => 'parent'));

        $this->hasMany('id', 'TagBind', 'tag', array('alias' => 'binds'));
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
     * @param $parent
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @param $tag_type
     * @return $this
     */
    public function setTagType($tag_type)
    {
        $this->tag_type = $tag_type;
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
     * Returns the value of field tag
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Returns the value of field parent
     *
     * @return integer
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns the value of field tag_type
     *
     * @return integer
     */
    public function getTagType()
    {
        return $this->tag_type;
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
