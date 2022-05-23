<?php
declare(strict_types=1);

namespace Netlogix\Varnish\AllowCookie\Tests\Unit\Middleware;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Varnish\AllowCookie\Exception\InvalidPathPatternGiven;
use Netlogix\Varnish\AllowCookie\Middleware\SetAllowCookieMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetAllowCookieMiddlewareTest extends UnitTestCase
{

    /**
     * @test
     */
    public function Options_are_optional(): void
    {
        $middleware = new SetAllowCookieMiddleware();
        $middleware->process(
            self::getRequest('/foo'),
            self::getRequestHandlerWithResponse(true)
        );

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function If_the_path_is_allowed_the_Allow_Header_will_be_set(): void
    {
        $middleware = new SetAllowCookieMiddleware();
        $middleware->injectSettings([
            'allowedRequestPathPatterns' => [
                '#^/foo$#',
            ]
        ]);
        $response = $middleware->process(
            self::getRequest('/foo'),
            self::getRequestHandlerWithResponse(true)
        );

        $this->assertTrue($response->hasHeader('X-Allow-Cookie'));
    }

    /**
     * @test
     * @dataProvider provideRequestUris
     */
    public function Multiple_patters_are_supported(string $requestUri, bool $allowed): void
    {
        $middleware = new SetAllowCookieMiddleware();
        $middleware->injectSettings([
            'allowedRequestPathPatterns' => [
                '#^/foo$#',
                '#^/bar/(foo|baz)#',
                '#^/baz#',
            ]
        ]);
        $response = $middleware->process(
            self::getRequest($requestUri),
            self::getRequestHandlerWithResponse(true)
        );
        $this->assertSame($allowed, $response->hasHeader('X-Allow-Cookie'));
    }

    public function provideRequestUris(): iterable
    {
        yield '/foo' => ['requestUri' => '/foo', 'allowed' => true];
        yield '/bar/foo' => ['requestUri' => '/bar/foo', 'allowed' => true];
        yield '/bar/baz' => ['requestUri' => '/bar/baz', 'allowed' => true];
        yield '/bar/baz/something/else?query=true' => ['requestUri' => '/bar/baz', 'allowed' => true];
        yield '/baz' => ['requestUri' => '/baz', 'allowed' => true];

        yield '/bar' => ['requestUri' => '/bar', 'allowed' => false];
        yield '/bar/bar' => ['requestUri' => '/bar/bar', 'allowed' => false];
    }

    /**
     * @test
     */
    public function If_no_Set_Cookie_header_is_set_the_Allow_Header_wont_be_set(): void
    {
        $middleware = new SetAllowCookieMiddleware();
        $middleware->injectSettings([
            'allowedRequestPathPatterns' => [
                '#^/foo$#',
            ]
        ]);
        $response = $middleware->process(
            self::getRequest('/foo'),
            self::getRequestHandlerWithResponse(false)
        );
        $this->assertFalse($response->hasHeader('X-Allow-Cookie'));
    }

    /**
     * @test
     */
    public function Invalid_patters_will_throw_an_exception(): void
    {
        $this->expectException(InvalidPathPatternGiven::class);

        $middleware = new SetAllowCookieMiddleware();
        $middleware->injectSettings([
            'allowedRequestPathPatterns' => [
                '#^/foo',
            ]
        ]);
        $middleware->process(
            self::getRequest('/foo'),
            self::getRequestHandlerWithResponse(true)
        );
    }

    protected static function getRequest(string $requestPath): ServerRequestInterface
    {
        $request = ServerRequest::fromGlobals();

        return $request->withUri($request->getUri()->withPath($requestPath));
    }

    protected static function getRequestHandlerWithResponse(bool $setCookie): RequestHandlerInterface
    {
        return new class($setCookie) implements RequestHandlerInterface {
            private bool $setCookie;

            public function __construct(bool $setCookie) {
                $this->setCookie = $setCookie;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $response = new Response();

                if ($this->setCookie) {
                    $response = $response->withAddedHeader(
                        'Set-Cookie',
                        'foo=bar'
                    );
                }

                return $response;
            }
        };
    }

}
