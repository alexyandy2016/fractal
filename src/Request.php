<?php

namespace Appkr\Fractal;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{

    use ApiHelper;

    /**
     * {@inheritdoc}
     */
    public function response(array $errors)
    {
        if ($this->is('api/*')) {
            return $this->respondUnprocessableError($errors);
        }

        return $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))
            ->withErrors($errors, $this->errorBag);
    }

    /**
     * {@inheritdoc}
     */
    public function forbiddenResponse()
    {
        return $this->respondUnauthorized();
    }

    /**
     * @return bool
     */
    protected function isUpdateRequest()
    {
        return in_array($this->input('_method'), ['put', 'patch', 'PUT', 'PATCH'])
        or in_array($this->header('x-http-method-override'), ['put', 'patch', 'PUT', 'PATCH']);
    }

    /**
     * @return bool
     */
    protected function isDeleteRequest()
    {
        return in_array($this->input('_method'), ['delete', 'DELETE'])
        or in_array($this->header('x-http-method-override'), ['delete', 'DELETE']);
    }

}
