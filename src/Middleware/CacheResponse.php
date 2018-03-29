<?php

namespace Flc\Laravel\Http\Middleware;

use Cache;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Response缓存中间件
 *
 * @author Flc <2018-03-29 09:14:48>
 *
 * @see http://flc.ren | http://flc.io
 */
class CacheResponse
{
    /**
     * 缓存命中状态，1为命中，0为未命中
     *
     * @var int
     */
    protected $cache_hit = 1;

    /**
     * 缓存Key
     *
     * @var string
     */
    protected $cache_key;

    /**
     * 缓存失效时间
     *
     * @var string
     */
    protected $cache_expire_at;

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $minutes = null)
    {
        $responseCache = $this->getResponseCache($request, $next, $minutes);

        $response = response($responseCache['content']);

        return $this->addHeaders($response);
    }

    /**
     * 返回Response-Cache
     *
     * @param \Illuminate\Http\Request $request
     * @param Closure                  $next
     * @param int|null                 $minutes
     *
     * @return array
     */
    protected function getResponseCache($request, $next, $minutes)
    {
        $this->cache_key = $key = $this->resolveRequestKey($request);

        $responseCache = Cache::remember(
            $key,
            $resolveMinutes = $this->resolveMinutes($minutes),
            function () use ($request, $next, $resolveMinutes) {
                $this->cacheMissed();

                $response = $next($request);

                return $this->resolveResponseCache($response) + [
                    'cacheExpireAt' => Carbon::now()->addMinutes($resolveMinutes)->format('Y-m-d\TH:i:s'),
                ];
            }
        );

        $this->cache_expire_at = $responseCache['cacheExpireAt'];

        return $responseCache;
    }

    /**
     * 确定需要缓存Response的数据
     *
     * @param \Illuminate\Http\Response $response
     *
     * @return array
     */
    protected function resolveResponseCache($response)
    {
        return [
            'content' => $response->getContent(),
        ];
    }

    /**
     * 追加Headers
     *
     * @param mixed
     */
    protected function addHeaders($response)
    {
        $response->headers->add(
            $this->getHeaders()
        );

        return $response;
    }

    /**
     * 返回Headers
     *
     * @return array
     */
    protected function getHeaders()
    {
        $headers = [
            'X-Cache' => $this->cache_hit ? 'Hit' : 'Missed',
            'X-Cache-Key' => $this->cache_key,
            'X-Cache-ExpireAt' => $this->cache_expire_at,
        ];

        return $headers;
    }

    /**
     * 根据请求获取指定的Key
     *
     * @param Illuminate\Http\Request $request
     *
     * @return string
     */
    protected function resolveRequestKey(Request $request)
    {
        return md5($request->fullUrl());
    }

    /**
     * 获取缓存的分钟
     *
     * @param int|null $minutes
     *
     * @return int
     */
    protected function resolveMinutes($minutes = null)
    {
        return is_null($minutes)
            ? $this->getDefaultMinutes()
            : max($this->getDefaultMinutes(), intval($minutes));
    }

    /**
     * 返回默认的缓存时间（分钟）
     *
     * @return int
     */
    protected function getDefaultMinutes()
    {
        return 10;
    }

    /**
     * 缓存未命中
     *
     * @return mixed
     */
    protected function cacheMissed()
    {
        $this->cache_hit = 0;
    }
}
