<?php
declare(strict_types=1);

namespace Netlogix\Varnish\AllowCookie\Middleware;

use Netlogix\Varnish\AllowCookie\Exception\InvalidPathPatternGiven;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

class SetAllowCookieMiddleware implements MiddlewareInterface
{

    private const SET_COOKIE = 'Set-Cookie';
    private const X_ALLOW_COOKIE = 'X-Allow-Cookie';

    private array $allowedRequestPathPatterns = [];

    public function injectSettings(array $settings): void
    {
        $this->allowedRequestPathPatterns = $settings['allowedRequestPathPatterns'] ?? [];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if (!$response->hasHeader(self::SET_COOKIE)) {
            return $response;
        }

        $requestPath = $request->getUri()->getPath();
        $isCookieAllowed = false;

        foreach ($this->allowedRequestPathPatterns as $allowedRequestPathPattern) {
            try {
                $result = preg_match($allowedRequestPathPattern, $requestPath);

                if ($result === 1) {
                    $isCookieAllowed = true;
                    break;
                }
            } catch (Throwable $t) {
                throw new InvalidPathPatternGiven(
                    sprintf(
                        'Invalid regular expression "%s" given to allowedRequestPathPatterns!',
                        $allowedRequestPathPattern
                    ),
                    1604914205,
                    $t
                );
            }
        }

        if (!$isCookieAllowed) {
            return $response;
        }

        return $response->withHeader(
            self::X_ALLOW_COOKIE,
            1
        );
    }

}
