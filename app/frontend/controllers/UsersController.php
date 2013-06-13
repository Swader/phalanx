<?php

namespace Frontend\Controllers;

use Bitfalls\Exceptions\UserException;
use Bitfalls\Mailer\Mailer;
use Bitfalls\Phalcon\ControllerBase;
use Bitfalls\Traits\Dates;
use Bitfalls\Utilities\JsonError;
use Bitfalls\Utilities\JsonSuccess;
use Bitfalls\Utilities\Parser;
use Bitfalls\Utilities\Stringer;
use Phalcon\Exception;
use Phalcon\Http\Cookie;
use Seld\JsonLint\JsonParser;
use Services\OrdersService;
use Services\UsersService;
use \Users;

/**
 * Class UsersController
 */
class UsersController extends ControllerBase
{

    use Dates;

    public function initialize()
    {
    }

    public function indexAction()
    {

    }

    public function deletecontactAction()
    {
        $oUser = Users::getCurrent();
        if ($oUser) {
            $id = $this->getParam('id', null, 'digit');
            if ($id) {
                $oContact = \Contacts::findFirst(array('id = :id:', 'bind' => array('id' => $id)));
                if ($oContact) {
                    /** @var \Contacts $oContact */
                    if ($oContact->getUserId() == $oUser->getId() || $oUser->hasSelfRole('admin')) {
                        if (!$oContact->setUserId(null)->save()) {
                            throw new \Exception('Could not remove contact! ' . $oContact->getMessages(true));
                        } else {
                            $this->redirectBack();
                            return;
                        }
                    } else {
                        throw new Exception('Insufficient permissions.');
                    }
                }
            }
        } else {
            throw new \Exception('You need to be logged in to do this.');
        }
    }

    /**
     * @return bool|\Phalcon\Http\ResponseInterface
     * @throws \Exception
     */
    public function myaccountAction()
    {

        /** @var Mailer $m */
        $m = $this->getDI()->get('mailer');
        $m->setDeveloperRecipient();

        $oUser = Users::getCurrent();
        if ($oUser) {
            $this->view->setVar('oUser', $oUser);

            $this->view->setVar('average', null);

            if ($this->request->isPost()) {
                if ($this->getParam('id') != $oUser->getId()) {
                    $m->expressMail(array(
                            'body' => 'A save attempt was made at /users/myaccount where ID from the form (' . $this->getParam('id') . ') differed from the ID of the logged in user (' . $oUser->getId() . ').',
                            'to' => 'bruno@skvorc.me'
                        )
                    );
                    $this->logoutAction();
                    $this->response->redirect('/', true);
                } else {

                    $aFields = array(
                        'first_name' => true,
                        'last_name' => true
                    );

                    foreach ($aFields as $k => &$v) {
                        $value = $this->getParam($k);
                        if ($v === true && (!$value || empty($value))) {
                            throw new \Exception($k . ' is a required field!');
                        }
                        $sMethod = 'set' . Stringer::toCamelCase($k);
                        $oUser->$sMethod($value);
                    }

                    $sPassword = trim($this->getParam('password'));
                    $sConfirmPassword = trim($this->getParam('confirm_password'));
                    if (!empty($sPassword) && !empty($sConfirmPassword)) {
                        if ($sPassword == $sConfirmPassword) {
                            $oUser->setPassword($sPassword);
                        } else {
                            throw new \Exception('Passwords do not match!');
                        }
                    }

                    if ($this->getParam('mainEmail', null, 'digit')) {
                        $oContact = \Contacts::findFirst(array('id = :id:', 'bind' => array('id' => $this->getParam('mainEmail', null, 'digit'))));
                        if ($oContact) {
                            /** @var \Contacts $oContact */
                            if ($oContact->getUserId() == $oUser->getId()) {
                                if ($oUser->getMainContactId() != $oContact->getId()) {
                                    $oUser->setMainContactId($oContact->getId());
                                }
                            } else {
                                throw new \Exception('Contact does not belong to user. Cannot set as main.');
                            }
                        } else {
                            throw new \Exception('Could not find given contact while saving main.');
                        }
                    }

                    if ($oUser->save() === false) {
                        $sMessage = 'Save error: ' . implode('. ', $oUser->getMessages());
                        throw new \Exception($sMessage);
                    } else {

                        $new = $this->getParam('newContact', null, null, 'email');
                        if ($new) {
                            $newContact = \Contacts::findFirst(array('email = :email:', 'bind' => array('email' => $new)));
                            if ($newContact) {
                                /** @var \Contacts $newContact */
                                if ($newContact->mainFor && $newContact->mainFor->getId() != Users::getCurrent()->getId()) {
                                    throw new \Exception('Given new email address is set as main for another account.');
                                } else {
                                    $newContact->setUserId($oUser->getId())->save();
                                }
                            } else {
                                $newContact = new \Contacts();
                                if (!$newContact
                                    ->setEmail($new)
                                    ->setUserId($oUser->getId())
                                    ->setActivated(0)
                                    ->save()
                                ) {
                                    throw new \Exception('Could not save user id for email address: ' . $newContact->getMessages(true));
                                }
                            }
                        }

                        return $this->response->redirect('/users/myaccount', true);
                    }

                }
            }
        } else {
            return $this->response->redirect('/users/login', true);
        }

        return true;
    }

    public function ajaxgetemailsAction()
    {
        $this->view->disable();
        $this->loginCheckAjax();

        $id = $this->getParam('id', null, 'digit');
        $oUser = Users::getCurrent();
        if ($id) {
            if ($id == Users::getCurrent()->getId() || Users::getCurrent()->hasSelfRole('admin')) {
                $oUser = Users::findFirst(array('id = :id:', 'bind' => array('id' => $id)));
                if (!$oUser) {
                    JsonError::getInstance()->setMessage('Unable to find user with ID ' . $id)->raise(true);
                }
            } else {
                JsonError::getInstance()->setMessage('Unable to fetch user. Insufficient permissions.')->raise(true);
            }
        }

        $aReturn = array();
        /** @var \Contacts $oContact */
        foreach ($oUser->contacts as $oContact) {
            $sStatusMessage = '';
            if ($oContact->getId() == $oUser->getMainContactId()) {
                $sStatusMessage .= 'Default email. ';
            }
            if ($oContact->getActivated()) {
                $sStatusMessage .= 'Activated on ' . $oContact->getActivatedOn(true);
            } else {
                $sStatusMessage .= 'Not activated.';
            }
            $oDummy = $oContact->getDummy();
            $oDummy->statusMessage = $sStatusMessage;
            $aReturn[] = $oDummy;
        }

        JsonSuccess::getInstance()->setResult($aReturn)->raise(true);
    }

    public function ajaxgetemailAction()
    {
        $this->view->disable();
        $this->loginCheckAjax();

        /** @var \Contacts $oContact */
        $oContact = \Contacts::findFirst(array('id = :id:', 'bind' => array('id' => $this->getParam('id', null, 'digit'))));

        if ($oContact) {
            if (Users::getCurrent()->hasSelfRole('admin') || $oContact->getUserId() == Users::getCurrent()->getId()) {
                JsonSuccess::getInstance()->setResult($oContact->getDummy())->raise(true);
            } else {
                JsonError::getInstance()->setMessage('Unable to fetch. Insufficient permissions.')->raise(true);
            }
        } else {
            JsonError::getInstance()->setMessage('Contact not found')->raise(true);
        }
    }

    public function ajaxsaveaddressAction()
    {
        $this->view->disable();
        /** @var array $post */
        $post = $this->getAjaxPost(true);

        $this->loginCheckAjax();

        $oUser = Users::getCurrent();

        if (isset($post['id'])) {
            $oModel = \AddressBook::findFirst(array('id = :id:', 'bind' => array('id' => $post['id'])));
            if (!$oModel) {
                JsonError::getInstance()->setMessage('Invalid ID for address. Please contact support.')->raise(true);
            }
        } else {
            $oModel = new \AddressBook();
        }

        $aFields = array(
            'first_name' => true,
            'last_name' => true,
            'city' => true,
            'zip' => true,
            'street' => true,
            'residence_type' => true,
            'phone' => true,
            'additional_info' => false
        );

        foreach ($aFields as $k => &$v) {
            $value = (isset($post[$k])) ? $post[$k] : null;
            if ($v === true && (!$value || empty($value))) {
                JsonError::getInstance()->setMessage('The field ' . ucfirst($k) . ' is required!')->raise(true);
            }
            $sMethod = 'set' . Stringer::toCamelCase($k);
            $oModel->$sMethod($value);
        }

        $oModel->setUserId($oUser->getId());
        if ($oUser->addresses->count() == 0 || ($oModel && $oModel->getIsDef() == "1")) {
            $oModel->setIsDef(1);
        } else {
            $oModel->setIsDef(0);
        }

        if ($oModel->save() === false) {
            $sMessage = 'Save error: ' . implode('. ', $oModel->getMessages());
            JsonError::getInstance()->setMessage('Saving failed: ' . $sMessage)->raise(true);
        } else {
            JsonSuccess::getInstance()->setMessage('Successfully saved!')->setResult($oModel->getId())->raise(true);
        }

    }

    public function ajaxgetaddressesAction()
    {
        $this->view->disable();
        /** @var array $post */
        $this->loginCheckAjax();

        $oUser = Users::getCurrent();

        if (isset($_GET['id'])) {
            /** @var \AddressBook $oAddress */
            $oAddress = \AddressBook::findFirst(array('id = :id:', 'bind' => array('id' => (int)$_GET['id'])));
            if (!$oAddress) {
                JsonError::getInstance()->setMessage('Invalid id: ' . $_GET['id'] . ', address entry not found.')->raise(true);
            } else if ($oAddress->getUserId() == $oUser->getId() || $oUser->hasSelfRole('admin')) {
                $oDummy = $oAddress->getDummy();
                $oDummy->country_id = $oAddress->oCity->country->getId();
                $oDummy->country_name = $oAddress->oCity->country->getCountryName();
                $oDummy->city_name = $oAddress->oCity->getName();
                $oDummy->residence_type_name = $oAddress->type->getName();
                JsonSuccess::getInstance()->setResult($oDummy)->raise(true);
            } else {
                JsonError::getInstance()->setMessage('Logged in user is neither admin nor owner of requested address. Forbidden.')->raise(true);
            }
        } else {
            $aResultSet = array();
            foreach ($oUser->addresses as $oModel) {
                $aResultSet[] = $oModel->getDummy();
            }
            JsonSuccess::getInstance()->setResult($aResultSet)->raise(true);
        }
    }

    public function ajaxdeleteaddressAction()
    {
        $this->view->disable();
        /** @var array $post */
        $this->loginCheckAjax();
        $post = $this->getAjaxPost(true);
        $oUser = Users::getCurrent();

        if (isset($post['id'])) {
            /** @var \AddressBook $oAddress */
            $oAddress = \AddressBook::findFirst(array('id = :id:', 'bind' => array('id' => (int)$post['id'])));
            if (!$oAddress) {
                JsonError::getInstance()->setMessage('Invalid id: ' . $_GET['id'] . ', address entry not found.')->raise(true);
            } else if ($oAddress->getUserId() == $oUser->getId() || $oUser->hasSelfRole('admin')) {
                if ($oAddress->orders->count() > 0) {
                    JsonError::getInstance()->setMessage('Cannot delete an address still bound to orders.')->raise(true);
                }
                /** @var Users $oAddressUser */
                $oAddressUser = $oAddress->user;
                if ($oAddress->delete()) {
                    if (!$oAddressUser->getDefaultAddress()) {
                        /** @var \AddressBook $oAdd */
                        foreach ($oAddressUser->addresses as $oAdd) {
                            $this->getDI()->get('addressService')->makeDefault($oAdd);
                            break;
                        }
                    }
                    JsonSuccess::getInstance()->raise(true);
                } else {
                    JsonError::getInstance()->setMessage($oAddress->getMessages(true))->raise(true);
                }
            } else {
                JsonError::getInstance()->setMessage('Logged in user is neither admin nor owner of requested address. Forbidden.')->raise(true);
            }
        } else {
            JsonError::getInstance()->setMessage('No ID given. Cannot change default address.');
        }
    }


    public function ajaxmakedefaultaddressAction()
    {
        $this->view->disable();
        /** @var array $post */
        $this->loginCheckAjax();
        $post = $this->getAjaxPost(true);
        $oUser = Users::getCurrent();

        if (isset($post['id'])) {
            /** @var \AddressBook $oAddress */
            $oAddress = \AddressBook::findFirst(array('id = :id:', 'bind' => array('id' => (int)$post['id'])));
            if (!$oAddress) {
                JsonError::getInstance()->setMessage('Invalid id: ' . $_GET['id'] . ', address entry not found.')->raise(true);
            } else if ($oAddress->getUserId() == $oUser->getId() || $oUser->hasSelfRole('admin')) {
                if ($oAddress->getIsDef() == "1") {
                    JsonSuccess::getInstance()->setMessage('Address is already default.')->raise(true);
                } else {
                    try {
                        if ($this->getDI()->get('addressService')->makeDefault($oAddress)) {
                            JsonSuccess::getInstance()->setMessage('Successfully saved!')->raise(true);
                        } else {
                            JsonError::getInstance()->setMessage('Failed for unknown reasons!')->raise(true);
                        }
                    } catch (\Exception $e) {
                        JsonError::getInstance()->setMessage($e->getMessage())->raise(true);
                    }
                }
            } else {
                JsonError::getInstance()->setMessage('Logged in user is neither admin nor owner of requested address. Forbidden.')->raise(true);
            }
        } else {
            JsonError::getInstance()->setMessage('No ID given. Cannot change default address.');
        }
    }

    public function deleteaddressAction()
    {
        $this->view->disable();
        /** @var array $post */
        $post = $this->getAjaxPost(true);

        $this->loginCheckAjax();

        $oUser = Users::getCurrent();

        $post['id'] = (isset($post['id'])) ? $post['id'] : 'null';
        $oModel = \AddressBook::findFirst(array('id = :id:', 'bind' => array('id' => $post['id'])));

        if ($oModel) {
            if ($oModel->orders->count() > 0) {
                JsonError::getInstance()->setMessage('Cannot delete an address still bound to orders!')->raise(true);
            } else {
                if (!$oModel->delete()) {
                    JsonError::getInstance()->setMessage('Failed to delete! ' . implode(', ', $oModel->getMessages()))->raise(true);
                } else {
                    JsonSuccess::getInstance()->setMessage('Successfully deleted!')->raise(true);
                }
            }
        }

    }


    public function resetpassAction()
    {
        $this->view->setVar('result', false);
        $sKey = $this->getParam('hash');
        $this->view->setVar('sHash', $sKey);
        /** @var Users $oUser */
        $oUser = Users::findFirst(array('password_reset_hash = :prh:', 'bind' => array('prh' => $sKey)));
        if (!$oUser) {
            $this->view->setVar('result', 'no_user');
        } else {
            if ($this->request->isPost()) {
                $pass = $this->getParam('password');
                $pass_confirm = $this->getParam('password_confirm');
                if ($pass != $pass_confirm) {
                    $this->view->setVar('result', 'no_match');
                } else {
                    $oUser
                        ->setPassword($pass)
                        ->setPasswordResetHash(null)
                        ->save();
                    $this->view->setVar('result', 'success');

                    /** @var \EmailsTemplates $et */
                    $et = \EmailsTemplates::findBySlug('password_reset_success');
                    $p = new Parser();
                    $m = $this->getDI()->get('mailer');
                    $m->setDeveloperRecipient();
                    $m->prepareEmail(
                        $oUser->mainContact->getEmail(),
                        Mailer::getDefaultSender(),
                        $p->doParse(array(
                            'recipient.first_name' => $oUser->getFirstName()
                        ), array(
                            'body' => $et->getBody(),
                            'subject' => $et->getSubject()
                        ))
                    )->sendPreparedEmails();

                }
            }
        }
    }

    public function forgotpassAction()
    {
        $this->view->disable();
        if ($this->request->isPost()) {
            $email = $this->getParam('email', null, null, 'email');
            /** @var \Contacts $oContact */
            $oContact = \Contacts::findFirst(array('email = :email:', 'bind' => array('email' => $email)));
            if (!$oContact) {
                die(json_encode(array('status' => 'error', 'message' => 'No such email in our database. Please contact support if you need assistance.')));
            } else if ($oContact && !$oContact->getUserId()) {
                die(json_encode(array('status' => 'error', 'message' => 'Email is not registered to any existing account. Please contact support if you need assistance.')));
            } else {
                /** @var Users $oUser */
                $oUser = Users::findFirst(array('id = :id:', 'bind' => array('id' => $oContact->getUserId())));
                $oUser->setPasswordResetHash(md5(md5(time())));
                $oUser->save();
                $aTagValues = array(
                    'recipient.password_reset_link' => $this->getDI()->get('baseUri') . '/users/resetpass/hash/' . $oUser->getPasswordResetHash(),
                    'recipient.first_name' => $oUser->getFirstName()
                );
                /** @var \EmailsTemplates $et */
                $et = \EmailsTemplates::findBySlug('password_forgot_email');
                $p = new Parser();
                $m = $this->getDI()->get('mailer');
                $m->setDeveloperRecipient();
                $m->prepareEmail(
                    $oContact->getEmail(),
                    Mailer::getDefaultSender(),
                    $p->doParse($aTagValues, array(
                        'body' => $et->getBody(),
                        'subject' => $et->getSubject()
                    ))
                )->sendPreparedEmails();
                if ($m->getNumberOfSent()) {
                    die(json_encode(array('status' => 'success', 'message' => 'Successfully sent. Please check your inbox. If the email has not yet arrived, try re-sending it or check your spam folder.')));
                } else {
                    die(json_encode(array('status' => 'error', 'message' => 'It seems the email was not sent. Please contact support.')));
                }
            }
        }
    }

    public function activateAction()
    {
        $this->view->setVar('result', false);
        $sUser = $this->getParam('user');
        $sKey = $this->getParam('key');

        $oUser = Users::findFirst(
            array('md5(md5(username)) = :username:',
                'bind' => array(
                    'username' => $sUser
                )
            )
        );

        if (!$sUser || !$sKey) {
            $this->view->setVar('result', 'no_key');
        } else {
            $oUser = Users::findFirst(
                array('md5(md5(username)) = :username:',
                    'bind' => array(
                        'username' => $sUser
                    )
                )
            );
            if (!$oUser) {
                $this->view->setVar('result', 'no_user');
            } else {
                /** @var \Contacts $oContact */
                /** @var \Users $oUser */
                $oContact = \Contacts::findFirst(array(
                        'user_id = :user_id: AND activation_key = :activation_key:',
                        'bind' => array(
                            'user_id' => $oUser->getId(),
                            'activation_key' => $sKey
                        ))
                );
                if (!$oContact) {
                    $this->view->setVar('result', 'wrong_key');
                } else {
                    $oContact->setActivated(1);
                    if ($oContact->save()) {
                        $this->view->setVar('result', 'success');

                        // Send confirmation email
                        /** @var Mailer $mailer */
                        $mailer = $this->getDI()->get('mailer');
                        $mailer->setDeveloperRecipient();
                        /** @var \EmailsTemplates $template */
                        $template = \EmailsTemplates::findFirst(array('slug = "new_user_activation_done"'));
                        $aData = array(
                            'subject' => $template->getSubject(),
                            'body' => html_entity_decode($template->getBody())
                        );
                        $aTags = array(
                            'recipient.username' => $oUser->getUsername(),
                        );
                        $oParser = new Parser();
                        $aData = $oParser->doParse($aTags, $aData);
                        $mailer->prepareEmail(
                            $oUser->mainContact->getEmail(),
                            $mailer->getDefaultSender(),
                            $aData
                        )->sendPreparedEmails();

                    } else {
                        $this->view->setVar('result', 'error_activating');
                    }
                }
            }
        }
    }

    public function loginAction()
    {
        if (Users::getCurrent()) {
            $this->view->setVar('loggedIn', true);
        } else {
            $this->view->setVar('loggedIn', false);

            if ($this->request->isPost()) {
                // Throttling to prevent brute force attacks and DoS
                sleep(1);
                $sUsername = $this->request->getPost('username', 'alphanum', false);
                $sPassword = $this->request->getPost('password');

                $bRememberMe = (bool)$this->getParam('remember_me', null, 'digit');

                try {

                    $oUsers = new Users();
                    /** @var Users $oRequestedUser */
                    $oRequestedUser = $oUsers->findFirst(
                        array(
                            'username = :username:',
                            'bind' => array('username' => $sUsername)
                        )
                    );

                    if ($oRequestedUser) {
                        if (Users::hashPassword($sPassword, $sUsername) == $oRequestedUser->getPassword()) {
                            $sUserHash = $oRequestedUser->getLoginSessionHash();
                            $aSessionData = array(
                                'userhash' => $sUserHash,
                                'full_name' => $oRequestedUser->getFullName()
                            );
                            $this->session->set('auth', $aSessionData);

                            if ($bRememberMe) {
                                $oRs = new \RememberedSessions();
                                $oRs->setRememberMeCookie($sUsername);
                            }

                            $this->response->redirect('/', true);
                        } else {
                            throw new UserException('Invalid credentials', 001);
                        }
                    } else {
                        throw new UserException('Invalid credentials', 002);
                    }

                } catch (\Exception $e) {
                    $this->view->setVar('errorMessage', $e->getMessage());
                    $this->view->setVar('errorCode', $e->getCode());
                    $this->view->setVar('stackTrace', $e->getTraceAsString());
                }

            }
        }
    }

    public function registerAction()
    {
        if (Users::getCurrent()) {
            $this->view->setVar('loggedIn', true);
        } else {
            $this->view->setVar('loggedIn', false);

            if ($this->request->isPost()) {
                $sUsername = $this->request->getPost('username', 'alphanum', false);
                $sPassword = $this->request->getPost('password');
                $sFirstName = $this->request->getPost('firstname');
                $sLastName = $this->request->getPost('lastname');
                $sPasswordConfirm = $this->request->getPost('password_confirm');
                $sEmail = $this->request->getPost('email', 'email');
                $sEmailConfirm = $this->request->getPost('email_confirm', 'email');

                try {

                    if (
                        empty($sPassword)
                        || empty($sPasswordConfirm)
                        || empty($sUsername)
                        || empty($sEmail)
                        || empty($sEmailConfirm)
                        || empty($sFirstName)
                        || empty($sLastName)
                    ) {
                        throw new UserException('All fields are required.');
                    }

                    if ($sPassword != $sPasswordConfirm) {
                        throw new UserException('Passwords must match.');
                    }

                    if ($sEmail != $sEmailConfirm) {
                        throw new UserException('Email fields must match.');
                    }

                    $oUsers = new Users();
                    /** @var Users $oRequestedUser */
                    $oRequestedUser = $oUsers->findFirst(
                        array(
                            'username = :username:',
                            'bind' => array('username' => $sUsername)
                        )
                    );

                    $oContacts = new \Contacts();
                    $oRequestedContact = $oContacts->findFirst(array(
                        'email = :email:',
                        'bind' => array('email' => $sEmail)
                    ));

                    if ($oRequestedUser) {
                        throw new UserException('A user with this username already exists, sorry.');
                    } else {
                        if ($oRequestedContact && $oRequestedContact->mainFor) {
                            throw new UserException('This email address is already in use as the main
                            email address for an existing account. Please pick another or retrieve an
                            account if you lost your credentials.');
                        } else {
                            if (!$oRequestedContact) {
                                $oRequestedContact = new \Contacts();
                                $oRequestedContact->setEmail($sEmail);
                                $oRequestedContact->setActivated(0);
                                $oRequestedContact->setActivationKey(md5(time()));
                                if (!$oRequestedContact->save()) {
                                    throw new UserException(
                                        'Email saving error: '
                                        . implode(', ', $oRequestedContact->getMessages()));
                                }
                            }
                            $oUsers
                                ->setUsername($sUsername)
                                ->setFirstName($sFirstName)
                                ->setLastName($sLastName)
                                ->setPassword($sPassword)
                                ->setCreatedBy(Users::getCurrent())
                                ->setType(1)
                                ->setMainContactId($oRequestedContact->getId());

                            if (!$oUsers->save()) {
                                throw new UserException(
                                    'Account saving error: '
                                    . implode(', ', $oUsers->getMessages()));
                            } else {
                                $oRequestedContact->setUserId($oUsers->getId());
                                if (!$oRequestedContact->save()) {
                                    throw new UserException(
                                        'Email->user saving error: '
                                        . implode(', ', $oRequestedContact->getMessages()));
                                } else {
                                    // Send activation email
                                    /** @var Mailer $mailer */
                                    $mailer = $this->getDI()->get('mailer');
                                    $mailer->setDeveloperRecipient();
                                    /** @var \EmailsTemplates $template */
                                    $template = \EmailsTemplates::findFirst(array('slug = "new_user_activation_needed"'));
                                    $aData = array(
                                        'subject' => $template->getSubject(),
                                        'body' => $template->getBody()
                                    );
                                    $aTags = array(
                                        'recipient.first_name' => $oUsers->getFirstName(),
                                        'recipient.activation_link' =>
                                        'http://' . trim($this->getDI()->get('config')->application->baseUri, '/')
                                        . '/users/activate/key/'
                                        . $oRequestedContact->getActivationKey()
                                        . '/user/' . md5(md5($oUsers->getUsername()))
                                    );
                                    $oParser = new Parser();
                                    $aData = $oParser->doParse($aTags, $aData);
                                    $mailer->prepareEmail(
                                        $oRequestedContact->getEmail(),
                                        $mailer->getDefaultSender(),
                                        $aData
                                    )->sendPreparedEmails();
                                    // Everything went fine
                                    $this->view->setVar('registered', true);
                                }
                            }

                        }
                    }

                } catch (\Exception $e) {
                    $this->view->setVar('errorMessage', $e->getMessage());
                    $this->view->setVar('errorCode', $e->getCode());
                    $this->view->setVar('stackTrace', $e->getTraceAsString());
                }

            }
        }
    }

    /**
     * @return bool
     */
    public function logoutAction()
    {
        if ($this->session->has('auth')) {
            $this->session->remove('auth');
            $oRs = new \RememberedSessions();
            $oRs->removeRememberMeCookie();
        }
        return true;
    }

}

