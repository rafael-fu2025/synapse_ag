<?php

namespace App\Controllers;

use App\Controllers\BaseController;

/**
 * Developer reference page for the SYNAPSE UI component library.
 *
 * Renders all six component types (dialogs, toasts, spinners,
 * skeletons, pagination, date/time picker) with working examples.
 *
 * Access at: GET /ui
 */
class UiController extends BaseController
{
    public function showcase()
    {
        // Build a fake pager for the demo so the pagination example renders
        // without needing a real database query. 487 records, 10 per page
        // = 49 pages, currently on page 7. This exercises the ellipsis
        // logic (which kicks in past 7 pages).
        $demoPager = service('pager');
        $demoPager->store('default', 7, 10, 487);

        return view('ui/showcase', [
            'title'    => 'UI Components — SYNAPSE',
            'heading'  => 'UI Components',
            'demoPager' => $demoPager,
        ]);
    }
}
