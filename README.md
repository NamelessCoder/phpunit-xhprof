PHPUnit XHProfile integration
=============================

[![Build Status](https://img.shields.io/travis/NamelessCoder/phpunit-xhprof.svg?style=flat-square&label=package)](https://travis-ci.org/NamelessCoder/phpunit-xhprof) [![Coverage Status](https://img.shields.io/coveralls/NamelessCoder/phpunit-xhprof/master.svg?style=flat-square)](https://coveralls.io/r/NamelessCoder/phpunit-xhprof)

Provides a single Trait that can be used in your test cases to analyse profiling
data and make assertions based on it in your test. In short, allows calling any
amount of code and analysing the result to know for example how many times a
certain method or class was used, the total number of classes and functions used,
CPU and memory usage, calls from class A to class B and more.

You can view this much like an extended version of the `$this->at($index)` and
`$this->exactly()` and other *expectation* methods of PHPUnit: it allows you to
assert much the same but does so not by using mocks but by profiling. It can
therefore cover any amount and complexity of logic and still be able to, for
example, tell how many total times class A called method X on class B.

In addition to this comes the ability to support CPU and memory usage profiling.
There is, however, a warning along with this.

Caveat
------

Note: you can include both CPU time and memory use in the profiling results,
however, when your tests are expected to execute on multiple platforms of
varying potency you should probably avoid this and stick to number of methods
called (or a relative measure, e.g. percent of total profiled time, but even so
platform differences may cause skew/failure).

Usage
-----

```php
public function testSomething() {
    // Profile only a specific scope plus a single
    // related function "myfunction". The result
    // will return everything that happens inside
    // any class of the defined scope, plus every
    // call to "myfunction" regardless of where the
    // function is called. You can find the syntax
    // of profile keys in the xhprof documentation.
    $methods = array(
        '/^Vendor\\Namespace\\Scope\\.+/',
        '/.+myfunction$'
    );
    $closure = function() use $foo, $bar {
        // do something that gets profiled. We have
        // already created and prepped our instances
        // so we don't profile that part of the code.
        $foo->doThatWith($bar);
    }
    $profile = $this->profileClosure($closure, $methods);
    $this->assertLessThan(
        10,
        $profile['Vendor\\Class::funcA==>Vendor\\Class::funcB']['ct'],
        'Method funcB was called from funcA more than expected 10 times'
    );
}
```

And that's about it. You can consult the xhprof documentation for possible flags
and options, as well as examples of the structure of the `$profile` array.

* [Main documentation for xhprof](http://pecl.php.net/package/xhprof)
* [PHP extension- and function documentation](http://php.net/manual/en/book.xhprof.php)
