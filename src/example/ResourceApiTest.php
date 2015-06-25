<?php

namespace Appkr\Fractal\Example\Test;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithoutMiddleware;

class ResourceApiTest extends \TestCase
{
    use WithoutMiddleware;
    use DatabaseTransactions;

    /**
     * Stubbed Manager model
     *
     * @var \Appkr\Fractal\Example\Manager
     */
    protected $manager;

    /**
     * Stubbed resource
     *
     * @var array
     */
    protected $resource = [];

    /**
     * JWT token
     *
     * @var string
     */
    protected $jwtToken = 'header.payload.signature';

    /** @before */
    public function stub()
    {
        $this->manager = factory(\Appkr\Fractal\Example\Manager::class)->create([
            'name' => 'foo'
        ]);

        $this->resource = factory(\Appkr\Fractal\Example\Resource::class)->create([
            'title'      => 'title',
            'manager_id' => $this->manager->id
        ])->toArray();
    }

    /** @test */
    public function itFetchesACollectionOfResources()
    {
        $this->get(route('api.v1.resource.index'), $this->getHeaders())
            ->seeStatusCode(200)
            ->seeJson();
    }

    /** @test */
    public function itFetchesASingleResource()
    {
        $this->get(route('api.v1.resource.show', $this->resource['id']), $this->getHeaders())
            ->seeStatusCode(200)
            ->seeJson();
    }

    /** @test */
    public function itResponds404IfAResourceIsNotFound()
    {
        $this->get(route('api.v1.resource.show', 100000), $this->getHeaders())
            ->seeStatusCode(404)
            ->seeJson();
    }

    /** @test */
    public function itResponds422IfANewResourceRequestFailsValidation()
    {
        $payload = [
            'title'       => null,
            'manager_id'  => null,
            'description' => 'n'
        ];

        $this->post(route('api.v1.resource.store'), $payload, $this->getHeaders())
            ->seeStatusCode(422)
            ->seeJson();
    }

    /** @test */
    public function itResponds201WithCreatedResourceAfterCreation()
    {
        $payload = [
            'title'       => 'new title',
            'manager_id'  => $this->manager->id,
            'description' => 'new description'
        ];

        $this->actingAs($this->manager)
            ->post(route('api.v1.resource.store'), $payload, $this->getHeaders())
            ->seeInDatabase('resources', ['title' => 'new title'])
            ->seeStatusCode(201)
            ->seeJsonContains(['title' => 'new title']);
    }

    /** @test */
    public function itResponds200IfAUpdateRequestIsSucceed()
    {
        $this->actingAs($this->manager)
            ->put(
                route('api.v1.resource.update', $this->resource['id']),
                ['title' => 'MODIFIED title', '_method' => 'PUT'],
                $this->getHeaders()
            )
            ->seeInDatabase('resources', ['title' => 'MODIFIED title'])
            ->seeStatusCode(200)
            ->seeJson();
    }

    /** @test */
    public function itResponds200IfADeleteRequestIsSucceed()
    {
        $this->actingAs($this->manager)
            ->delete(
            route('api.v1.resource.destroy', $this->resource['id']),
            ['_method' => 'DELETE'],
            $this->getHeaders()
        )
            ->notSeeInDatabase('resources', ['id' => $this->resource['id']])
            ->seeStatusCode(200)
            ->seeJson();
    }

    /**
     * Set/Get http request header
     *
     * @param array $append
     *
     * @return array
     */
    protected function getHeaders($append = [])
    {
        return [
            'HTTP_Authorization' => "Bearer {$this->jwtToken}",
            'HTTP_Accept'        => 'application/json'
        ] + $append;
    }
}