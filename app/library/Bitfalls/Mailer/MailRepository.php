<?php
    namespace Bitfalls\Mailer;

    use Bitfalls\Objects\Result;

    /**
     * Class MailRepository
     * @package Bitfalls\Mailer
     */
    interface MailRepository
    {

        /**
         * @param BitfallsMessage $oMessage
         * @param null $sDate
         * @param int $iPriority
         * @return mixed
         */
        function queueEmail(BitfallsMessage $oMessage, $sDate = null, $iPriority = 0);

        /**
         * @param $mInput
         * @return mixed
         */
        function markAsSent($mInput);

        /**
         * @param $mInput
         * @return mixed
         */
        function deleteEmail($mInput);

        /**
         * @param $iRange
         * @param null $sDate
         * @param null $iPriority
         * @param null $sRecipient
         * @param null $sSender
         * @param array $aOther
         * @return Result
         */
        function searchArchiveQueue($aSearchParams);

        /**
         * @param $aSearchParams
         * @return mixed
         */
        function fetchMessageObjectsArrayFromQueue($aSearchParams);

        /**
         * @param $sRecipient
         * @param null $sSender
         * @return mixed
         */
        function lastContact($sRecipient, $sSender = null);

        /**
         * @param BitfallsMessage $oMessage
         * @return mixed
         */
        function getSavedUnsentMessageExists(BitfallsMessage $oMessage);
    }
