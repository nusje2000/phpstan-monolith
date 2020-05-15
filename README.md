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

### Known issue
1. Due to recent updates within phpstan, when an error occours which can be solved by adding a package to the composer.json, the cache must be manually removed because the php files which had the error will still show the cached result because the file itself was not changed.
