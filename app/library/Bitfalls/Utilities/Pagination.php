<?php

namespace Bitfalls\Utilities;

use Bitfalls\Objects\Result;

/**
 * Class Pagination
 * @package Bitfalls\Utilities
 */
class Pagination
{

    const URL_MODE_QUERY = 0;
    const URL_MODE_PAIRS = 1;

    /** @var Result */
    protected $oInjectedResult;

    /** @var int */
    protected $iNumberOfPages;

    /** @var int */
    protected $iPageRange = 10;

    /** @var string */
    protected $sUrlPrefix;

    /** @var string */
    protected $sDivClass;

    /** @var string */
    protected $sPageKey = 'page';

    /** @var string */
    protected $sUrlSuffix;

    /** @var int */
    protected $iUrlMode;

    /**
     * Takes a result object as input and uses it to build pagination controls
     * @param Result $oResult
     */
    public function __construct(Result $oResult)
    {
        $this->oInjectedResult = $oResult;
    }

    /**
     * Calculates number of pages
     * @return int
     */
    public function getNumberOfPages()
    {
        if ($this->iNumberOfPages === null) {
            $iCount = $this->oInjectedResult->count();
            $iLimit = $this->oInjectedResult->getSearchParams()['limit'];
            if ($iCount == 0) {
                $this->iNumberOfPages = 0;
            } else {
                $this->iNumberOfPages = ($iCount % $iLimit != 0)
                    ? floor($iCount / $iLimit + 1)
                    : floor($iCount / $iLimit);
            }
        }
        return (int)$this->iNumberOfPages;
    }

    /**
     * @param int $iMode Mode can be URL_MODE_QUERY or URL_MODE_PAIRS, defaults to QUERY.
     * Query builds a URL like mysearch?q=term&someparam=somevalue&otherParam=otherValue... while PAIRS
     * builds a URL like mysearch/q/terms/someparam/somevalue/otherParam/otherValue ...
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function setUrlMode($iMode = self::URL_MODE_PAIRS)
    {
        $aAllowedUrlModes = array(
            self::URL_MODE_PAIRS,
            self::URL_MODE_QUERY
        );
        if (in_array($iMode, $aAllowedUrlModes)) {
            $this->iUrlMode = $iMode;
        } else {
            throw new \InvalidArgumentException('Mode not allowed.');
        }
        return $this;
    }

    /**
     * @return int
     */
    public function getUrlMode()
    {
        if ($this->iUrlMode === null) {
            $this->setUrlMode();
        }
        return $this->iUrlMode;
    }

    /**
     * The page key is the URL param that denotes the current page, i.e. myurl/myparam/myvalue/page/1
     * @param string $sKey
     * @return $this
     */
    public function setPageKey($sKey = 'page')
    {
        $this->sPageKey = (string)$sKey;
        return $this;
    }

    /**
     * @return int
     */
    public function getCurrentPage()
    {
        if (!isset($this->oInjectedResult->getSearchParams()['page'])) {
            return 1;
        } else {
            return (int)$this->oInjectedResult->getSearchParams()['page'];
        }
    }

    /**
     * @param int $iRange
     * @return $this
     */
    public function setPageRange($iRange = 10)
    {
        $this->iPageRange = (int)$iRange;
        return $this;
    }

    /**
     * @return int
     */
    public function getPageRange() {
        return $this->iPageRange;
    }

    /**
     * Sets the URL prefix for all links in the control
     * This is usually something like /controller/action
     *
     * Trailing slash is always stripped.
     *
     * @param string $sUrlPrefix
     *
     * @return $this
     */
    public function setUrlPrefix($sUrlPrefix)
    {
        $this->sUrlPrefix = rtrim($sUrlPrefix, '/');
        return $this;
    }

    /**
     * @return string
     */
    public function getUrlPrefix()
    {
        return $this->sUrlPrefix;
    }

    /**
     * Sets the class of the entire pagination control div
     *
     * @param string $sClass
     *
     * @return $this
     */
    public function setDivClass($sClass = 'pagination pagination-small')
    {
        $aClass = (array)$sClass;
        $aClass = array_map(function ($el) {
            return filter_var($el, FILTER_SANITIZE_STRING);
        }, $aClass);
        $this->sDivClass = implode(' ', $aClass);
        return $this;
    }

    /**
     * @return string
     */
    public function getDivClass()
    {
        if ($this->sDivClass === null) {
            $this->setDivClass();
        }
        return $this->sDivClass;
    }

    /**
     * @return string
     */
    public function getPageKey()
    {
        return $this->sPageKey;
    }

    /**
     * Sets the URL params for the pagination links.
     * The array needs to be a list of key => value pairs
     * and will be appended to the URL prefix in this way:
     * {$sUrlPrefix}/{$key}/{$value}...
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function getUrlSuffix()
    {
        if ($this->sUrlSuffix === null) {
            if ($this->getUrlMode() == self::URL_MODE_QUERY) {
                $sUrlParamString = '?';
                foreach ($this->oInjectedResult->getSearchParams() as $k => $v) {
                    if ($v !== null && $k != $this->getPageKey()) {
                        if (is_array($v)) {
                            $k = $k . htmlspecialchars('%5B%5D');
                            foreach ($v as $valEl) {
                                $sUrlParamString .= $k . '=' . $valEl . '&';
                            }
                        } else {
                            $sUrlParamString .= $k . '=' . $v . '&';
                        }
                    }
                }
            } else if ($this->getUrlMode() == self::URL_MODE_PAIRS) {
                $sUrlParamString = '/';
                foreach ($this->oInjectedResult->getSearchParams() as $k => $v) {
                    if ($v !== null && $k != $this->getPageKey()) {
                        if (is_array($v)) {
                            $k = $k . htmlspecialchars('%5B%5D');
                            foreach ($v as $valEl) {
                                $sUrlParamString .= $k . '/' . $valEl . '/';
                            }
                        } else {
                            $sUrlParamString .= $k . '/' . $v . '/';
                        }
                    }
                }
            } else {
                throw new \InvalidArgumentException('Invalid mode given.');
            }
            $this->sUrlSuffix = $sUrlParamString;
        }
        return $this->sUrlSuffix;
    }

    /**
     * Generates a link string that can be appended to the pagination control
     *
     * @param int $iPage
     * @param string $sTitle
     * @param string $sLabel
     * @param bool   $bFake
     * @param string $sClass
     *
     * @return string
     */
    protected function appendLink($iPage, $sTitle, $sLabel, $bFake = false, $sClass = 'overlayTrigger')
    {
        if ($bFake) {
            return '<li><span class="disabled">' . $sLabel . '</span></li>';
        } else {
            switch ($this->getUrlMode()) {
                case self::URL_MODE_QUERY:
                    $sPageFragment = 'page=' . $iPage;
                    break;
                default:
                case self::URL_MODE_PAIRS:
                    $sPageFragment = 'page/' . $iPage;
                    break;
            }
            return '<li class="' . $sClass . '"><a
            href="' . $this->getUrlPrefix() . $this->getUrlSuffix() . $sPageFragment . '"
            title="' . $sTitle . '">' . $sLabel . '</a></li>';
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        $sOutput = '';
        if ($this->getNumberOfPages()) {
            $sOutput .= '<div class="' . $this->getDivClass() . '"><ul>';

            // First and Previous Link
            $sOutput .= $this->appendLink(1, 'First Page', '<< First', !($this->getCurrentPage() > 1));
            $sOutput .= $this->appendLink($this->getCurrentPage() - 1, 'Previous Page', '< Prev', !($this->getCurrentPage() > 1));

            // Middle Numeric Links
            $iRange = ($this->getNumberOfPages() > $this->getPageRange()) ? $this->getPageRange() : $this->getNumberOfPages();
            if ($this->getCurrentPage() <= $this->getPageRange() / 2) {
                for ($i = 1; $i <= $iRange; $i++) {
                    $sOutput .= $this->appendLink($i, 'Page ' . $i, $i, false, ($this->getCurrentPage() == $i) ? 'active' : '');
                }
            } elseif (($this->getCurrentPage() >= $this->getNumberOfPages() - $this->getPageRange() / 2)) {
                for ($i = 0; $i < $iRange; $i++) {
                    $iPageNumber = $this->getNumberOfPages() - ($iRange - ($i + 1));
                    $sOutput .= $this->appendLink($iPageNumber, 'Page '.$iPageNumber, $iPageNumber, false, ($this->getCurrentPage() == $iPageNumber) ? 'disabled' : '');
                }
            } else {
                for ($i = -$iRange / 2; $i < $iRange / 2; $i++) {
                    $iPageNumber = $this->getCurrentPage()+$i;
                    $sOutput .= $this->appendLink($iPageNumber, 'Page '.$iPageNumber, $iPageNumber, false, ($this->getCurrentPage() == $iPageNumber) ? 'disabled' : '');
                }
            }

            // Next and Last Link
            $sOutput .= $this->appendLink($this->getCurrentPage() + 1, 'Next Page', 'Next >', !($this->getCurrentPage() < $this->getNumberOfPages()));
            $sOutput .= $this->appendLink($this->getNumberOfPages(), 'Last Page', 'Last >>', !($this->getCurrentPage() < $this->getNumberOfPages()));

            $sOutput .= '</ul></div>';
        }
        return $sOutput;
    }

}