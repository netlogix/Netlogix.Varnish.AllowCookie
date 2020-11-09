# Netlogix.Varnish.AllowCookie

This package provides a Flow http component that sets a `X-Allow-Cookie` header for configured request patterns which
will set a cookie.

This can be useful when only a select few requests should be able to set a cookie. Varnish can check if the beresp
contains a `Set-Cookie` header and remove it if `X-Allow-Cookie` is not set.

## Installation
`composer require netlogix/varnish-allowcookie`

## Configuration
The allowed patterns can be configured like this:

```yaml
Neos:
  Flow:
    http:
      chain:
        postprocess:
          chain:
            setAllowCookie:
              componentOptions:
                allowedRequestPathPatterns:
                  - '#^/neos#'
                  - '#^/some/other/(request|uri)#'
```

Requests staring with `/neos` will be allowed by default.

## Tests
Tests currently require this package to be installed in a Flow environment.

`FLOW_CONTEXT=Test/Unit ./bin/phpunit -c Build/BuildEssentials/PhpUnit/UnitTests.xml Packages/Application/Netlogix.Varnish.AllowCookie/Tests/Unit/`
