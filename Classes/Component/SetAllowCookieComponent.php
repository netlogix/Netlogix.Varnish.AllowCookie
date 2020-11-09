<?php
declare(strict_types=1);

namespace Netlogix\Varnish\AllowCookie\Component;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Component\ComponentContext;
use Neos\Flow\Http\Component\ComponentInterface;
use Netlogix\Varnish\AllowCookie\Exception\InvalidPathPatternGiven;
use Throwable;

/**
 * @Flow\Proxy(false)
 */
class SetAllowCookieComponent implements ComponentInterface
{

	private const SET_COOKIE = 'Set-Cookie';
	private const X_ALLOW_COOKIE = 'X-Allow-Cookie';

	/**
	 * @var array
	 */
	private $allowedRequestPathPatterns;

	/**
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		$this->allowedRequestPathPatterns = $options['allowedRequestPathPatterns'] ?? [];
	}

	public function handle(ComponentContext $componentContext): void
	{
		$response = $componentContext->getHttpResponse();
		if (!$response->hasHeader(static::SET_COOKIE)) {
			return;
		}

		$request = $componentContext->getHttpRequest();
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
			return;
		}

		$componentContext->replaceHttpResponse(
			$response->withHeader(
				static::X_ALLOW_COOKIE,
				1
			)
		);
	}

}
