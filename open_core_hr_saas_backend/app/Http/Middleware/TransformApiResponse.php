<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TransformApiResponse
{
  /**
   * Handle an incoming request.
   *
   * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
   */
  public function handle(Request $request, Closure $next)
  {
    $response = $next($request);

    // Transform keys of successful JSON responses to camelCase
    if ($response->isSuccessful() && $response->headers->get('Content-Type') === 'application/json') {
      $data = json_decode($response->getContent(), true);
      if ($data) {
        $transformedData = $this->transformKeysToCamelCase($data);
        $response->setContent(json_encode($transformedData));
      }
    }

    return $response;
  }

  /**
   * Transform keys of an array to camelCase.
   *
   * @param array $data
   * @return array
   */
  private function transformKeysToCamelCase($data)
  {
    $result = [];
    foreach ($data as $key => $value) {
      // Here we use the Str::camel() method from Laravel
      $camelKey = Str::camel($key);
      $result[$camelKey] = is_array($value) ? $this->transformKeysToCamelCase($value) : $value;
    }
    return $result;
  }
}
