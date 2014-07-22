CBOR-PHP
====

A PHP implementation of Concise Binary Object Representation, a general-purpose binary data format defined in RFC 7049. According to that RFC, CBOR's data model "is an extended version of the JSON data model", supporting many more types of data than JSON. "CBOR was inspired by MessagePack", but "is not intended as a version of or replacement for MessagePack."

This implementation was written by Peter O. and is released to the Public Domain under the [CC0 Declaration](http://creativecommons.org/publicdomain/zero/1.0/).

This implementation is currently a work in progress.  Some features of CBOR are not yet supported.

How to use
--------

To read a CBOR object from a file handle, call `CBOR::read`, as in the following example.

    $data = CBOR::read($filehandle);
