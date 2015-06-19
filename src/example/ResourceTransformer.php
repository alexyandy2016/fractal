<?php

namespace Appkr\Fractal\Example;

use League\Fractal;
use League\Fractal\TransformerAbstract;

class ResourceTransformer extends TransformerAbstract
{

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
     *
     * @return array
     */
    public function transform(Resource $resource)
    {
        return [
            'id'          => (int) $resource->id,
            'title'       => $resource->title,
            'description' => $resource->description,
            'deprecated'  => (bool) ($resource->deprecated == 1) ? true : false,
            'created_at'  => (int) $resource->created_at->getTimestamp()
        ];
    }

    /**
     * Include User
     *
     * @param Resource $resource
     *
     * @return
     */
    public function includeManager(Resource $resource)
    {
        $manager = $resource->manager;

        return $manager
            ? $this->item($manager, new ManagerTransformer)
            : null;
    }

}
