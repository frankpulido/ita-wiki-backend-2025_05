<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Resource;
use App\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UpdateResourceTest extends TestCase
{
    use WithFaker;

    protected $student1;
    protected $student2;
    protected $mentor;
    protected $resource1;
    protected $resource2;

    public function setUp(): void
    {
        parent::setUp();
        $this->student1 = Role::factory()->create([
            'github_id' => 123456,
            'role' => 'student'
        ]);
        $this->student2 = Role::factory()->create([
            'github_id' => 654321,
            'role' => 'student'
        ]);
        $this->mentor = Role::factory()->create([
            'github_id' => 234567,
            'role' => 'mentor'
        ]);
        $this->resource1 = Resource::factory()->create([
            'github_id' => $this->student1->github_id,
            'title' => 'Resource 1',
            'description' => 'Description 1',
            'url' => 'https://example.com/resource1',
        ]);
        $this->resource2 = Resource::factory()->create([
            'github_id' => $this->student2->github_id,
            'title' => 'Resource 2',
            'description' => 'Description 2',
            'url' => 'https://example.com/resource2',
        ]);
    }

    // Solicitar la creación de un reource
    private function createResource(array $overrides = []): Resource
    {
        return Resource::factory()->create($overrides);
    }

    // Solicitar una actualizacion de un resource
    private function updateResourceRequest(int $resourceId, array $data)
    {
        return $this->putJson(route('resources.update', $resourceId), $data);
    }

    // Test un student puede actualizar un resource del que es autor
    public function testStudentCanUpdateAResource()
    {
        // Datos para la actualización
        $data = [
            'github_id' => $this->student1->github_id, 
            'title' => 'Updated title',
            'description' => 'Updated description',
            'url' => 'https://updated-url.com',
        ];

         // Solicitud de actualización
        $response = $this->updateResourceRequest($this->resource1->id, $data);

        //Respuesta
        $response->assertStatus(200)
                ->assertJson([
                    'title' => 'Updated title',
                    'description' => 'Updated description',
                    'url' => 'https://updated-url.com',
                ]);

        // Verificar que se hayan actualizado en la base de datos
        $this->assertDatabaseHas('resources', [
            'id' => $this->resource1->id,
            'title' => 'Updated title',
            'description' => 'Updated description',
            'url' => 'https://updated-url.com',
        ]);
    }

    // Test un student NO puede actualizar un resource del que NO es autor
    public function testStudentCannotUpdateAResourceCreatedByOtherStudent()
    {
        // Datos para la actualización
        $data = [
            'github_id' => $this->student1->github_id, 
            'title' => 'Updated title',
            'description' => 'Updated description',
            'url' => 'https://updated-url.com',
        ];

        // Solicitud de actualización
        $response = $this->updateResourceRequest($this->resource2->id, $data);

        //Respuesta
        $response->assertStatus(422)
                ->assertJsonFragment([
                    'No puedes modificar un recurso creado por otro estudiante.'
                ]);

        // Verificar que no se haya actualizado en la base de datos
        $this->assertDatabaseHas('resources', [
            'id' => $this->resource2->id,
            'title' => $this->resource2->title,
            'description' => $this->resource2->description,
            'url' => $this->resource2->url,
        ]);
    }

    // Devuelve 422 cuando se intenta usar una URL duplicada
    // ESTA CONDICIÓN YA NO EXISTE, SE PUEDE USAR LA MISMA URL EN 2 RECURSOS DISTINTOS

    /**
     *
     * @dataProvider resourceValidationProvider
     */
    public function testItCanShowStatus422WithInvalidData(array $invalidData, string $fieldName)
    {
        // Crear Resource
        $resource = $this->createResource();

      // Datos válidos
        $data = [
            'title' => 'Updated title',
            'description' => 'Updated description',
            'url' => 'https://updated-url.com',
        ];

        // Combinamos datos válidos y inválidos
        $data = array_merge($data, $invalidData);

    
        $response = $this->updateResourceRequest($resource->id, $data);

        // Verificar que se devuelva un error 422
        $response->assertStatus(422)
            
                ->assertJsonPath($fieldName, function ($errors) {
                    return is_array($errors) && count($errors) > 0;
                });

         // Verificar que el Resource no se haya actualizado
        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'title' => $resource->title,
            'description' => $resource->description,
            'url' => $resource->url,
        ]);
    }

    public static function resourceValidationProvider()
    {
        return [
    
            'missing title' => [['title' => null], 'title'],
            'invalid title (too short)' => [['title' => 'a'], 'title'],
            'invalid title (too long)' => [['title' => self::generateLongText(256)], 'title'],
            'invalid title (array)' => [['title' => []], 'title'],
            
            //'missing description' => [['description' => null], 'description'],
            'invalid description (too short)' => [['description' => 'short'], 'description'],
            'invalid description (too long)' => [['description' => self::generateLongText(1001)], 'description'],
            'invalid description (array)' => [['description' => []], 'description'],
            
            'missing url' => [['url' => null], 'url'],
            'invalid url (not a url)' => [['url' => 'not a url'], 'url'],
            'invalid url (array)' => [['url' => []], 'url'],
            'invalid url (integer)' => [['url' => 123], 'url'],
        ];
    }


    public static function generateLongText(int $length): string
    {
        return str_repeat('a', $length);
    }
}