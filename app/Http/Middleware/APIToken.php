<?php

namespace App\Http\Middleware;

use Closure;
use App\Models\User;
use Illuminate\Http\Request;

class APIToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        if($request->header('Authorization'))
        {
            $token = $request->header('Authorization');
            $user = User::where('api_token',$token)->first();
            if($user) 
            {
                return $next($request);
            } else {
                return response()->json([
                    'message' => 'Invalid Token!',
                ]);
            }
        }
        return response()->json([
            'message' => 'Not a valid API request.',
        ]);
    }
}
