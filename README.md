Acme PHP Core
=============

[![Build Status](https://travis-ci.org/acmephp/core.svg?branch=master)](https://travis-ci.org/acmephp/core)

> Note : this repository is in development

The ACME protocol is a protocol defined by the Let's Encrypt Certificate Authority.
You can see the complete specification on https://letsencrypt.github.io/acme-spec/.

The ACME PHP project aims to implement the ACME protocol in PHP to be able to use it
easily in various projects.

This repository is the core of the project : it is a basis for the other repositories.

If you want a more high-level library, have a look at the [other ACME PHP repositories](https://github.com/acmephp). 
There are some very useful framework implementations.

How to use this library?
------------------------

This library provides the building blocks for you to create your own certificates management script.
It does nothing more than implementing the protocol : the generated SSL keys and certificates are
stored in memory and then given to your script. You are the one who need to store/retrieve them using
utilities provided by the library (`KeyPairManager` is very useful for that).

This schema gives an idea of how the library should be used (you can read the API documentation to learn more):

![How ACME PHP works?](https://raw.githubusercontent.com/acmephp/core/master/docs/acme.jpg)

Logging
-------

Acme PHP Core provides a logging mechanism based on `psr/log` standard. You can provide a logger to
your Acme PHP client:

```` php
<?php
$client = new LetsEncryptClient(KeyPairManager::load('/.../public.pem', '/.../private.pem'), $logger);
```

Acme PHP Core registers logs using the following convention:

-   Informations about the progress of registering, requesting or checking elements is logged
    as `DEBUG` ;
-   Success messages (such as the successful check of a domain or the successful request of a
    certificate) are logged as `INFO` ;
-   Error messages (such as the error to check a domain or the error to request a
    certificate) are logged as `ERROR` ;
