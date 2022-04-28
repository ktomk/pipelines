# this file is part of pipelines
#
# Pipelines HTML-Docs Makefile (Mkdocs)
#
SHELL := /bin/bash

# project: like $(shell git rev-parse --show-toplevel)
project := ../../..
# source: optional, build documentation from a different source root
source ?= $(project)

build_dir := $(addsuffix /build,$(project))
html_dir = $(addsuffix /$@,$(build_dir))

# python command
python ?= python3
# mkdocs command, e.g. to use src version: mkdocs="PYTHONPATH=~/workspace/mkdocs python3 -m mkdocs" make ...
#				  note: use VIRTUAL_ENV (recommended) see ./.gitignore
mkdocs ?= mkdocs

# SOURCE_DATE_EPOCH, Mkdocs build supports SOURCE_DATE_EPOCH, see https://reproducible-builds.org/specs/source-date-epoch/
export SOURCE_DATE_EPOCH ?= $(shell git -C "$(source)" --no-pager log -1 --format=%cd --date=unix)

.PHONY: html-docs # mkdocs build for static html files (html-docs)
html-docs: mkdocs-install mkdocs.yml docs/import prebuild-offline | include
	$(mkdocs) build --strict --site-dir $(html_dir) && \
		rm -f $(html_dir)/import && \
		rm -v -- $(html_dir)/sitemap.xml*
	@echo "html-docs at file://$(abspath $(html_dir))/index.html"
$(build_dir)/html-docs.tar.gz: mtime = --mtime="$(shell git -C "$(source)" --no-pager log -1 --format=%cd --date=iso)"
$(build_dir)/html-docs.tar.gz: html-docs
	tar -cf $(@:.gz=) -C $(@D) --owner=0 --group=0 $(mtime) html-docs
	gzip --no-name --keep --force -9 $(@:.gz=)

.PHONY: gh-pages # mkdocs build for github pages (gh-pages)
gh-pages: site_dir := site
gh-pages: mkdocs-install mkdocs.yml.gh-pages.yml docs/import prebuild-online | include
	$(info mkdocs $@ -> $(html_dir)/docs)
	rm -rf $(html_dir) && mkdir $(html_dir)
	$(mkdocs) build --strict --site-dir $(html_dir)/docs --config-file $(word 2,$^) && \
		$(mkdocs) build --strict --site-dir $(site_dir) --use-directory-urls --config-file $(word 2,$^) && \
		find $(site_dir) -type f -name '*.html' -print0 \
			| xargs -0 --no-run-if-empty $(python) script/mkdocs-dir-fix.py \
				--site-dir $(site_dir) --out-dir $(html_dir)/docs -q \
				--config-file $(word 2,$^) --prefer-index-html && \
		rm -f $(html_dir)/docs/import && \
		cp -- include/gh-pages-README.md $(html_dir)/README.md
	@echo "gh-pages at file://$(abspath $(html_dir))/docs/index.html"

.PHONY: pub-gh-pages # publish documentation on github-pages
pub-gh-pages: html_dir := $(addsuffix /gh-pages,$(build_dir))
pub-gh-pages: branch ?= gh-pages
pub-gh-pages:
	$(info publish github-pages: dir: $(html_dir) -> branch: $(branch))
	script/publish.sh --work-tree=$(source) --branch=$(branch) -- $(html_dir)

mkdocs.yml.gh-pages.yml: mkdocs.yml # github pages mkdocs configuration
	sed '/./{H;$$!d} ; x ; s/^# \([a-z_]\+:\)/\1/gm' $< \
		 | sed '1d' \
		 | sed '/  homepage: \.\/index\.html/d' \
		>| $@

site: mkdocs-install mkdocs.yml docs/import # mkdocs build
	$(mkdocs) build --strict
	@echo "browse at file://$(abspath $@)/index.html"

.PHONY: serve # mkdocs serve
serve: mkdocs-install mkdocs.yml docs
	$(mkdocs) serve

.PHONY: serve-gh # mkdocs serve with github pages configuration
serve-gh: mkdocs-install mkdocs.yml.gh-pages.yml docs/import prebuild-online
	$(mkdocs) serve --config-file $(word 2,$^)

.PHONY: serve-html # mkdocs serve with offline preprocessing
serve-html: mkdocs-install mkdocs.yml docs/import prebuild-offline
	$(mkdocs) serve --config-file $(word 2,$^)

.PHONY: mkdocs-debug # debug build against mkdocs default theme
mkdocs-debug: mkdocs-install docs/import
	$(mkdocs) build --theme mkdocs

.PHONY: serve-mkdocs-debug # debug build (via mkdocs serve) against mkdocs default theme
serve-mkdocs-debug: mkdocs-install docs/import
	$(mkdocs) serve --theme mkdocs

docs: docs/import

docs/import: $(source)/README.md $(source)/CHANGELOG.md include/COPYING_VENDOR_LICENSING.md $(source)/lib/package/docker-42.42.1-binsh-test-stub.yml import.lst
	script/import.sh $(lastword $(^)) $(source) && \
		touch $@

.PHONY: prebuild-offline # remove badge images from readme
prebuild-offline:
	rm -f docs/README.md && \
		sed '5,8d' "$(source)/README.md" > docs/README.md

.PHONY: prebuild-online # retain badge images from readme
prebuild-online:
	ln -sfT "$$(realpath --relative-to=docs $(source)/README.md)" docs/README.md

include/COPYING_VENDOR_LICENSING.md: $(source)/vendor/autoload.php script/vendor-licensing.php
	./script/vendor-licensing.php $(realpath $(source)) >| $@

$(source)/vendor/autoload.php: $(source)/composer.json $(source)/composer.lock
	composer -n -q --working-dir=$(realpath $(source)) install --ignore-platform-reqs && \
		touch $@

$(source)/lib/package/docker-42.42.1-binsh-test-stub.yml:
	cd $(source) && ./test/data/package/make-test-stub-package.sh

.PHONY: clean
clean: ; git clean -qffdx -- "include" "docs" "site"

# python (mkdocs) requirements
# note: use VIRTUAL_ENV (recommended) otherwise --user instead of system

pip = $(python) -m pip --disable-pip-version-check
pip_user = $(if $(VIRTUAL_ENV),,--user)
pip_less = | sed '/^Requirement already satisfied: /d;/^\#\#/,$$ d'
stash_install = test -f mkdocs-install && cp -v mkdocs-install .mkdocs-install/mkdocs-install.~$$(md5sum mkdocs-install | cut -d' ' -f1) || :
pip_venv = $(if $(VIRTUAL_ENV),$(pip) install -U pip wheel setuptools $(pip_less),:)

mkdocs-install: requirements.txt # install and stash (mkdocs) requirements
	$(pip_venv)
	$(stash_install)
	$(pip) install $(pip_user) -r $< $(pip_less)
	$(pip) freeze -r requirements.txt --all | tee $@ $(pip_less)
	$(stash_install)

.PHONY: mkdocs-uninstall # remove mkdocs requirements
mkdocs-uninstall: requirements.txt
	$(stash_install)
	$(pip) freeze $(pip_user) -r $< 2>/dev/null | sed '/^##/,$$d' | xargs -r $(pip) uninstall -y
	rm -f mkdocs-install

.PHONY: mkdocs-update # update and freeze (mkdocs) requirements
mkdocs-update: requirements.txt
	$(pip_venv)
	$(stash_install)
	$(pip) install $(pip_user) -U -r $< $(pip_less)
	$(pip) freeze -r $< --all | tee mkdocs-install $(pip_less) | tee $<.update
	$(stash_install)
	mv $<.update $<
	sed -i '/^from=/s/:.*"$$/:'"$$(grep -oP '(?<=^mkdocs-material==).*$$' $<)"'"/' ../../container/mkdocs-material.sh
