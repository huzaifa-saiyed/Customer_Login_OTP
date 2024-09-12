<?php

namespace Kitchen365\LoginWithOtp\Controller\Otp;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Kitchen365\LoginWithOtp\Helper\ConfigData;

class VerifyOtp extends Action
{
    protected $jsonFactory;
    protected $customerFactory;
    protected $customerSession;
    protected $helper;

    public function __construct(
        Context $context,
        JsonFactory $jsonFactory,
        CustomerFactory $customerFactory,
        CustomerSession $customerSession,
        ConfigData $helper
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->customerFactory = $customerFactory;
        $this->customerSession = $customerSession;
        $this->helper = $helper;
        parent::__construct($context);
    }

    public function execute()
    {
        $otpEnable = $this->helper->isOtpLoginEnabled();

        if(!$otpEnable){
            return false;
        }
        
        $result = $this->jsonFactory->create();
        $email = $this->getRequest()->getParam('email');
        $otpInput = $this->getRequest()->getParam('otp');

        $customer = $this->customerFactory->create()->getCollection()
            ->addFieldToFilter('email', $email)
            ->getFirstItem();

        if (!$customer->getId()) {
            return $result->setData(['success' => false, 'message' => __('Email not found.')]);
        }

        $sessionOtpData = $this->customerSession->getData('otp_' . $email);

        if ($sessionOtpData) {

            $sessionOtp = $sessionOtpData['otp'];
            $sessionOtpExpiry = $sessionOtpData['expiry'];

            // $sessionOtp = $this->customerSession->getOtpCode();
            // $sessionOtpExpiry = $this->customerSession->getOtpExpiry();
    
            if ($otpInput == $sessionOtp && time() <= $sessionOtpExpiry) {
                $this->customerSession->setCustomerAsLoggedIn($customer);
                return $result->setData(['success' => true, 'message' => __('Login successful.')]);
            } else {
                return $result->setData(['success' => false, 'message' => __('Invalid or expired OTP.')]);
            }
        } else {
            return $result->setData(['success' => false, 'message' => __('No OTP found for this email.')]);
        }
    }
}
