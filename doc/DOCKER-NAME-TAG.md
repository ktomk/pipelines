# Docker Container (Image/Tag) Name Syntax

This document describes the syntax of a docker container name, an image
name w/ or w/o tag or digest (supported since 0.0.64).

Created for the image name validation in `pipelines` and kept for
reference and further improvements.

[Syntax](#syntax) | [Known Issues](#known-issues) | [Grammar](#grammar) | [References](#references)

## Syntax

The textual description is taken from the Docker documentation and
following is a transposition into a more formal grammar similar to EBNF.

Encoding is US-ASCII; [grammar at once](#grammar).

~~~
   container             ::= image-name ( ":" tag-name | "@" digest )?

   /*  ":" ASCII 58 x3A colon        */
~~~

[Image Name](#image-name) | [Tag Name](#tag-name) | [Digest](#digest)

### Image Name

A container consists of the name of an image optionally followed by a
tag or digest (both separated by a colon).

~~~
   image-name            ::= prefix? name-components
   prefix                ::= hostname port? "/"
   hostname              ::= [a-zA-Z0-9.-]+
   port                  ::= ":" [0-9]+

   /*  "/" ASCII 47 x2F slash        */
   /*  "-" ASCII 45 x2D dash         */
~~~

An image name is made up of slash-separated name components, optionally
prefixed by a registry hostname.

The hostname must comply with standard DNS rules, but may not contain
underscores.

If a hostname is present, it may optionally be followed by a port
number in the format `:8080`.

If not present, the command uses Docker’s public registry located at
`registry-1.docker.io` by default.

~~~
   name-components       ::= name-component ( "/" name-component )?
   name-component        ::= name ( name-separator name )*
   name                  ::= [a-z0-9]+
   name-separator        ::= ( "." | "_" "_"? | "-"+ )

   /*  "." ASCII 46 x2E period       */
   /*  "_" ASCII 95 x5F underscore   */
~~~

Name components may contain lowercase letters, digits and separators. A
separator is defined as a period, one or two underscores, or one or
more dashes. A name component may not start or end with a separator.

### Tag Name

A tag name must be valid ASCII and may contain lowercase and uppercase
letters, digits, underscores, periods and dashes. A tag name may not
start with a period or a dash and may contain a maximum of 128
characters.

~~~
   tag-name              ::= tag-start tag-follow{0,127}
   tag-start             ::= [a-zA-Z0-9_]
   tag-follow            ::= tag-start | [.-]
~~~

### Digest

In `pipelines` the digest is first of all supported to allow docker to
fetch container images from docker-hub by content / collision-resistant
hash of the bytes (the digest) and not by tag (only; tags are not
stable).

Image with:

* Tag: `ktomk/pipelines:busybox`
* Digest: `ktomk/pipelines@sha256:2ef9a59041a7c4f36001abaec4fe7c10c26c1ead4da11515ba2af346fe60ddac`

In practice the SHA-256 is in use by all compliant implementations (this
is Open Container Interface (OCI) material).

~~~
   digest                ::= algorithm ":" encoded
   algorithm             ::= algorithm-component (algorithm-separator algorithm-component)*
   algorithm-component   ::= [a-z0-9]+
   algorithm-separator   ::= [+._-]
   encoded               ::= [a-zA-Z0-9=_-]+

   /*  "=" ASCII 61 x3D equals-sign  */
~~~

## Known Issues

* The hostname part is not RFC conform (also depending on which RFC)
  and has not yet been double checked with standard DNS rules.

* The digest part currently only verifies per the grammar for forward
  compatibility. No constraint is made on validation on the algorithm
  component for example.

Often this level of specific is not needed for `pipelines` so prone to
errors apart of the validation itself. Image/container names are passed
to `docker(1)` which might then unable to pull an image even if the
regular expression based validation in the `pipelines` utility did let
it pass as valid. Double check the hostname and the digest then.

## Grammar

For field formats described in this document, we use a limited subset of [Extended Backus-Naur Form][EBNF]\[EBNF], similar to that used by the [XML specification][XML-EBNF]\[XML-EBNF].

Encoding is US-ASCII.

~~~
   container             ::= image-name ( ":" ( tag-name | digest ) )?

   image-name            ::= prefix? name-components

   prefix                ::= hostname port? "/"
   hostname              ::= [a-zA-Z0-9.-]+
   port                  ::= ":" [0-9]+

   name-components       ::= name-comp ( "/" name-component )?
   name-component        ::= name ( name-separator name )*
   name                  ::= [a-z0-9]+
   name-separator        ::= ( "." | "_" "_"? | "-"+ )

   tag-name              ::= tag-start tag-follow{0,127}
   tag-start             ::= [a-zA-Z0-9_]
   tag-follow            ::= tag-start | [.-]

   digest                ::= algorithm ":" encoded
   algorithm             ::= algorithm-component (algorithm-separator algorithm-component)*
   algorithm-component   ::= [a-z0-9]+
   algorithm-separator   ::= [+._-]
   encoded               ::= [a-zA-Z0-9=_-]+

   /*  ":" ASCII 58 x3A colon        */
   /*  "/" ASCII 47 x2F slash        */
   /*  "-" ASCII 45 x2D dash         */
   /*  "." ASCII 46 x2E period       */
   /*  "_" ASCII 95 x5F underscore   */
   /*  "=" ASCII 61 x3D equals-sign  */
~~~

## References

* Source: <https://docs.docker.com/engine/reference/commandline/tag/>
* Original Copyright: Code and documentation copyright 2017 Docker, inc,
    released under the Apache 2.0 license.
* License: `Apache-2.0`; [LICENSE-2.0.txt](LICENSE-2.0.txt)

* Source: <https://github.com/opencontainers/image-spec/blob/main/descriptor.md>
* Original Copyright: Copyright (C) 2020 The Linux Foundation(R). All
    rights reserved., released under the Apache 2.0 license.
* License: `Apache-2.0`; [LICENSE-2.0.txt](LICENSE-2.0.txt)

---

* \[EBNF]: https://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_form
* \[XML-EBNF]: https://www.w3.org/TR/REC-xml/#sec-notation

[EBNF]: https://en.wikipedia.org/wiki/Extended_Backus%E2%80%93Naur_form
[XML-EBNF]: https://www.w3.org/TR/REC-xml/#sec-notation
