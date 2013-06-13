<?php

namespace Bitfalls\Phalcon;

use Phalcon\Events\Event,
    Phalcon\Mvc\User\Plugin,
    Phalcon\Mvc\Dispatcher,
    Users,
    Phalcon\Acl;

/**
 * Security
 *
 * This is the security plugin which controls that users only have access to the modules they're assigned to
 */
class Security extends Plugin
{

    /**
     * @param $dependencyInjector
     */
    public function __construct($dependencyInjector)
    {
        $this->_dependencyInjector = $dependencyInjector;
    }

    /**
     * This action is executed before execute any action in the application
     */
    public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {

        // Ignore all this if the user is logging out
        $sModuleName = strtolower(explode('\\', $dispatcher->getNamespaceName())[0]);
        if (
            $this->dispatcher->getActionName() == 'logout'
            && $this->dispatcher->getControllerName() == 'users'
            && $sModuleName == 'frontend'
        ) {
            return;
        }

        // Otherwise proceed with various whatnots and security checks

        /** @var Users $oLoggedInUser */
        $oLoggedInUser = false;

        $oRs = new \RememberedSessions();
        $sUsername = $oRs->getValidPersistentSession();
        if ($sUsername) {
            $oLoggedInUser = Users::findFirst(
                array(
                    'username = :un:',
                    'bind' => array('un' => $sUsername)
                )
            );
            $sUserHash = $oLoggedInUser->getLoginSessionHash();
            $aSessionData = array(
                'userhash' => $sUserHash,
                'full_name' => $oLoggedInUser->getFullName()
            );
            $this->session->set('auth', $aSessionData);
        } else {
            $auth = $this->session->get('auth');
            if ($auth && isset($auth['userhash'])) {
                $oLoggedInUser = Users::findFirst(
                    array(
                        'MD5(CONCAT(password, username, "' . Users::HASH_SALT . '")) = :userhash:',
                        'bind' => array('userhash' => $auth['userhash'])
                    )
                );
            }
        }

        /** @var Users $oLoggedInUser */
        if ($oLoggedInUser) {
            Users::setCurrent($oLoggedInUser);
        }

        $sModuleName = strtolower(explode('\\', $dispatcher->getNamespaceName())[0]);
        if ($sModuleName != 'frontend' && !$oLoggedInUser) {
            if ($this->getDI()->get('request')->isAjax()) {
                die(json_encode(array('status' => 'error', 'message' => 'Insufficient permissions')));
            } else {
                $this->getDI()->get('response')->redirect('/users/login', true);
                return false;
            }
        }

        $acl = array(
            'frontend' => '*',
            'admin' => 'admin'
        );

        $aUserRoleSlugs = array();
        if ($sModuleName != 'frontend') {
            /** @var \UsersRoles $oUR */
            foreach (Users::getCurrent()->usersRoles as $oUR) {
                $aUserRoleSlugs[] = $oUR->getRoleSlug();
                $aUserRoleSlugs = array_unique($aUserRoleSlugs);
            }
        }

        if (isset($acl[$sModuleName]) && $acl[$sModuleName] != '*') {
            $aPermissions = (array)$acl[$sModuleName];
            if (array_intersect($aUserRoleSlugs, $aPermissions) == array()) {
                throw new \Exception('You do not have sufficient permissions to access this part of the website.');
            }
        }


    }

}