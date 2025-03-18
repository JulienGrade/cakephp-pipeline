<?php
declare(strict_types=1);

namespace App\Controller;

use Authentication\Controller\Component\AuthenticationComponent;
/**
 * Ressources Controller
 *
 * @property \App\Model\Table\RessourcesTable $Ressources
 */
class RessourcesController extends AppController
{

    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Authentication.Authentication');
    }

    /**
     * Index method
     *
     * @return \Cake\Http\Response|null|void Renders view
     */
    public function index()
    {
        $query = $this->Ressources->find()
            ->contain(['Owners'])
            ->where(['Ressources.is_public' => true])  // Filtre par ressources publiques
            ->order(['Ressources.created' => 'DESC']);

        $ressources = $this->paginate($query, [
            'limit' => 25,
            'order' => [
                'Ressources.created' => 'DESC'
            ]
        ]);

        if ($ressources->isEmpty()) {
            return $this->response->withType('application/json')
                ->withStatus(404)
                ->withStringBody(json_encode(['message' => 'Aucune ressource trouvée.']));
        }

        return $this->response->withType('application/json')
            ->withStatus(200)
            ->withStringBody(json_encode([
                'message' => 'Ressources chargées avec succès.',
                'data' => $ressources
            ]));
    }
    public function indexusers() {
        $user = $this->request->getAttribute('identity');

        // Vérification si l'utilisateur est authentifié
        if (!$user) {
            return $this->response->withType('application/json')
                ->withStatus(401)
                ->withStringBody(json_encode(['message' => 'Utilisateur non authentifié.']));
        }

        $userId = $user->getIdentifier();

        // Requête pour récupérer les ressources publiques de l'utilisateur
        $query = $this->Ressources->find()
            ->contain(['Owners'])
            ->where(['Ressources.owner_id' => $userId, 'Ressources.is_public' => true]) // Filtre par utilisateur
            ->order(['Ressources.created' => 'DESC']);

        // Pagination
        $ressources = $this->paginate($query, [
            'limit' => 25,
            'order' => ['Ressources.created' => 'DESC']
        ]);

        // Si aucune ressource n'est trouvée
        if ($ressources->isEmpty()) {
            return $this->response->withType('application/json')
                ->withStatus(200) // Statut 200 pour indiquer qu'il n'y a pas d'erreur
                ->withStringBody(json_encode([
                    'message' => 'Aucune ressource trouvée.',
                    'data' => []
                ]));
        }

        // Récupération du nombre total de ressources publiques pour l'utilisateur
        $totalCount = $this->Ressources->find()
            ->where(['Ressources.owner_id' => $userId, 'Ressources.is_public' => true])
            ->count();

        // Réponse avec les ressources et le total des ressources
        return $this->response->withType('application/json')
            ->withStatus(200)
            ->withStringBody(json_encode([
                'message' => 'Ressources de l\'utilisateur chargées avec succès.',
                'data' => $ressources,
                'totalCount' => $totalCount
            ]));
    }

    /**
     * View method
     *
     * @param string|null $id Ressource id.
     * @return \Cake\Http\Response|null|void Renders view
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function view($id = null) //A MOFIDIER PLUS TARD AVEC LES COMMENTAIRES ET LES LIKES
    {
        if (!$this->request->is('post')) {
            return $this->response->withType('application/json')
                ->withStatus(405)
                ->withStringBody(json_encode(['error' => 'Méthode non autorisée. Utilisez POST.']));
        }

        if (!$id) {
            return $this->response->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['error' => 'ID de la ressource requis.']));
        }

        $resource = $this->Ressources->find()
            ->where(['Ressources.id' => $id])
            ->contain(['Owners'])
            ->first();

        if (!$resource) {
            return $this->response->withType('application/json')
                ->withStatus(404)
                ->withStringBody(json_encode(['error' => 'Ressource non trouvée.']));
        }

        return $this->response->withType('application/json')
            ->withStatus(200)
            ->withStringBody(json_encode(['resource' => $resource]));
    }

    /**
     * Add method
     *
     * @return \Cake\Http\Response|null|void Redirects on successful add, renders view otherwise.
     */
    public function add()
    {
        $ressource = $this->Ressources->newEmptyEntity();
        $ressource->is_public = false; // Rendre la ressource privée par défaut
        $category = $this->request->getData('category');

        if ($category) {
            $ressource->category = $category;
        }
        if ($this->request->is('post')) {
            $data = $this->request->getData();

            // Vérifier si une URL d'image est fournie
            if (!empty($data['file_url']) && filter_var($data['file_url'], FILTER_VALIDATE_URL)) {
                $optimizedUrl = $this->optimizeAndUploadImage($data['file_url']);

                if (!$optimizedUrl) {
                    return $this->response->withType('application/json')
                        ->withStatus(400)
                        ->withStringBody(json_encode(['error' => 'Échec de l’optimisation de l’image.']));
                }

                $data['file_url'] = $optimizedUrl;

                $ressource = $this->Ressources->patchEntity($ressource, $data);

                // Assigner l'ID du propriétaire si l'utilisateur est connecté
                $user = $this->request->getAttribute('identity');
                if ($user) {
                    $ressource->owner_id = $user->getIdentifier();
                }

                if ($this->Ressources->save($ressource)) {
                    $owner = $this->Ressources->Owners->get($ressource->owner_id);
                    return $this->response->withType('application/json')
                        ->withStatus(200)
                        ->withStringBody(json_encode([
                            'message' => 'La ressource a été sauvegardée avec succès.',
                            'ressource' => [
                                'title'       => $ressource->title,
                                'description' => $ressource->description,
                                'owner'       => [
                                    'id'   => $ressource->owner_id,
                                    'name' => $owner->username
                                ],
                                'file_url'    => $ressource->file_url,
                                'is_public'   => $ressource->is_public,
                                'category'    => $ressource->category,
                            ]
                        ]));
                } else {
                    return $this->response->withType('application/json')
                        ->withStatus(400)
                        ->withStringBody(json_encode(['error' => 'Impossible de sauvegarder la ressource.']));
                }
            } else {
                return $this->response->withType('application/json')
                    ->withStatus(400)
                    ->withStringBody(json_encode(['error' => 'URL invalide ou manquante.']));
            }
        }

        return $this->response->withType('application/json')
            ->withStatus(400)
            ->withStringBody(json_encode(['error' => 'Requête invalide.']));
    }

    /**
     * Upload l'image vers Imgur et retourne l'URL de l'image
     */
    private function uploadToImgur($imagePath)
    {
        $clientId = '938538a0a079805'; // Client ID
        $url = 'https://api.imgur.com/3/upload';

        $imageData = file_get_contents($imagePath);
        $postFields = ['image' => base64_encode($imageData)];

        $headers = [
            "Authorization: Client-ID $clientId"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return $result['data']['link'] ?? null; // Retourne l'URL de l'image ou null si erreur
    }

    /**
     * Télécharge, compresse et redimensionne une image avant de la stocker sur un service externe.
     */
    private function optimizeAndUploadImage($imageUrl)
    {
        // Télécharge l'image depuis l'URL
        $imageData = file_get_contents($imageUrl);
        if ($imageData === false) {
            return null; // Si l'image ne peut pas être téléchargée
        }

        // Créer une ressource image
        $image = imagecreatefromstring($imageData);
        if ($image === false) {
            return null; // Si l'image ne peut pas être lue
        }

        // Redimensionner l'image si nécessaire
        $width = imagesx($image);
        $height = imagesy($image);
        $maxWidth = 1200;
        $maxHeight = 800;

        if ($width > $maxWidth || $height > $maxHeight) {
            $ratio = min($maxWidth / $width, $maxHeight / $height);
            $newWidth = (int) ($width * $ratio);
            $newHeight = (int) ($height * $ratio);

            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

            // Remplace l'image par la version redimensionnée
            $image = $resizedImage;
        }

        // Sauvegarde l'image compressée temporairement
        $tempPath = WWW_ROOT . 'tmp' . DS . 'optimized_' . time() . '.jpg';
        imagejpeg($image, $tempPath, 75);
        imagedestroy($image);

        // Upload de l'image vers Imgur
        $imgurUrl = $this->uploadToImgur($tempPath);

        // Supprime l'image temporaire
        unlink($tempPath);

        return $imgurUrl;
    }

    public function publish($id)
    {
        $ressource = $this->Ressources->get($id);

        $user = $this->request->getAttribute('identity');
        if (!$user || $user->get('role') !== 'admin') {
            return $this->response->withType('application/json')
                ->withStatus(403)
                ->withStringBody(json_encode(['message' => 'Accès refusé.']));
        }
        // Mettre à jour la ressource en public
        $ressource->is_public = true;

        if ($this->Ressources->save($ressource)) {
            return $this->response->withType('application/json')
                ->withStatus(200)
                ->withStringBody(json_encode(['message' => 'Ressource rendue publique avec succès.']));
        }

        return $this->response->withType('application/json')
            ->withStatus(500)
            ->withStringBody(json_encode(['message' => 'Erreur lors de la mise à jour.']));
    }
    public function edit($id = null)
    {
        $resource = $this->Ressources->get($id);

        if (!$resource) {
            return $this->response->withType('application/json')
                ->withStatus(404)
                ->withStringBody(json_encode(['error' => 'Ressource introuvable.']));
        }

        $currentUser = $this->request->getAttribute('identity');

        if ($currentUser->getIdentifier() !== $resource->owner_id) {
            return $this->response->withType('application/json')
                ->withStatus(403)
                ->withStringBody(json_encode(['error' => 'Vous n\'avez pas les droits nécessaires pour modifier cette ressource.']));
        }

        $category = $this->request->getData('category');
        if ($category) {
            $resource->category = $category;
        }

        if ($this->request->is(['patch', 'post', 'put'])) {
            $data = $this->request->getData();

            // Si file_url est envoyé et qu'il est vide, on le supprime
            if (array_key_exists('file_url', $data) && empty($data['file_url'])) {
                $resource->file_url = null;
            } elseif (isset($data['file_url']) && $data['file_url'] !== $resource->file_url) {
                return $this->response->withType('application/json')
                    ->withStatus(403)
                    ->withStringBody(json_encode(['error' => 'Modification de l\'image non autorisée.']));
            }

            $this->Ressources->patchEntity($resource, $data);

            if ($this->Ressources->save($resource)) {
                return $this->response->withType('application/json')
                    ->withStatus(200)
                    ->withStringBody(json_encode(['message' => 'Ressource modifiée avec succès.', 'data' => $resource]));
            } else {
                return $this->response->withType('application/json')
                    ->withStatus(500)
                    ->withStringBody(json_encode(['error' => 'Impossible de mettre à jour la ressource. Veuillez réessayer.']));
            }
        }

    }
    /**
     * Delete method
     *
     * @param string|null $id Ressource id.
     * @return \Cake\Http\Response|null Redirects to index.
     * @throws \Cake\Datasource\Exception\RecordNotFoundException When record not found.
     */
    public function delete($id = null)
    {
        $this->request->allowMethod(['post', 'delete']);

        // Récupérer la ressource à partir de l'ID
        $ressource = $this->Ressources->get($id);

        // Vérifier si l'IP de l'utilisateur correspond à celle du propriétaire de la ressource
        $user = $this->request->getAttribute('identity');
        if ($user) {
            // Vérifie si l'utilisateur connecté est le propriétaire de la ressource
            if ($ressource->owner_id !== $user->getIdentifier()) {
                return $this->response->withType('application/json')
                    ->withStatus(403) // 403 Forbidden
                    ->withStringBody(json_encode(['error' => 'Vous n\'êtes pas autorisé à supprimer cette ressource.']));
            }
        }

        // Supprimer l'image externe si elle existe
        if (!empty($ressource->file_url)) {
            $this->deleteImageFromExternalStorage($ressource->file_url);
        }

        // Supprimer la ressource de la base de données
        if ($this->Ressources->delete($ressource)) {
            return $this->response->withType('application/json')
                ->withStatus(200)
                ->withStringBody(json_encode(['message' => 'Ressource supprimée avec succès.']));
        } else {
            return $this->response->withType('application/json')
                ->withStatus(500)
                ->withStringBody(json_encode(['error' => 'La ressource n’a pas pu être supprimée. Veuillez réessayer.']));
        }
    }

    private function deleteImageFromExternalStorage($imageUrl)
    {
        // Récupère l'ID de l'image depuis l'URL
        $imageId = basename($imageUrl);

        $clientId = '938538a0a079805'; // Client ID
        $url = 'https://api.imgur.com/3/image/' . $imageId;

        $headers = [
            "Authorization: Client-ID $clientId"
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Récupère le code HTTP de la réponse
        curl_close($ch);

        // Vérifie si la suppression a réussi
        if ($statusCode === 200) {
            $result = json_decode($response, true);
            if (isset($result['data']['delete']) && $result['data']['delete'] === true) {
                return true; // L'image a été supprimée avec succès
            }
        }

        // Si le code HTTP n'est pas 200 ou la suppression échoue, retour false
        return false;
    }
    public function filterByCategory($category = null)
    {
        if (!$category) {
            return $this->response->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['error' => 'Veuillez fournir une catégorie.']));
        }

        $resources = $this->Ressources->find()
            ->where([
                'category' => $category,
                'is_public' => 1
            ])
            ->toArray();

        return $this->response->withType('application/json')
            ->withStatus(200)
            ->withStringBody(json_encode(['data' => $resources]));
    }
    public function search()
    {
        $query = $this->request->getQuery('q'); // Récupère le paramètre "q"
        $isPublic = $this->request->getQuery('is_public'); // Vérifie si on filtre par public/privé

        if (!$query) {
            return $this->response->withType('application/json')
                ->withStatus(400)
                ->withStringBody(json_encode(['error' => 'Veuillez fournir un terme de recherche.']));
        }

        $conditions = ['title LIKE' => '%' . $query . '%'];

        // Si "is_public" est précisé, on l'ajoute au filtre
        if ($isPublic !== null) {
            $conditions['is_public'] = (bool) $isPublic;
        }

        $ressources = $this->Ressources->find()
            ->where($conditions)
            ->toArray();

        return $this->response->withType('application/json')
            ->withStatus(200)
            ->withStringBody(json_encode(['results' => $ressources]));
    }

}
