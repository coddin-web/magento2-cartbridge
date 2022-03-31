<?php

declare(strict_types=1);

namespace Coddin\CartBridge\Controller\Index;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;
use Magento\Quote\Model\QuoteIdToMaskedQuoteId;

class Index implements ActionInterface
{
    private JsonFactory $jsonFactory;
    private CustomerSession $customerSession;
    private CheckoutSession $checkoutSession;
    private GuestCartManagementInterface $guestCartManagement;
    private CartManagementInterface $cartManagement;
    private QuoteIdToMaskedQuoteId $quoteIdToMaskedQuoteId;
    private MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId;

    public function __construct(
        JsonFactory $jsonFactory,
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        GuestCartManagementInterface $guestCartManagement,
        CartManagementInterface $cartManagement,
        QuoteIdToMaskedQuoteId $quoteIdToMaskedQuoteId,
        MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->guestCartManagement = $guestCartManagement;
        $this->cartManagement = $cartManagement;
        $this->quoteIdToMaskedQuoteId = $quoteIdToMaskedQuoteId;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
    }

    /**
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function execute(): Json
    {
        $cartApiId = null;
        $result = $this->jsonFactory->create();
        $responseCode = 200;

        if ($this->customerSession->isLoggedIn()) {
            try {
                // User is logged in and has a filled cart
                $quote = $this->checkoutSession->getQuote();
                $quoteId = $quote->getEntityId();
            } catch (NoSuchEntityException | LocalizedException $e) {
                // User is logged in but has an empty cart
                $quoteId = $this->cartManagement->createEmptyCartForCustomer($this->customerSession->getCustomerId());
            }
        } else {
            $quote = $this->checkoutSession->getQuote();
            $quoteId = $quote->getEntityId();

            // User is not logged in and has not already added a product to his cart
            if ($quoteId === null) {
                // Initialized Quote model but no actual Quote
                $cartApiId = $this->guestCartManagement->createEmptyCart();
                $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartApiId);
                $this->checkoutSession->setQuoteId($quoteId);
                $responseCode = 201;
            }
        }

        if ($cartApiId === null) {
            try {
                $cartApiId = $this->quoteIdToMaskedQuoteId->execute((int)$quoteId);
            } catch (NoSuchEntityException $e) {
                $result->setHttpResponseCode(404);

                return $result;
            }
        }

        $result->setHttpResponseCode($responseCode);
        $result->setData($cartApiId);

        return $result;
    }
}
