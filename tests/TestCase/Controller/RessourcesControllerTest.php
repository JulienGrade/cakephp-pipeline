<?php
declare(strict_types=1);

namespace App\Test\TestCase\Controller;

use App\Model\Entity\Ressource;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class RessourcesControllerTest extends TestCase
{
    use IntegrationTestTrait;

    /**
     * Fixtures utilisées dans les tests.
     *
     * @var array
     */
    protected array $fixtures = [
        'app.Ressources',
        'app.Users'
    ];

    /**
     * Test de l'index (GET /ressources)
     */
    public function testIndex(): void
    {
        $this->get('/ressources');

        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertCount(1, $response['data']); // 1 ressource publique dans la fixture
        $this->assertEquals('Test Resource 1', $response['data'][0]['title']);
    }

    /**
     * Test de l'index utilisateur (GET /ressources/indexusers)
     */
    public function testIndexUsers(): void
    {
        $this->session([
            'Auth' => [
                'id' => '5f5c5e5f-5f5f-5e5e-5e5f-5f5e5f5e5f5e'
            ]
        ]);

        $this->get('/ressources/indexusers');

        $this->assertResponseOk();
        $this->assertContentType('application/json');

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertArrayHasKey('data', $response);
        $this->assertCount(1, $response['data']); // 1 ressource publique appartenant à l'utilisateur
        $this->assertEquals('Test Resource 1', $response['data'][0]['title']);
    }

    /**
     * Test de la vue d'une ressource spécifique (POST /ressources/view)
     */
    public function testView(): void
    {
        $this->post('/ressources/view/1a2b3c4d-1234-5678-9101-112131415161');

        $this->assertResponseOk();
        $response = json_decode((string)$this->_response->getBody(), true);

        $this->assertArrayHasKey('resource', $response);
        $this->assertEquals('Test Resource 1', $response['resource']['title']);
    }

    /**
     * Test de l'ajout d'une ressource (POST /ressources/add)
     */
    public function testAdd(): void
    {
        $data = [
            'title' => 'New Resource',
            'description' => 'New Description',
            'file_url' => 'https://example.com/new.jpg',
            'category' => 'New Category'
        ];

        $this->session([
            'Auth' => [
                'id' => '5f5c5e5f-5f5f-5e5e-5e5f-5f5e5f5e5f5e'
            ]
        ]);

        $this->post('/ressources/add', $data);

        $this->assertResponseOk();
        $response = json_decode((string)$this->_response->getBody(), true);

        $this->assertArrayHasKey('message', $response);
        $this->assertEquals('La ressource a été sauvegardée avec succès.', $response['message']);
    }

    /**
     * Test de la modification d'une ressource (PATCH /ressources/edit)
     */
    public function testEdit(): void
    {
        $data = [
            'title' => 'Updated Resource',
            'description' => 'Updated Description'
        ];

        $this->session([
            'Auth' => [
                'id' => '5f5c5e5f-5f5f-5e5e-5e5f-5f5e5f5e5f5e'
            ]
        ]);

        $this->patch('/ressources/edit/1a2b3c4d-1234-5678-9101-112131415161', $data);

        $this->assertResponseOk();

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertEquals('Ressource modifiée avec succès.', $response['message']);
        $this->assertEquals('Updated Resource', $response['data']['title']);
    }

    /**
     * Test de la suppression d'une ressource (POST /ressources/delete)
     */
    public function testDelete(): void
    {
        $this->session([
            'Auth' => [
                'id' => '5f5c5e5f-5f5f-5e5e-5e5f-5f5e5f5e5f5e'
            ]
        ]);

        $this->post('/ressources/delete/1a2b3c4d-1234-5678-9101-112131415161');

        $this->assertResponseOk();

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertEquals('Ressource supprimée avec succès.', $response['message']);
    }

    /**
     * Test de la publication d'une ressource (POST /ressources/publish)
     */
    public function testPublish(): void
    {
        $this->session([
            'Auth' => [
                'id' => '5f5c5e5f-5f5f-5e5e-5e5f-5f5e5f5e5f5e',
                'role' => 'admin'
            ]
        ]);

        $this->post('/ressources/publish/1a2b3c4d-1234-5678-9101-112131415161');

        $this->assertResponseOk();

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertEquals('Ressource rendue publique avec succès.', $response['message']);
    }

    /**
     * Test du filtrage par catégorie (GET /ressources/filterByCategory)
     */
    public function testFilterByCategory(): void
    {
        $this->get('/ressources/filterByCategory/Category%201');

        $this->assertResponseOk();

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertCount(1, $response['data']);
        $this->assertEquals('Category 1', $response['data'][0]['category']);
    }

    /**
     * Test de la recherche (GET /ressources/search)
     */
    public function testSearch(): void
    {
        $this->get('/ressources/search?q=Test');

        $this->assertResponseOk();

        $response = json_decode((string)$this->_response->getBody(), true);
        $this->assertCount(2, $response['results']);
        $this->assertEquals('Test Resource 1', $response['results'][0]['title']);
    }
}
