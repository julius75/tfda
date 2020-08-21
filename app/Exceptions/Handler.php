<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof \Illuminate\Session\TokenMismatchException) {
            $res=array(
                'success'=>false,
                'message'=>'<p>Token Mismatch Exception Thrown!!<br> Your Token Has Expired, Please RELOAD/REFRESH The System!!</p>'
            );
            return response()->json($res);
        }
        if (str_contains($exception->getMessage(), 'unserialize')) {
            $cookie1 = \Cookie::forget('laravel_session');
            $cookie2 = \Cookie::forget('XSRF-TOKEN');

            return redirect()->to('/')
                ->withCookie($cookie1)
                ->withCookie($cookie2);
        }
        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, \Illuminate\Auth\AuthenticationException $exception)
    {
        $res=array(
            'success'=>false,
            'message'=>'<p>Unauthenticated Access Token!!<br>Your Access Token could not be verified, Please RELOAD/REFRESH the System!!</p>'
        );
        return response()->json($res);
    }
}
