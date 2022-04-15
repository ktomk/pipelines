#!/usr/bin/env python3
# this file is part of pipelines
#
# mkdocs-dir-fix.py - migrate directory urls via rel canonical
#
# mkdocs migration from use_directory_urls: true to use_directory_urls: false
# for build index.html files canonical and link hrefs.
#
# fixes for <name>/index.html files:
#
#   - directory-style <name>/index.html page urls to their <name>.html links
#   - canonical to <name>.html
#
# fixed files can be stored into a different directory tree to add old files
# to (correct) new files
#

import sys, re, argparse, htmlmin
from pathlib import Path, PurePosixPath
from bs4 import BeautifulSoup
from bs4.formatter import HTMLFormatter
from bs4.dammit import EntitySubstitution
from mkdocs import config
from mkdocs.exceptions import ConfigurationError

class File(str):
  """Mkdocs .html output file"""
  def __init__(self, str):
    self.str = str
    self.path = Path(str)
    if not self.exists(): raise argparse.ArgumentTypeError(f"not a file: {str}")
    self.site_dir = self.site_dir_file = None

  def init_site_dir(self, site_dir):
    try: site_dir_file = self.path.resolve().relative_to(site_dir.resolve())
    except ValueError: site_dir_file = None
    if site_dir_file:
      self.site_dir_file = site_dir_file
      self.site_dir = site_dir

  def is_site_dir_file(self):
    return not self.site_dir_file == None
  def is_html(self):
    return self.site_dir_file.suffix == '.html'
  def is_index_html(self):
    return self.site_dir_file and self.site_dir_file.name == 'index.html'
  def is_directory_url(self):
    """for the homepage index.html this is not distinguishable, therefore always false"""
    """for other index.html the parent directory name must not be lower case"""
    if not self.is_index_html: return False
    parentName = self.site_dir_file.parent.name
    return not parentName.lower() == parentName

  def exists(self):
    """exists and is a file"""
    return self.path.is_file()

  def self_link(self):
    """relative URL to the non-directory-url version"""
    assert self.is_index_html()
    assert self.site_dir_file
    name = self.site_dir_file.parent.name
    if name == "": return None
    return f"../{name}.html"

def arg_dir(dir):
  """Directory validator for args"""
  dir = Path(dir)
  if dir == "": return dir
  if not dir.is_dir(): raise argparse.ArgumentTypeError(f"not a directory: {dir}")
  return dir

def debug_print(*x):
  """Debug printing"""
  if args.debug:
    print("debug:", *x, file=sys.stderr)

def quiet_print(*x, **kwargs):
  """Quiet printing, suppressed when quiet"""
  if not args.quiet:
    print(*x, **kwargs)

def fix_html(html: str, file: File):
  """HTML string fixer - three-phase HTML fixer (parse, DOM-fix and HTML output encode)"""
  def fix_canonical():
    """Fix <link href=https://ktomk.github.io/pipelines/doc/CONFIGURATION-PARAMETERS/ rel=canonical>"""
    def fix(ctx, new):
      """apply fix new on item["href"] """
      debug_print(f"[{ctx}]", href, "->", new, item)
      item["href"] = new

    item = soup.head.find("link", rel="canonical")
    if not item:
      """adding link rel=canonical in head, site dir file is: {file.site_dir_file}"""
      path = "../" + str(PurePosixPath(str(PurePosixPath(file.site_dir_file).parent) + ".html").name)
      item = soup.new_tag("link", href=path, rel="canonical")
      soup.head.append(item)
      return True, item

    if not "href" in item.attrs: return False, "no href in canonical"
    href = item["href"]
    if not re.match(r"(https?)?://", href): return False, f"ref '{href}' not absolute"
    if not href[-1:] == "/": return False, f"ref '{href}' not a directory url"
    fix("canonical ref to directory url", href[:-1] + ".html")
    return True, item

  def fix_links():
    """Fix <a href=... >"""
    def void(ctx):
      pass # debug_print("[{}]".format(ctx), href)
    def fix(ctx, new):
      debug_print(f"[{ctx}]", href, "->", new, item)
      item["href"] = new
    links = soup.find_all("a", href=True)
    for item in links:
      href = item["href"]
      if re.match(r"(https?)?://", href):
        if href == args.config_file.site_url and args.prefer_index_html == True:
          fix("absolute site_url", PurePosixPath(href) / "index.html")
          continue
        void("absolute")
      elif re.match(r"#", href):
        void("pure fragment")
      elif re.match(r"^\.\.(/\.\.)*$", href):
        if args.prefer_index_html == True:
          fix("relative directory url", PurePosixPath(href) / "index.html")
        void("relative to index.html files (fine for httpd only)")
      elif href == "./":
        fix("nav self link", file.self_link())
      elif href[-1:] == "/":
        fix("nav link to directory URL", href[:-1] + ".html")
      else:
        debug_print(f"[link n/a] {href}")
    return True, [links]

  # Phase 1: Parse HTML
  if html == None: return False, f"no html to parse"
  soup = BeautifulSoup(html, features="lxml")
  if not soup.head:
    return False, f"unable to parse html head"

  # Phase 2: Fix hypertext references
  ok, msg = fix_canonical()
  if not ok:
    return False, f"canonical {msg}"
  ok, href = fix_links()
  if not ok:
    return False, f"unhandled href: {href}"

  # Phase 3: String Post-processing
  #
  # BeautifulSoup is used as HTML parser / modifier in fixers and has output issues
  # which _require_ a BeautifulSoup HTMLFormatter, replacing strings as found and
  # minification with htmlmin.
  #
  class FixHTMLFormatter(HTMLFormatter):
      """Fix (change after build) Mkdocs HTML output (BeautifulSoup 4 HTMLFormatter)"""
      def __init__(self, *args, **kwargs):
        kwargs["entity_substitution"]       = EntitySubstitution.substitute_html
        kwargs["void_element_close_prefix"] = ""
        super().__init__(*args, **kwargs)

      def attributes(self, tag):
          """Unsorted attributes"""
          for k, v in tag.attrs.items():
              yield k, v

  buffer = (
    str(soup.encode(formatter=FixHTMLFormatter()), "utf-8")
    .replace("<!DOCTYPE html>", "<!doctype html>", 1) # radical doctype fix (BeautifulSoup bug doctype output)
    .replace("></path>", "/>") # radical svg path fix (BeautifulSoup bug on closing tags)
  )

  minified = htmlmin.minify(
    buffer,
    remove_comments=True, remove_empty_space=True, remove_all_empty_space=False,
    reduce_boolean_attributes=False, remove_optional_attribute_quotes=True,
    convert_charrefs=True, keep_pre=False, pre_tags=["pre", "textarea"], pre_attr="pre"
  )

  return True, minified

if __name__ == "__main__":
  suffix_default = ".dir-fix.html"

  class MkdocsConfig:
    """Mkdocs config type argparse and args"""
    def __init__(self, path = None):
      self.path = path
      try: self.config = config.load_config(config_file=path)
      except ConfigurationError as e: raise argparse.ArgumentTypeError(str(e))
    @property
    def site_url(self):
      return self.config.get("site_url", None)

  parser = argparse.ArgumentParser()
  parser.add_argument("--debug", action="store_true", help="write debug messages")
  parser.add_argument("-n", "--dry-run", action="store_true", help="do not write files to disk or create directories")
  parser.add_argument("-q", "--quiet", action="store_true", help="be quiet")
  parser.add_argument("-d", "--site-dir", metavar="<path>", type=arg_dir, help="the directory the result of the documentation build was output and to read files from")
  parser.add_argument("--out-dir", metavar="<path>", type=arg_dir, help="the directory to output files to")
  parser.add_argument("--suffix", nargs="?", metavar="<suffix>", default=False, type=str, help=f"suffix for save filename, defaults to '{suffix_default}' on debug or with no <suffix>")
  parser.add_argument("-f", "--config-file", metavar="<path>", default=MkdocsConfig, type=MkdocsConfig, help="provide a specific MkDocs config")
  parser.add_argument("--prefer-index-html", action="store_true", help="use index.html when ambiguous, e.g. on root")
  parser.add_argument("file", nargs="+", metavar="<file>", type=File)
  args = parser.parse_args()

  def args_validate_suffix():
    """Parse --suffix [<suffix>] (defaults to suffix_default when no <suffix> or --debug)"""
    if args.debug and args.suffix in [None, False]:
      args.suffix = suffix_default
    elif not args.debug and args.suffix == None:
      args.suffix = suffix_default
    elif not args.debug and args.suffix == False:
      args.suffix = ""

  def args_validate_site_dir():
    """Initialize args.site_dir and validate that all files are in site_dir"""
    if args.site_dir == None: args.site_dir = arg_dir(args.config_file.config["site_dir"])
    else: args.config_file.config["site_dir"] = args.site_dir
    site_dir = args.site_dir
    c = 0
    for i, file in enumerate(args.file):
      file.init_site_dir(site_dir)
      if not file.is_site_dir_file():
        c += 1
        print(f"error: not in site-dir '{file}'", file=sys.stderr)
        continue
    if c:
      print(f"fatal: {c} file%s out of site-dir '{site_dir}', exiting." % ("" if c == 1 else "s"), file=sys.stderr)
      sys.exit(1)

  args_validate_suffix()
  args_validate_site_dir()

  errors = 0
  creates = 0
  skips = 0

  for i, file in enumerate(args.file):
    quiet_print(f"file: {file}", flush=True)

    if not file.is_directory_url():
      quiet_print(f"not a directory-url file, skipping")
      skips += 1
      continue

    html = None
    with open(file, "r") as f: html = f.read()

    try:
      ok, out = fix_html(html, file)
      if not ok:
        errors += 1
        print(f"{file}: fix html error: {out}", file=sys.stderr)
        continue
    except:
      print(f"{file}: unexpected error:", sys.exc_info()[0])
      raise

    outfile = (args.out_dir or file.site_dir) / f"{file.site_dir_file}{args.suffix}"

    if not outfile.parent.is_dir():
      if args.dry_run: print(f"dry run, not creating directory '{outfile.parent}'")
      else:
        quiet_print(f"create directory '{outfile.parent}'", flush=True)
        outfile.parent.mkdir(parents=True, exist_ok=True)

    creates += 1
    if args.dry_run: print(f"dry run, not writing '{outfile}'")
    else:
      if not Path(outfile) == Path(file): quiet_print(f"write '{outfile}'", flush=True)
      print(out, file=open(outfile, "w"))

  if creates:
    if args.dry_run: print(
      f"dry run, would have dir-fix-ed {creates} file%s%s" % (
        "" if creates == 1 else "s"
        , f", skipped {skips} file%s" % ("" if skips == 1 else "s") if skips else ""
      ), flush=True
    )
    else: print(
      f"{Path(sys.argv[0]).name}: {creates} file%s done%s" % (
        "" if creates == 1 else "s"
        , f", {skips} skipped" if skips else ""
      ), flush=True
    )

  if errors:
    if errors > 1:
      print(f"{Path(sys.argv[0]).name}: {errors} file error%s" % ("" if errors == 1 else "s"), file=sys.stderr)
    sys.exit(1)
