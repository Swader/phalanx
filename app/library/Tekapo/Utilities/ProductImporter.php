<?php

namespace Tekapo\Utilities;

use Phalcon\Exception;
use Tags, Stores, Products, Packaging, Sku;

/**
 * Class ProductImporter
 * @package Tekapo\Utilities
 */
class ProductImporter
{
    const FILE_TYPE_XLS = 'xls';

    const SKIP_EXISTING = 1;
    const ALL_NEW = 2;

    /** @var array */
    private static $aAllowedFileTypes = array('xls');

    /** @var string */
    protected $sFilePath;

    /** @var string */
    protected $sFileType;

    /** @var array */
    protected $aErrors = array();

    /** @var array */
    protected $aSkipped = array();

    /** @var array */
    protected $aImports = array();

    /** @var array */
    protected $instances = array();

    /**
     * @param string $sFilePath
     * @param null $sType
     */
    public function __construct($sFilePath = '', $sType = null)
    {
        $this->setFileType($sType);
        $this->setFilePath($sFilePath);
    }

    /**
     * @param $sType
     * @return $this
     * @throws ProductImporterException
     */
    public function setFileType($sType)
    {
        if ($sType === null) {
            $sType = self::FILE_TYPE_XLS;
        }
        if (in_array($sType, self::$aAllowedFileTypes)) {
            $this->sFileType = $sType;
        } else {
            throw new ProductImporterException('File type ' . (string)$sType . ' not allowed.');
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getFileType()
    {
        return $this->sFileType;
    }

    /**
     * @param $sPath
     * @return $this
     */
    public function setFilePath($sPath)
    {
        $this->sFilePath = $sPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->sFilePath;
    }

    protected function initialize()
    {

        $aTagTypes = array('product class', 'product category', 'product group', 'product brand', 'product manufacturer');
        foreach ($aTagTypes as &$s) {
            $oType = \TagTypes::findFirst(array('type = :type:', 'bind' => array('type' => $s)));
            if (!$oType) {
                $oType = new \TagTypes();
                $oType->setType($s)->save();
            }
            $this->instances['tagtypes'][$s] = $oType;
        }

        $aEntities = array('product' => 'products', 'sku' => 'sku');
        foreach ($aEntities as $s => &$v) {
            $oEntity = \Entities::findFirst(array('entity = :entity:', 'bind' => array('entity' => $s)));
            if (!$oEntity) {
                $oEntity = new \Entities();
                $oEntity->setEntity($s)->setReferences($v)->save();
            }
            $this->instances['entities'][$s] = $oEntity;
        }

    }

    /**
     * @param int $iType
     * @return $this
     */
    public function import($iType = self::SKIP_EXISTING)
    {

        $this->aImports = array();
        $this->aErrors = array();
        $this->aSkipped = array();

        $this->initialize();

        // Cells list in XLS:
        /*
         * 0 product_class
         * 1 store_name
         * 2 product_category
         * 3 product_group
         * 4 sku_name
         * 5 sku_status
         * 6 sku_status_enddate
         * 7 product_brand
         * 8 product_manufacturer
         * 9 product_tekapo_size
         * 10 product_size_value
         * 11 product_size_metric
         * 12 product_count
         * 13 price_average8
         * 14 price_normal
         * 15 promo_status
         * 16 price_promo
         * 17 promo_price_enddate
         * 18 promocondition
         * 19 promocondition_enddate
         * 20 date_lastupdated
         * 21 packaging
         * 22 halal_certificate
         * 23 halal_status
         * 24 total_sugar
         * 25 total_sugar_comparison
         * 26 total_sodium
         * 27 total_sodium_comparison
         * 28 preservatives_status
         * 29 artificial_coloring_status
         * 30 artificial_flavoring_status
         * 31 food_conditioner_status
         * 32 artificial_sweetener_status
         * 33 photo_url
         * 34 comment
         * 35 tekapo_note
         * 36 pricegroup
         *
         */

        set_time_limit(0);

        $file = trim(file_get_contents($this->getFilePath()));
        if (!empty($file)) {
            $sSeparator = null;
            foreach (explode("\n", $file) as $i => $line) {
                if (!$sSeparator) {
                    $sSeparator = $this->determineSeparator($line);
                    //die($sSeparator);
                }
                $aCells = explode($sSeparator, $line);

                //var_dump($aCells);


                try {

                    // Check if it's even worth reading
                    if (!isset($aCells[4]) || empty($aCells[4]) || trim(strtolower($aCells['4'])) == 'sku_name') {
                        $this->aErrors[$i] = array('line' => $line, 'reason' => 'Missing sku_name');
                        continue;
                    }

                    $aProductTags = array();

                    // Determine product_class
                    $sClass = strtolower(trim($aCells[0]));
                    if (!empty($sClass)) {
                        if (isset($this->instances['tags'][$sClass])) {
                            $oClassTag = $this->instances['tags'][$sClass];
                        } else {
                            $oClassTag = Tags::find(array("tag = :tag:", 'bind' => array('tag' => $sClass)))->getFirst();
                            if (!$oClassTag) {
                                $oClassTag = new Tags();
                                $oClassTag->setTag($sClass);
                                $oClassTag->setTagType($this->instances['tagtypes']['product class']->getId());
                                if (!$oClassTag->save()) {
                                    $sMessages = $oClassTag->getMessages(true);
                                    $this->aErrors[$i] = array('line' => $line, 'reason' => $sMessages);
                                    continue;
                                }
                            }
                            $this->instances['tags'][$sClass] = $oClassTag;
                        }
                        $aProductTags[] = $oClassTag;
                    }

                    // Determine product_category
                    $sClass = strtolower(trim($aCells[2]));
                    if (!empty($sClass)) {
                        if (isset($this->instances['tags'][$sClass])) {
                            $oCategoryTag = $this->instances['tags'][$sClass];
                        } else {
                            $oCategoryTag = Tags::find(array("tag = :tag:", 'bind' => array('tag' => $sClass)))->getFirst();
                            if (!$oCategoryTag) {
                                $oCategoryTag = new Tags();
                                $oCategoryTag->setTag($sClass);
                                $oCategoryTag->setTagType($this->instances['tagtypes']['product category']->getId());
                                if (!$oCategoryTag->save()) {
                                    $sMessages = $oCategoryTag->getMessages(true);
                                    $this->aErrors[$i] = array('line' => $line, 'reason' => $sMessages);
                                    continue;
                                }
                            }
                            $this->instances['tags'][$sClass] = $oCategoryTag;
                        }
                        $aProductTags[] = $oCategoryTag;
                    }

                    // Determine product group
                    $sClass = strtolower(trim($aCells[3]));
                    if (!empty($sClass)) {
                        if (isset($this->instances['tags'][$sClass])) {
                            $oGroupTag = $this->instances['tags'][$sClass];
                        } else {
                            $oGroupTag = Tags::find(array("tag = :tag:", 'bind' => array('tag' => $sClass)))->getFirst();
                            if (!$oGroupTag) {
                                $oGroupTag = new Tags();
                                $oGroupTag->setTag($sClass);
                                $oGroupTag->setTagType($this->instances['tagtypes']['product group']->getId());
                                if (!$oGroupTag->save()) {
                                    $sMessages = $oGroupTag->getMessages(true);
                                    $this->aErrors[$i] = array('line' => $line, 'reason' => $sMessages);
                                    continue;
                                }
                            }
                            $this->instances['tags'][$sClass] = $oGroupTag;
                        }
                        $aProductTags[] = $oGroupTag;
                    }

                    // Determine product brand
                    $sClass = strtolower(trim($aCells[7]));
                    if (!empty($sClass)) {
                        if (isset($this->instances['tags'][$sClass])) {
                            $oBrandTag = $this->instances['tags'][$sClass];
                        } else {
                            $oBrandTag = Tags::find(array("tag = :tag:", 'bind' => array('tag' => $sClass)))->getFirst();
                            if (!$oBrandTag) {
                                $oBrandTag = new Tags();
                                $oBrandTag->setTag($sClass);
                                $oBrandTag->setTagType($this->instances['tagtypes']['product brand']->getId());
                                if (!$oBrandTag->save()) {
                                    $sMessages = $oBrandTag->getMessages(true);
                                    $this->aErrors[$i] = array('line' => $line, 'reason' => $sMessages);
                                    continue;
                                }
                            }
                            $this->instances['tags'][$sClass] = $oBrandTag;
                        }
                        $aProductTags[] = $oBrandTag;
                    }

                    // Determine product manufacturer
                    $sClass = strtolower(trim($aCells[8]));
                    if (!empty($sClass)) {
                        if (isset($this->instances['tags'][$sClass])) {
                            $oManufacturerTag = $this->instances['tags'][$sClass];
                        } else {
                            $oManufacturerTag = Tags::find(array("tag = :tag:", 'bind' => array('tag' => $sClass)))->getFirst();
                            if (!$oManufacturerTag) {
                                $oManufacturerTag = new Tags();
                                $oManufacturerTag->setTag($sClass);
                                $oManufacturerTag->setTagType($this->instances['tagtypes']['product manufacturer']->getId());
                                if (!$oManufacturerTag->save()) {
                                    $sMessages = $oManufacturerTag->getMessages(true);
                                    $this->aErrors[$i] = array('line' => $line, 'reason' => $sMessages);
                                    continue;
                                }
                            }
                            $this->instances['tags'][$sClass] = $oManufacturerTag;
                        }
                        $aProductTags[] = $oManufacturerTag;
                    }

                    // Check store
                    $sStore = strtolower(trim($aCells[1]));
                    if (isset($this->instances['stores'][$sClass])) {
                        $oStoreByName = $this->instances['stores'][$sStore];
                    } else {
                        /** @var $oStoreByName Stores */
                        $oStoreByName = Stores::find(array('store_name = :name:', 'bind' => array('name' => $sStore)))->getFirst();
                        if (!$oStoreByName) {
                            $this->aErrors[$i] = array(
                                'line' => $line,
                                'reason' => 'Store ' . $sStore . ' does not exist. Product not added. Add store first.'
                            );
                            continue;
                        }
                        $this->instances['stores'][$sStore] = $oStoreByName;
                    }

                    // Check packaging
                    $sPackaging = strtolower(trim($aCells[21]));
                    if (empty($sPackaging)) {
                        $sPackaging = 'n/a';
                        $sSanitized = 'n/a';
                    } else {
                        $sSanitized = $this->sanitize($sPackaging);
                    }
                    if (isset($this->instances['packaging'][$sSanitized])) {
                        $oPackaging = $this->instances['packaging'][$sSanitized];
                    } else {
                        $oPackaging = Packaging::find(array('slug = :slug:', 'bind' => array('slug' => $sSanitized)))->getFirst();
                        if (!$oPackaging) {
                            $oPackaging = new Packaging();
                            $oPackaging->setSlug($sSanitized);
                            $oPackaging->setName(($sPackaging == 'n/a') ? 'n/a' : ucfirst($sPackaging));
                            if (!$oPackaging->save()) {
                                $sMessages = 'Could not save packaging, skipping entire line. Errors: ' . $oPackaging->getMessages(true);
                                $this->aErrors[$i] = array('line' => $line, 'reason' => $sMessages);
                                continue;
                            }
                        }
                        $this->instances['packaging'][$sSanitized] = $oPackaging;
                    }

                    // Find out if product exists
                    $sSkuName = trim($aCells[4]);

                    if ($iType == self::ALL_NEW) {
                        $oProduct = false;
                    } else {
                        /** @var $oProduct Products */
                        $oProduct = Products::find(array('name = :name:', 'bind' => array('name' => $sSkuName)))->getFirst();
                    }
                    $bNewProduct = false;
                    if (!$oProduct) {
                        $oProduct = new Products();
                        $oProduct->setName($sSkuName);
                        $oProduct->setActive(1);
                        $oProduct->setFeatured(0);

                        if ($this->cellok($aCells[10])) {
                            $oProduct->setProductSizeValue((int)$aCells[10]);
                        }
                        if ($this->cellok($aCells[11])) {
                            $oProduct->setProductSizeMetric(strtolower($aCells[11]));
                        }
                        if ($this->cellok($aCells[9])) {
                            $oProduct->setTekapoSize($aCells[9]);
                        } else {
                            $oProduct->setTekapoSize('n/a');
                        }
                        if ((int)$this->cellok($aCells[12]) > 0) {
                            $oProduct->setProductCount((int)$this->cellok($aCells[12]));
                        }

                        if (isset($aCells[22]) && $this->cellok($aCells[22])) {
                            $oProduct->setHalalCertificate($this->cellok($aCells[22]));
                        } else {
                            $oProduct->setHalalCertificate('n/a');
                        }

                        if (isset($aCells[23]) && $this->cellok($aCells[23])) {
                            $oProduct->setHalalStatus(strtolower($this->cellok($aCells[23])));
                        } else {
                            $oProduct->setHalalStatus('n/a');
                        }

                        if (isset($aCells[28])) $oProduct->setPreservatives($this->yntoint($aCells[28]));
                        if (isset($aCells[31])) $oProduct->setFoodConditioner($this->yntoint($aCells[31]));
                        if (isset($aCells[29])) $oProduct->setArtificialColoring($this->yntoint($aCells[29]));
                        if (isset($aCells[30])) $oProduct->setArtificialFlavoring($this->yntoint($aCells[30]));
                        if (isset($aCells[32])) $oProduct->setArtificialSweetener($this->yntoint($aCells[32]));

                        if (!$oProduct->save()) {
                            $sMessages = 'Could not save product, skipping entire line. Errors: ' . $oProduct->getMessages(true);
                            $this->aErrors[$i] = array('line' => $line, 'reason' => $sMessages);
                            continue;
                        } else {
                            $bNewProduct = true;
                        }
                    }

                    // Add Tags to Product
                    $sTagsAttachedToProduct = '';
                    /** @var Tags $oTag */
                    foreach ($aProductTags as &$oTag) {
                        $oTagBind = \TagBind::findFirst(array(
                            'entity_type = :entity_type: AND entity_id = :entity_id: AND tag = :tag: ',
                            'bind' => array('entity_type' => 'product', 'entity_id' => $oProduct->getId(), 'tag' => $oTag->getId())));
                        if (!$oTagBind) {
                            $oTagBind = new \TagBind();
                            $oTagBind
                                ->setEntityType('product')
                                ->setEntityId($oProduct->getId())
                                ->setTag($oTag->getId());
                            if (!$oTagBind->save()) {
                                $sMessages = 'Could not save tagbind, skipping tag ' . $oTag->getTag() . ' on product ' . $oProduct->getId() . '. Errors: ' . $oTagBind->getMessages(true);
                                $this->aErrors[$i] = array('line' => $line, 'reason' => $sMessages);
                            } else {
                                $sTagsAttachedToProduct .= $oTag->getTag().' ('.$oTag->type->getType().'), ';
                            }
                        }
                    }
                    $sTagsAttachedToProduct = trim($sTagsAttachedToProduct, ', ');

                    // Find sku by store and product
                    $oSku = Sku::findFirst(array(
                        'store_id = :store_id: AND product_id = :product_id:',
                        'bind' => array('store_id' => $oStoreByName->getId(), 'product_id' => $oProduct->getId())
                    ));
                    if (!$oSku) {
                        $oSku = new Sku();
                        $oSku->setName($oProduct->getName());
                        $oSku->setStoreId($oStoreByName->getId());
                        if ($this->cellok($aCells[14])) {
                            $oSku->setPriceNormal((float)$aCells[14]);
                        }
                        if ($this->cellok($aCells[16])) {
                            $oSku->setPricePromo((float)$aCells[16]);
                        }
                        if ($this->cellok($aCells[17])) {
                            $oSku->setPricePromoEnddate(date('Y-m-d', strtotime($aCells[17])));
                        }
                        if ($this->cellok($aCells[18])) {
                            $oSku->setPromoCondition($aCells[18]);
                        }
                        if ($this->cellok($aCells[19])) {
                            $oSku->setPromoConditionEnddate(date('Y-m-d', strtotime($aCells[19])));
                        }
                        if ($this->cellok($aCells[5]) || $this->cellok($aCells[5] === null)) {
                            $oSku->setSkuActiveStatus($this->cellok(strtolower($aCells[5]) == 'n') ? 0 : 1);
                        }

                        if ($this->cellok($aCells[6])) {
                            $oSku->setSkuActiveStatusEnddate(date('Y-m-d', strtotime($aCells[6])));
                        }

                        $oSku->setPackaging($oPackaging->getSlug());

                        $oSku->setSkuPromoStatus($this->yntoint($aCells[15], 0));

                        $oSku->setProductId($oProduct->getId());

                        if (isset($aCells[35]) && $this->cellok($aCells[35])) {
                            $oSku->setAdminNote(trim($aCells[35]));
                        }

                        if (!$oSku->save()) {
                            $this->aErrors[$i] = array('line' => $line, 'reason' => $oSku->getMessages(true));
                            continue;
                        } else {
                            $sReason = 'Successfully imported SKU "' . $oSku->getName() . '" in store "' . $oStoreByName->getStoreName() . '"';
                            if ($bNewProduct) {
                                $sReason .= '. A parent product was created as well under the ID ' . $oProduct->getId() . '. ';
                                if (!empty($sTagsAttachedToProduct)) {
                                    $sReason .= 'The parent product was given the following tags: '.$sTagsAttachedToProduct;
                                }
                            }
                            $this->aImports[$i] = array(
                                'line' => $line,
                                'reason' => $sReason
                            );
                        }

                    } else {
                        $sReason = 'Skipping row, SKU already exists';
                        if (!empty($sTagsAttachedToProduct)) {
                            $sReason .= ', however, the parent product was given the following tags: '.$sTagsAttachedToProduct;
                        }
                        $this->aSkipped[$i] = array(
                            'line' => $line,
                            'reason' => $sReason
                        );
                    }

                } catch (ProductImporterException $e) {
                    $this->aErrors[$i] = array('line' => $line, 'reason' => 'ProductImporter Exception: ' . $e->getMessage(), 'stack' => $e->getTraceAsString());
                } catch (Exception $e) {
                    $this->aErrors[$i] = array('line' => $line, 'reason' => 'Phalcon Exception: ' . $e->getMessage(), 'stack' => $e->getTraceAsString());
                } catch (\Exception $e) {
                    $this->aErrors[$i] = array('line' => $line, 'reason' => $e->getMessage(), 'stack' => $e->getTraceAsString());
                }

            }

        }

        return $this;

    }

    /**
     * @return array
     */
    public function getImports()
    {
        return $this->aImports;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->aErrors;
    }

    /**
     * @return array
     */
    public function getSkipped()
    {
        return $this->aSkipped;
    }

    /**
     * @param $sLine
     * @return string
     */
    private function determineSeparator($sLine)
    {
        return (count(explode(",", $sLine)) > count(explode("\t", $sLine))) ? "," : "\t";
    }

    /**
     * @param $str
     * @return mixed
     */
    private function sanitize($str)
    {
        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", '_', $clean);
        return $clean;
    }

    /**
     * @param $val
     * @return bool|string
     */
    private function cellok($val)
    {
        $val = trim($val);
        if (!empty($val)) return $val;
        return false;
    }

    /**
     * @param $val
     * @param $def
     * @return int
     */
    private function yntoint($val, $def = null)
    {
        $val = strtolower(trim($val));
        switch ($val) {
            case 'n':
                return 0;
            case 'y';
                return 1;
            default:
                return $def;
        }
    }
}

/**
 * Class ProductImporterException
 * @package Tekapo\Utilities
 */
class ProductImporterException extends \Exception
{

}