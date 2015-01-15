<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Quote\Api;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\TestFramework\ObjectManager;
use Magento\TestFramework\TestCase\WebapiAbstract;
use Magento\Webapi\Model\Rest\Config as RestConfig;

class CartRepositoryInterfaceTest extends WebapiAbstract
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchBuilder;

    /**
     * @var SortOrderBuilder
     */
    private $sortOrderBuilder;

    /**
     * @var FilterBuilder
     */
    private $filterBuilder;

    protected function setUp()
    {
        $this->objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();
        $this->filterBuilder = $this->objectManager->create(
            'Magento\Framework\Api\FilterBuilder'
        );
        $this->sortOrderBuilder = $this->objectManager->create(
            'Magento\Framework\Api\SortOrderBuilder'
        );
        $this->searchBuilder = $this->objectManager->create(
            'Magento\Framework\Api\SearchCriteriaBuilder'
        );
    }

    protected function tearDown()
    {
        try {
            $cart = $this->getCart('test01');
            $cart->delete();
        } catch (\InvalidArgumentException $e) {
            // Do nothing if cart fixture was not used
        }
        parent::tearDown();
    }

    /**
     * Retrieve quote by given reserved order ID
     *
     * @param string $reservedOrderId
     * @return \Magento\Quote\Model\Quote
     * @throws \InvalidArgumentException
     */
    protected function getCart($reservedOrderId)
    {
        /** @var $cart \Magento\Quote\Model\Quote */
        $cart = $this->objectManager->get('Magento\Quote\Model\Quote');
        $cart->load($reservedOrderId, 'reserved_order_id');
        if (!$cart->getId()) {
            throw new \InvalidArgumentException('There is no quote with provided reserved order ID.');
        }
        return $cart;
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/quote.php
     */
    public function testGetCart()
    {
        $cart = $this->getCart('test01');
        $cartId = $cart->getId();

        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/carts/' . $cartId,
                'httpMethod' => RestConfig::HTTP_METHOD_GET,
            ],
            'soap' => [
                'service' => 'quoteQuoteRepositoryV1',
                'serviceVersion' => 'V1',
                'operation' => 'quoteQuoteRepositoryV1GetCart',
            ],
        ];

        $requestData = ['cartId' => $cartId];
        $cartData = $this->_webApiCall($serviceInfo, $requestData);
        $this->assertEquals($cart->getId(), $cartData['id']);
        $this->assertEquals($cart->getCreatedAt(), $cartData['created_at']);
        $this->assertEquals($cart->getUpdatedAt(), $cartData['updated_at']);
        //this check will be uncommented when all cart related services are ready
//        $this->assertEquals($cart->getStoreId(), $cartData['store_id']);
        $this->assertEquals($cart->getIsActive(), $cartData['is_active']);
        $this->assertEquals($cart->getIsVirtual(), $cartData['is_virtual']);
        $this->assertEquals($cart->getOrigOrderId(), $cartData['orig_order_id']);
        $this->assertEquals($cart->getItemsCount(), $cartData['items_count']);
        $this->assertEquals($cart->getItemsQty(), $cartData['items_qty']);
        //following checks will be uncommented when all cart related services are ready
//        $this->assertContains('customer', $cartData);
//        $this->assertEquals(1, $cartData['customer']['is_guest']);
//        $this->assertContains('totals', $cartData);
//        $this->assertEquals($cart->getSubtotal(), $cartData['totals']['subtotal']);
//        $this->assertEquals($cart->getGrandTotal(), $cartData['totals']['grand_total']);
//        $this->assertContains('currency', $cartData);
//        $this->assertEquals($cart->getGlobalCurrencyCode(), $cartData['currency']['global_currency_code']);
//        $this->assertEquals($cart->getBaseCurrencyCode(), $cartData['currency']['base_currency_code']);
//        $this->assertEquals($cart->getQuoteCurrencyCode(), $cartData['currency']['quote_currency_code']);
//        $this->assertEquals($cart->getStoreCurrencyCode(), $cartData['currency']['store_currency_code']);
//        $this->assertEquals($cart->getBaseToGlobalRate(), $cartData['currency']['base_to_global_rate']);
//        $this->assertEquals($cart->getBaseToQuoteRate(), $cartData['currency']['base_to_quote_rate']);
//        $this->assertEquals($cart->getStoreToBaseRate(), $cartData['currency']['store_to_base_rate']);
//        $this->assertEquals($cart->getStoreToQuoteRate(), $cartData['currency']['store_to_quote_rate']);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage No such entity with
     */
    public function testGetCartThrowsExceptionIfThereIsNoCartWithProvidedId()
    {
        $cartId = 9999;

        $serviceInfo = [
            'soap' => [
                'service' => 'quoteQuoteRepositoryV1',
                'serviceVersion' => 'V1',
                'operation' => 'quoteQuoteRepositoryV1GetCart',
            ],
            'rest' => [
                'resourcePath' => '/V1/carts/' . $cartId,
                'httpMethod' => RestConfig::HTTP_METHOD_GET,
            ],
        ];

        $requestData = ['cartId' => $cartId];
        $this->_webApiCall($serviceInfo, $requestData);
    }

    /**
     * @magentoApiDataFixture Magento/Sales/_files/quote.php
     */
    public function testGetCartList()
    {
        $cart = $this->getCart('test01');

        $serviceInfo = [
            'rest' => [
                'resourcePath' => '/V1/carts',
                'httpMethod' => RestConfig::HTTP_METHOD_PUT,
            ],
            'soap' => [
                'service' => 'quoteQuoteRepositoryV1',
                'serviceVersion' => 'V1',
                'operation' => 'quoteQuoteRepositoryV1GetCartList',
            ],
        ];

        // The following two filters are used as alternatives. The target cart does not match the first one.
        $grandTotalFilter = $this->filterBuilder->setField('grand_total')
            ->setConditionType('gteq')
            ->setValue(15)
            ->create();
        $subtotalFilter = $this->filterBuilder->setField('subtotal')
            ->setConditionType('eq')
            ->setValue($cart->getSubtotal())
            ->create();

        $yesterdayDate = (new \DateTime())->sub(new \DateInterval('P1D'))->format('Y-m-d');
        $tomorrowDate = (new \DateTime())->add(new \DateInterval('P1D'))->format('Y-m-d');
        $minCreatedAtFilter = $this->filterBuilder->setField('created_at')
            ->setConditionType('gteq')
            ->setValue($yesterdayDate)
            ->create();
        $maxCreatedAtFilter = $this->filterBuilder->setField('created_at')
            ->setConditionType('lteq')
            ->setValue($tomorrowDate)
            ->create();

        $this->searchBuilder->addFilter([$grandTotalFilter, $subtotalFilter]);
        $this->searchBuilder->addFilter([$minCreatedAtFilter]);
        $this->searchBuilder->addFilter([$maxCreatedAtFilter]);
        /** @var SortOrder $sortOrder */
        $sortOrder = $this->sortOrderBuilder->setField('subtotal')->setDirection(SearchCriteria::SORT_ASC)->create();
        $this->searchBuilder->setSortOrders([$sortOrder]);
        $searchCriteria = $this->searchBuilder->create()->__toArray();

        $requestData = ['searchCriteria' => $searchCriteria];
        $searchResult = $this->_webApiCall($serviceInfo, $requestData);
        $this->assertArrayHasKey('total_count', $searchResult);
        $this->assertEquals(1, $searchResult['total_count']);
        $this->assertArrayHasKey('items', $searchResult);
        $this->assertCount(1, $searchResult['items']);

        $cartData = $searchResult['items'][0];
        $this->assertEquals($cart->getId(), $cartData['id']);
        $this->assertEquals($cart->getCreatedAt(), $cartData['created_at']);
        $this->assertEquals($cart->getUpdatedAt(), $cartData['updated_at']);
        $this->assertEquals($cart->getIsActive(), $cartData['is_active']);
        //following checks will be uncommented when all cart related services are ready
//        $this->assertEquals($cart->getStoreId(), $cartData['store_id']);

//        $this->assertContains('totals', $cartData);
//        $this->assertEquals($cart->getBaseSubtotal(), $cartData['totals']['base_subtotal']);
//        $this->assertEquals($cart->getBaseGrandTotal(), $cartData['totals']['base_grand_total']);
//        $this->assertContains('customer', $cartData);
//        $this->assertEquals(1, $cartData['customer']['is_guest']);
    }

    /**
     * @expectedException \Exception
     */
    public function testGetCartListThrowsExceptionIfProvidedSearchFieldIsInvalid()
    {
        $serviceInfo = [
            'soap' => [
                'service' => 'quoteQuoteRepositoryV1',
                'serviceVersion' => 'V1',
                'operation' => 'quoteQuoteRepositoryV1GetCartList',
            ],
            'rest' => [
                'resourcePath' => '/V1/carts',
                'httpMethod' => RestConfig::HTTP_METHOD_PUT,
            ],
        ];

        $invalidFilter = $this->filterBuilder->setField('invalid_field')
            ->setConditionType('eq')
            ->setValue(0)
            ->create();

        $this->searchBuilder->addFilter([$invalidFilter]);
        $searchCriteria = $this->searchBuilder->create()->__toArray();
        $requestData = ['searchCriteria' => $searchCriteria];
        $this->_webApiCall($serviceInfo, $requestData);
    }
}
