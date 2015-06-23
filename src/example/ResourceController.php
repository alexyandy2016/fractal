<?php

namespace Appkr\Fractal\Example;

use Appkr\Fractal\Controller;

class ResourceController extends Controller
{
    /**
     * @var Resource
     */
    private $model;

    /**
     * @param Resource $model
     */
    public function __construct(Resource $model)
    {
        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        // Respond with pagination
        return $this->response()->setMeta(['foo' => 'bar'])->withPagination(
            $this->model->with('manager')->latest()->paginate(25),
            new ResourceTransformer
        );

        // Respond as a collection
        return $this->response()->setMeta(['foo' => 'bar'])->withCollection(
            $this->model->with('manager')->latest()->get(),
            new ResourceTransformer
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ResourceRequest $request
     *
     * @return Response
     */
    public function store(ResourceRequest $request)
    {
        // Merging manager_id. In real project
        // we should use $request->user()->id instead.
        $data = array_merge(
            $request->all(),
            ['manager_id' => 1]
        );

        if (! $resource = Resource::create($data)) {
            return $this->response()->internalError('Failed to create !');
        }

        // respond created item with 201 status code
        return $this->response()->setStatusCode(201)->withItem(
            $resource,
            new ResourceTransformer,
            ['additionalHttpResponseHeader' => 'value'] // additional headers
        );

        // respond with simple message
        return $this->response()->created('Created');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return Response
     */
    public function show($id)
    {
        return $this->response()->setMeta(['foo' => 'bar'])->withItem(
            $this->model->findOrFail($id),
            new ResourceTransformer
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ResourceRequest $request
     * @param  int            $id
     *
     * @return Response
     */
    public function update(ResourceRequest $request, $id)
    {
        $resource = $this->model->findOrFail($id);

        if (! $resource->update($request->all())) {
            return $this->response()->internalError('Failed to update !');
        }

        return $this->response()->success('Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ResourceRequest $request
     * @param  int            $id
     *
     * @return Response
     */
    public function destroy(ResourceRequest $request, $id)
    {
        $resource = $this->model->findOrFail($id);

        if (! $resource->delete()) {
            return $this->response()->internalError('Failed to delete !');
        }

        return $this->response()->success('Deleted');
    }
}
