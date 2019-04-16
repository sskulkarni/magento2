<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\GraphQl\Quote;

use Magento\TestFramework\Helper\Bootstrap;
use Magento\TestFramework\TestCase\GraphQlAbstract;
use Magento\Catalog\Api\ProductCustomOptionRepositoryInterface;

/**
 * Add virtual product with custom options to cart testcases
 */
class AddVirtualProductWithCustomOptionsToCartTest extends GraphQlAbstract
{
    /**
     * @var GetMaskedQuoteIdByReservedOrderId
     */
    private $getMaskedQuoteIdByReservedOrderId;

    /**
     * @var ProductCustomOptionRepositoryInterface
     */
    private $productCustomOptionsRepository;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $objectManager = Bootstrap::getObjectManager();
        $this->getMaskedQuoteIdByReservedOrderId = $objectManager->get(GetMaskedQuoteIdByReservedOrderId::class);
        $this->productCustomOptionsRepository = $objectManager->get(ProductCustomOptionRepositoryInterface::class);
    }

    /**
     * Test adding a virtual product to the shopping cart with all supported
     * customizable options assigned
     *
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual_with_options.php
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     */
    public function testAddVirtualProductWithOptions()
    {
        $sku = 'virtual';
        $qty = 1;
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_order_1');

        $customOptionsValues = $this->getCustomOptionsValuesForQuery($sku);

        /* Generate customizable options fragment for GraphQl request */
        $queryCustomizableOptions = preg_replace('/"([^"]+)"\s*:\s*/', '$1:', json_encode($customOptionsValues));

        $query = <<<QUERY
mutation {  
  addVirtualProductsToCart(
    input: {
      cart_id: "{$maskedQuoteId}", 
      cartItems: [
        {
          data: {
            qty: $qty
            sku: "$sku"
          },
          customizable_options: $queryCustomizableOptions  
        }
      ]
    }
  ) {
    cart {
      items {
        ... on VirtualCartItem {
          customizable_options {
            label
              values {
                value  
              }
            }
        }
      }
    }
  }
}
QUERY;

        $response = $this->graphQlMutation($query);

        self::assertArrayHasKey('items', $response['addVirtualProductsToCart']['cart']);
        self::assertCount(1, $response['addVirtualProductsToCart']['cart']);

        $customizableOptionsOutput = $response['addVirtualProductsToCart']['cart']['items'][0]['customizable_options'];
        $assignedOptionsCount = count($customOptionsValues);
        for ($counter = 0; $counter < $assignedOptionsCount; $counter++) {
            self::assertEquals(
                $customOptionsValues[$counter]['value'],
                $customizableOptionsOutput[$counter]['values'][0]['value']
            );
        }
    }

    /**
     * Test adding a virtual product with empty values for required options
     *
     * @magentoApiDataFixture Magento/Catalog/_files/product_virtual_with_options.php
     * @magentoApiDataFixture Magento/Checkout/_files/active_quote.php
     */
    public function testAddVirtualProductWithNoRequiredOptionsSet()
    {
        $sku = 'virtual';
        $qty = 1;
        $maskedQuoteId = $this->getMaskedQuoteIdByReservedOrderId->execute('test_order_1');

        $query = <<<QUERY
mutation {  
  addVirtualProductsToCart(
    input: {
      cart_id: "{$maskedQuoteId}", 
      cartItems: [
        {
          data: {
            qty: $qty
            sku: "$sku"
          }
        }
      ]
    }
  ) {
    cart {
      items {
        ... on VirtualCartItem {
          customizable_options {
            label
              values {
                value  
              }
            }
        }
      }
    }
  }
}
QUERY;

        self::expectExceptionMessage(
            'The product\'s required option(s) weren\'t entered. Make sure the options are entered and try again.'
        );

        $this->graphQlMutation($query);
    }

    /**
     * Generate an array with test values for customizable options
     * based on the option type
     *
     * @param string $sku
     * @return array
     */
    private function getCustomOptionsValuesForQuery(string $sku): array
    {
        $customOptions = $this->productCustomOptionsRepository->getList($sku);
        $customOptionsValues = [];

        foreach ($customOptions as $customOption) {
            $optionType = $customOption->getType();
            if ($optionType == 'field' || $optionType == 'area') {
                $customOptionsValues[] = [
                    'id' => (int) $customOption->getOptionId(),
                    'value' => 'test'
                ];
            } elseif ($optionType == 'drop_down') {
                $optionSelectValues = $customOption->getValues();
                $customOptionsValues[] = [
                    'id' => (int) $customOption->getOptionId(),
                    'value' => reset($optionSelectValues)->getOptionTypeId()
                ];
            }
        }

        return $customOptionsValues;
    }
}
