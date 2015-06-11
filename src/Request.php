<?php

namespace Appkr\Fractal;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest
{
    /**
     * {@inheritDoc}
     */
    public function response(array $errors)
    {
        if ($this->isApiRequest()) {
            return app('api.response')->unprocessableError($errors);
        }

        return $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))
            ->withErrors($errors, $this->errorBag);
    }

    /**
     * {@inheritDoc}
     */
    protected function failedAuthorization()
    {
        if ($this->isApiRequest()) {
            return app('api.response')->unauthorizedError();
        }

        return parent::failedAuthorization();
    }

    /**
     * Determine if the request is update
     *
     * @return bool
     */
    protected function isUpdateRequest()
    {
        $needle = ['put', 'PUT', 'patch', 'PATCH'];

        return in_array($this->input('_method'), $needle)
            or in_array($this->header('x-http-method-override'), $needle);
    }

    /**
     * Determine if the request is delete
     *
     * @return bool
     */
    protected function isDeleteRequest()
    {
        $needle = ['delete', 'DELETE'];

        return in_array($this->input('_method'), $needle)
            or in_array($this->header('x-http-method-override'), $needle);
    }

    /**
     * Determine if the current request belongs to api request
     *
     * @return bool
     */
    protected function isApiRequest() {
        return $this->is(config('fractal.pattern'));
    }
}
