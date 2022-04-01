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
                $quoteId = $quote->getEntityId();
            } catch (NoSuchEntityException | LocalizedException $e) {
                // User is logged in but has an empty cart
                $quoteId = $this->cartManagement->createEmptyCartForCustomer($this->customerSession->getCustomerId());
            }
        } else {
            try {
                // User is not logged in and has a filled cart
                $quote = $this->checkoutSession->getQuote();
                $quoteId = $quote->getEntityId();
            } catch (NoSuchEntityException | LocalizedException $e) {
                // User is not logged in and has an empty cart
                $cartApiId = $this->guestCartManagement->createEmptyCart();
                $quoteId = $this->maskedQuoteIdToQuoteId->execute($cartApiId);
                $this->checkoutSession->setQuoteId($quoteId);
            }
        }

        if ($quote === null) {
            $quote = $this->quoteRepository->get($quoteId);
        }

        return $quote;
    }

    /**
     * @throws LocalizedException
     */
    public function addProduct(
        CartInterface $quote,
        ProductInterface $product,
        int $quantity = 1
    ): void {
        $quote->addProduct($product, $quantity);

        $this->quoteRepository->save($quote);
    }
}
