<?php

namespace Services;

use Bitfalls\Mailer\MailRepository;
use Bitfalls\Phalcon\Abstracts\ServiceAbstract;
use Bitfalls\Objects\Result;
use Bitfalls\Mailer\BitfallsMessage;
use Bitfalls\Traits\Dates;
use Bitfalls\Exceptions\MailerException;

/**
 * Class MailerService
 * @package Services
 */
class MailerService extends ServiceAbstract implements MailRepository
{
    use Dates;

    /** @var string */
    protected $sTable_Main = '`emails_templates` `main`';

    /** @var string */
    protected $sTable_Queue = '`emails_queue`';

    /**
     * @param $aSearchParams
     * @return Result
     */
    public function search($aSearchParams)
    {

        $aBind = array();
        $sQuery = 'SELECT %s FROM ' . $this->sTable_Main . '
        WHERE 1 ';

        if (isset($aSearchParams['id'])) {
            $sQuery .= ' AND (`main`.`id` = :id) ';
            $aBind['id'] = $aSearchParams['id'];
        }

        if (isset($aSearchParams['q'])) {
            $q = $aSearchParams['q'];
            // @todo Test perf. concat(field1, field2) LIKE vs regular multi-field LIKE
            $sQuery .= ' AND (
                `main`.`slug` LIKE :q
                OR `main`.`name` LIKE :q
                OR `main`.`subject` LIKE :q
                ) ';
            $aBind['q'] = '%' . $q . '%';
        }

        $oResult = $this->fetchPaginatedResult($sQuery, $aBind, $aSearchParams);

        return $oResult;

    }

    /**
     * @param BitfallsMessage $oMessage
     * @return bool|mixed
     */
    public function getSavedUnsentMessageExists(BitfallsMessage $oMessage)
    {
        $aRow = $this->getDb()->fetchOne(
            'SELECT `id` FROM ' . $this->sTable_Queue . ' WHERE `sent` = 0 AND `blobhash` = "' . $oMessage->calculateBlobhash() . '"'
        );
        return (isset($aRow['id']) && $aRow['id'] > 0);
    }

    /**
     * Saves an email message object into the Database for later sending
     * Returns database ID of the generated row entry
     *
     * @param BitfallsMessage $oMessage
     * @param null           $sDate
     * @param int            $iPriority
     *
     * @return int ID of the database entry created
     * @throws MailerException
     *
     */
    function queueEmail(BitfallsMessage $oMessage, $sDate = null, $iPriority = 0)
    {

        $oEntry = new \EmailsQueue();
        $oEntry
            ->setSerializedRecipient(serialize($oMessage->getTo()))
            ->setSerializedSender(serialize($oMessage->getFrom()))
            ->setPriority($iPriority)
            ->setToBeSentOn(($sDate) ? date('Y-m-d H:i:s', strtotime($sDate)) : null)
            ->setEmailObject(base64_encode(serialize($oMessage)))
            ->setBlobhash($oMessage->calculateBlobhash())
            ->setSentOn(null)
            ->setSent(0)
            ->setHeaders($oMessage->getHeaders()->toString())
            ->setSlug($oMessage->getSlug())
            ->setCreatedOn(date('Y-m-d H:i:s'));

        if (!$oEntry->save()) {
            throw new MailerException('MailerService error: Could not queue email: ' . implode(', ', $oEntry->getMessages()));
        } else {
            return $oEntry->getId();
        }
    }

    /**
     * @param $mInput
     * @return mixed|void
     * @throws MailerException
     */
    public function markAsSent($mInput)
    {
        $oQueuedEmail = $this->inputToModel($mInput);
        if (!$oQueuedEmail) {
            throw new MailerException('Cannot mark invalid email as SENT');
        } else {
            /** @var \EmailsQueue $oQueuedEmail */
            $oQueuedEmail
                ->setSent(1)
                ->setSentOn($this->dateToMysqlFull())
                ->setToBeSentOn(null)
                ->save();
        }
    }

    /**
     * @param $mInput
     * @return bool|\EmailsQueue|\Phalcon\Mvc\Model
     */
    protected function inputToModel($mInput)
    {
        $oQE = new \EmailsQueue();
        if (is_numeric($mInput)) {
            $oQueuedEmail = $oQE->findFirst(array('id = :id:', 'bind' => array('id' => (int)$mInput)));
        } else if ($mInput instanceof BitfallsMessage) {
            $oQueuedEmail = $oQE->findFirst(array(
                'blobhash = :blobhash:',
                'bind' => array('blobhash' => $mInput->calculateBlobhash()),
                'order' => 'id DESC'
            ));
        } else if ($mInput instanceof \EmailsQueue && $mInput->getId()) {
            $oQueuedEmail = $mInput;
        }
        if (!isset($oQueuedEmail) || !$oQueuedEmail) {
            return false;
        }
        return $oQueuedEmail;
    }

    /**
     * @param int $mInput
     * @return bool
     * @throws MailerException
     */
    function deleteEmail($mInput)
    {
        $oQueuedEmail = $this->inputToModel($mInput);
        if ($oQueuedEmail) {
            return $oQueuedEmail->delete();
        } else {
            throw new MailerException('Matching queued email not found. Unable to un-queue.');
        }
    }

    /**
     * @param $aSearchParams
     * @return Result|mixed
     */
    function searchArchiveQueue($aSearchParams)
    {

        if (isset($aSearchParams['fields'])) {
            $sFields = $aSearchParams['fields'];
        } else {
            $sFields =
                '`id`,
                `created_on`,
                `to_be_sent_on`,
                `sent_on`,
                `priority`,
                `serialized_recipient`,
                `serialized_sender`,
                `headers`,
                `sent`,
                `slug`,
                `blobhash`';
        }

        $aBind = array();
        $sQuery = 'SELECT %s FROM ' . $this->sTable_Queue . ' `main`
        WHERE 1 ';

        if (isset($aSearchParams['id'])) {
            $sQuery .= ' AND (`main`.`id` = :id) ';
            $aBind['id'] = $aSearchParams['id'];
        }

        if (isset($aSearchParams['q'])) {
            $q = $aSearchParams['q'];
            $sQuery .= ' AND (
                `main`.`headers` LIKE :q
                OR `main`.`serialized_recipient` LIKE :q
                OR `main`.`serialized_sender` LIKE :q
                OR `main`.`slug` LIKE :q
                ) ';
            $aBind['q'] = '%' . $q . '%';
        }

        if (isset($aSearchParams['minPriority'])) {
            $sQuery .= ' AND (`main`.`priority` >= :minPriority) ';
            $aBind['minPriority'] = $aSearchParams['minPriority'];
        }

        if (isset($aSearchParams['maxPriority'])) {
            $sQuery .= ' AND (`main`.`priority` <= :maxPriority) ';
            $aBind['maxPriority'] = $aSearchParams['maxPriority'];
        }

        if (isset($aSearchParams['sender'])) {
            $sQuery .= ' AND (`main`.`serialized_sender` LIKE :sender) ';
            $aBind['sender'] = '%' . $aSearchParams['sender'] . '%';
        }

        if (isset($aSearchParams['recipient'])) {
            $sQuery .= ' AND (`main`.`serialized_recipient` LIKE :recipient) ';
            $aBind['recipient'] = '%' . $aSearchParams['recipient'] . '%';
        }

        if (isset($aSearchParams['headers'])) {
            $sQuery .= ' AND (`main`.`headers` LIKE :headers) ';
            $aBind['headers'] = '%' . $aSearchParams['headers'] . '%';
        }

        if (isset($aSearchParams['sent'])) {
            $sQuery .= ' AND (`main`.`sent` = :sent) ';
            $aBind['sent'] = (int)$aSearchParams['sent'];
        }

        if (isset($aSearchParams['slug'])) {
            $sQuery .= ' AND (`main`.`slug` LIKE :slug) ';
            $aBind['slug'] = '%' . $aSearchParams['slug'] . '%';
        }

        if (isset($aSearchParams['createdFrom'])) {
            $sQuery .= ' AND (`main`.`created_on` >= :createdFrom) ';
            $aBind['createdFrom'] = $aSearchParams['createdFrom'];
        }

        if (isset($aSearchParams['createdTo'])) {
            $sQuery .= ' AND (`main`.`created_on` <= :createdFrom) ';
            $aBind['createdFrom'] = $aSearchParams['createdFrom'];
        }

        if (isset($aSearchParams['toBeSentOnFrom'])) {
            $sQuery .= ' AND (`main`.`to_be_sent_on` >= :toBeSentOn) ';
            $aBind['toBeSentOn'] = $aSearchParams['toBeSentOn'];
        }

        if (isset($aSearchParams['toBeSentOnTo'])) {
            $sQuery .= ' AND (`main`.`to_be_sent_on` <= :toBeSentOn) ';
            $aBind['toBeSentOn'] = $aSearchParams['toBeSentOn'];
        }

        if (isset($aSearchParams['sentOnFrom'])) {
            $sQuery .= ' AND (`main`.`sent_on` >= :sentOnFrom) ';
            $aBind['sentOnFrom'] = $aSearchParams['sentOnFrom'];
        }

        if (isset($aSearchParams['sentOnTo'])) {
            $sQuery .= ' AND (`main`.`sent_on` <= :sentOnTo) ';
            $aBind['sentOnTo'] = $aSearchParams['sentOnTo'];
        }

        $oResult = $this->fetchPaginatedResult(
            $sQuery,
            $aBind,
            $aSearchParams,
            $sFields
        );

        return $oResult;

    }

    /**
     * @param $aSearchParams
     * @return Result
     */
    public function fetchMessageObjectsArrayFromQueue($aSearchParams)
    {
        $aSearchParams['fields'] = '*';
        $aResult = $this->searchArchiveQueue($aSearchParams);
        $aData = array();
        foreach ($aResult as $i => $aEmail) {
            $aData[$aEmail['id']] = unserialize(base64_decode($aEmail['email_object']));
        }
        return $aData;
    }

    /**
     * @param $sRecipient
     * @param null $sSender
     * @return bool|mixed
     */
    function lastContact($sRecipient, $sSender = null)
    {
        //@todo Info accessible through mailer::processTextHeader
        return false;

        /*
        $sQuery = ' SELECT headers FROM ' . $this->sArchive . ' WHERE 1 ';
        $sQuery .= ' AND serialized_recipient LIKE "%' . $sRecipient . '%" ';
        if ($sSender) {
            $sQuery .= ' AND serialized_sender LIKE "%' . $sSender . '%" ';
        }
        $sQuery .= 'ORDER BY date_sent DESC LIMIT 1';

        return $this->fetchOne($sQuery);*/
    }

}