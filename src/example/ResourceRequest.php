<?php

namespace Appkr\Fractal\Example;

use Appkr\Fractal\Request;

class ResourceRequest extends Request
{

    /**
     * @var array
     */
    protected $rules = [
        'title'       => 'required|min:2',
        'description' => 'min:2'
    ];

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        //if ($this->isUpdateRequest() or $this->isDeleteRequest()) {
        //    $id = $this->route('resource');
        //
        //    return Resource::where('id', $id)
        //        ->where('manager_id', $this->manager()->id)->exists();
        //}

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = $this->rules;

        if ($this->isUpdateRequest()) {
            $rules['deprecated'] = 'boolean';
        }

        if ($this->isDeleteRequest()) {
            $rules = [];
        }

        return $rules;
    }

}