<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Quote\Guest;

use Magento\GraphQl\Quote\GetMaskedQuoteIdByReservedOrderId;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;

/**
 * Test for getting cart information
 */
class GetCartTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
    }

    /**
     * @magentoApiDataFixture Magento/Catalog/_files/product_simple.php
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_simple_product.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/add_virtual_product.php
     */
    public function testGetCart()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId);

        $response = $this->graphQlQuery($query);

        self::assertArrayHasKey('cart', $response);
        self::assertArrayHasKey('items', $response['cart']);
        self::assertCount(2, $response['cart']['items']);

        self::assertNotEmpty($response['cart']['items'][0]['id']);
        self::assertEquals(2, $response['cart']['items'][0]['qty']);
        self::assertEquals('simple', $response['cart']['items'][0]['product']['sku']);

        self::assertNotEmpty($response['cart']['items'][1]['id']);
        self::assertEquals(2, $response['cart']['items'][1]['qty']);
        self::assertEquals('virtual-product', $response['cart']['items'][1]['product']['sku']);
    }

    /**
     * _security
     * @magentoApiDataFixture Magento/Customer/_files/customer.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/customer/create_empty_cart.php
     */
    public function testGetCustomerCart()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId);

        $this->expectExceptionMessage(
            "The current user cannot perform operations on cart \"{$maskedQuoteId}\""
        );
        $this->graphQlQuery($query);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Could not find a cart with ID "non_existent_masked_id"
     */
    public function testGetNonExistentCart()
    {
        $maskedQuoteId = 'non_existent_masked_id';
        $query = $this->getQuery($maskedQuoteId);

        $this->graphQlQuery($query);
    }

    /**
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/guest/create_empty_cart.php
     * @magentoApiDataFixture Magento/GraphQl/Quote/_files/make_cart_inactive.php
     *
     * @expectedException \Exception
     * @expectedExceptionMessage Current user does not have an active cart.
     */
    public function testGetInactiveCart()
    {
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_quote');
        $query = $this->getQuery($maskedQuoteId);

        $this->graphQlQuery($query);
    }

    /**
     * @param string $maskedQuoteId
     * @return string
     */
    private function getQuery(string $maskedQuoteId): string
    {
        return <<<QUERY
{
  cart(cart_id: "{$maskedQuoteId}") {
    items {
      id
      qty
      product {
        sku
      }
    }
  }
}
QUERY;
    }
}
