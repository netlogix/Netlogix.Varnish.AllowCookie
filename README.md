# Netlogix.Varnish.AllowCookie

This package provides an HTTP Middleware that sets a `X-Allow-Cookie` header for configured request patterns which
will set a cookie.

This can be useful when only a select few requests should be able to set a cookie. Varnish can check if the beresp
contains a `Set-Cookie` header and remove it if `X-Allow-Cookie` is not set.

## Installation
`composer require netlogix/varnish-allowcookie`

## Configuration
The allowed patterns can be configured like this:

```yaml
Netlogix:
  Varnish:
    AllowCookie:
      allowedRequestPathPatterns:
        - '#^/neos#'
        - '#^/some/other/(request|uri)#'
```

Requests staring with `/neos` will be allowed by default.

## Tests
Tests currently require this package to be installed in a Flow environment.

`FLOW_CONTEXT=Testing/Unit ./bin/phpunit -c Packages/Application/Netlogix.Varnish.AllowCookie/phpunit.xml.dist --testsuite="Unit" --bootstrap "Build/BuildEssentials/PhpUnit/UnitTestBootstrap.php"`
