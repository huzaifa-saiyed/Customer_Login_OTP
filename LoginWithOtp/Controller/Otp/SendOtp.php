<?php

namespace Kitchen365\LoginWithOtp\Controller\Otp;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Kitchen365\LoginWithOtp\Helper\ConfigData;

class SendOtp extends Action
{
    protected $jsonFactory;
    protected $customerFactory;
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $scopeConfig;
    protected $helper;
    protected $customerSession;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CustomerFactory $customerFactory,
        TransportBuilder $transportBuilder,
        StateInterface $inlineTranslation,
        ScopeConfigInterface $scopeConfig,
        ConfigData $helper,
        CustomerSession $customerSession
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->customerFactory = $customerFactory;
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

    public function execute()
    {
        $otpEnable = $this->helper->isOtpLoginEnabled();

        if(!$otpEnable){
            return false;
        }
        
        $result = $this->jsonFactory->create();

        $otpLength = $this->helper->getOtpLength();
        $otpExpiry = $this->helper->getOtpExpiry();

        $email = $this->getRequest()->getParam('email');

        $customer = $this->customerFactory->create()->getCollection()
            ->addFieldToFilter('email', $email)
            ->getFirstItem();

        if (!$customer->getId()) {
            return $result->setData(['success' => false, 'message' => __('Email not registered.')]);
        }

        $otp = rand(pow(10, $otpLength - 1), pow(10, $otpLength) - 1);

        $this->customerSession->setData('otp_' . $email, ['otp' => $otp, 'expiry' => time() + ($otpExpiry * 60)]);

        // $this->customerSession->setOtpCode($otp);
        // $this->customerSession->setOtpExpiry(time() + ($otpExpiry * 60));

        $templateOptions = [
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => \Magento\Store\Model\Store::DEFAULT_STORE_ID
        ];

        $adminEmail = $this->scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE);
        $adminName = $this->scopeConfig->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE);

        $otpEmail = $this->helper->getOtpEmail() ?: 'loginOtpSection_loginOtpGroup_email_otp_template';

        try {
            $this->inlineTranslation->suspend();

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($otpEmail)
                ->setTemplateOptions($templateOptions)
                ->setTemplateVars(['otp' => $otp])
                ->setFrom(['email' => $adminEmail, 'name' => $adminName])
                ->addTo($email)
                ->getTransport();

            $transport->sendMessage();

            $this->inlineTranslation->resume();

            return $result->setData([
                'success' => true, 
                'message' => __('OTP sent to your email.'),
                'expiry' => time() + ($otpExpiry * 60)
            ]);
        } catch (\Exception $e) {
            return $result->setData(['success' => false, 'message' => __('Failed to send OTP.')]);
        }
    }
}
