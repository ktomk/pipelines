#!/dev/null
#
# this file is part of pipelines
#
# this file is to be sourced, not executed

# have project utilities in $PATH
PATH="$PATH:../../bin"

####
# invoke php having assertions throwing
#
# $1 : path to php file
# ...: zero or more arguments
assert() {
  local php_file
  php_file="$1"
  shift 1
  "${PHP_BINARY-php}" -dphar.readonly=0 -dzend.assertions=1 -dassert.exception=1 -f "$php_file" -- "$@"
}

####
# invoke utility-name with php having assertions throwing
#
# $1 : utility-name
# ...: zero or more utility arguments
assert_utility() {
  local utility_name
  utility_name="$1"
  shift 1
  assert "$(which "$utility_name")" -- "$@"
}

####
# clean for test
#
# $1: test-file, for sanity check that test is in current working directory b/f
#     git clean is executed which can be destructive
clean_test() {
  [[ -f "$1" ]]
  git clean -d -X -f | wc -l | sed 's/^/clean: /; s/$/ path(s)/'
}

####
# run test plan
# $1 : test-file
# ...: one or more tests
run_test() {
  local test_file
  test_file="$1"
  shift 1

  until [[ "${1:-}" == "" ]]; do
    "$test_file" "$1"
    shift 1
  done
}
