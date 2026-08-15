@include('errors.page', ['status' => method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 500])
