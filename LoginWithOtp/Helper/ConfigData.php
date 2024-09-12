<?php

namespace Kitchen365\LoginWithOtp\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class ConfigData extends AbstractHelper
{
    protected $scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    public function isOtpLoginEnabled()
    {
        $otpLoginEnable = $this->scopeConfig->getValue('loginOtpSection/loginOtpGroup/enable', ScopeInterface::SCOPE_STORE);

        return $otpLoginEnable;
    }

    public function getOtpExpiry()
    {
        $otpExpiry = $this->scopeConfig->getValue('loginOtpSection/loginOtpGroup/otp_expiry', ScopeInterface::SCOPE_STORE);

        return (int)$otpExpiry;
    }

    public function getOtpLength()
    {
        $otpLength = $this->scopeConfig->getValue('loginOtpSection/loginOtpGroup/otp_length', ScopeInterface::SCOPE_STORE);
        
        return (int)$otpLength;
    }

    public function getOtpEmail()
    {
        $otpLength = $this->scopeConfig->getValue('loginOtpSection/loginOtpGroup/email_otp_template', ScopeInterface::SCOPE_STORE);
        
        return (int)$otpLength;
    }
}
