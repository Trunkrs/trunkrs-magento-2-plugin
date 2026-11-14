<?php

namespace Trunkrs\Carrier\Test\Unit\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Trunkrs\Carrier\Model\Carrier\Shipping;

class ShippingTest extends TestCase
{
    public function testCollectRatesDoesNotCallApiWhenNotConfigured(): void
    {
        $shipping = $this->createShipping(false, 'NL');
        $shipping->expects($this->never())->method('getTrunkrsShippingMethod');

        $this->assertFalse($shipping->collectRates($this->createStub(RateRequest::class)));
    }

    public function testCollectRatesCallsApiWhenConfigured(): void
    {
        $shipping = $this->createShipping(true, 'NL');
        $shipping->expects($this->once())->method('getTrunkrsShippingMethod');

        $this->assertFalse($shipping->collectRates($this->createStub(RateRequest::class)));
    }

    /**
     * @return Shipping&MockObject
     */
    private function createShipping(bool $isConfigured, string $country): MockObject
    {
        $shipping = $this->getMockBuilder(Shipping::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getTrunkrsShippingMethod', 'getIsConfigured', 'getCountry'])
            ->getMock();
        $shipping->method('getIsConfigured')->willReturn($isConfigured);
        $shipping->method('getCountry')->willReturn($country);

        return $shipping;
    }
}
