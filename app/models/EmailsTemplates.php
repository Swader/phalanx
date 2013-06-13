<?php

/**
 * Class EmailsTemplates
 */
class EmailsTemplates extends \Bitfalls\Phalcon\Model
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
    protected $name;

    /**
     * @var string
     *
     */
    protected $slug;

    /**
     * @var string
     *
     */
    protected $subject;

    /**
     * @var string
     *
     */
    protected $body;

    /**
     * @var string
     *
     */
    protected $body_html;

    /**
     * @var string
     *
     */
    protected $template_info;


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
     * @param $sSlug
     * @return \Phalcon\Mvc\Model
     */
    public static function findBySlug($sSlug)
    {
        return self::findFirst(array('slug = :slug:', 'bind' => array('slug' => $sSlug)));
    }

    /**
     * Method to set the value of field name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Method to set the value of field slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = \Bitfalls\Utilities\Stringer::cleanString($slug);
    }

    /**
     * Method to set the value of field subject
     *
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * Method to set the value of field body
     *
     * @param string $body
     */
    public function setBody($body)
    {
        $this->body = $body;
    }

    /**
     * Method to set the value of field body_html
     *
     * @param string $body_html
     */
    public function setBodyHtml($body_html)
    {
        $this->body_html = $body_html;
    }

    /**
     * Method to set the value of field template_info
     *
     * @param string $template_info
     */
    public function setTemplateInfo($template_info)
    {
        $this->template_info = $template_info;
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
     * Returns the value of field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
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
     * Returns the value of field subject
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Returns the value of field body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Returns the value of field body_html
     *
     * @return string
     */
    public function getBodyHtml()
    {
        return $this->body_html;
    }

    /**
     * Returns the value of field template_info
     *
     * @return string
     */
    public function getTemplateInfo()
    {
        return $this->template_info;
    }

}
