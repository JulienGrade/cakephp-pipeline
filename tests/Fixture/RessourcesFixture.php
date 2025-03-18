<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class RessourcesFixture extends TestFixture
{
    public array $import = ['table' => 'ressources'];

    public function init(): void
    {
        $this->records = [
            [
                'id' => '1a2b3c4d-1234-5678-9101-112131415161',
                'title' => 'Test Resource 1',
                'description' => 'Description for Test Resource 1',
                'file_url' => 'https://example.com/test1.jpg',
                'visibility' => 'public',
                'owner_id' => '5f5c5e5f-5f5f-5e5e-5e5f-5f5e5f5e5f5e',
                'created' => '2025-03-17 10:00:00',
                'modified' => '2025-03-17 10:00:00',
                'is_public' => true,
                'category' => 'Category 1'
            ],
            [
                'id' => '2b3c4d5e-2234-5678-9101-212131415161',
                'title' => 'Test Resource 2',
                'description' => 'Description for Test Resource 2',
                'file_url' => 'https://example.com/test2.jpg',
                'visibility' => 'private',
                'owner_id' => '6f6c6e6f-6f6f-6e6e-6e6f-6f6e6f6e6f6e',
                'created' => '2025-03-17 11:00:00',
                'modified' => '2025-03-17 11:00:00',
                'is_public' => false,
                'category' => 'Category 2'
            ]
        ];
        parent::init();
    }
}
