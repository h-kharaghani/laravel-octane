<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ContentLegthMiddleware
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
        $response = $next($request);
        $content = $response->content();
        $contentLength = strlen($content);
        $useCompressedOutput = ($contentLength && isset($_SERVER['HTTP_ACCEPT_ENCODING']) &&
        strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false);

     if ($useCompressedOutput) {
         // In order to accurately set Content-Length, we have to compress the data ourselves
         // rather than letting PHP do it automatically.
         $compressedContent = gzencode($content, 6, FORCE_GZIP);
         $compressedContentLength = strlen($compressedContent);
         if ($compressedContentLength/$contentLength < 0.9) {
             if (ini_get('zlib.output_compression')) ini_set('zlib.output_compression', false);
             $response->header('Content-Encoding', 'gzip');
             $response->setContent($compressedContent);
             $contentLength = $compressedContentLength;
         }
     }

     // compressed or not, sets the Content-Length
     $response->header('Content-Length', $contentLength);

     return $response;
    }
}
