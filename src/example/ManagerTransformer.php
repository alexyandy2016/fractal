<?php namespace Appkr\Fractal\Example;

use League\Fractal;
use League\Fractal\TransformerAbstract;

class ManagerTransformer extends TransformerAbstract {

    /**
     * Transform single resource
     *
     * @param Manager $manager
     * @return array
     */
    public function transform(Manager $manager) {
        return [
            'id'         => (int) $manager->id,
            'name'       => $manager->name,
            'email'      => $manager->email,
            'created_at' => (string) $manager->created_at
        ];
    }
}
