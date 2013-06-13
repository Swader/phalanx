<?php

namespace Bitfalls\Mailer;

use Bitfalls\Mailer\MailRepository as MailRepo;
use Bitfalls\Exceptions\MailerException;

/**
 * Class Mailer
 * @package Bitfalls\Mailer
 */
class Mailer
{

    /**
     * @see setDeveloperRecipient and sendMail
     * @var string
     */
    protected $sDeveloperRecipient = '';

    /**
     * How many emails to take from the queue and send
     *
     * @var int
     */
    protected $iQueueRange;

    /**
     * The transport to be used for sending
     *
     * @var \Swift_Transport
     */
    protected $oTransport;

    /**
     * Defines the default sender.
     * Can be single-element assoc. array ( name => email ) or just an email address
     *
     * @var array|string
     */
    protected static $defaultSender;

    /**
     * Swift Mailer object
     *
     * @var \Swift_Mailer
     */
    protected $oMailer;

    /**
     * The array holding $emails - each email is an object in itself, with all required data.
     *
     * @var array
     */
    protected $aPreparedEmails;

    /**
     * Array of failed recipients per sent email.
     * This is an assoc. array where keys are email IDs,
     * while values are arrays of failed recipients per sent email
     *
     * @var array
     */
    protected $aFailedRecipients;

    /**
     * Number of queued emails during last queueing
     *
     * @var int
     */
    protected $iQueued = 0;

    /**
     * The service that allows the mailer to have a database connection
     *
     * @var null
     */
    protected $oRepo = null;

    /**
     * Whether or not to send to the archive inbox as well, in the format of a BCC email
     *
     * @var bool
     */
    protected $bDefaultBccActive = true;

    /**
     * Whether or not to archive the sent emails
     *
     * @var null
     */
    protected $bArchiving = null;

    /**
     * Number of successfully sent emails
     *
     * @var int
     */
    protected $iSent = 0;

    /** @var string */
    protected $sArchiveAddress;

    /** @var string */
    protected static $sAttachmentFolderDefaultPath = null;


    /**
     * The constructor accepts a valid MailRepository instance for communication with a given
     * database of other kind of Repo.
     *
     * @param MailRepo $oRepo
     */
    public function __construct(MailRepository $oRepo = null)
    {
        $this->aPreparedEmails = array();

        // Set up defaults
        $this->setDefaultBccActive();
        $this->setQueueRange();
        $this->archiving(true);
        $this->setTransport(1);
        if (self::getDefaultAttachmentPath() === null) {
            self::setDefaultAttachmentPath();
        }

        if ($oRepo) {
            $this->setMailRepo($oRepo);
        }

        $this->oMailer = \Swift_Mailer::newInstance($this->getTransport());
    }

    /**
     * @param bool $bDefaultBccActive
     * @return $this
     */
    public function setDefaultBccActive($bDefaultBccActive = true)
    {
        $this->bDefaultBccActive = (bool)$bDefaultBccActive;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDefaultBccActive()
    {
        return $this->bDefaultBccActive;
    }


    /**
     * @param $sPath
     * @return bool
     * @throws \Bitfalls\Exceptions\MailerException
     */
    public static function setDefaultAttachmentPath($sPath = null)
    {
        if ($sPath === null) {
            self::setDefaultAttachmentPath('../data/attachments');
        } else if (is_readable($sPath)) {
            self::$sAttachmentFolderDefaultPath = $sPath;
            return true;
        } else {
            throw new MailerException('Default Attachment Path not readable: ' . $sPath . ' (real: ' . realpath($sPath) . ' )');
        }
        return false;
    }

    /**
     * @return string
     */
    public static function getDefaultAttachmentPath()
    {
        return self::$sAttachmentFolderDefaultPath;
    }

    /**
     * Defines to which archive inbox to send BCC copies of all sent emails.
     * Leave empty to disable.
     *
     * @param string $sArchiveAddress
     * @throws MailerException
     */
    public function setArchiveAddress($sArchiveAddress)
    {
        if (filter_var($sArchiveAddress, FILTER_VALIDATE_EMAIL)) {
            $this->sArchiveAddress = $sArchiveAddress;
        } else {
            throw new MailerException('The archival email ' . $sArchiveAddress . ' is not a valid email.');
        }
    }

    /**
     * Returns the archive address set by setArchiveAddress
     *
     * @return string
     */
    public function getArchiveAddress()
    {
        return $this->sArchiveAddress;
    }

    /**
     * Sets the default sender for the expressMail method.
     * Can be a single-element array (name => email) or pure email string
     *
     * @param $mInput
     */
    public static function setDefaultSender($mInput)
    {
        self::$defaultSender = $mInput;
    }

    /**
     * @return array|string
     */
    public static function getDefaultSender()
    {
        return self::$defaultSender;
    }

    /**
     * Sets the number of queued emails. Automatic when queuePreparedEmails is called.
     *
     * @param $iQueued
     *
     * @return Mailer
     */
    protected function setNumberOfQueued($iQueued)
    {
        $this->iQueued = $iQueued;

        return $this;
    }

    /**
     * Returns the number of queued emails during last queueing.
     *
     * @return int
     */
    public function getNumberOfQueued()
    {
        return $this->iQueued;
    }

    /**
     * Sets the queue range of the Mailer instance
     *
     * @param int $iValue
     *
     * @return Mailer
     * @throws MailerException
     */
    public function setQueueRange($iValue = 500)
    {
        if ($iValue > 0 && $iValue < 1000) {
            $this->iQueueRange = $iValue;
        } else {
            throw new MailerException('Wrong queueRange value given - value must be between 0 and 1000, you gave ' . $iValue);
        }

        return $this;
    }

    /**
     * Sets the developer recipient.
     * Leave default of pass false to deactivate debugging send mode
     * and send to normal recipients. Pass email to define a custom
     * email address which should receive all the emails.
     *
     * @param bool|string $sEmail
     *
     * @return Mailer
     */
    public function setDeveloperRecipient($sEmail = false)
    {
        $this->sDeveloperRecipient = $sEmail;

        return $this;
    }

    /**
     * Returns the defined developer recipient
     * Will be empty string if no developer recipient was set
     *
     * @return string|bool
     */
    public function getDeveloperRecipient()
    {
        return $this->sDeveloperRecipient;
    }

    /**
     * Retrieves the queueRange of the given Mailer instance
     *
     * @return int
     */
    public function getQueueRange()
    {
        return $this->iQueueRange;
    }

    /**
     * Returns the number of successfully sent emails
     *
     * @return int
     * @since         2012-06-19
     * @author        Bruno Škvorc <bruno@skvorc.me>
     */
    public function getNumberOfSent()
    {
        return $this->iSent;
    }

    /**
     * Sets the transport. Defaults to 127.0.0.1
     * To use a custom transport, pass an instance of Swift_Transport
     * To use a custom SmtpTransport host, just pass in the hostname
     *
     * Otherwise, there are two predefined cases possible:
     * 1: \Swift_SmtpTransport (127.0.0.1)
     * 2: \Swift_MailTransport
     *
     * @param int|\Swift_Transport $mTransport
     *
     * @return Mailer
     */
    public function setTransport($mTransport = 1)
    {
        if (is_a($mTransport, '\Swift_Transport')) {
            $this->oTransport = $mTransport;
        } else {
            if (is_int($mTransport)) {
                switch ($mTransport) {
                    default:
                    case 1:
                        $this->oTransport = new \Swift_SmtpTransport('127.0.0.1');
                        break;
                    case 2:
                        $this->oTransport = \Swift_MailTransport::newInstance();
                        break;
                }
            } else {
                $this->oTransport = new \Swift_SmtpTransport($mTransport);
            }
        }
        $this->oMailer = \Swift_Mailer::newInstance($this->getTransport());

        return $this;
    }

    /**
     * Returns the transport in use on the current Mailer instance
     *
     * @return \Swift_Transport
     */
    public function getTransport()
    {
        return $this->oTransport;
    }

    /**
     * Sends a prepared message
     *
     * @param BitfallsMessage $oMessage
     *
     * @return int Number of recipients accepted for delivery
     * @throws MailerException
     */
    protected function sendMail(BitfallsMessage $oMessage)
    {

        if ($this->getDeveloperRecipient() !== false) {
            $sEmail = filter_var($this->getDeveloperRecipient(), FILTER_VALIDATE_EMAIL);
            if (!empty($sEmail)) {
                $oMessage->setTo($sEmail);
            } else {
                throw new MailerException('Developer recipient is set but invalid. Sending will not happen');
            }
        }

        return $this->oMailer->send($oMessage, $this->aFailedRecipients[$oMessage->getId()]);
    }

    /**
     * Pass in true/false to activate/deactivate archiving.
     * Defaults to true.
     * Omit parameter to use as getter.
     *
     * @param bool $bVal
     *
     * @return Mailer|bool
     * @throws MailerException
     */
    public function archiving($bVal = null)
    {
        if ($bVal !== null) {
            if ($bVal === true || $bVal === false) {
                $this->bArchiving = $bVal;

                return $this;
            } else {
                throw new MailerException('Cannot set archiving mode to ' . $bVal . ' You must provide a boolean value.');
            }
        }

        return (bool)$this->bArchiving;
    }

    /**
     * Saves the sent message into the archive via the Mailer's registered service.
     * This method will throw an exception is the service is not set or is invalid.
     *
     * @param BitfallsMessage $oMessage
     *
     * @return Mailer
     * @throws MailerException
     */
    protected function archiveEmail(BitfallsMessage $oMessage)
    {

        $this->checkService();
        $oMR = $this->getMailRepo();

        if (!$oMR->getSavedUnsentMessageExists($oMessage)) {
            $oMR->queueEmail($oMessage);
        }
        $oMR->markAsSent($oMessage);

        return $this;
    }

    /**
     * Creates and sends an instant message
     *
     * @param array $aSettings Needs to have body and from, to and subject are optional values.
     * @param bool  $bArchive  Set to true if you wish the message to be saved into the archive after sending
     *
     * @return Mailer
     * @throws MailerException
     */
    public function expressMail($aSettings, $bArchive = false)
    {
        if ($aSettings instanceof BitfallsMessage) {
            $message = $aSettings;

        } else {
            $aSettings['subject'] = (isset($aSettings['subject'])) ? $aSettings['subject'] : 'Express message via BitFalls Mailer and Swift';

            if (!isset($aSettings['from'])) {
                $ds = self::getDefaultSender();
                if (empty($ds)) {
                    throw new MailerException(
                        'Please define defaultSender.'
                    );
                } else {
                    $aSettings['from'] = $ds;
                }
            }

            if (!isset($aSettings['to']) || !isset($aSettings['body'])) {
                throw new MailerException('Both the "to" and "body" values need to be provided.');
            } else {
                $message = BitfallsMessage::newInstance($aSettings['subject'])->setFrom($aSettings['from'])->setTo(
                    $aSettings['to']
                )->setBody($aSettings['body']);
            }
        }
        $this->iSent = $this->sendMail($message);
        if ($bArchive) {
            $this->archiveEmail($message);
        }
        return $this;
    }

    /**
     * Returns the array of failed recipients. This is an assoc. array which has message IDs as keys and an array of failed recipients per given email as the value
     *
     * @param mixed $id The message id. If provided, only fails for the given email are retrieved, otherwise, everything is retrieved.
     *
     * @return array
     */
    public function getFailedRecipients($id = 0)
    {
        if ($id) {
            return $this->aFailedRecipients[$id];
        } else {
            return $this->aFailedRecipients;
        }
    }

    /**
     * Sends and optionally archives the prepared emails
     *
     * @param string $sDevelopmentRecipient Enter custom email address if you want the email sent to this address instead (good for testing)
     *
     * @return Mailer
     * @throws MailerException
     */
    public function sendPreparedEmails($sDevelopmentRecipient = null)
    {
        if ($this->hasPreparedEmails()) {
            /** @var BitfallsMessage $oEmail */
            foreach ($this->getPreparedEmails() as $i => $oEmail) {
                if ($sDevelopmentRecipient) {
                    $oEmail->setTo($sDevelopmentRecipient);
                }
                if ($this->sendMail($oEmail)) {
                    $this->iSent++;
                    if ($this->archiving()) {
                        $this->archiveEmail($oEmail);
                    }
                    unset($this->aPreparedEmails[$i]);
                } else {
                    throw new MailerException('Failed to send email.');
                }
            }
        }

        return $this;
    }

    /**
     * Returns the array of prepared email objects
     *
     * @return array
     */
    public function getPreparedEmails()
    {
        return $this->aPreparedEmails;
    }

    /**
     * Returns whether or not there are any prepared emails in the instance
     *
     * @return bool
     */
    public function hasPreparedEmails()
    {
        return (bool)(count($this->getPreparedEmails()));
    }

    /**
     * Verifies that all the data required for proper email content has been set.<br />
     * This checks for the body and subject, and throws exceptions if any of those aren't found
     *
     * This method takes a reference to the data array and as such might make some minor changes to
     * it (i.e. inserting an empty array key if the 'signature' key is not present etc)
     *
     * @param array $aData
     *
     * @return boolean|MailerException
     */
    protected function verifySendableContent(&$aData)
    {
        $e = true;
        if (!isset($aData['body'])) {
            $e = new MailerException('Content Verification Failed: An email MUST have plain text content.');
        }
        if (!isset($aData['subject'])) {
            $e = new MailerException('Content Verification Failed: An email MUST have a subject.');
        }
        if (!isset($aData['signature'])) {
            $aData['signature'] = '';
        }

        return $e;
    }

    /**
     * Sets the Mail Repository with which this Mailer class can access repo/db functionality
     *
     * @param MailRepository $oRepo
     *
     * @return Mailer
     */
    public function setMailRepo(MailRepository $oRepo)
    {
        $this->oRepo = $oRepo;

        return $this;
    }

    /**
     * Returns the defined Mailer Service for Mailer's database access or null if not defined
     *
     * @return MailRepository
     */
    public function getMailRepo()
    {
        return $this->oRepo;
    }

    /**
     * Prepares a new message for sending
     *
     * @param array|string $mTo
     * @param array|string $mFrom
     * @param array        $aData
     * @param array        $aHeaders
     * @param array        $aAttachments
     *
     * @return Mailer
     * @throws bool|MailerException
     */
    public function prepareEmail($mTo, $mFrom, $aData, $aHeaders = array(), $aAttachments = null)
    {

        $mVerification = $this->verifySendableContent($aData);
        if ($mVerification === true) {

            $message = BitfallsMessage::newInstance();
            $message->setSubject($aData['subject']);
            $message->setBody($aData['body'] . $aData['signature']);
            if (isset($aData['slug'])) {
                $message->setSlug($aData['slug']);
            }

            if (isset($aData['bodyHtml']) && !empty($aData['bodyHtml'])) {
                $aData['body_html'] = $aData['bodyHtml'];
            }
            if (isset($aData['body_html']) && !empty($aData['body_html'])) {
                $message->addPart($aData['body_html'], 'text/html');
            }
            $message->setTo($mTo);
            $message->setFrom($mFrom);

            if ($this->getDefaultBccActive()) {
                $message->addBcc($this->getArchiveAddress());
            }

            if (!empty($aHeaders)) {
                foreach ($aHeaders as $sHeaderType => $aHeader) {
                    switch ($sHeaderType) {
                        case 'cc':
                            foreach ($aHeader as $aCc) {
                                $message->addCc($aCc);
                            }
                            break;
                        case 'bcc':
                            foreach ($aHeader as $aBcc) {
                                $message->addBcc($aBcc);
                            }
                            break;
                        case 'x-smtpapi':
                            $sCategoryString = '';
                            $sArgumentsString = '';
                            foreach ($aHeader as $sType => $aArray) {
                                switch ($sType) {
                                    case 'categories':
                                        $sCategoryString = '"category":' . json_encode($aArray);
                                        break;
                                    case 'unique_args':
                                        $oObject = new \stdClass();
                                        foreach ($aArray as $k => $v) {
                                            $oObject->$k = $v;
                                        }
                                        $sArgumentsString = '"unique_args":' . json_encode($oObject);
                                        break;
                                    default:
                                        throw new MailerException('X-SMTPAPI header type ' . $sType . ' not supported.');
                                        break;
                                }
                            }

                            $sTextHeader = '{' . trim(
                                implode(',', array($sArgumentsString, $sCategoryString)),
                                ','
                            ) . '}';
                            $message->getHeaders()->addTextHeader('X-SMTPAPI', $sTextHeader);

                            break;
                        default:
                            if (is_string($sHeaderType) && is_string($aHeader)) {
                                $message->getHeaders()->addTextHeader($sHeaderType, $aHeader);
                            } else {
                                throw new MailerException('Invalid header format. Both header key and value need to be string.');
                            }
                            break;
                    }
                }
            }

            if (!empty($aAttachments)) {
                foreach ($aAttachments as $mAtt) {

                    if ($mAtt instanceof \Swift_Attachment) {
                        $message->attach($mAtt);
                    } else {

                        if (is_string($mAtt)) {
                            $mAtt = array('file' => $mAtt);
                        }

                        $mAtt = (array)$mAtt;

                        if (isset($mAtt['file'])) {

                            if (is_readable($mAtt['file'])) {
                                $sFilePath = $mAtt['file'];
                            } else {
                                $sFilePath = rtrim(
                                    self::getDefaultAttachmentPath(),
                                    '/'
                                ) . '/' . $mAtt['file'];
                            }

                            if (!is_readable($sFilePath)) {
                                throw new MailerException('Static file attachment ' . $sFilePath . ' not found or is not readable.');
                            }

                            $mAtt['name'] = (isset($mAtt['name'])) ? $mAtt['name'] : $mAtt['file'];

                            $message->attach(\Swift_Attachment::fromPath($sFilePath)->setFilename($mAtt['name']));
                        } else {
                            if (!isset($mAtt['mime'])) {
                                throw new MailerException('When building attachments, mimetype must be provided via the mime key in the attachment\'s array. Either provide the mimetype, or attach the file by passing a filename through the \'file\' key.');
                            }

                            if (!isset($mAtt['content'])) {
                                throw new MailerException('No content or filename given. Attachment would be empty. The attachment array must have either a \'file\' key or \'content\' key.');
                            }

                            $mAtt['name'] = (isset($mAtt['name'])) ? $mAtt['name'] : 'Attachment';

                            $message->attach(
                                \Swift_Attachment::newInstance($mAtt['content'], $mAtt['name'], $mAtt['mime'])
                            );
                        }
                    }
                }
            }
            $this->aPreparedEmails[] = $message;
        } else {
            throw $mVerification;
        }

        if ($this->getNumberOfSent() > 0) {
            $this->iSent = 0;
        }

        return $this;
    }

    /**
     * Queues prepared emails for later sending
     *
     * @param string $sDate Y-m-d format
     * @param int    $iPriority
     *
     * @return Mailer
     * @throws MailerException
     */
    public function queuePreparedEmails($sDate = null, $iPriority = 0)
    {

        if ($sDate) {
            if (strpos($sDate, '+') === 0) {
                $sTrimmed = trim($sDate, '+ ');
                if (is_numeric($sTrimmed)) {
                    $sTrimmed = '+' . $sTrimmed . ' day';
                } else {
                    throw new MailerException('Failed to queue - date is invalid: ' . $sDate);
                }
                $sDate = date('Y-m-d', strtotime($sTrimmed));
            } else {
                if (strtotime($sDate) < time()) {
                    throw new MailerException('Queue date cannot be in the past!');
                }
            }
        }

        $this->checkService();

        $iQueued = 0;
        if ($this->hasPreparedEmails()) {
            /** @var $oEmail BitfallsMessage */
            foreach ($this->aPreparedEmails as $i => &$oEmail) {
                if ($this->getMailRepo()->queueEmail($oEmail, $sDate, $iPriority)) {
                    $iQueued++;
                    unset($this->aPreparedEmails[$i]);
                } else {
                    throw new MailerException('Could not queue email');
                }
            }
        }
        $this->setNumberOfQueued($iQueued);

        return $this;
    }

    /**
     * Sends emails from queue. If options are provided, fetches non-default set.
     * Available filter options are: range, date, priority, recipient and sender.
     *
     * @param array  $aOptions
     * @param string $sDevelopmentRecipient Provide if you want to force-send the emails to a specific recipient (for testing purposes)
     *
     * @return Mailer
     * @throws MailerException
     */
    public function sendQueuedEmails($aOptions = array(), $sDevelopmentRecipient = null)
    {

        $this->checkService();
        if (isset($aOptions['date']) && $aOptions['date'] == 'today') {
            $aOptions['toBeSentOnFrom'] = date('Y-m-d') . ' 00:00:00';
            $aOptions['toBeSentOnTo'] = date('Y-m-d') . ' 23:59:59';
        }
        $aSearchParams = array_merge(
            array(
                'sent' => 0,
                'limit' => $this->getQueueRange(),
                'sort' => 'id',
                'order' => 'DESC',
                'page' => 1
            ),
            $aOptions
        );

        $aQueuedEmails = $this->getMailRepo()->fetchMessageObjectsArrayFromQueue($aSearchParams);

        if (count($aQueuedEmails)) {
            /** @var BitfallsMessage $oEmail */
            foreach ($aQueuedEmails as $id => $oEmail) {
                if ($sDevelopmentRecipient) {
                    $oEmail->setTo($sDevelopmentRecipient);
                }
                if ($this->sendMail($oEmail)) {
                    $this->iSent++;
                    if ($this->archiving()) {
                        $this->archiveEmail($oEmail);
                    } else {
                        if (!$this->getMailRepo()->deleteEmail($id)) {
                            throw new MailerException('Email ID ' . $id . ' was sent, but could not be unqueued.');
                        }
                    }
                } else {
                    throw new MailerException('Failed to send queued email with ID ' . $id);
                }
            }
        }

        return $this;
    }

    /**
     * Parses an email header into an array of readable and usable values.
     * The array will contain sub arrays of email addresses in keys "to", "from", "reply_to", "sender", "cc", "bcc" and other data.
     *
     * @param $sHeader
     *
     * @return array
     * @throws MailerException
     *
     */
    public static function processTextHeader($sHeader)
    {

        $aResult = array();
        if (!empty($sHeader) && is_string($sHeader)) {

            if (!function_exists('imap_rfc822_parse_headers')) {
                throw new MailerException('No IMAP RFC822 function available. Did you install the IMAP extension into php?');
            }
            $oHeader = imap_rfc822_parse_headers($sHeader);
            $aResult = array();

            $aHeaderBits = array('to', 'from', 'reply_to', 'sender', 'cc', 'bcc');
            foreach ($aHeaderBits as &$sHeaderBit) {
                $aResult[$sHeaderBit] = array();
                if (isset($oHeader->$sHeaderBit)) {
                    foreach ($oHeader->$sHeaderBit as &$sHeaderBitObject) {
                        $aResult[$sHeaderBit][] = $sHeaderBitObject->mailbox . '@' . $sHeaderBitObject->host;
                    }
                }
            }

            $aResult['date'] = $oHeader->date;
            $oDateTime = new \DateTime($oHeader->date);
            $aResult['days_ago'] = $oDateTime->diff(new \DateTime())->days;

            $aResult['subject'] = $oHeader->subject;
            $aResult['message_id'] = $oHeader->message_id;
            $aResult['unique_args'] = array();
            $aResult['categories'] = array();

            unset($oHeader, $oDateTime);

            foreach (explode("\n", $sHeader) as $sHeaderEntry) {
                if (strpos($sHeaderEntry, 'X-SMTPAPI') !== false) {
                    $x_smtpapi = json_decode(str_replace('X-SMTPAPI: ', '', $sHeaderEntry));
                    if (isset($x_smtpapi->unique_args) && !empty($x_smtpapi->unique_args) && is_a(
                        $x_smtpapi->unique_args,
                        '\stdClass'
                    )
                    ) {
                        $aResult['unique_args'] = get_object_vars($x_smtpapi->unique_args);
                    }
                    if (isset($x_smtpapi->category) && !empty($x_smtpapi->category)) {
                        $aResult['categories'] = $x_smtpapi->category;
                    }
                    break;
                }
            }
        }

        return $aResult;
    }

    /**
     * Checks if the service is properly set and throws exception if not
     *
     * @throws MailerException
     */
    protected function checkService()
    {
        if ($this->oRepo === null) {
            throw new MailerException('A Repo needs to be provided, and must implement the MailRepository Interface.');
        }
    }
}