<?php

declare(strict_types=1);

namespace Coddin\CartBridge\Service;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;
use Magento\Quote\Model\Quote;

class CustomerQuote
{
    private CustomerSession $customerSession;
    private CheckoutSession $checkoutSession;
    private GuestCartManagementInterface $guestCartManagement;
    private CartManagementInterface $cartManagement;
    private MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId;
    private CartRepositoryInterface $quoteRepository;

    public function __construct(
        CustomerSession $customerSession,
        CheckoutSession $checkoutSession,
        GuestCartManagementInterface $guestCartManagement,
        CartManagementInterface $cartManagement,
        MaskedQuoteIdToQuoteId $maskedQuoteIdToQuoteId,
        CartRepositoryInterface $quoteRepository
    ) {
        $this->customerSession = $customerSession;
        $this->checkoutSession = $checkoutSession;
        $this->guestCartManagement = $guestCartManagement;
        $this->cartManagement = $cartManagement;
        $this->maskedQuoteIdToQuoteId = $maskedQuoteIdToQuoteId;
        $this->quoteRepository = $quoteRepository;
    }

    /**
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function getQuote(): CartInterface
    {
        $quote = null;

        if ($this->customerSession->isLoggedIn()) {
            try {
                // User is logged in and has a filled cart
                $quote = $this->checkoutSession->getQuote();
            } catch (NoSuchEntityException | LocalizedException $e) {
                // User is logged in but has an empty cart or
                // something went wrong when trying to get the customer cart
                $quoteId = $this->cartManagement->createEmptyCartForCustomer($this->customerSession->getCustomerId());
                $quote = $this->quoteRepository->get($quoteId);
            }
        } else {
            try {
                // User is not logged in and has a filled or empty cart
                $quote = $this->checkoutSession->getQuote();
            } catch (NoSuchEntityException | LocalizedException $e) {
                // User is not logged in and has an empty cart or
                // something went wrong when trying to get the guest cart
                $cartApiId = $this->guestCartManagement->createEmptyCart();
                $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartApiId);
                $quote = $this->quoteRepository->get($quoteId);
            }
        }

        // Always return a fully loaded Quote
        if ($quote->getId() === null) {
            $this->quoteRepository->save($quote);
            $quote = $this->quoteRepository->get($quote->getId());
        }

        $this->checkoutSession->setQuoteId($quote->getId());

        return $quote;
    }

    /**
     * @throws LocalizedException
     */
    public function addProduct(
        CartInterface $quote,
        ProductInterface $product,
        ?\Magento\Framework\DataObject $request = null
    ): void {
        $quote->addProduct($product, $request);

        $this->quoteRepository->save($quote);
    }
}
