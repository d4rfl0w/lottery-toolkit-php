<?php

declare(strict_types=1);

namespace LotteryToolkitExample;

use Rupcom\LotteryToolkit\Bridge\Symfony\LotteryToolkitBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new LotteryToolkitBundle();
    }

    public function getProjectDir(): string
    {
        return dirname(__DIR__);
    }
}
