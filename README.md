# VestaCP Webhosting Plugin

[![Tests](https://github.com/detain/myadmin-vestacp-webhosting/actions/workflows/tests.yml/badge.svg)](https://github.com/detain/myadmin-vestacp-webhosting/actions/workflows/tests.yml)
[![Latest Stable Version](https://poser.pugx.org/detain/myadmin-vestacp-webhosting/version)](https://packagist.org/packages/detain/myadmin-vestacp-webhosting)
[![Total Downloads](https://poser.pugx.org/detain/myadmin-vestacp-webhosting/downloads)](https://packagist.org/packages/detain/myadmin-vestacp-webhosting)
[![License](https://poser.pugx.org/detain/myadmin-vestacp-webhosting/license)](https://packagist.org/packages/detain/myadmin-vestacp-webhosting)

A MyAdmin plugin that provides VestaCP webhosting integration. This package supplies a PHP API client for the VestaCP control panel and an event-driven plugin that handles the full hosting account lifecycle -- activation, reactivation, suspension, and termination -- through the Symfony EventDispatcher.

## Features

- VestaCP API client with support for account creation, suspension, deletion, domain and database management, and credential verification
- Event-driven plugin architecture using Symfony EventDispatcher hooks
- Full hosting lifecycle management (activate, reactivate, deactivate, terminate)
- Administrative settings and menu integration for the MyAdmin control panel

## Requirements

- PHP >= 5.0
- ext-curl
- ext-soap
- ext-mbstring
- Symfony EventDispatcher ^5.0

## Installation

Install with Composer:

```sh
composer require detain/myadmin-vestacp-webhosting
```

## Running Tests

```sh
composer install
vendor/bin/phpunit
```

## License

This package is licensed under the [LGPL-2.1](LICENSE) license.
