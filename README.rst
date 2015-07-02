***********
PHP_Archive
***********

Userland only implementation of PHP archives.


==========
Unit tests
==========

Preparation
===========
Run ``php tests/maketestphars.php.inc`` after changing ``PHP_Archive``.


Running
=======
::

    $ pear run-tests tests/

There are tests that require PHP's native ``phar`` extension to be installed,
while others require it not to be installed.
The tests automatically detect if its installed and skip themselves if
it's the wrong combination.

To get full coverage, you have to run the tests with different PHP
configurations.
