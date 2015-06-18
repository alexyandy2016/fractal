<?php

namespace Appkr\Fractal;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class Request extends FormRequest
{
    use ApiHelper;

    /**
     * {@inheritDoc}
     */
    public function response(array $errors)
    {
        if ($this->isApiRequest()) {
            return $this->respondUnprocessableError($errors);
        }

        return $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))
            ->withErrors($errors, $this->errorBag);
    }

    /**
     * {@inheritDoc}
     */
    //protected function failedValidation(Validator $validator)
    //{
    //    if ($this->isApiRequest()) {
    //        return $this->respondUnprocessableError($validator->errors()->getMessages());
    //    }
    //
    //    return parent::failedValidation($validator);
    //}

    /**
     * {@inheritDoc}
     */
    protected function failedAuthorization()
    {
        if ($this->isApiRequest()) {
            return $this->respondUnauthorized();
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
        return $this->is('api/*');
    }
}
