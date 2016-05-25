# DI
PHPixie Dependency Injection library

[![Build Status](https://travis-ci.org/PHPixie/DI.svg?branch=master)](https://travis-ci.org/PHPixie/DI)
[![Test Coverage](https://codeclimate.com/github/PHPixie/DI/badges/coverage.svg)](https://codeclimate.com/github/PHPixie/DI)
[![Code Climate](https://codeclimate.com/github/PHPixie/DI/badges/gpa.svg)](https://codeclimate.com/github/PHPixie/DI)
[![HHVM Status](https://img.shields.io/hhvm/phpixie/di.svg?style=flat-square)](http://hhvm.h4cc.de/package/phpixie/di)

[![Author](http://img.shields.io/badge/author-@dracony-blue.svg?style=flat-square)](https://twitter.com/dracony)
[![Source Code](http://img.shields.io/badge/source-phpixie/di-blue.svg?style=flat-square)](https://github.com/phpixie/di)
[![Software License](https://img.shields.io/badge/license-BSD-brightgreen.svg?style=flat-square)](https://github.com/phpixie/di/blob/master/LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/phpixie/di.svg?style=flat-square)](https://packagist.org/packages/phpixie/di)

PHPixie DI is a simple, fast, strict and flexible dependency injection container 
designed to make your code more decoupled and allow for more fluid development.
It also provides an optional static interface that most DI containers usually miss.

**Usage**

To start using DI extend the base container specifying your configuration with the `configure()` method:

```php
class Container extends \PHPixie\DI\Container\Root
{
    public function configure()
   {
       // Simple value stored by key
       $this->value('apiToken', '1234567890');

       // Dynamic method definition
       $this->callback('addFive', function($a, $b) {
           return $a + $b;
       });

       // This method will be called only once,
       // which is perfect for service definitions
       $this->build('twitterService', function() {
           return new TwitterService($this->apiToken());
           // or
           return new TwitterService($this->get('apiToken'));
       });

       // Or you can also use a shortcut.
       // Note that the parameters with '@' in their name 
       // will be replaced by corresponding value in the container
       $this->instance('twitterService', TwitterService::class, ['@apiToken']);

      // Nested group
      $this->group('user', function() {
          $this->instance('repository', UserRepository::class, ['@twitterService']);
      });
   }
}

// initialize the container
$container = new Container();

// Getting by key
$container->get('apiToken');
$container->apiToken();

// Static methods are only allowed
// after the container has been constructed
Container::apiToken();
Container::get('apiToken');

// Dynamic method call
// (also works via static methods)

$container->add(6, 7); // 13
$container->call('add', [6, 7]);
$callable = $container->get('add');
$callable(6, 7);

// Accessing nested definitions
$container->get('user.repository');

$userGroup = $container->user();
$userGroup->repository();

Container::user()->repository();
// etc...
```

It is also possible to use the container to access deep methods in container services, 
for example if the `TwitterService` class contains a `getTweets` method, it's possible to
call it like this:

```php
// $container->twitterService()->getTweets();
$container->get('twitterService.getTweets'); 

// $container->twitterService()->getTweets()->first()->delete(true);
$container->call('twitterService.getTweets.first.delete', [true]); 

// The above also works using static methods
Container::call('twitterService.getTweets.first.delete', [true]); 
```

The configurations methods such as `value` and `callback` are defined as `protected` so they
are only accessable from within the class. This makes the container immutable once it's initialized
and ensures that all configuration is contained in once place. Of course you can still allow that behavior
by overriding them as `public`.

**Type hinting in your IDE**

Since the values are defined dynamically, you won't get type hinting for your services when 
using the container. To get that functionality you can use the `@method` annotation:

```php
/**
 * @method TwitterService twitterService()
 * @method static TwitterService twitterService()
 */
class Container
{
    //...
}
```

**Usage with PHPixie**

All PHPixie components use Factory classes to build dependencies, but the default bundle 
comes with a base container that already contains some useful configuration. It also makes 
this component entirely optional. 

First create your Container class:

```php
namespace Project\App;

// Note that we are extnding a different class this time
class Container extends \PHPixie\DefaultBundle\Container
{
    public function configure()
    {
          //....your own definitions
          
          parent::configure(); // don't forget this call
    }
}
```

And register it in your Builder:

```php
namespace Project\App;

class Builder extends \PHPixie\DefaultBundle\Builder
{
    protected function buildContainer()
    {
         return new Container($this);
    }

}
```

The Builder will automatically initialize your container once it is defined,
so you can use static methods immediately if you like. And some usage examples:

```php
$container = $builder->container();

$container->get('components.orm');
$query = $container->call('components.orm.query', ['user']);

$builder = Container::builder();
$frameworkBuilder = Container::frameworkBuilder();
```
