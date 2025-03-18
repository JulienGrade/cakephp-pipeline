<?php

declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class HomepageControllerTest extends TestCase
{
    use IntegrationTestTrait;

    public function testHomepageIsAccessible(): void
    {
        $this->get('/');
        $this->assertResponseOk();
    }
}
