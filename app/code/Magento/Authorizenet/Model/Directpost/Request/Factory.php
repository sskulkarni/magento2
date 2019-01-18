<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Authorizenet\Model\Directpost\Request;

use Magento\Authorizenet\Model\Request\Factory as AuthorizenetRequestFactory;

/**
 * Factory class for @see \Magento\Authorizenet\Model\Directpost\Request
 * @deprecated 2.3.1 Authorize.net is removing all support for this payment method in July 2019
 */
class Factory extends AuthorizenetRequestFactory
{
    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     * @deprecated
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \Magento\Authorizenet\Model\Directpost\Request::class
    ) {
        parent::__construct($objectManager, $instanceName);
    }
}
