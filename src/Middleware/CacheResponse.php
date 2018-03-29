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
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Closure
     */
    protected $next;

    /**
     * 缓存分钟
     *
     * @var int|null
     */
    protected $minutes;

    /**
     * 缓存数据
     *
     * @var array
     */
    protected $responseCache;

    /**
     * 缓存命中状态，1为命中，0为未命中
     *
     * @var int
     */
    protected $cacheHit = 1;

    /**
     * 缓存Key
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param int|null                 $minutes
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $minutes = null)
    {
        $this->prepare($request, $next, $minutes);

        $this->responseCache();

        $response = response($this->responseCache['content']);

        return $this->addHeaders($response);
    }

    /**
     * 预备
     *
     * @return mixed
     */
    protected function prepare($request, Closure $next, $minutes = null)
    {
        $this->request = $request;
        $this->next = $next;

        // 初始化值
        $this->cacheKey = $this->resolveKey();
        $this->minutes = $this->resolveMinutes($minutes);
    }

    /**
     * 生成或读取Response-Cache
     *
     * @return array
     */
    protected function responseCache()
    {
        $this->responseCache = Cache::remember(
            $this->cacheKey,
            $this->minutes,
            function () {
                $this->cacheMissed();

                $response = ($this->next)($this->request);

                return $this->resolveResponseCache($response) + [
                    'cacheExpireAt' => Carbon::now()->addMinutes($this->minutes)->format('Y-m-d H:i:s T'),
                ];
            }
        );

        return $this->responseCache;
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
            'X-Cache' => $this->cacheHit ? 'Hit from cache' : 'Missed',
            'X-Cache-Key' => $this->cacheKey,
            'X-Cache-Expires' => $this->responseCache['cacheExpireAt'],
        ];

        return $headers;
    }

    /**
     * 根据请求获取指定的Key
     *
     * @return string
     */
    protected function resolveKey()
    {
        return md5($this->request->fullUrl());
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
        $this->cacheHit = 0;
    }
}
