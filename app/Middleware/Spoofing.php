<?php
namespace BRM\Vivid\app\Middleware;
use Closure;


class Spoofing
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

      if($request->has('_method')) {
        $request->setMethod($request->input('_method'));
      }
    
      return $next($request);
    }

}