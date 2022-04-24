---
title: Copying and License Information
---
# Copying

<!-- limit visual toc depth to two -->
<!-- remove github page editing button -->
<style>
.md-nav__list .md-nav__list .md-nav__list {display: none}
a[title^="Edit this page"].md-content__button.md-icon {display: none}
</style>

Pipelines - Run Bitbucket Pipelines Wherever They Dock.
Copyright (C) 2017-2021 Tom Klingenberg

This program is free software: you can redistribute it and/or modify it
under the terms of the GNU Affero General Public License as published
by the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Affero
General Public License for more details.

You should have received a copy of the GNU Affero General Public
License along with this program. If not, see
\<https://www.gnu.org/licenses/>.

## GNU Affero General Public License

    --8<-- "../../../../COPYING"

### SPDX

* Full Name: `GNU Affero General Public License v3.0 or later`
* Short Identifier: `AGPL-3.0-or-later`
* Reference: https://spdx.org/licenses/AGPL-3.0-or-later.html

## Additional Licensing

Some files in the project have been copied, are a derivative work and
the original file(s) are under a different than and compatible to the
projects' license. Their original license is preserved to ease exchange
with the upstream projects.

Affected files follow with a description and their license:

### Docker/Open Container Initiative (Apache License Version 2.0)

The file [`doc/DOCKER-NAME-TAG.md`](doc/DOCKER-NAME-TAG.md) is derived
from [Docker documentation][1-DDC] (Copyright 2013-2017 Docker, Inc.) and
[Open Container Initiative (OCI) Image Format Specification][2-OCI]
(Copyright 2016 The Linux Foundation); both used under the
*Apache License Version 2.0*.

[1-DDC]: https://docs.docker.com/engine/reference/commandline/tag/
[2-OCI]: https://github.com/opencontainers/image-spec/blob/main/descriptor.md

    --8<-- "doc/LICENSE-2.0.txt"

#### SPDX

* Full Name: `Apache License 2.0`
* Short Identifier: `Apache-2.0`
* Reference: https://spdx.org/licenses/Apache-2.0.html

### Timestamps (MIT License)

The file [`src/PharBuild/Timestamps.php`](src/PharBuild/Timestamps.php)
is derived from [`phar-utils` (Seldaek/Jordi Boggiano)][1-SJB] and used under
the *MIT License*.

[1-SJB]: https://github.com/Seldaek/phar-utils

```
Copyright (c) 2015 Jordi Boggiano

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is furnished
to do so, subject to the following conditions:
The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
```

#### SPDX

* Full Name: `MIT License`
* Short Identifier: `MIT`
* Reference: https://spdx.org/licenses/MIT.html


### Coverage Checker (MIT License)

The file `lib/build/coverage-checker.php` is based on [the
`coverage-checker.php` script written by Marco Pivetta (ocramius)][CCP-1]
and used under the *MIT License* per the licensing in the
[VersionEyeModule][CCP-2] file:

[CCP-1]: http://ocramius.github.io/blog/automated-code-coverage-check-for-github-pull-requests-with-travis/
[CCP-2]: https://github.com/Ocramius/VersionEyeModule/blob/master/coverage-checker.php

```
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

This software consists of voluntary contributions made by many individuals
and is licensed under the MIT license.
```

<!-- NOTE: disclaimer is from BSD -->
<!-- NOTE: single author in repo is Marco Pivetta -->

#### SPDX

* Full Name: `MIT License`
* Short Identifier: `MIT`
* Reference: https://spdx.org/licenses/MIT.html

### Schema (MIT License)

The file [`lib/pipelines/schema/piplines-schema.json`](lib/pipelines/schema/pipelines-schema.json)
is derived from [`atlascode` (Atlassian Partner Integrations)][1-API] and used under
the *MIT License*.

[1-API]: https://bitbucket.org/atlassianlabs/atlascode/src/main/resources/schemas/pipelines-schema.json

```
MIT License

Copyright (c) Atlassian and others. All rights reserved.

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

#### SPDX

* Full Name: `MIT License`
* Short Identifier: `MIT`
* Reference: https://spdx.org/licenses/MIT.html

## Licensing for Libraries

The project depends on third party software that is managed via Composer. These
packages are under a different than and compatible to the projects' license.
Their original license is preserved to ease exchange with the upstream
projects when forked.

--8<-- "../include/COPYING_VENDOR_LICENSING.md"

## HTML Documentation

Parts of the HTML documentation are derived from other projects.  These projects
are under a different than and compatible to the Pipelines projects' license.

Projects derived from in the HTML documentation follow with a description and
their license.

When files from these projects have been incorporated into the `pipelines`
project, they remain under their original license to ease contributing
changes back upstream.

### Mkdocs (BSD 2 Clause)

The HTML documentation is build by and derived from
[*Mkdocs - Project documentation with Markdown*][MKDOCS],
incorporating its theme files which are used under license:

```
Copyright © 2014, Tom Christie. All rights reserved.

Redistribution and use in source and binary forms, with or
without modification, are permitted provided that the following
conditions are met:

Redistributions of source code must retain the above copyright
notice, this list of conditions and the following disclaimer.
Redistributions in binary form must reproduce the above copyright
notice, this list of conditions and the following disclaimer in
the documentation and/or other materials provided with the
distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND
CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF
USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
POSSIBILITY OF SUCH DAMAGE.
```

#### SPDX

* Full Name: `BSD 2-Clause "Simplified" License`
* Short Identifier: `BSD-2-Clause`
* Reference: https://spdx.org/licenses/BSD-2-Clause.html

### Material for Mkdocs (MIT License)

The [*Mkdocs*][MKDOCS] theme is extended by
[*Material for Mkdocs - A Material Design theme for MkDocs*]
[MKDOCS-MATERIAL] and its files are used under license.

Project modifications and incorporation on file-level can be
found in the `lib/build/mkdocs` directory.

```
Copyright (c) 2016-2020 Martin Donath <martin.donath@squidfunk.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to
deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
sell copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
IN THE SOFTWARE.
```

#### SPDX

* Full Name: `MIT License`
* Short Identifier: `MIT`
* Reference: https://spdx.org/licenses/MIT.html

### Iframe-Worker (MIT License)

The [*Material for Mkdocs*][MKDOCS-MATERIAL] based theme is extended
in the pipelines project with [*iframe-worker - A tiny WebWorker
polyfill for the file:// protocol*][IFRAME-WORKER] and used under
license.

The pipelines repository is with the minified library only, see file
[`assets/javascripts/iframe-worker.js`](assets/javascripts/iframe-worker-0.2.0.js),
for sources please see the upstream project and/or the fork kept to the
date of the non-source file shipping with the pipelines documentation
located at `https://github.com/ktomk/iframe-worker`.

```
Copyright (c) 2020 Martin Donath <martin.donath@squidfunk.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to
deal in the Software without restriction, including without limitation the
rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
sell copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS
IN THE SOFTWARE.
```

#### SPDX

* Full Name: `MIT License`
* Short Identifier: `MIT`
* Reference: https://spdx.org/licenses/MIT.html

### Font Awesome (Font Awesome Free License)

The [*Material for Mkdocs*][MKDOCS-MATERIAL] theme contains SVG files (named
*Icons* in the *Font Awesome Free License*) from the
[*Font Awesome - The iconic SVG, font, and CSS framework*][FONT-AWESOME]
project.

Some of these SVG files are put into the HTML output and used under license:

```
Font Awesome Free License
-------------------------

Font Awesome Free is free, open source, and GPL friendly. You can use it for
commercial projects, open source projects, or really almost whatever you want.
Full Font Awesome Free license: https://fontawesome.com/license/free.

# Icons: CC BY 4.0 License (https://creativecommons.org/licenses/by/4.0/)
In the Font Awesome Free download, the CC BY 4.0 license applies to all icons
packaged as SVG and JS file types.

# Fonts: SIL OFL 1.1 License (https://scripts.sil.org/OFL)
In the Font Awesome Free download, the SIL OFL license applies to all icons
packaged as web and desktop font files.

# Code: MIT License (https://opensource.org/licenses/MIT)
In the Font Awesome Free download, the MIT license applies to all non-font and
non-icon files.

# Attribution
Attribution is required by MIT, SIL OFL, and CC BY licenses. Downloaded Font
Awesome Free files already contain embedded comments with sufficient
attribution, so you shouldn't need to do anything additional when using these
files normally.

We've kept attribution comments terse, so we ask that you do not actively work
to remove them from files, especially code. They're a great way for folks to
learn about Font Awesome.

# Brand Icons
All brand icons are trademarks of their respective owners. The use of these
trademarks does not indicate endorsement of the trademark holder by Font
Awesome, nor vice versa. **Please do not use brand logos for any purpose except
to represent the company, product, or service to which they refer.**
```
#### SPDX

* Full Name: `Creative Commons Attribution 4.0 International`
* Short Identifier: `CC-BY-4.0`
* Reference: https://spdx.org/licenses/CC-BY-4.0.html

### Additional Credits

Some software that has no specific requirement when (indirectly) in use
to be made available nor noted, does not belong into the bill of
material. Nevertheless credit where credit is due.

#### Mkdocs Local-Search Plugin (MIT License)

The HTML documentation could not be build without the [*Mkdocs Local Search
Plugin*][MKDOCS-LOCALSEARCH] written by Lars Wilhelmer; [*A MkDocs plugin to
make the native "search" plugin work locally (file:// protocol)*]
[MKDOCS-LOCALSEARCH].

Its license does not require a notion *comme &ccedil;a* as the documentation is
already build (when shipped/published), nevertheless the build would not work
without it and a HTML documentation that works (static HTML is best served fresh
on platter) can not be taken for granted these days and requires notion.

```
Copyright (c) 2019 Lars Wilhelmer

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

##### SPDX

* Full Name: `MIT License`
* Short Identifier: `MIT`
* Reference: https://spdx.org/licenses/MIT.html

[FONT-AWESOME]: https://github.com/FortAwesome/Font-Awesome
[IFRAME-WORKER]: https://github.com/squidfunk/iframe-worker
[MKDOCS]: https://github.com/mkdocs/mkdocs
[MKDOCS-LOCALSEARCH]: https://github.com/wilhelmer/mkdocs-localsearch
[MKDOCS-MATERIAL]: https://github.com/squidfunk/mkdocs-material
