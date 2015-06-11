<?php

namespace Appkr\Fractal\Example\Test;

class ResourceApiTest extends \TestCase
{

    /**
     * Api endpoint
     *
     * @var string
     */
    protected $basePath = '/api/v1/resource';

    /**
     * Overriding value for testing
     *
     * @var array
     */
    protected $overrides = [
        'title'      => 'title',
        'manager_id' => 1
    ];

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
    protected $jwtToken = 'some_random_string';

    /** @before */
    public function stub()
    {
        $managers = factory(\Appkr\Fractal\Example\Manager::class, 3)->create()->toArray();

        $this->resource = factory(\Appkr\Fractal\Example\Resource::class)->create($this->overrides)->toArray();
    }

    /** @test */
    public function it_fetches_a_collection_of_resources()
    {
        $this->get($this->basePath, $this->getHeaders())
            ->seeStatusCode(200)
            ->seeJson();
    }

    /** @test */
    public function it_fetches_a_single_resource()
    {
        $this->get("{$this->basePath}/{$this->resource['id']}", $this->getHeaders())
            ->seeStatusCode(200)
            ->seeJson();
    }

    /** @test */
    public function it_responds_404_if_a_resource_is_not_found()
    {
        $this->get("{$this->basePath}/10000", $this->getHeaders())
            ->seeStatusCode(404)
            ->seeJson();
    }

    /** @test */
    public function it_responds_422_if_a_new_resource_request_fails_validation()
    {
        $payload = [
            'title'       => null,
            'manager_id'  => null,
            'description' => 'n'
        ];

        $this->post($this->basePath, $payload, $this->getHeaders())
            ->seeStatusCode(422)
            ->seeJson();
    }

    /** @test */
    public function it_responds_201_with_created_resource_after_creation()
    {
        $payload = [
            'title'       => 'new title',
            'manager_id'  => 1,
            'description' => 'new description'
        ];

        $this->post($this->basePath, $payload, $this->getHeaders())
            ->seeInDatabase('resources', $payload)
            ->seeStatusCode(201)
            ->seeJsonContains(['title' => 'new title']);
    }

    /** @test */
    public function it_responds_200_if_a_update_request_is_succeed()
    {
        $this->put(
            "{$this->basePath}/{$this->resource['id']}",
            ['title' => 'MODIFIED title'],
            $this->getHeaders(['x-http-method-override' => 'put'])
        )
            ->seeInDatabase('resources', ['title' => 'MODIFIED title'])
            ->seeStatusCode(200)
            ->seeJson();
    }

    /** @test */
    public function it_responds_200_if_a_delete_request_is_succeed()
    {
        $this->delete(
            "{$this->basePath}/{$this->resource['id']}",
            ['title' => 'MODIFIED title'],
            $this->getHeaders(['x-http-method-override' => 'put'])
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
        return ['Authorization' => "Bearer $this->jwtToken"] + $append;
    }

}