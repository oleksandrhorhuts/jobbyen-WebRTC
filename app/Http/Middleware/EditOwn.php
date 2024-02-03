<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class EditOwn
{
    
     public function handle($request, Closure $next)
     {
         if (Auth::user()->username != $request->username) {
             return redirect($request->username);
         }

         return $next($request);
     }
}
