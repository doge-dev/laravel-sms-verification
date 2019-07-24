<?php

namespace Freelabois\SMSVerification\Traits;

use Freelabois\SMSVerification\DogeDevSNSClientFacade;
use Freelabois\SMSVerification\Exceptions\TooManySMSVerificationAttempts;
use GuzzleHttp\Client;

/**
 * Trait VerifiesSMSCode
 *
 * Sends an SMS message containing a verificaiton code to a given mobile number (using AWS SNS)
 * Contains methods for verifying the user submitted code (authorising action) and monitoring attempts.
 *
 * @package Freelabois\SMSVerification\Traits
 */
trait VerifiesSMSCode
{
    protected $sms_verification_attempt_limit = 5;

    /**
     * Sends the SMS confirmation code
     *
     * @param $mobile
     * @return $this
     */
    public function setSMSVerificationNumber($mobile)
    {
        $code = $this->getNewCode();

       DogeDevSNSClientFacade::publish([
            "SenderId"    => $this->getSMSVerificationSender(),
            "SMSType"     => "Transactional",
            "Message"     => $this->getSMSVerificationMessage($code),
            "PhoneNumber" => $mobile,
        ]);

        $this->sms_verification_number = $mobile;
        $this->sms_verification_code   = $code;
        $this->sms_verification_status = false;

        if ($this->SMSVerificationAttemptLimitEnabled()) {

            $this->sms_verification_attempts = 0;
        }

        $this->save();

        return $this;
    }

    /**
     * Verifies the submitted SMS code
     *
     * @param $code
     * @return bool
     * @throws \Exception
     */
    public function verifySMSCode($code)
    {
        return $this
            ->validateSMSVerificationAttempts()
            ->setSMSVerificationStatus($this->sms_verification_code === $code);
    }

    /**
     * Validates SMS Verification attempts
     *
     * @return $this
     * @throws TooManySMSVerificationAttempts
     */
    private function validateSMSVerificationAttempts()
    {
        if ( $this->SMSVerificationAttemptLimitEnabled() && $this->SMSVerificationAttemptLimitExceeded()) {

            throw new TooManySMSVerificationAttempts("Too many SMS verification attempts. Please re-send the SMS code");
        }

        return $this;
    }

    /**
     * Checks it SMS Verification attempt limit is enabled
     *
     * @return bool
     */
    private function SMSVerificationAttemptLimitEnabled()
    {
        return !empty($this->sms_verification_attmpt_limit);
    }

    /**
     * Checks if SMS Verification attempt limit exceeded
     *
     * @return bool
     */
    private function SMSVerificationAttemptLimitExceeded()
    {
        return $this->sms_verification_attempts > $this->sms_verification_attempt_limit;
    }

    /**
     * Sets the SMS verification status
     *
     * @param bool $status
     * @return bool
     */
    private function setSMSVerificationStatus(bool $status)
    {
        $this->sms_verification_status = $status;
        $this->sms_verification_code = null;

        $this
            ->updateSMSVerificationAttempts($status)
            ->save();

        return $status;
    }

    /**
     * Updates the SMS Verification attempts
     *
     * @param bool $status
     * @return $this
     */
    private function updateSMSVerificationAttempts(bool $status)
    {
        if ($this->SMSVerificationAttemptLimitEnabled() && !$status) {

            $this->sms_verification_attempts++;
        }

        return $this;
    }

    /**
     * Gets the message to be sent with the SMS
     *
     * @param $code
     * @return string
     */
    protected function getSMSVerificationMessage($code)
    {
        return "Your SMS verification code is: $code";
    }

    /**
     * Gets the sender of the verification SMS
     *
     * @return string
     */
    protected function getSMSVerificationSender()
    {
        return env('APP_NAME');
    }
    
    protected function getNewCode()
    {
        return str_random(6);
    }
}
