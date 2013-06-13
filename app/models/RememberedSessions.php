<?php

/**
 * Class RememberedSessions
 */
class RememberedSessions extends \Bitfalls\Phalcon\Model
{

    use \Bitfalls\Traits\TimeAware;

    // @todo: Remove Devlog when done
    use \Bitfalls\Traits\Devlog;

    const REMEMBER_ME_SALT = '43qt,024 5+t02+.-.,-.p5430487q90q56+0';

    /**
     * @var integer
     *
     */
    protected $id;

    /**
     * @var string
     *
     */
    protected $usernamehashed;

    /**
     * @var string
     *
     */
    protected $cookievaluehashed;

    /**
     * @var string
     *
     */
    protected $created_on;


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
     * @param $usernamehashed
     * @return $this
     */
    public function setUsernamehashed($usernamehashed)
    {
        $this->usernamehashed = $usernamehashed;
        return $this;
    }

    /**
     * @param $sUsername
     * @param null $iLifetime
     * @return bool
     * @throws Exception
     */
    public function setRememberMeCookie($sUsername, $iLifetime = null)
    {

        if ($iLifetime === null) {
            // 14 days
            $iLifetime = time() + 60 * 60 * 24 * 14;
        }

        //$this->log('---------------- SETTING START ----------------------');
        $sValue = $this->calculateValue($sUsername);
        //$this->log('Calculated value: '.$sValue);
        $this->setCookievaluehashed(hash('sha512', $sValue));
        //$this->log('Calculated cookie hash: '.$this->getCookievaluehashed());
        $this->setUsernamehashed(hash('sha512', $sUsername));
        //$this->log('Calculated usernamehash: '.$this->getUsernamehashed());
       // $this->log('---------------- SETTING DONE ----------------------');

        if ($this->save()) {
            setcookie('remember-me', $sValue, $iLifetime, '/', null, null, true);
            return true;
        } else {
            throw new \Exception('Unable to save remembered session, please contact <a href="mailto:support@tekapo.co">support</a> for help!');
        }
    }

    /**
     * @param $sUsername
     * @return string
     */
    protected function calculateValue($sUsername)
    {
        return md5($sUsername . md5(self::REMEMBER_ME_SALT . time())) . '!!!separator!!!' . $sUsername;
    }

    /**
     * @return $this
     */
    public function removeRememberMeCookie()
    {
        //$this->log('Starting remember me removal.');
        $sValue = isset($_COOKIE['remember-me']) ? $_COOKIE['remember-me'] : false;
        //$this->log('Value found: '.$sValue);
        if ($sValue) {
            $sUsername = explode('!!!separator!!!', $sValue)[1];
            //$this->log('Username extracted: '.$sUsername);
            $aBind = array(
                'cvh' => hash('sha512', $sValue),
                'unh' => hash('sha512', $sUsername)
            );
            //$this->log('Looking for session with following binds: '.$this->vdp($aBind));
            $oSession = self::findFirst(array(
                'cookievaluehashed = :cvh: AND usernamehashed = :unh:',
                'bind' => $aBind
            ));
            //->log('Session find result: '.$this->vdp($oSession));
            if ($oSession) {
                if (!$oSession->delete()) {
                    //$this->log('Session could not be deleted: '.implode(', ', $oSession->getMessages()));
                }
            }
        }

        setcookie('remember-me', 'bogus', time() - 1000, '/', null, null, true);
        //$this->log('Removed cookie, finishing.');
        return $this;
    }

    /**
     * @return bool|string
     */
    public function getValidPersistentSession()
    {
        $sValue = isset($_COOKIE['remember-me']) ? $_COOKIE['remember-me'] : false;
        if ($sValue) {
            $sUsername = explode('!!!separator!!!', $sValue)[1];
            $oSession = self::findFirst(array(
                'cookievaluehashed = :cvh: AND usernamehashed = :unh:',
                'bind' => array(
                    'cvh' => hash('sha512', $sValue),
                    'unh' => hash('sha512', $sUsername)
                )
            ));
            /** @var RememberedSessions $oSession */
            if ($oSession) {
                if ($this->getDI()->get('request')->isAjax()) {
                    return $sUsername;
                }
                if ($oSession->setRememberMeCookie($sUsername)) {
                    if ($oSession->save()) {
                        return $sUsername;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            } else {
                foreach (RememberedSessions::find('usernamehashed = "' . hash('sha512', $sUsername).'"') as $oSession) {
                    $oSession->delete();
                }
                $this->removeRememberMeCookie();
                /** @var \Bitfalls\Mailer\Mailer $oMailer */
                $oMailer = $this->getDI()->get('mailer');
                $oMailer->setDeveloperRecipient();
                $oMailer->expressMail(array(
                    'to' => 'bruno@skvorc.me',
                    'body' => 'It would seem there was an illegal remember me attempt on the user ' . $sUsername . '. All remembered sessions deleted.'
                ));
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * @param $cookievaluehashed
     * @return $this
     */
    public function setCookievaluehashed($cookievaluehashed)
    {
        $this->cookievaluehashed = $cookievaluehashed;
        return $this;
    }

    /**
     * @param $created_on
     * @return $this
     */
    public function setCreatedOn($created_on)
    {
        $this->created_on = $created_on;
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
     * Returns the value of field usernamehashed
     *
     * @return string
     */
    public function getUsernamehashed()
    {
        return $this->usernamehashed;
    }

    /**
     * Returns the value of field cookievaluehashed
     *
     * @return string
     */
    public function getCookievaluehashed()
    {
        return $this->cookievaluehashed;
    }

    /**
     * Returns the value of field created_on
     *
     * @return string
     */
    public function getCreatedOn()
    {
        return $this->created_on;
    }

}
