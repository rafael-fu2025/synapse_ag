<?php
/**
 * Pagination helper for SYNAPSE UI.
 *
 * ONE pagination component for the entire application. Every paginated
 * table (admin, clinic, counselling, inventory, reports, etc.)
 * uses this helper, so visual consistency is guaranteed.
 *
 * ---- Usage ----
 *
 * 1. CI4 pager (preferred — auto-handles page + total + perPage):
 *      // Controller
 *      $data['rows']  = $model->paginate(20);
 *      $data['pager'] = $model->pager;
 *      // View
 *      <?= pagination_links($pager) ?>
 *
 * 2. Plain array (for controllers using manual LIMIT/OFFSET):
 *      // Controller
 *      $rows = $db->table(...)->limit($perPage, ($page - 1) * $perPage)->get();
 *      $data['pager'] = [
 *          'current'  => $page,
 *          'total'    => $totalPages,        // page count
 *          'perPage'  => $perPage,
 *          'totalRec' => $totalRecords,     // record count
 *      ];
 *      // View
 *      <?= pagination_links($pager, '/admin/audit') ?>
 *
 * 3. Custom URL + preserved filters:
 *      <?= pagination_links($pager, '/admin/users', ['role' => 'staff']) ?>
 *
 * 4. Per-page selector (lets users pick 10/25/50/100 per page):
 *      <?= pagination_links($pager, null, [], [10, 25, 50, 100]) ?>
 *
 * The function auto-detects the current query string and merges it
 * so search filters, sort params, etc. are preserved across pages.
 */

if (! function_exists('pagination_normalize_pager')) {
    /**
     * Normalize whatever was passed (CI4 Pager, array, null) into a
     * simple array with current / total / perPage / totalRec keys.
     * Returns null if nothing useful is available.
     */
    function pagination_normalize_pager($pager): ?array
    {
        if ($pager === null) {
            return null;
        }
        if (is_object($pager) && method_exists($pager, 'getCurrentPage')) {
            return [
                'current'  => (int) $pager->getCurrentPage(),
                'total'    => (int) $pager->getPageCount(),
                'perPage'  => (int) $pager->getPerPage(),
                'totalRec' => (int) $pager->getTotal(),
            ];
        }
        if (is_array($pager)) {
            $current  = (int) ($pager['current']  ?? $pager['page']      ?? 1);
            $total    = (int) ($pager['total']    ?? $pager['totalPages'] ?? 1);
            $perPage  = (int) ($pager['perPage']  ?? 20);
            $totalRec = (int) ($pager['totalRec'] ?? $pager['total']     ?? 0);
            return [
                'current'  => max(1, $current),
                'total'    => max(1, $total),
                'perPage'  => max(1, $perPage),
                'totalRec' => max(0, $totalRec),
            ];
        }
        return null;
    }
}

if (! function_exists('pagination_links')) {
    /**
     * Render a pagination control.
     *
     * @param mixed        $pager       CI4 Pager instance OR array with current/total/perPage/totalRec
     * @param string|null  $baseUrl     Override the base URL (else uses current request URL)
     * @param array        $queryParams Extra query params to preserve
     * @param array|null   $perPageOpts Optional list of per-page values to render a selector. Default: off.
     *                                   Pass an empty array to explicitly disable.
     *
     * @return string HTML
     */
    function pagination_links($pager, ?string $baseUrl = null, array $queryParams = [], ?array $perPageOpts = null): string
    {
        $state = pagination_normalize_pager($pager);
        if ($state === null) {
            return '';
        }

        $current  = $state['current'];
        $total    = $state['total'];
        $perPage  = $state['perPage'];
        $totalRec = $state['totalRec'];

        if ($total <= 1) {
            // Still show count info but no controls
            $start = $totalRec > 0 ? 1 : 0;
            $end   = min($perPage, $totalRec);
            $info  = $totalRec > 0
                ? "Showing <strong>{$start}–{$end}</strong> of <strong>{$totalRec}</strong>"
                : 'No results';
            $perPageHtml = pagination_render_per_page_selector($perPage, $perPageOpts, $baseUrl, $queryParams);
            return '<nav class="syn-pagination" aria-label="Pagination">'
                 .     '<div class="syn-pagination-info">' . $info . $perPageHtml . '</div>'
                 . '</nav>';
        }

        // Build base URL with query params
        if ($baseUrl === null) {
            $uri = service('uri');
            $baseUrl = ($uri->getScheme() ? $uri->getScheme() . '://' : '')
                     . ($uri->getHost() ? $uri->getHost() : '')
                     . ($uri->getPort() ? ':' . $uri->getPort() : '')
                     . $uri->getPath();
        }

        // Capture existing query params
        $request = service('request');
        $existingParams = $request->getGet();
        unset($existingParams['page']);
        unset($existingParams['per_page']);
        $params = array_merge($existingParams, $queryParams);

        $buildUrl = function (int $page) use ($baseUrl, $params): string {
            $params['page'] = $page;
            $query = http_build_query($params);
            return $baseUrl . ($query ? '?' . $query : '');
        };

        $start = ($current - 1) * $perPage + 1;
        $end   = min($current * $perPage, $totalRec);

        $html  = '<nav class="syn-pagination" aria-label="Pagination">';

        // ---- Left: count info + per-page selector ----
        $info  = "Showing <strong>{$start}–{$end}</strong> of <strong>{$totalRec}</strong>";
        $info .= " &middot; page <strong>{$current}</strong> of <strong>{$total}</strong>";
        $html .=     '<div class="syn-pagination-info">'
                 .         $info
                 .         pagination_render_per_page_selector($perPage, $perPageOpts, $baseUrl, $queryParams)
                 .     '</div>';

        // ---- Right: controls ----
        $html .=     '<ul class="syn-pagination-list" role="list">';

        // First / Prev
        $html .= '<li class="syn-pagination-item">';
        if ($current <= 1) {
            $html .= '<span class="syn-pagination-link syn-pagination-edge is-disabled" aria-label="First page" aria-disabled="true"><i class="fas fa-angles-left"></i></span>';
        } else {
            $html .= '<a class="syn-pagination-link syn-pagination-edge" href="' . esc($buildUrl(1)) . '" aria-label="First page" rel="first"><i class="fas fa-angles-left"></i></a>';
        }
        $html .= '</li>';

        $html .= '<li class="syn-pagination-item">';
        if ($current <= 1) {
            $html .= '<span class="syn-pagination-link syn-pagination-edge is-disabled" aria-label="Previous page" aria-disabled="true"><i class="fas fa-angle-left"></i></span>';
        } else {
            $html .= '<a class="syn-pagination-link syn-pagination-edge" href="' . esc($buildUrl($current - 1)) . '" aria-label="Previous page" rel="prev"><i class="fas fa-angle-left"></i></a>';
        }
        $html .= '</li>';

        // Page numbers with ellipsis
        $pageNumbers = computePageRange($current, $total);
        foreach ($pageNumbers as $p) {
            $html .= '<li class="syn-pagination-item">';
            if ($p === '...') {
                $html .= '<span class="syn-pagination-ellipsis" aria-hidden="true">&hellip;</span>';
            } elseif ($p === $current) {
                $html .= '<span class="syn-pagination-link is-active" aria-current="page" aria-label="Page ' . $p . '">' . $p . '</span>';
            } else {
                $html .= '<a class="syn-pagination-link" href="' . esc($buildUrl($p)) . '" aria-label="Page ' . $p . '">' . $p . '</a>';
            }
            $html .= '</li>';
        }

        // Next / Last
        $html .= '<li class="syn-pagination-item">';
        if ($current >= $total) {
            $html .= '<span class="syn-pagination-link syn-pagination-edge is-disabled" aria-label="Next page" aria-disabled="true"><i class="fas fa-angle-right"></i></span>';
        } else {
            $html .= '<a class="syn-pagination-link syn-pagination-edge" href="' . esc($buildUrl($current + 1)) . '" aria-label="Next page" rel="next"><i class="fas fa-angle-right"></i></a>';
        }
        $html .= '</li>';

        $html .= '<li class="syn-pagination-item">';
        if ($current >= $total) {
            $html .= '<span class="syn-pagination-link syn-pagination-edge is-disabled" aria-label="Last page" aria-disabled="true"><i class="fas fa-angles-right"></i></span>';
        } else {
            $html .= '<a class="syn-pagination-link syn-pagination-edge" href="' . esc($buildUrl($total)) . '" aria-label="Last page" rel="last"><i class="fas fa-angles-right"></i></a>';
        }
        $html .= '</li>';

        $html .=     '</ul>';
        $html .= '</nav>';

        return $html;
    }
}

if (! function_exists('pagination_render_per_page_selector')) {
    /**
     * Render the per-page selector (e.g. "Per page: 10 / 25 / 50").
     * Returns '' if no options were requested.
     *
     * Each <option> carries a `data-synapse-per-page-url` attribute; the
     * JS auto-wire in synapse-ui.js binds the change event so we don't
     * need an inline `onchange` (cleaner, CSP-friendly, removable).
     */
    function pagination_render_per_page_selector(int $currentPerPage, ?array $opts, ?string $baseUrl, array $queryParams): string
    {
        if ($opts === null || count($opts) === 0) {
            return '';
        }

        $request   = service('request');
        $existing  = $request->getGet();
        unset($existing['per_page'], $existing['page']);
        $params    = array_merge($existing, $queryParams);
        // Make sure no leftover 'per_page' / 'page' leaks in.
        unset($params['per_page'], $params['page']);

        $urlFor = function (int $pp) use ($baseUrl, $params): string {
            $p             = $params;
            $p['per_page'] = $pp;
            $query         = http_build_query($p);
            return ($baseUrl ?: '') . ($query ? '?' . $query : '');
        };

        $sel  = '<label class="syn-pagination-size">';
        $sel .=     '<span>Per page</span>';
        $sel .=     '<select class="syn-pagination-size-select" data-synapse-per-page-select aria-label="Items per page">';
        foreach ($opts as $opt) {
            $opt    = (int) $opt;
            $sel   .= '<option value="' . esc($urlFor($opt)) . '"'
                  .     ($opt === $currentPerPage ? ' selected' : '') . '>'
                  .     $opt . '</option>';
        }
        $sel .=     '</select>';
        $sel .= '</label>';
        return $sel;
    }
}

if (! function_exists('computePageRange')) {
    /**
     * Compute the range of page numbers to display, with ellipses.
     * Returns array like: [1, '...', 4, 5, 6, '...', 12]
     */
    function computePageRange(int $current, int $total): array
    {
        $range = [];

        if ($total <= 7) {
            for ($i = 1; $i <= $total; $i++) {
                $range[] = $i;
            }
            return $range;
        }

        // Always show first page
        $range[] = 1;

        // Left side
        if ($current > 4) {
            $range[] = '...';
        }

        $start = max(2, $current - 1);
        $end   = min($total - 1, $current + 1);

        for ($i = $start; $i <= $end; $i++) {
            $range[] = $i;
        }

        // Right side
        if ($current < $total - 3) {
            $range[] = '...';
        }

        // Always show last page
        $range[] = $total;

        return $range;
    }
}
