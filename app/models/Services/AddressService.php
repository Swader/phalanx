<?php

namespace Services;

use Bitfalls\Phalcon\Abstracts\ServiceAbstract;
use Bitfalls\Objects\Result;
use Bitfalls\Traits\Devlog;
use Phalcon\Db;

/**
 * Class AddressService
 * @package Services
 */
class AddressService extends ServiceAbstract
{
    use Devlog;

    /** @var string */
    protected $sTable_Countries = '`address_book` `main`';

    /** @var string */
    protected $sTable_Cities = '`address_residence_types` `adr`';

    /**  */
    public function search($aSearchParams = array())
    {

    }

    /**
     * @param \AddressBook $oAddressEntry
     * @return bool
     * @throws \Exception
     */
    public function makeDefault(\AddressBook $oAddressEntry)
    {
        $this->getDb()->begin();
        /** @var \AddressBook $oAddress */
        foreach ($oAddressEntry->user->addresses as $oAddress) {
            if ($oAddress->getId() != $oAddressEntry->getId()) {
                if (!$oAddress->setIsDef(0)->save()) {
                    $this->getDb()->rollback();
                    throw new \Exception('Failed to save: ' . $oAddress->getMessages(true));
                }
            }
        }
        if (!$oAddressEntry->setIsDef(1)->save()) {
            $this->getDb()->rollback();
            throw new \Exception('Failed to set as default: ' . $oAddressEntry->getMessages(true));
        }
        return $this->getDb()->commit();
    }

}