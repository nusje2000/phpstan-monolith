# PHPStan Monolith
PHPStan extension that can validate usages of namespaces based on the composer dependencies in internal packages

### Installation
Using composer:
```shell
composer require nusje2000/phpstan-monolith --dev
```

Add the following include to your phpstan.neon:
```neon
includes:
    - vendor/nusje2000/phpstan-monolith/extension.neon
```
