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
        if (is_api_request()) {
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
        if (is_api_request()) {
            return app('api.response')->unauthorizedError();
        }

        return parent::failedAuthorization();
    }
}
