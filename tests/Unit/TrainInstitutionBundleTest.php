<?php

declare(strict_types=1);

namespace Tourze\TrainInstitutionBundle\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Tourze\TrainInstitutionBundle\TrainInstitutionBundle;

class TrainInstitutionBundleTest extends TestCase
{
    private TrainInstitutionBundle $bundle;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bundle = new TrainInstitutionBundle();
    }

    public function testBundleImplementsInterface(): void
    {
        $this->assertInstanceOf(BundleInterface::class, $this->bundle);
    }

    public function testGetPath(): void
    {
        $path = $this->bundle->getPath();
        $this->assertStringEndsWith('train-institution-bundle/src', $path);
    }

    public function testGetName(): void
    {
        $this->assertSame('TrainInstitutionBundle', $this->bundle->getName());
    }

    public function testGetNamespace(): void
    {
        $namespace = $this->bundle->getNamespace();
        $this->assertSame('Tourze\TrainInstitutionBundle', $namespace);
    }
}