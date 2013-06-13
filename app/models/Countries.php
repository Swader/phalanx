<?php

/**
 * Class Countries
 */
class Countries extends \Bitfalls\Phalcon\Model
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
    protected $country_code;

    /**
     * @var string
     *
     */
    protected $country_name;

    /**
     * @var string
     *
     */
    protected $iso_numeric;

    /**
     * @var string
     *
     */
    protected $iso_alpha3;

    /**
     * @var string
     *
     */
    protected $fips;

    /**
     * @var string
     *
     */
    protected $continent;

    /**
     * @var integer
     *
     */
    protected $phone_code;

    /**
     * @var string
     *
     */
    protected $tld;

    /**
     * @var string
     *
     */
    protected $currency_code;

    /**
     * @var string
     *
     */
    protected $currency_name;

    /**
     * @var string
     *
     */
    protected $postal_format;

    /**
     * @var string
     *
     */
    protected $postal_regex;

    /**
     * @var integer
     *
     */
    protected $geonameid;

    /**
     * @var string
     *
     */
    protected $neighbours;

    /** @var array */
    protected $importErrors = array();

    /** @var array */
    protected $importSuccesses = array();

    /**
     * @return array
     */
    public static function getCachedPairs()
    {
        $sKey = 'countries_pairs_id_name';
        if (!apc_exists($sKey)) {
            $aAll = Countries::find(array('order' => 'country_name ASC'));
            $aPairs = array();
            /** @var Countries $oCountry */
            foreach ($aAll as $oCountry) {
                $aPairs[$oCountry->getId()] = $oCountry->getCountryName();
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
        if (apc_exists('countries_pairs_id_name')) {
            apc_delete('countries_pairs_id_name');
        }
        parent::save($data, $whitelist);
        self::getCachedPairs();
    }

    /**
     * @param null $sPath
     * @return $this
     */
    public function import($sPath = null)
    {
        $this->importErrors = array();
        $this->importSuccesses = array();

        set_time_limit(0);
        ini_set('memory_limit','256M');


        if ($sPath === null) {
            $sPath = 'http://download.geonames.org/export/dump/countryInfo.txt';
        }
        $sContents = file_get_contents($sPath);
        if (!empty($sContents)) {

            $aImportData = array(
                'IsoAlpha3' => array('index' => 1),
                'IsoNumeric' => array('index' => 2),
                'Fips' => array('index' => 3),
                'CountryName' => array('index' => 4),
                'Continent' => array('index' => 8),
                'Tld' => array('index' => 9),
                'CurrencyCode' => array('index' => 10),
                'CurrencyName' => array('index' => 11),
                'PhoneCode' => array('index' => 12),
                'PostalFormat' => array('index' => 13),
                'PostalRegex' => array('index' => 14),
                'Geonameid' => array('index' => 16),
                'Neighbours' => array('index' => 17)
            );

            foreach (explode("\n", $sContents) as $i => $line) {

                $line = trim($line);
                $aCells = explode("\t", $line);
                try {

                    if (
                        substr($line, 0, 1) == '#'
                        || empty($line)
                        || empty($aCells)
                    ) {
                        continue;
                    }

                    /**
                     * FIELDS
                     *
                     * 0 => country_code
                     * 1 => iso_alpha3
                     * 2 => iso_numeric
                     * 3 => fips
                     * 4 => country_name
                     * 8 => continent
                     * 9 => tld
                     * 10 => currency_code
                     * 11 => currency_name
                     * 12 => phone_code
                     * 13 => postal_format
                     * 14 => postal_regex
                     * 16 => geonameid
                     * 17 => neighbours
                     */

                    if (
                        !isset($aCells[0])
                        || !isset($aCells[4])
                    ) {
                        $this->importErrors[$i] = array(
                            'line' => $line,
                            'reason' => 'Error on line ' . $i . ': One of the following was missing: country code, country name'
                        );
                        continue;
                    }

                    $oCountry = self::findFirst(array('country_code = :cc:', 'bind' => array('cc' => $aCells[0])));
                    $bNewCountry = false;
                    if (!$oCountry) {
                        $oCountry = new self();
                        $oCountry->setCountryCode($aCells[0]);
                        $bNewCountry = true;
                    }

                    for ($j = 1; $j < 18; $j++) {
                        if (!isset($aCells[$j]) || empty($aCells[$j])) {
                            $aCells[$j] = null;
                        }
                    }

                    foreach ($aImportData as $k => &$a) {
                        $sMethod = 'set' . $k;
                        $oCountry->$sMethod($aCells[$a['index']]);

                        if (
                            !isset($a['longest'])
                            || ($a['longest'] < strlen($aCells[$a['index']]))
                        ) {
                            $a['longest'] = strlen($aCells[$a['index']]);
                        }

                        if (!isset($a['types'])) {
                            $a['types'] = array();
                        }

                        if (is_numeric($aCells[$a['index']]) && !in_array('numeric', $a['types'])) {
                            $a['types'][] = 'numeric';
                        } else if (is_string($aCells[$a['index']]) && !in_array('string', $a['types'])) {
                            $a['types'][] = 'string';
                        }

                    }
                    $oCountry->setIsoAlpha3($aCells[1]);
                    $oCountry->setIsoNumeric($aCells[2]);
                    $oCountry->setFips($aCells[3]);
                    $oCountry->setCountryName($aCells[4]);
                    $oCountry->setContinent($aCells[8]);
                    $oCountry->setTld($aCells[9]);
                    $oCountry->setCurrencyCode($aCells[10]);
                    $oCountry->setCurrencyName($aCells[11]);
                    $oCountry->setPhoneCode($aCells[12]);
                    $oCountry->setPostalFormat($aCells[13]);
                    $oCountry->setPostalRegex($aCells[14]);
                    $oCountry->setGeonameid($aCells[16]);

                    if (!empty($aCells[17])) {
                        $aCells[17] = implode(', ', array_map(function ($el) {
                            return trim($el);
                        }, explode(',', $aCells[17])));
                    }
                    $oCountry->setNeighbours($aCells[17]);


                    if ($oCountry->save()) {
                        $this->importSuccesses[$i] = array(
                            'line' => $line,
                            'reason' => ($bNewCountry) ? 'New country inserted!' : 'Country saved.'
                        );
                    } else {
                        $this->importErrors[$i] = array(
                            'line' => $line,
                            'reason' => 'Error on line ' . $i . ': ' . implode(', ', $oCountry->getMessages())
                        );
                    }
                } catch (\Exception $e) {
                    $this->importErrors[$i] = array(
                        'line' => $line,
                        'reason' => $e->getMessage(),
                        'stack' => $e->getTraceAsString()
                    );
                }
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->importErrors;
    }

    /**
     * @return array
     */
    public function getSuccesses()
    {
        return $this->importSuccesses;
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
     * @param $country_code
     * @return $this
     */
    public function setCountryCode($country_code)
    {
        $this->country_code = $country_code;
        return $this;
    }

    /**
     * @param $country_name
     * @return $this
     */
    public function setCountryName($country_name)
    {
        $this->country_name = $country_name;
        return $this;
    }

    /**
     * @param $iso_numeric
     * @return $this
     */
    public function setIsoNumeric($iso_numeric)
    {
        $this->iso_numeric = $iso_numeric;
        return $this;
    }

    /**
     * @param $iso_alpha3
     * @return $this
     */
    public function setIsoAlpha3($iso_alpha3)
    {
        $this->iso_alpha3 = $iso_alpha3;
        return $this;
    }

    /**
     * @param $fips
     * @return $this
     */
    public function setFips($fips)
    {
        $this->fips = $fips;
        return $this;
    }

    /**
     * @param $continent
     * @return $this
     */
    public function setContinent($continent)
    {
        $this->continent = $continent;
        return $this;
    }

    /**
     * @param $phone_code
     * @return $this
     */
    public function setPhoneCode($phone_code)
    {
        $this->phone_code = $phone_code;
        return $this;
    }

    /**
     * @param $tld
     * @return $this
     */
    public function setTld($tld)
    {
        $this->tld = $tld;
        return $this;
    }

    /**
     * @param $currency_code
     * @return $this
     */
    public function setCurrencyCode($currency_code)
    {
        $this->currency_code = $currency_code;
        return $this;
    }

    /**
     * @param $currency_name
     * @return $this
     */
    public function setCurrencyName($currency_name)
    {
        $this->currency_name = $currency_name;
        return $this;
    }

    /**
     * @param $postal_format
     * @return $this
     */
    public function setPostalFormat($postal_format)
    {
        $this->postal_format = $postal_format;
        return $this;
    }

    /**
     * @param $postal_regex
     * @return $this
     */
    public function setPostalRegex($postal_regex)
    {
        $this->postal_regex = $postal_regex;
        return $this;
    }

    /**
     * @param $geonameid
     * @return $this
     */
    public function setGeonameid($geonameid)
    {
        $this->geonameid = $geonameid;
        return $this;
    }

    /**
     * @param $neighbours
     * @return $this
     */
    public function setNeighbours($neighbours)
    {
        $this->neighbours = $neighbours;
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
     * Returns the value of field country_code
     *
     * @return string
     */
    public function getCountryCode()
    {
        return $this->country_code;
    }

    /**
     * Returns the value of field country_name
     *
     * @return string
     */
    public function getCountryName()
    {
        return $this->country_name;
    }

    /**
     * Returns the value of field iso_numeric
     *
     * @return string
     */
    public function getIsoNumeric()
    {
        return $this->iso_numeric;
    }

    /**
     * Returns the value of field iso_alpha3
     *
     * @return string
     */
    public function getIsoAlpha3()
    {
        return $this->iso_alpha3;
    }

    /**
     * Returns the value of field fips
     *
     * @return string
     */
    public function getFips()
    {
        return $this->fips;
    }

    /**
     * Returns the value of field continent
     *
     * @return string
     */
    public function getContinent()
    {
        return $this->continent;
    }

    /**
     * Returns the value of field phone_code
     *
     * @return integer
     */
    public function getPhoneCode()
    {
        return $this->phone_code;
    }

    /**
     * Returns the value of field tld
     *
     * @return string
     */
    public function getTld()
    {
        return $this->tld;
    }

    /**
     * Returns the value of field currency_code
     *
     * @return string
     */
    public function getCurrencyCode()
    {
        return $this->currency_code;
    }

    /**
     * Returns the value of field currency_name
     *
     * @return string
     */
    public function getCurrencyName()
    {
        return $this->currency_name;
    }

    /**
     * Returns the value of field postal_format
     *
     * @return string
     */
    public function getPostalFormat()
    {
        return $this->postal_format;
    }

    /**
     * Returns the value of field postal_regex
     *
     * @return string
     */
    public function getPostalRegex()
    {
        return $this->postal_regex;
    }

    /**
     * Returns the value of field geonameid
     *
     * @return integer
     */
    public function getGeonameid()
    {
        return $this->geonameid;
    }

    /**
     * Returns the value of field neighbours
     *
     * @return string
     */
    public function getNeighbours()
    {
        return $this->neighbours;
    }

}
