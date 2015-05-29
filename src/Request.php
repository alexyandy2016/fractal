<?php namespace Appkr\Fractal;

use Illuminate\Foundation\Http\FormRequest;

class Request extends FormRequest {

    use ApiHelper;

    /**
     * {@inheritdoc}
     */
    public function response(array $errors) {
        if ($this->ajax() || $this->wantsJson()) {
            return $this->respondUnprocessableError($errors);
        }

        return $this->redirector->to($this->getRedirectUrl())
            ->withInput($this->except($this->dontFlash))
            ->withErrors($errors, $this->errorBag);
    }

    /**
     * {@inheritdoc}
     */
    public function forbiddenResponse() {
        return $this->respondUnauthorized();
    }

}
