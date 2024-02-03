<?php

namespace App\Http\Middleware;

use App\Timeline;
use Closure;
use Illuminate\Support\Facades\Auth;

class EditPage
{
    
    public function handle($request, Closure $next)
    {
        $timeline = Timeline::where('username', $request->username)->first();
        $page = $timeline->page()->first();
        if (!$page->is_admin(Auth::user()->id)) {
            return redirect($request->username);
        }

        return $next($request);
    }
}
