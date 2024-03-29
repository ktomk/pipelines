<?php

$config = (new PhpCsFixer\Config())
    ->registerCustomFixers(new PhpCsFixerCustomFixers\Fixers())
    ->setCacheFile('build/cache/.php_cs.cache')
    ->setRiskyAllowed(true)
    ->setRules(array(
        PhpCsFixerCustomFixers\Fixer\CommentSurroundedBySpacesFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\InternalClassCasingFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoDuplicatedArrayKeyFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoDuplicatedImportsFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\NoPhpStormGeneratedCommentFixer::name() => true,
        'operator_linebreak' => false,
        PhpCsFixerCustomFixers\Fixer\PhpdocNoSuperfluousParamFixer::name() => true,
        // problems with psalm annotations: PhpCsFixerCustomFixers\Fixer\PhpdocParamOrderFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\SingleSpaceAfterStatementFixer::name() => true,
        PhpCsFixerCustomFixers\Fixer\SingleSpaceBeforeStatementFixer::name() => true,
        'declare_strict_types' => false,
        'braces' => true,
        'cast_spaces' => array('space' => 'none'),
        'single_quote' => false,
        'concat_space' => array('spacing' => 'one'),
        'align_multiline_comment' => true,
        'array_syntax' => array('syntax' => 'long'),
        'trailing_comma_in_multiline' => true,
        'whitespace_after_comma_in_array' => true,
        'no_trailing_comma_in_singleline_array' => true,
        'blank_line_before_statement' => true,
        'combine_consecutive_issets' => true,
        'combine_consecutive_unsets' => true,
        'compact_nullable_typehint' => true,
        'escape_implicit_backslashes' => true,
        'explicit_indirect_variable' => true,
        'explicit_string_variable' => true,
        'final_internal_class' => true,
        'heredoc_to_nowdoc' => true,
        'list_syntax' => array('syntax' => 'long'),
        'method_chaining_indentation' => true,
        'method_argument_space' => array('on_multiline' =>'ensure_fully_multiline'),
        'multiline_comment_opening_closing' => true,
        'no_extra_blank_lines' => array('tokens' => array('break', 'continue', 'extra', 'return', 'throw', 'use', 'parenthesis_brace_block', 'square_brace_block', 'curly_brace_block')),
        'no_null_property_initialization' => true,
        'echo_tag_syntax' => true,
        'no_superfluous_elseif' => true,
        'no_unneeded_curly_braces' => true,
        'no_unneeded_final_method' => true,
        'no_unreachable_default_argument_value' => true,
        'no_unused_imports' => true,
        'no_useless_else' => true,
        'no_useless_return' => true,
        'ordered_class_elements' => array(
            'order' => array('constant_public', 'constant_protected', 'constant_private', 'property_public', 'property_protected', 'property_private', 'method_public_static', 'construct', 'destruct', 'magic', 'phpunit', 'method_public', 'method_protected', 'method_private')
        ),
        'ordered_imports' => true,
        'php_unit_strict' => true,
        'php_unit_test_annotation' => true,
        'php_unit_test_class_requires_covers' => true,
        'phpdoc_add_missing_param_annotation' => true,
        'phpdoc_order' => true,
        'phpdoc_separation' => true,
        'phpdoc_types_order' => true,
        'php_unit_test_case_static_method_calls' => array('call_type' => 'self'),
        'semicolon_after_instruction' => true,
        'strict_comparison' => true,
        'strict_param' => true,
        'yoda_style' => true,
    ))
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->notPath('PharBuild/Timestamps.php')
            ->in(array('src', 'test'))
    )
;

return $config;
