<?php

namespace Services;

use Bitfalls\Mailer\Mailer;
use Bitfalls\Phalcon\Abstracts\ServiceAbstract;
use Bitfalls\Objects\Result;
use Bitfalls\Utilities\Parser;

/**
 * Class ContactsService
 * @package Services
 */
class ContactsService extends ServiceAbstract
{

    /** @var string */
    protected $sTable_Main = '`contacts` `main`';

    /** @var string */
    protected $sTable_Users = 'users';

    /**
     * @param $aSearchParams
     * @return Result
     */
    public function search($aSearchParams)
    {

        $aBind = array();
        $sFields = '`main`.*, users.id as `mainFor` ';
        $sQuery = 'SELECT %s FROM ' . $this->sTable_Main . '
        LEFT JOIN ' . $this->sTable_Users . ' `users` ON (`users`.`main_contact_id` = `main`.id)
        WHERE 1 ';

        if (isset($aSearchParams['id'])) {
            $sQuery .= ' AND (`main`.`id` = :id) ';
            $aBind['id'] = $aSearchParams['id'];
        }

        if (isset($aSearchParams['q'])) {
            $q = $aSearchParams['q'];
            if (filter_var($q, FILTER_VALIDATE_EMAIL)) {
                $sQuery .= ' AND (`main`.`email` = :q) ';
                $aBind['q'] = $q;
            } else {
                $sQuery .= ' AND (`main`.`email` LIKE :q) ';
                $aBind['q'] = '%' . $q . '%';
            }
        }

        $oResult = $this->fetchPaginatedResult($sQuery, $aBind, $aSearchParams, $sFields);

        return $oResult;

    }

    /**
     * @param \Contacts $oContact
     * @param string $sEmailTemplate
     * @return bool
     * @throws \Exception
     */
    public function sendActivationEmail(\Contacts $oContact, $sEmailTemplate = 'email_activation_needed')
    {

        /** @var \Users $oUser */
        $oUser = $oContact->user;
        if (!$oUser) {
            throw new \Exception('No user bound to this email address. Cannot send activation email.');
        }

        /** @var \EmailsTemplates $template */
        $template = \EmailsTemplates::findFirst(array('slug = :slug:', 'bind' => array('slug' => $sEmailTemplate)));

        if (!$template) {
            throw new \Exception('Unable to find template "'.$sEmailTemplate.'"');
        }

        $oContact->setActivationKey(md5(time()))->save();

        // Send activation email
        /** @var Mailer $mailer */
        $mailer = $this->getDI()->get('mailer');
        $mailer->setDeveloperRecipient();

        $aData = array(
            'subject' => $template->getSubject(),
            'body' => $template->getBody()
        );

        $aTags = array(
            'recipient.first_name' => $oUser->getFirstName(),
            'recipient.activation_link' =>
            'http://' . trim($this->getDI()->get('config')->application->baseUri, '/')
            . '/users/activate/key/'
            . $oContact->getActivationKey()
            . '/user/' . md5(md5($oUser->getUsername())),
            'site.url' => $this->getDi()->get('config')->application->siteUrl
        );

        $oParser = new Parser();
        $aData = $oParser->doParse($aTags, $aData);
        $mailer->prepareEmail(
            $oContact->getEmail(),
            $mailer->getDefaultSender(),
            $aData
        )->sendPreparedEmails();

        return (bool)$mailer->getNumberOfSent();
    }

}