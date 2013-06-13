<?php

/**
 * Class Cities
 */
class Cities extends \Bitfalls\Phalcon\Model
{

    use \Bitfalls\Traits\Devlog;

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
    protected $clean_name;

    /**
     * @var float
     *
     */
    protected $latitude;

    /**
     * @var float
     *
     */
    protected $longitude;

    /**
     * @var integer
     *
     */
    protected $state_id;

    /**
     * @var integer
     *
     */
    protected $country_id;

    /**
     * @var string
     *
     */
    protected $zip;

    /** @var string */
    protected $alternativenames;

    /** @var string */
    protected $geonameid;

    /** @var array */
    protected $importErrors = array();
    /** @var array */
    protected $importSuccesses = array();

    public function initialize()
    {
        parent::initialize();

        $this->belongsTo('country_id', 'Countries', 'id', array('alias' => 'country'));
        $this->belongsTo('state_id', 'States', 'id', array('alias' => 'state'));

        $this->hasMany('id', 'AddressBook', 'city', array('alias' => 'addresses'));
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
     * @param Cities $oCity
     * @return int
     */
    public function distanceInKm(Cities $oCity)
    {
        return $this->distanceInKmToCoordinates($oCity->getLatitude(), $oCity->getLongitude());
    }

    /**
     * @param $fLatitude
     * @param $fLongitude
     * @return int
     */
    public function distanceInKmToCoordinates($fLatitude, $fLongitude)
    {
        return self::havershine($this->getLatitude(), $fLatitude, $this->getLongitude(), $fLongitude);
    }

    /**
     * @param $lat1
     * @param $lat2
     * @param $long1
     * @param $long2
     * @param string $unit
     * @return int
     */
    public static function havershine($lat1, $lat2, $long1, $long2, $unit = 'km')
    {
        $r = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($long2 - $long1);
        $lat1 = deg2rad($lat1);
        $lat2 = deg2rad($lat2);

        $a = sin($dLat / 2) * sin($dLat / 2) + sin($dLon / 2) * sin($dLon / 2) * cos($lat1) * cos($lat2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $d = $r * $c;

        switch ($unit) {
            case 'm':
                return $d * 1000;
                break;
            case 'mi':
                return $d * 0.621371;
                break;
            case 'ft':
                return $d * 3280.84;
                break;
            case 'km':
            default:
                return $d;
                break;
        }
    }

    /**
     * @param bool $bIgnoreExisting
     * @return $this
     * @throws Exception
     */
    public function import($bIgnoreExisting = true)
    {

        $this->log('Starting');
        set_time_limit(0);
        ini_set('memory_limit', '256M');

        $this->importErrors = array();
        $this->importSuccesses = array();

        $aFetchedCountries = array();
        $aFetchedStates = array();

        $sPath = 'http://download.geonames.org/export/dump/cities1000.zip';

        if (!class_exists('\ZipArchive')) {
            throw new \Exception('No ZipArchive class found, was php compiled with --enable-zip?');
        }

        $newfname = $this->getDI()->get('config')->application->cacheDir . 'citieszip.zip';

        if (!is_readable($newfname)) {
            $this->log('City zip not found, redownloading.');
            $file = @fopen($sPath, "rb");
            if ($file) {
                $newf = @fopen($newfname, "wb");
                if ($newf) {
                    while (!feof($file)) {
                        fwrite($newf, fread($file, 1024 * 8), 1024 * 8);
                    }
                    fclose($newf);
                }
                fclose($file);
            }
            $this->log('Redownload done');
        }

        // Country code is [8]
        // State code is [10]
        // Alternative names is [3]
        $aImportData = array(
            'Geonameid' => array('index' => 0),
            'Name' => array('index' => 1),
            'CleanName' => array('index' => 2),
            'Latitude' => array('index' => 4, 'cast' => 'float'),
            'Longitude' => array('index' => 5, 'cast' => 'float'),
        );

        if (is_readable($newfname)) {

            $sTxt = str_replace('citieszip.zip', 'cities1000.txt', $newfname);
            if (!is_readable($sTxt)) {
                $this->log('No extracted txt file found, extracting.');
                $zip = new ZipArchive();
                $zip->open($newfname);
                $zip->extractTo($this->getDI()->get('config')->application->cacheDir);
                $this->log('Extracting done.');
            }
            $unzipped = fopen($sTxt, 'r');
            $j = 0;

            $aGeonameIds = array();
            if ($bIgnoreExisting) {
                $aGeonameIds = $this->getDI()->get('geoService')->getGeonameIdsCities();
            }

            while (($line = fgets($unzipped))) {

                $j++;
                $i = $j - 1;

                if ($j % 1000 == 0) {
                    $this->log($i . ' entries processed');
                }

                $aCells = explode("\t", $line);
                $aCells[2] = (empty($aCells[2])) ? $aCells[1] : $aCells[2];

                if (!isset($aCells[0]) || empty($aCells[0])) {
                    $this->importErrors[$i] = array(
                        'line' => $line,
                        'reason' => 'Error on line ' . $i . ': GeonameID missing.'
                    );
                    continue;
                }

                $oCity = false;
                if ($bIgnoreExisting) {
                    if (in_array($aCells[0], $aGeonameIds)) {
                        continue;
                    }
                } else {
                    $oCity = Cities::findFirst(array(
                        'geonameid = :gn:',
                        'bind' => array('gn' => $aCells[0])
                    ));
                }

                $bNewCity = false;
                if (!$oCity) {
                    $oCity = new Cities();
                    $bNewCity = true;
                }

                try {

                    foreach ($aImportData as $k => &$a) {
                        $sMethod = 'set' . $k;
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
                        $oCity->$sMethod($aCells[$a['index']]);
                    }

                    if (!empty($aCells[3])) {
                        $aCells[3] = implode(', ', array_map(function ($el) {
                            return trim($el);
                        }, explode(',', $aCells[3])));
                    }
                    $oCity->setAlternativenames($aCells[3]);

                    if (!isset($aFetchedCountries[$aCells[8]])) {
                        /** @var Countries $oCountry */
                        $oCountry = Countries::findFirst(array(
                            'country_code = :cc:',
                            'bind' => array('cc' => $aCells[8])
                        ));
                        if (!$oCountry) {
                            $this->importErrors[$i] = array(
                                'line' => $line,
                                'reason' => 'Error on line ' . $i . ': Country could not be determined. Country code: ' . $aCells[8]
                            );
                            continue;
                        }
                        $aFetchedCountries[$aCells[8]] = $oCountry;
                    }
                    $oCountry = $aFetchedCountries[$aCells[8]];
                    $oCity->setCountryId($oCountry->getId());

                    if (is_numeric($aCells[10])) {
                        $aFetchedStates[$aCells[10]] = false;
                    } else if (!isset($aFetchedStates[$aCells[10]])) {
                        /** @var States $oState */
                        $oState = States::findFirst(array(
                            'short_name = :sn:',
                            'bind' => array('sn' => $aCells[10])
                        ));
                        if (!$oState && $aCells[8] == 'US') {
                            $oState = new States();
                            $oState->setShortName($aCells[10]);
                            $oState->setCountryId($aFetchedCountries['US']->getId());
                            $oState->save();
                        }
                        $aFetchedStates[$aCells[10]] = $oState;
                    }
                    $oState = $aFetchedStates[$aCells[10]];
                    $oCity->setStateId(($oState) ? $oState->getId() : null);

                    if ($oCity->save()) {
                        $this->importSuccesses[$i] = array(
                            'line' => $line,
                            'reason' => ($bNewCity) ? 'New city inserted!' : 'City saved.'
                        );
                    } else {
                        $this->importErrors[$i] = array(
                            'line' => $line,
                            'reason' => 'Error on line ' . $i . ': ' . implode(', ', $oCity->getMessages())
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
            fclose($unzipped);
        } else {
            throw new \Exception('Unable to read the downloaded zip file: ' . $newfname);
        }

        $this->log('All done');

        return $this;
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
     * @param $id
     * @return $this
     */
    public function setGeonameid($id)
    {
        $this->geonameid = $id;
        return $this;
    }

    /**
     * @param $names
     * @return $this
     */
    public function setAlternativenames($names)
    {
        $this->alternativenames = $names;
        return $this;
    }

    /**
     * @return string
     */
    public function getGeonameid()
    {
        return $this->geonameid;
    }

    /**
     * @return string
     */
    public function getAlternativenames()
    {
        return $this->alternativenames;
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
     * @param $clean_name
     * @return $this
     */
    public function setCleanName($clean_name)
    {
        $this->clean_name = $clean_name;
        return $this;
    }

    /**
     * @param $latitude
     * @return $this
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
        return $this;
    }

    /**
     * @param $longitude
     * @return $this
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
        return $this;
    }

    /**
     * @param $state_id
     * @return $this
     */
    public function setStateId($state_id)
    {
        $this->state_id = $state_id;
        return $this;
    }

    /**
     * @param $country_id
     * @return $this
     */
    public function setCountryId($country_id)
    {
        $this->country_id = $country_id;
        return $this;
    }

    /**
     * @param $zip
     * @return $this
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
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
     * Returns the value of field name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the value of field clean_name
     *
     * @return string
     */
    public function getCleanName()
    {
        return $this->clean_name;
    }

    /**
     * Returns the value of field latitude
     *
     * @return float
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Returns the value of field longitude
     *
     * @return float
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * Returns the value of field state_id
     *
     * @return integer
     */
    public function getStateId()
    {
        return $this->state_id;
    }

    /**
     * Returns the value of field country_id
     *
     * @return integer
     */
    public function getCountryId()
    {
        return $this->country_id;
    }

    /**
     * Returns the value of field zip
     *
     * @return string
     */
    public function getZip()
    {
        return $this->zip;
    }

}
