<?php namespace Appkr\Fractal\Example;

use League\Fractal;
use League\Fractal\TransformerAbstract;

class ResourceTransformer extends TransformerAbstract {

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'manager'
    ];

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'manager'
    ];

    /**
     * Transform single resource
     *
     * @param Resource $resource
     * @return array
     */
    public function transform(Resource $resource) {
        return [
            'id'          => (int) $resource->id,
            'title'       => $resource->title,
            'description' => $resource->description,
            'deprecated'  => (bool) ($resource->deprecated == 1) ? true : false,
            'created_at'  => (string) $resource->created_at
        ];
    }

    /**
     * Include User
     *
     * @param Resource|Resource $resource
     * @return Fractal\Resource\Item
     */
    public function includeManager(Resource $resource) {
        return $this->item($resource->manager, new ManagerTransformer);
    }

}
