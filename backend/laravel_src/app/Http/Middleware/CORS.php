<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CORS
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
		$headers = [
			'Access-Control-Allow-Origin' => '*',
			'Access-Control-Allow-Methods' => 'POST, GET, OPTIONS, PUT, DELETE',
			'Access-Control-Allow-Credentials' => true,
			'Access-Control-Allow-Headers' => 'Access-Control-Allow-Origin, Access-Control-Allow-Methods, Access-Control-Allow-Headers, Origin,Accept, X-Requested-With, Content-Type, Access-Control-Request-Headers, Authorization'
		];
		
		
		if($request->getMethod() == "OPTIONS")
		{
			return response()->make('OK', 200, $headers);
		}

		$response = $next($request);
		
		foreach($headers as $key => $value)
			$response->header($key, $value);
			
		return $response;
    }
}