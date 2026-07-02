<?php

/**
 * LIKE wildcard escape helper.
 *
 * Use on ANY user-supplied search term BEFORE passing it to a query builder
 * `like()` / `orLike()` call. Without escaping, characters like `%`, `_`,
 * and `\` in the user's input are treated as SQL wildcards:
 *
 *   - `%` matches any sequence (so searching "100%" matches every row
 *     ending in "100")
 *   - `_` matches any single character
 *
 * Escape them with a leading backslash so they're matched literally.
 *
 * Usage:
 *   $term = escape_like($this->request->getGet('q') ?? '');
 *   $model->like('email', $term);
 *
 * @param string $value Raw user input.
 * @return string Escaped value safe to wrap with `%...%`.
 */
function escape_like(string $value): string
{
    // Order matters: escape the escape character first, then the two wildcards.
    return addcslashes($value, '\\%_');
}
