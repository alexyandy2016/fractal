<?php namespace Appkr\Fractal\Example;

use Appkr\Fractal\Controller;

class ResourceController extends Controller {

    /**
     * @var Resource
     */
    private $model;

    /**
     * @param Resource $model
     */
    public function __construct(Resource $model) {
        // Uncomment if tymondesigns/jwt-auth is installed
        //$this->middleware('jwt.auth');
        //$this->middleware('jwt.refresh');

        $this->model = $model;
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index() {
        // Respond with pagination
        return $this->respondWithPagination(
            $this->model->with('manager')->paginate(25),
            new ResourceTransformer
        );

        // Respond as a collection
        return $this->respondCollection(
            $this->model->with('manager')->get(),
            new ResourceTransformer
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ResourceRequest $request
     * @return Response
     */
    public function store(ResourceRequest $request) {
        // Merging manager_id. In real project
        // we should use $request->user()->id instead.
        $data = array_merge(
            $request->all(),
            ['manager_id' => 1]
        );

        if (! $resource = Resource::create($data)) {
            return $this->respondInternalError('Failed to create !');
        }

        // respond created item with 201 status code
        return $this->setStatusCode(201)->respondItem(
            $resource,
            new ResourceTransformer
        );

        // respond with simple message
        return $this->respondCreated('Created');
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id) {
        return $this->respondItem(
            $this->model->findOrFail($id),
            new ResourceTransformer
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ResourceRequest $request
     * @param  int            $id
     * @return Response
     */
    public function update(ResourceRequest $request, $id) {
        $resource = $this->model->findOrFail($id);

        if (! $resource->update($request->all())) {
            return $this->respondInternalError('Failed to update !');
        }

        return $this->respondSuccess('Updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param ResourceRequest $request
     * @param  int            $id
     * @return Response
     */
    public function destroy(ResourceRequest $request, $id) {
        $resource = $this->model->findOrFail($id);

        if (! $resource->delete()) {
            return $this->respondInternalError('Failed to delete !');
        }

        return $this->respondSuccess('Deleted');
    }

}
