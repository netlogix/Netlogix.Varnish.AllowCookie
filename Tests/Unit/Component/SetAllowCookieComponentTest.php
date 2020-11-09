<?php
declare(strict_types=1);

namespace Netlogix\Varnish\AllowCookie\Tests\Unit\Component;

use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Tests\UnitTestCase;
use Netlogix\Varnish\AllowCookie\Component\SetAllowCookieComponent;
use Netlogix\Varnish\AllowCookie\Exception\InvalidPathPatternGiven;

class SetAllowCookieComponentTest extends UnitTestCase
{

	/**
	 * @test
	 */
	public function Options_are_optional(): void
	{
		$component = new SetAllowCookieComponent([]);
		$context = $this->getComponentContext('/foo', true);
		$component->handle($context);

		$this->assertTrue(true);
	}

	/**
	 * @test
	 */
	public function If_the_path_is_allowed_the_Allow_Header_will_be_set(): void
	{
		$component = new SetAllowCookieComponent([
			'allowedRequestPathPatterns' => [
				'#^/foo$#',
			]
		]);
		$context = $this->getComponentContext('/foo', true);
		$component->handle($context);

		$response = $context->getHttpResponse();
		$this->assertTrue($response->hasHeader('X-Allow-Cookie'));
	}

	/**
	 * @test
	 * @dataProvider provideRequestUris
	 */
	public function Multiple_patters_are_supported(string $requestUri, bool $allowed): void
	{
		$component = new SetAllowCookieComponent([
			'allowedRequestPathPatterns' => [
				'#^/foo$#',
				'#^/bar/(foo|baz)#',
				'#^/baz#',
			]
		]);
		$context = $this->getComponentContext($requestUri, true);
		$component->handle($context);

		$response = $context->getHttpResponse();
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
		$component = new SetAllowCookieComponent([
			'allowedRequestPathPatterns' => [
				'#^/foo$#',
			]
		]);
		$context = $this->getComponentContext('/foo', false);
		$component->handle($context);

		$response = $context->getHttpResponse();
		$this->assertFalse($response->hasHeader('X-Allow-Cookie'));
	}

	/**
	 * @test
	 */
	public function Invalid_patters_will_throw_an_exception(): void
	{
		$this->expectException(InvalidPathPatternGiven::class);

		$component = new SetAllowCookieComponent([
			'allowedRequestPathPatterns' => [
				'#^/foo',
			]
		]);
		$context = $this->getComponentContext('/foo', true);
		$component->handle($context);
	}

	protected function getComponentContext(string $requestPath, bool $setCookie): ComponentContext
	{
		$request = ServerRequest::fromGlobals();
		$request = $request->withUri($request->getUri()->withPath($requestPath));
		$response = new Response();
		if ($setCookie) {
			$response = $response->withAddedHeader(
				'Set-Cookie',
				'foo=bar'
			);
		}

		return new ComponentContext($request, $response);
	}

}
