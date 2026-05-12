<?php

declare(strict_types=1);

namespace Tests\Liip\MetadataParser\NamingStrategy;

use Liip\MetadataParser\ModelParser\NamingStrategy\IdenticalPropertyNamingStrategy;
use Liip\MetadataParser\ModelParser\NamingStrategy\PropertyNamingStrategyInterface;
use Liip\MetadataParser\ModelParser\NamingStrategy\SnakeCasePropertyNamingStrategy;
use PHPUnit\Framework\TestCase;

/**
 * @small
 */
class NamingStrategyTest extends TestCase
{
    protected PropertyNamingStrategyInterface $jmsNamingStrategy;
    protected PropertyNamingStrategyInterface $snakeCaseNamingStrategy;
    protected PropertyNamingStrategyInterface $identicalNamingStrategy;

    protected function setUp(): void
    {
        $this->jmsNamingStrategy = SnakeCasePropertyNamingStrategy::jmsSnakeCase();
        $this->snakeCaseNamingStrategy = new SnakeCasePropertyNamingStrategy();
        $this->identicalNamingStrategy = new IdenticalPropertyNamingStrategy();
    }

    public function testSimpleLowerCase()
    {
        $propertyName = 'total';

        self::assertEquals('total', $this->jmsNamingStrategy->getSerializedName($propertyName));
        self::assertEquals('total', $this->snakeCaseNamingStrategy->getSerializedName($propertyName));
        self::assertEquals('total', $this->identicalNamingStrategy->getSerializedName($propertyName));
    }

    public function testSimpleCamelCase()
    {
        $propertyName = 'totalPrice';

        self::assertEquals('total_price', $this->jmsNamingStrategy->getSerializedName($propertyName));
        self::assertEquals('total_price', $this->snakeCaseNamingStrategy->getSerializedName($propertyName));
        self::assertEquals('totalPrice', $this->identicalNamingStrategy->getSerializedName($propertyName));
    }

    public function testAcronymCamelCase()
    {
        $propertyName = 'totalVAT';

        self::assertEquals('total_vat', $this->jmsNamingStrategy->getSerializedName($propertyName));
        self::assertEquals('total_v_a_t', $this->snakeCaseNamingStrategy->getSerializedName($propertyName));
        self::assertEquals('totalVAT', $this->identicalNamingStrategy->getSerializedName($propertyName));
    }
}
