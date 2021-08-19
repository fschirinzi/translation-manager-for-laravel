<?php

declare(strict_types=1);

namespace Fschirinzi\TranslationManager\Tests\Unit\Support;

use Fschirinzi\TranslationManager\Support\TranslationManager;
use Fschirinzi\TranslationManager\Tests\TestCase;

final class TranslationManagerTest extends TestCase
{
    /** @test */
    public function it_does_check_default_path()
    {
        $tM = new TranslationManager(null);
        $this->assertStringContainsString('/resources/lang', $tM->getRootLocalePath());
    }

    /** @test */
    public function it_can_load_data()
    {
        $tM = new TranslationManager(null);
        $tM->load();
        $this->assertStringContainsString('/resources/lang', $tM->getRootLocalePath());
    }
}
