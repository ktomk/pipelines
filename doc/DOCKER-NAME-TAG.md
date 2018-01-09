# Docker Image/Tag Name Syntax

This document describes the syntax of an image name w/ or w/o
tag. Created for the image name validation in pipelines and kept
for reference and further improvements.

## Syntax

The textual description is taken from the Docker documentation
and follows the transposition into more formal syntax constructs.

Encoding is US-ASCII.

    <image-name>( ":" <tag-name> )?

      ":" := ASCII 58 colon

    <image-name> ::

      <prefix>? <name-components>

      <prefix>   := <hostname> <port>? "/"
      <hostname> := [^_\x00-\x20\x7F-\xFF]+
      <port>     := ":" [0-9]+

      "/" := ASCII 47 slash
      "_" := ASCII 95 underscore

An image name is made up of slash-separated name components,
optionally prefixed by a registry hostname.

The hostname must comply with standard DNS rules,
but may not contain underscores.

If a hostname is present, it may optionally be followed by a
port number in the format :8080.

If not present, the command uses Docker’s public registry
located at registry-1.docker.io by default.

    <name-components>
                := <name-comp> ( "/" <name-comp> )?
    <name-comp> := <name> ( <sperator> <name> )*
    <name>      := [a-z0-9]+
    <sperator>  := "." | "_" "_"? | "-"+

    "-" := ASCII 45 dash
    "." := ASCII 46 period

Name components may contain lowercase letters, digits and
separators.
A separator is defined as a period, one or two
underscores, or one or more dashes.
A name component may not start or end with a separator.

    <tag-name> ::

      <tag-start> <tag-follow>{0, 127}

      <tag-start>  := [a-zA-Z0-9_]
      <tag-follow> := <tag-start> | [.-]

A tag name must be valid ASCII and may contain lowercase and
uppercase letters, digits, underscores, periods and dashes.
A tag name may not start with a period or a dash and may
contain a maximum of 128 characters.

## Known Issues

* The hostname part is not RFC conform and has not yet been 
  double checked with standard DNS rules. Often this part is not 
  needed for pipelines and should pass the validation.

## References

* source: https://docs.docker.com/engine/reference/commandline/tag/
* license: Apache-2.0 https://github.com/docker/docker.github.io/blob/master/LICENSE
