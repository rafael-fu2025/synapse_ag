<?php
/**
 * Search highlight helper for SYNAPSE UI.
 *
 * ONE consistent helper used everywhere the user types into a search box
 * and we want to highlight the matching substring inside result rows.
 *
 * The server is the source of truth for filtering — so the server is also
 * where we wrap matches in <mark> tags. The client doesn't have to do
 * any DOM post-processing: the markup arrives already highlighted.
 *
 * Usage in a view:
 *
 *     <td><?= search_highlight($student['email'], $q) ?></td>
 *     <td><?= search_highlight($medicine['generic_name'], $q) ?></td>
 *     <td><?= search_highlight($log['first_name'] . ' ' . $log['last_name'], $q) ?></td>
 *
 * Behaviour:
 *   - Case-insensitive matching (AUTO-LOWERCASED before comparison).
 *   - Whole-word AND substring matching both work — anything the user
 *     types is highlighted wherever it appears.
 *   - HTML-safe: the input is escaped first, then <mark> tags are
 *     injected around the match. User-supplied content can't inject
 *     markup because we use HTML-escape on the full string before
 *     re-inserting.
 *   - Empty / whitespace-only queries are no-ops (return input as-is).
 *   - Very long queries are truncated to prevent regex blow-up.
 *
 * Why `<mark>` instead of `<span class="highlight">`:
 *   - `<mark>` is a semantic HTML5 element — browsers style it
 *     by default and screen readers announce it ("highlighted text").
 *   - We override its visual style in synapse-ui.css to match the
 *     SYNAPSE brand (soft primary-yellow background).
 */

if (! function_exists('search_highlight')) {
    /**
     * Wrap occurrences of $query inside $text in <mark> tags.
     *
     * @param string      $text   The source text (will be HTML-escaped)
     * @param string      $query  The user's search term
     * @param string|null $tag    Optional override for the wrapper tag
     *
     * @return string HTML
     */
    function search_highlight(string $text, ?string $query, ?string $tag = 'mark'): string
    {
        /* Null or whitespace-only query → return escaped text as-is. */
        if ($query === null || trim($query) === '') {
            return esc($text);
        }

        /* Truncate absurdly long queries so the regex engine stays happy.
           256 chars is well past anything a user would ever type. */
        $needle = mb_substr(trim($query), 0, 256);

        /* Escape the input first, THEN wrap matches. This guarantees the
           output is safe even if the user typed "<script>" — we escape
           the whole string before injecting <mark> tags around the
           already-escaped needle. */
        $escaped = esc($text);

        /* preg_quote the escaped needle so regex special characters don't
           break the search. Use 'u' for unicode (multi-byte safe). */
        $quoted = preg_quote($escaped, '/');

        /* Replace all occurrences (case-insensitive) with the wrapped
           form. The $1 captures the original (already-escaped) match. */
        return preg_replace(
            '/(' . preg_quote(esc($needle), '/') . ')/iu',
            '<' . $tag . '>$1</' . $tag . '>',
            $escaped
        );
    }
}

if (! function_exists('search_highlight_trim')) {
    /**
     * Highlight helper for very long strings. Truncates the source
     * around the first match (so a 500-character blob doesn't flood
     * the result row) and highlights all matches in the visible window.
     *
     * Useful for log entries, audit "details" JSON, or any long text
     * field where the user wants to see WHERE the match is.
     *
     * @param string $text     Full source text
     * @param string $query    User's search term
     * @param int    $radius   How many chars of context on either side
     * @param string $ellipsis The string appended/prepended to truncation
     *
     * @return string HTML
     */
    function search_highlight_trim(string $text, ?string $query, int $radius = 60, string $ellipsis = '…'): string
    {
        if ($query === null || trim($query) === '') {
            return esc(mb_strlen($text) > $radius * 2 ? mb_substr($text, 0, $radius * 2) . $ellipsis : $text);
        }

        $needle = mb_substr(trim($query), 0, 256);

        /* Case-insensitive search. mb_stripos returns the position of the
           first match (or false). */
        $pos = mb_stripos($text, $needle);
        if ($pos === false) {
            /* No match in the source — return the start, ellipsised. */
            return esc(mb_strlen($text) > $radius * 2 ? mb_substr($text, 0, $radius * 2) . $ellipsis : $text);
        }

        /* Compute a window around the match. */
        $start   = max(0, $pos - $radius);
        $length  = min(mb_strlen($text) - $start, mb_strlen($needle) + $radius * 2);
        $snippet = mb_substr($text, $start, $length);

        $prefix = $start > 0 ? $ellipsis : '';
        $suffix = ($start + $length) < mb_strlen($text) ? $ellipsis : '';

        return $prefix . search_highlight($snippet, $needle) . $suffix;
    }
}