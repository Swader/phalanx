<?php

namespace Bitfalls\Objects;

/**
 * Class Result
 * @package Bitfalls\Objects
 */
class Result
    implements \Iterator
{

    /** @var int */
    protected $iCount;

    /** @var \ArrayIterator */
    protected $oDataIterator;

    /**
     * @var array
     */
    protected $aSearchParams;

    /**
     * Consumes data, turns the data into an array and stores it.
     * This data can then be iterated.
     *
     * If count is provided, a custom count is set (for pagination
     * purposes). Otherwise, the length of the data property is
     * taken as the count value.
     *
     * @throws ResultException
     *
     * @param mixed $aData
     * @param int   $iCount
     */
    public function __construct($aData, $iCount = null)
    {
        if ($iCount && !is_numeric($iCount)) {
            throw new ResultException('Count must be numeric!');
        }
        if (!is_array($aData)) {
            $aData = array($aData);
        }
        $this->oDataIterator = new \ArrayIterator($aData);

        if ($aData === false) {
            $this->iCount = 0;
        } else {
            $this->iCount = ($iCount) ? (int)$iCount : $this->oDataIterator->count();
        }

        $this->setSearchParams(array());
    }

    /**
     * Useful for single-result boolean checks, whether or not an operation was
     * successful
     * @return bool
     */
    public function toBool()
    {
        $aData = $this->getData();
        if (is_array($aData)) {
            if (isset($aData[0])) {
                return (bool)$aData[0];
            } else {
                return (bool)$aData;
            }
        }
        return false;
    }

    /**
     * Returns the first element of the result set.
     * @return array|null
     */
    public function getFirst()
    {
        $aData = $this->getData();
        if (is_array($aData)) {
            if (isset($aData[0])) {
                return $aData[0];
            } else {
                return $aData;
            }
        }
        return null;
    }

    /**
     * Magic get method for retrieving properties
     *
     * @param string $sName
     *
     * @return mixed
     * @throws ResultException
     */
    public function __get($sName)
    {
        $sMethodName = 'get' . ucfirst($sName);
        if (method_exists($this, $sMethodName)) {
            return $this->$sMethodName();
        }
        throw new ResultException('Illegal property ' . $sName . '!');
    }

    /**
     * Magic method for setting miscellaneous properties.
     * Properties 'data', 'oIterator', 'iCount', 'count', 'query' and 'oQuery'
     * are not permitted.
     *
     * @param string $sName
     * @param mixed  $mValue
     */
    public function __set($sName, $mValue)
    {
        if (!in_array($sName, array('data', 'oIterator', 'iCount', 'count', 'query', 'oQuery'))) {
            $this->$sName = $mValue;
        }
    }

    /**
     * Returns the data contained in the Result
     *
     * @return array
     */
    public function getData()
    {
        return $this->oDataIterator->getArrayCopy();
    }

    /**
     * Returns the current iterator key
     * @return mixed
     */
    public function key()
    {
        return $this->oDataIterator->key();
    }

    /**
     * Returns whether the current iterator element is valid
     * @return bool
     */
    public function valid()
    {
        return $this->oDataIterator->valid();
    }

    /**
     * @see \Iterator::next()
     */
    public function next()
    {
        $this->oDataIterator->next();
    }

    /**
     * Returns the current element of the iterator
     * @return mixed
     */
    public function current()
    {
        return $this->oDataIterator->current();
    }

    /**
     * @see \Iterator::rewind()
     */
    public function rewind()
    {
        $this->oDataIterator->rewind();
    }

    /**
     * Returns the total count of elements in the iterator
     * @return int
     */
    public function count()
    {
        return (int)$this->iCount;
    }

    /**
     * Used when using echo on the Result object.
     * It will try to fetch the first element of it's data member
     * and return in string form it if it is a string or number.
     *
     * @return string
     * @throws ResultException
     */
    public function __toString()
    {
        if ($this->count() > 1) {
            throw new ResultException('Usage as string only possible when result does not contain more than one element');
        }
        if ($this->count() == 0) {
            return '';
        }
        $aData = $this->getData();
        if (isset($aData[0]) && (is_string($aData[0]) || is_numeric($aData[0]))) {
            return (string)$aData[0];
        } else {
            throw new ResultException('Can not produce output, because data element does not give primitive value');
        }
    }

    /**
     * Set Search Params
     *
     * @param array $aSearchParams
     *
     * @return Result
     */
    public function setSearchParams(array $aSearchParams)
    {
        $this->aSearchParams = $aSearchParams;
        return $this;
    }

    /**
     * Get Search Params
     *
     * @return array
     */
    public function getSearchParams()
    {
        return $this->aSearchParams;
    }

    /**
     * @param $aData
     * @return $this
     */
    public function setData($aData) {
        $this->oDataIterator = new \ArrayIterator($aData);
        return $this;
    }
}

/**
 * Class ResultException
 * @package Bitfalls\Objects
 */
class ResultException extends \Exception
{

}