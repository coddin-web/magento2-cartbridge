<?php

declare(strict_types=1);

namespace Coddin\CartBridge\Test\Unit\Service;

use Coddin\CartBridge\Service\CustomerQuote;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Model\MaskedQuoteIdToQuoteId;
use Magento\Quote\Model\Quote;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CustomerQuoteTest extends TestCase
{
    /** @var CustomerSession|MockObject */
    private $customerSession;
    /** @var CheckoutSession|MockObject */
    private $checkoutSession;
    /** @var GuestCartManagementInterface|MockObject */
    private $guestCartManagement;
    /** @var CartManagementInterface|MockObject */
    private $cartManagement;
    /** @var MaskedQuoteIdToQuoteId|MockObject */
    private $maskedQuoteIdToQuoteId;

    public function setUp(): void
    {
        $this->customerSession = $this->createPartialMock(CustomerSession::class, ['isLoggedIn', 'getCustomerId']);
        $this->checkoutSession = $this->createPartialMock(CheckoutSession::class, ['getQuote', 'setQuoteId']);
        $this->guestCartManagement = $this->createMock(GuestCartManagementInterface::class);
        $this->cartManagement = $this->createMock(CartManagementInterface::class);
        $this->maskedQuoteIdToQuoteId = $this->createPartialMock(MaskedQuoteIdToQuoteId::class, ['execute']);
        $this->quoteRepository = $this->createMock(CartRepositoryInterface::class);
    }

    /**
     * @covers CustomerQuote::getQuote
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetQuoteGuestWithoutExistingCart(): void
    {
        $customerQuote = $this->setupCustomerQuote();

        $this->customerSession
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(false);

        $this->checkoutSession
            ->expects(self::once())
            ->method('getQuote')
            ->willThrowException(new NoSuchEntityException());

        $this->guestCartManagement
            ->expects(self::once())
            ->method('createEmptyCart')
            ->willReturn('maskedID1234');

        $this->maskedQuoteIdToQuoteId
            ->expects(self::once())
            ->method('execute')
            ->with('maskedID1234')
            ->willReturn(123);

        $this->checkoutSession
            ->expects(self::once())
            ->method('setQuoteId')
            ->with(123);

        $quoteMock = $this->createQuote();

        $this->quoteRepository
            ->expects(self::once())
            ->method('get')
            ->with(123)
            ->willReturn($quoteMock);

        $quote = $customerQuote->getQuote();

        self::assertEquals($quoteMock, $quote);
    }

    /**
     * @covers CustomerQuote::getQuote
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetQuoteGuestWithExistingCart(): void
    {
        $customerQuote = $this->setupCustomerQuote();

        $this->customerSession
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(false);

        $quoteMock = $this->createQuote();
        $quoteMock
            ->expects(self::once())
            ->method('getEntityId')
            ->willReturn(1);

        $this->checkoutSession
            ->expects(self::once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quote = $customerQuote->getQuote();

        self::assertEquals($quoteMock, $quote);
    }

    /**
     * @covers CustomerQuote::getQuote
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetQuoteCustomerWithoutExistingCart(): void
    {
        $customerQuote = $this->setupCustomerQuote();

        $this->customerSession
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $this->checkoutSession
            ->expects(self::once())
            ->method('getQuote')
            ->willThrowException(new NoSuchEntityException());

        $this->customerSession
            ->expects(self::once())
            ->method('getCustomerId')
            ->willReturn(12);

        $this->cartManagement
            ->expects(self::once())
            ->method('createEmptyCartForCustomer')
            ->with(12)
            ->willReturn(123);

        $quoteMock = $this->createQuote();

        $this->quoteRepository
            ->expects(self::once())
            ->method('get')
            ->with(123)
            ->willReturn($quoteMock);

        $quote = $customerQuote->getQuote();

        self::assertEquals($quoteMock, $quote);
    }

    /**
     * @covers CustomerQuote::getQuote
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testGetQuoteCustomerWithExistingCart(): void
    {
        $customerQuote = $this->setupCustomerQuote();

        $this->customerSession
            ->expects(self::once())
            ->method('isLoggedIn')
            ->willReturn(true);

        $quoteMock = $this->createQuote();
        $quoteMock
            ->expects(self::once())
            ->method('getEntityId')
            ->willReturn(1);

        $this->checkoutSession
            ->expects(self::once())
            ->method('getQuote')
            ->willReturn($quoteMock);

        $quote = $customerQuote->getQuote();

        self::assertEquals($quoteMock, $quote);
    }

    private function setupCustomerQuote(): CustomerQuote
    {
        return new CustomerQuote(
            $this->customerSession,
            $this->checkoutSession,
            $this->guestCartManagement,
            $this->cartManagement,
            $this->maskedQuoteIdToQuoteId,
            $this->quoteRepository
        );
    }

    /**
     * @return Quote|MockObject
     */
    private function createQuote(): MockObject
    {
        return $this->createMock(Quote::class);
    }
}