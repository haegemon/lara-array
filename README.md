# Lara-array
Lara-array provide postgresql array datatype support at [Eloquent ORM](http://laravel.com/docs/eloquent). Inspired by [darrylkuhn/dialect](https://github.com/darrylkuhn/dialect).

## Installation

Require this package in your `composer.json` file:

`"haegemon/lara-array": "dev-master"`

...then run `composer update` to download the package to your vendor directory.

## Usage

The feature is exposed through a trait rewrite standart casting attributes to change array parse from json_decode to special parser for postgresql array. For example we could create a Photos model like this:

```php

use Eloquent\LaraArray\LaraArray as LaraArray;

class Photo extends Eloquent
{
        use LaraArray;
    
        protected $casts = [
            'marks' => 'array',
        ];
}
```

And then this:
```php

$marks = $photo->marks;

var_dump($marks); // array(2) { [0]=> string(1) "5" [1]=> string(1) "2" }

```