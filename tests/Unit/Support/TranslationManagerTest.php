<?php

declare(strict_types=1);

namespace Fschirinzi\TranslationManager\Tests\Unit\Support;

use Fschirinzi\TranslationManager\Support\TranslationManager;
use Fschirinzi\TranslationManager\Tests\TestCase;
use TypeError;

final class TranslationManagerTest extends TestCase
{
    /** @test */
    public function it_does_check_default_path()
    {
        $tM = new TranslationManager(null);
        $this->assertStringContainsString('/resources/lang', $tM->getRootLocalePath());
    }

}
