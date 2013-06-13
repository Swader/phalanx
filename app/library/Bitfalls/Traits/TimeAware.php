<?php
namespace Bitfalls\Traits;

/**
 * Class TimeAware
 */
trait TimeAware
{
    use Dates;

    /** @var string */
    protected $sDateFormat = 'Y-m-d H:i:s';
    /** @var array */
    protected $aUpdatedStrings = array(
        'method' => 'setLastModifiedOn',
        'property' => 'last_modified_on'
    );
    /** @var array */
    protected $aCreatedStrings = array(
        'method' => 'setCreatedOn',
        'property' => 'created_on'
    );

    /**
     * @return $this
     */
    public function beforeValidationOnUpdate()
    {
        return $this->beTimeAware(
            $this->aUpdatedStrings['method'],
            $this->aUpdatedStrings['property']
        );
    }

    /**
     * @return $this
     */
    public function beforeValidationOnCreate()
    {
        return $this->beTimeAware(
            $this->aCreatedStrings['method'],
            $this->aCreatedStrings['property']
        )->beforeValidationOnUpdate();
    }

    /**
     * @param $sMethod
     * @param $sProperty
     * @return $this
     */
    protected function beTimeAware($sMethod, $sProperty)
    {
        $sDate = date($this->sDateFormat);
        if (method_exists($this, $sMethod)) {
            return $this->$sMethod($sDate);
        } else if (property_exists(__CLASS__, $sProperty)) {
            $this->$sProperty = $sDate;
            return $this;
        }
        return $this;
    }

    /**
     * @param bool $bReadable
     * @param null $sDateFormat
     * @return string
     */
    public function getCreatedOn($bReadable = false, $sDateFormat = null)
    {
        if (property_exists(__CLASS__, 'created_on')) {
            if ($bReadable) {
                if ($sDateFormat) {
                    return $this->dateTo($this->created_on, $sDateFormat);
                } else {
                    return $this->dateToReadable($this->created_on);
                }
            } else {
                return $this->created_on;
            }
        }
        return null;
    }

    /**
     * @param bool $bReadable
     * @param null $sDateFormat
     * @return string
     */
    public function getLastModifiedOn($bReadable = false, $sDateFormat = null)
    {
        if (property_exists(__CLASS__, 'last_modified_on')) {
            if ($bReadable) {
                if ($sDateFormat) {
                    return $this->dateTo($this->last_modified_on, $sDateFormat);
                } else {
                    return $this->dateToReadable($this->last_modified_on);
                }
            } else {
                return $this->last_modified_on;
            }
        }
        return null;
    }

    /**
     * @param bool $bReadable
     * @param null $sDateFormat
     * @return string
     */
    public function getActivatedOn($bReadable = false, $sDateFormat = null)
    {
        if (property_exists(__CLASS__, 'activated_on')) {
            if ($bReadable) {
                if ($sDateFormat) {
                    return $this->dateTo($this->activated_on, $sDateFormat);
                } else {
                    return $this->dateToReadable($this->activated_on);
                }
            } else {
                return $this->activated_on;
            }
        }
        return null;
    }

}