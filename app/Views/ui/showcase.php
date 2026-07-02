<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<style>
    .ui-section {
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 14px;
        padding: 1.75rem 2rem;
        margin-bottom: 1.5rem;
    }
    .ui-section h2 {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--gray-900);
        margin: 0 0 0.25rem 0;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    .ui-section h2 .badge {
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding: 0.2rem 0.5rem;
        border-radius: 9999px;
        background: var(--primary-50);
        color: var(--primary-700);
        border: 1px solid var(--primary-100);
    }
    .ui-section p.lead {
        font-size: 0.85rem;
        color: var(--gray-500);
        margin: 0 0 1.25rem 0;
    }
    .ui-section h3 {
        font-size: 0.7rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: var(--gray-500);
        margin: 1.25rem 0 0.65rem 0;
    }
    .ui-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .ui-stack {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    .ui-row {
        display: flex;
        flex-wrap: wrap;
        gap: 0.85rem;
        align-items: center;
    }
    .ui-spacer-sm { height: 0.5rem; }
    .ui-spacer { height: 1rem; }
    .ui-demo-box {
        background: var(--gray-50);
        border: 1px dashed var(--gray-200);
        border-radius: 10px;
        padding: 1.25rem;
        margin: 0.65rem 0;
    }
    code.inline {
        background: var(--gray-100);
        color: var(--primary-700);
        padding: 0.1rem 0.4rem;
        border-radius: 4px;
        font-family: var(--font-mono);
        font-size: 0.8em;
    }
</style>

<div class="page-content" style="max-width: 1100px; margin: 0 auto;">

    <div class="page-header" style="margin-bottom: 1.5rem;">
        <h1 style="font-family: var(--font-display, 'Outfit'), sans-serif; font-size: 1.85rem; font-weight: 800; color: var(--gray-900); margin: 0 0 0.25rem 0; letter-spacing: -0.02em;">SYNAPSE UI Library</h1>
        <p style="font-size: 0.9rem; color: var(--gray-500); margin: 0;">Reusable components loaded once via <code class="inline"><?= base_url('assets/css/synapse-ui.css') ?></code> and <code class="inline"><?= base_url('assets/js/synapse-ui.js') ?></code>.</p>
    </div>

    <!-- ============================================================
         1. DIALOGS / MODALS
         ============================================================ -->
    <section class="ui-section" id="dialogs">
        <h2>1. Dialogs <span class="badge">Modals</span></h2>
        <p class="lead">Confirmation dialogs, alerts, and the shadcn-style card dialog with copy-to-clipboard input. Backdrop blur, ESC to close, click-outside to dismiss.</p>

        <h3>Declarative (data-attribute)</h3>
        <div class="ui-row">
            <button type="button" class="syn-btn syn-btn--primary"
                data-synapse-open="dlg-info">
                <i class="fas fa-circle-info"></i> Open Info Dialog
            </button>
            <button type="button" class="syn-btn syn-btn--danger"
                data-synapse-confirm
                data-synapse-confirm-title="Delete this record?"
                data-synapse-confirm-body="This action cannot be undone. The record will be permanently removed from the system."
                data-synapse-confirm-text="Delete"
                data-synapse-confirm-danger>
                <i class="fas fa-trash"></i> Delete (confirm)
            </button>
            <button type="button" class="syn-btn syn-btn--secondary"
                data-synapse-toast="This is a toast!" data-synapse-toast-type="info">
                <i class="fas fa-bell"></i> Show Toast
            </button>
        </div>

        <h3>Imperative (JavaScript API)</h3>
        <div class="ui-row">
            <button type="button" class="syn-btn syn-btn--primary" onclick="demoTokenDialog()">
                <i class="fas fa-key"></i> Token Card (shadcn-style)
            </button>
            <button type="button" class="syn-btn syn-btn--secondary" onclick="demoCustomDialog()">
                <i class="fas fa-code"></i> Custom HTML Body
            </button>
            <button type="button" class="syn-btn syn-btn--danger" onclick="demoDangerDialog()">
                <i class="fas fa-triangle-exclamation"></i> Danger Dialog
            </button>
        </div>

        <div class="ui-spacer"></div>
        <details>
            <summary style="cursor: pointer; font-size: 0.8rem; color: var(--gray-600); font-weight: 500;">View usage examples</summary>
            <pre style="background: var(--gray-900); color: #E5E7EB; padding: 1rem 1.25rem; border-radius: 8px; font-size: 0.78rem; line-height: 1.6; overflow-x: auto; margin-top: 0.5rem;"><code>// Declarative — by element id
&lt;button data-synapse-open="myDialog"&gt;Open&lt;/button&gt;

// Declarative — confirm
&lt;button data-synapse-confirm
        data-synapse-confirm-title="Sure?"
        data-synapse-confirm-danger
        data-synapse-confirm-text="Delete"
        onclick="..."&gt;Delete&lt;/button&gt;

// Imperative — full control
synapse.dialog.confirm({
    title: 'Confirm action',
    body: 'Are you sure?',
    danger: true,
    confirmText: 'Delete',
    onConfirm: () =&gt; doDelete()
});</code></pre>
        </details>
    </section>

    <!-- ============================================================
         2. TOASTS
         ============================================================ -->
    <section class="ui-section" id="toasts">
        <h2>2. Toasts <span class="badge">Notifications</span></h2>
        <p class="lead">Stacked, auto-dismissing notifications with progress bar and four variants. Position: bottom-right, max 380px wide.</p>

        <div class="ui-row">
            <button type="button" class="syn-btn syn-btn--primary" onclick="showToast('success')">
                <i class="fas fa-check"></i> Success
            </button>
            <button type="button" class="syn-btn syn-btn--danger" onclick="showToast('error')">
                <i class="fas fa-xmark"></i> Error
            </button>
            <button type="button" class="syn-btn syn-btn--secondary" onclick="showToast('warning')">
                <i class="fas fa-exclamation"></i> Warning
            </button>
            <button type="button" class="syn-btn syn-btn--secondary" onclick="showToast('info')">
                <i class="fas fa-info"></i> Info
            </button>
            <button type="button" class="syn-btn syn-btn--ghost" onclick="showToast('sticky')">
                <i class="fas fa-thumbtack"></i> Sticky (no auto-dismiss)
            </button>
        </div>

        <div class="ui-spacer"></div>
        <pre style="background: var(--gray-900); color: #E5E7EB; padding: 1rem 1.25rem; border-radius: 8px; font-size: 0.78rem; line-height: 1.6; overflow-x: auto;"><code>// Simple
synapse.toast('Saved successfully', 'success');

// Full options
synapse.toast({
    type: 'error',
    title: 'Could not save',
    message: 'Network connection lost. Please try again.',
    duration: 6000     // 0 = sticky
});</code></pre>
    </section>

    <!-- ============================================================
         3. LOADING SPINNERS
         ============================================================ -->
    <section class="ui-section" id="spinners">
        <h2>3. Loading Spinners <span class="badge">Spinners</span></h2>
        <p class="lead">Pure-CSS spinners in four sizes. Can replace button content while submitting, or be used inline.</p>

        <h3>Standalone spinners</h3>
        <div class="ui-row" style="gap: 1.25rem;">
            <div style="text-align: center;">
                <div class="syn-spinner syn-spinner--sm"></div>
                <div style="font-size: 0.7rem; color: var(--gray-500); margin-top: 0.35rem;">sm (14px)</div>
            </div>
            <div style="text-align: center;">
                <div class="syn-spinner syn-spinner--md"></div>
                <div style="font-size: 0.7rem; color: var(--gray-500); margin-top: 0.35rem;">md (20px)</div>
            </div>
            <div style="text-align: center;">
                <div class="syn-spinner syn-spinner--lg"></div>
                <div style="font-size: 0.7rem; color: var(--gray-500); margin-top: 0.35rem;">lg (32px)</div>
            </div>
            <div style="text-align: center;">
                <div class="syn-spinner syn-spinner--xl"></div>
                <div style="font-size: 0.7rem; color: var(--gray-500); margin-top: 0.35rem;">xl (48px)</div>
            </div>
            <div style="text-align: center;">
                <div class="syn-spinner syn-spinner--md syn-spinner--light"
                     style="background: var(--gray-900); padding: 0.5rem; border-radius: 8px;"></div>
                <div style="font-size: 0.7rem; color: var(--gray-500); margin-top: 0.35rem;">light (on dark)</div>
            </div>
        </div>

        <h3>Button loading state</h3>
        <div class="ui-row">
            <button type="button" class="syn-btn syn-btn--primary" id="btn-spin-demo"
                onclick="demoButtonSpinner(this)">
                <i class="fas fa-floppy-disk"></i> Save Changes
            </button>
            <button type="button" class="syn-btn syn-btn--danger" id="btn-spin-demo-2"
                onclick="demoButtonSpinner(this)">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>

        <h3>Full-page loader</h3>
        <div class="ui-row">
            <button type="button" class="syn-btn syn-btn--secondary" onclick="demoPageLoader()">
                <i class="fas fa-window-maximize"></i> Show page loader (2s)
            </button>
        </div>

        <div class="ui-spacer"></div>
        <pre style="background: var(--gray-900); color: #E5E7EB; padding: 1rem 1.25rem; border-radius: 8px; font-size: 0.78rem; line-height: 1.6; overflow-x: auto;"><code>// Button loading state
const stop = synapse.spinner.button(buttonEl);
// ... do async work ...
stop();

// Page overlay
const hide = synapse.spinner.page('Loading records...');
fetch(...).then(hide);</code></pre>
    </section>

    <!-- ============================================================
         4. SKELETON LOADING
         ============================================================ -->
    <section class="ui-section" id="skeletons">
        <h2>4. Skeleton Loading <span class="badge">Placeholders</span></h2>
        <p class="lead">Shimmer placeholders shown while data loads. Six variants: text, line, title, avatar, thumb, block, card.</p>

        <h3>Primitive shapes</h3>
        <div class="ui-demo-box">
            <span class="syn-skel syn-skel--title"></span>
            <div class="ui-spacer-sm"></div>
            <span class="syn-skel syn-skel--line"></span>
            <span class="syn-skel syn-skel--text"></span>
            <span class="syn-skel syn-skel--text" style="width: 60%;"></span>
        </div>

        <h3>Card skeleton</h3>
        <div class="syn-skel-card">
            <div style="display: flex; gap: 0.75rem; margin-bottom: 0.5rem;">
                <span class="syn-skel syn-skel--avatar"></span>
                <div style="flex: 1;">
                    <span class="syn-skel syn-skel--line" style="width: 30%;"></span>
                    <span class="syn-skel syn-skel--line" style="width: 50%;"></span>
                </div>
            </div>
            <span class="syn-skel syn-skel--block"></span>
        </div>

        <h3>Auto-replace with async data</h3>
        <div id="skeleton-demo-target"></div>
        <button type="button" class="syn-btn syn-btn--primary" onclick="demoSkeleton()">
            <i class="fas fa-play"></i> Run skeleton demo
        </button>

        <div class="ui-spacer"></div>
        <pre style="background: var(--gray-900); color: #E5E7EB; padding: 1rem 1.25rem; border-radius: 8px; font-size: 0.78rem; line-height: 1.6; overflow-x: auto;"><code>synapse.skeleton('#myContainer', async () =&gt; {
    const res = await fetch('/api/records');
    const data = await res.json();
    return data.map(r =&gt; `&lt;div&gt;${r.name}&lt;/div&gt;`).join('');
});</code></pre>
    </section>

    <!-- ============================================================
         5. PAGINATION
         ============================================================ -->
    <section class="ui-section" id="pagination">
        <h2>5. Pagination <span class="badge">Lists</span></h2>
        <p class="lead">PHP helper renders first/prev/numbered/next/last controls with ellipsis. Auto-detects current query string so filters persist.</p>

        <h3>Helper output (demo pager with 25 pages)</h3>
        <div style="border: 1px solid var(--gray-200); border-radius: 10px; background: var(--gray-50); padding: 0.5rem 1rem;">
            <?= pagination_links($demoPager, '/ui') ?>
        </div>

        <h3>Usage in a controller</h3>
        <pre style="background: var(--gray-900); color: #E5E7EB; padding: 1rem 1.25rem; border-radius: 8px; font-size: 0.78rem; line-height: 1.6; overflow-x: auto;"><code>// In your controller
$data['records'] = $model-&gt;paginate(15);
$data['pager']   = $model-&gt;pager;
return view('records/index', $data);

// In your view
&lt;table&gt;...&lt;/table&gt;
&lt;?= pagination_links($pager) ?&gt;

// Custom URL with preserved filters
&lt;?= pagination_links($pager, '/admin/users', ['role' =&gt; 'staff']) ?&gt;</code></pre>
    </section>

    <!-- ============================================================
         6. DATE / TIME PICKER
         ============================================================ -->
    <section class="ui-section" id="datepickers">
        <h2>6. Date / Time Picker <span class="badge">Inputs</span></h2>
        <p class="lead">Powered by flatpickr (CDN, MIT, ~20KB). Auto-initializes on any input with class <code class="inline">syn-datepicker</code>. Three variants: date, datetime, time-only.</p>

        <h3>Date picker</h3>
        <input type="text" class="syn-datepicker" placeholder="YYYY-MM-DD">

        <h3>Date + time picker</h3>
        <input type="text" class="syn-datepicker syn-datepicker--time" placeholder="YYYY-MM-DD HH:MM">

        <h3>Time-only picker</h3>
        <input type="text" class="syn-datepicker syn-datepicker--time syn-datepicker--time-only" placeholder="HH:MM">

        <h3>Date range picker</h3>
        <input type="text" class="syn-datepicker syn-datepicker--range" placeholder="YYYY-MM-DD to YYYY-MM-DD">

        <h3>Pre-filled values</h3>
        <input type="text" class="syn-datepicker syn-datepicker--time" value="<?= date('Y-m-d H:i', strtotime('+3 days')) ?>">

        <div class="ui-spacer"></div>
        <pre style="background: var(--gray-900); color: #E5E7EB; padding: 1rem 1.25rem; border-radius: 8px; font-size: 0.78rem; line-height: 1.6; overflow-x: auto;"><code>&lt;input type="text" class="syn-datepicker"                          placeholder="Date"&gt;
&lt;input type="text" class="syn-datepicker syn-datepicker--time"   placeholder="Date + time"&gt;
&lt;input type="text" class="syn-datepicker syn-datepicker--time-only" placeholder="Time"&gt;
&lt;input type="text" class="syn-datepicker syn-datepicker--range"  placeholder="Range"&gt;

// Programmatic access
const fp = document.querySelector('.syn-datepicker')._flatpickr;
fp.setDate('2026-07-01');
fp.close();</code></pre>
    </section>

    <!-- ============================================================
         6.5. DROPDOWNS
         ============================================================ -->
    <section class="ui-section" id="dropdowns">
        <h2>6.5. Dropdowns <span class="badge">Selects &amp; Menus</span></h2>
        <p class="lead">Two flavors: opt-in native chevron styling via <code class="inline">class="syn-select"</code>, or full custom dropdown via <code class="inline">data-synapse-dropdown</code>. Plain <code class="inline">&lt;select&gt;</code> uses the browser default.</p>

        <h3>Branded native (opt-in via <code class="inline">class="syn-select"</code>)</h3>
        <p style="font-size: 0.8rem; color: var(--gray-500); margin: 0 0 0.65rem 0;">Plain <code class="inline">&lt;select&gt;</code> elements use the browser default. Add <code class="inline">class="syn-select"</code> to get the brand chevron and focus ring.</p>
        <div class="ui-row" style="align-items: flex-start;">
            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 0.75rem; color: var(--gray-600); margin-bottom: 0.3rem; font-weight: 500;">Default size</label>
                <select class="syn-select">
                    <option>Choose one…</option>
                    <option>Clinic Staff</option>
                    <option>Counsellor</option>
                    <option>PASIMEO Coordinator</option>
                    <option>System Administrator</option>
                </select>
            </div>
            <div style="width: 140px;">
                <label style="display: block; font-size: 0.75rem; color: var(--gray-600); margin-bottom: 0.3rem; font-weight: 500;">Small</label>
                <select class="syn-select syn-select--sm">
                    <option>All</option>
                    <option>Active</option>
                    <option>Inactive</option>
                </select>
            </div>
            <div style="width: 180px;">
                <label style="display: block; font-size: 0.75rem; color: var(--gray-600); margin-bottom: 0.3rem; font-weight: 500;">Large</label>
                <select class="syn-select syn-select--lg">
                    <option>Last 7 days</option>
                    <option>Last 30 days</option>
                    <option>Last 90 days</option>
                </select>
            </div>
        </div>

        <h3>Custom dropdown (rich UI)</h3>
        <div style="max-width: 360px;">
            <label style="display: block; font-size: 0.75rem; color: var(--gray-600); margin-bottom: 0.3rem; font-weight: 500;">Single-select with search</label>
            <select data-synapse-dropdown data-synapse-searchable data-placeholder="Select a role…">
                <option value=""></option>
                <option value="admin" data-icon="fas fa-shield-halved">System Administrator</option>
                <option value="clinic" data-icon="fas fa-stethoscope">Clinic Staff</option>
                <option value="counsellor" data-icon="fas fa-heart-pulse">Guidance Counsellor</option>
                <option value="pasimeo" data-icon="fas fa-people-carry-box">PASIMEO Coordinator</option>
                <option value="student" data-icon="fas fa-user-graduate">Student</option>
                <option value="parent" data-icon="fas fa-users">Parent / Guardian</option>
                <option value="nurse" data-icon="fas fa-user-nurse">School Nurse</option>
                <option value="dentist" data-icon="fas fa-tooth">Dentist</option>
                <option value="psychiatrist" data-icon="fas fa-brain">Psychiatrist</option>
                <option value="intern" data-icon="fas fa-user-plus">Intern</option>
            </select>
        </div>

        <h3>Multi-select with chips</h3>
        <div style="max-width: 480px;">
            <label style="display: block; font-size: 0.75rem; color: var(--gray-600); margin-bottom: 0.3rem; font-weight: 500;">Modules (select multiple)</label>
            <select multiple data-synapse-dropdown data-placeholder="Pick modules…">
                <option value="clinic" data-icon="fas fa-stethoscope">Clinic Management</option>
                <option value="counselling" data-icon="fas fa-heart-pulse">Counselling</option>
                <option value="pasimeo" data-icon="fas fa-people-carry-box">PASIMEO Outreach</option>
                <option value="inventory" data-icon="fas fa-pills">Inventory</option>
                <option value="iot" data-icon="fas fa-qrcode">IoT Kiosk</option>
                <option value="reports" data-icon="fas fa-chart-bar">Reports &amp; Analytics</option>
            </select>
        </div>

        <h3>With section headers and dividers</h3>
        <div style="max-width: 360px;">
            <select data-synapse-dropdown data-placeholder="Switch user…">
                <option value=""></option>
                <optgroup label="Administrators">
                    <option value="admin1" data-icon="fas fa-shield-halved">Maria Santos (Admin)</option>
                </optgroup>
                <optgroup label="Clinic Staff">
                    <option value="clinic1" data-icon="fas fa-stethoscope">Juan Dela Cruz</option>
                    <option value="clinic2" data-icon="fas fa-user-nurse">Ana Reyes</option>
                </optgroup>
                <optgroup label="Counsellors">
                    <option value="counsellor1" data-icon="fas fa-heart-pulse">Patricia Lim</option>
                </optgroup>
            </select>
        </div>

        <h3>Disabled state</h3>
        <div style="max-width: 320px;">
            <select data-synapse-dropdown disabled>
                <option value="locked">Locked by admin</option>
            </select>
        </div>

        <div class="ui-spacer"></div>
        <pre style="background: var(--gray-900); color: #E5E7EB; padding: 1rem 1.25rem; border-radius: 8px; font-size: 0.78rem; line-height: 1.6; overflow-x: auto;"><code>&lt;!-- Native (auto-styled, no JS) --&gt;
&lt;select&gt;
  &lt;option&gt;Clinic Staff&lt;/option&gt;
&lt;/select&gt;

&lt;!-- Custom dropdown (rich UI) --&gt;
&lt;select data-synapse-dropdown
        data-synapse-searchable
        data-placeholder="Select role…"&gt;
  &lt;option value=""&gt;&lt;/option&gt;
  &lt;option value="admin" data-icon="fas fa-shield-halved"&gt;Administrator&lt;/option&gt;
&lt;/select&gt;

&lt;!-- Multi-select with chips --&gt;
&lt;select multiple data-synapse-dropdown&gt;
  &lt;option value="clinic"&gt;Clinic&lt;/option&gt;
  &lt;option value="iot"&gt;IoT&lt;/option&gt;
&lt;/select&gt;

&lt;!-- Imperative API --&gt;
synapse.dropdown.build(document.querySelector('#mySelect'));</code></pre>
    </section>

    <!-- ============================================================
         7. BUTTONS (bonus reference)
         ============================================================ -->
    <section class="ui-section" id="buttons">
        <h2>7. Buttons <span class="badge">Primitives</span></h2>
        <p class="lead">Shared button styles used by dialogs, pagination, and inline actions.</p>

        <h3>Variants</h3>
        <div class="ui-row">
            <button class="syn-btn syn-btn--primary"><i class="fas fa-check"></i> Primary</button>
            <button class="syn-btn syn-btn--secondary"><i class="fas fa-xmark"></i> Secondary</button>
            <button class="syn-btn syn-btn--danger"><i class="fas fa-trash"></i> Danger</button>
            <button class="syn-btn syn-btn--ghost"><i class="fas fa-arrow-right"></i> Ghost</button>
            <button class="syn-btn syn-btn--primary" disabled>Disabled</button>
        </div>

        <h3>Sizes</h3>
        <div class="ui-row" style="align-items: center;">
            <button class="syn-btn syn-btn--primary syn-btn--sm">Small</button>
            <button class="syn-btn syn-btn--primary">Default</button>
            <button class="syn-btn syn-btn--primary syn-btn--lg">Large</button>
        </div>
    </section>

</div>

<!-- ============================================================
     DIALOG DECLARATIONS
     ============================================================ -->

<!-- Simple info dialog opened via data-synapse-open -->
<div class="syn-dialog" id="dlg-info" hidden>
    <div class="syn-dialog-header">
        <h2 class="syn-dialog-title">Information</h2>
        <p class="syn-dialog-desc">static-id-dialog</p>
    </div>
    <div class="syn-dialog-body">
        <p>This dialog was opened declaratively via a <code class="inline">data-synapse-open</code> attribute pointing to this element's id.</p>
        <p>It's a great fit for static content that doesn't change per-render — like a "What is this?" explainer, or a "Help" panel.</p>
    </div>
    <div class="syn-dialog-actions">
        <button type="button" class="syn-btn syn-btn--secondary" data-synapse-close>Close</button>
    </div>
</div>

<script>
// =================================================================
// Demo helpers for the showcase page
// =================================================================
function showToast(type) {
    const map = {
        success: { title: 'Saved successfully',     message: 'Your changes have been persisted to the database.', type: 'success' },
        error:   { title: 'Could not save',         message: 'A network error occurred. Please check your connection and try again.', type: 'error' },
        warning: { title: 'Session expiring soon',  message: 'You will be logged out in 5 minutes due to inactivity.', type: 'warning' },
        info:    { title: 'New message',            message: 'You have been assigned 3 new patient consultations for review.', type: 'info' },
        sticky:  { title: 'Sticky notification',    message: 'Click the X to dismiss — no auto-close on this one.', type: 'info', duration: 0 }
    };
    synapse.toast(map[type]);
}

function demoTokenDialog() {
    synapse.dialog.open({
        title: 'Token created successfully',
        subtitle: 'quiet-term-a4c4',
        body: '<p style="margin: 0 0 0.5rem 0; font-size: 0.8rem; color: var(--gray-500);">Account ID</p>',
        input: '85faa6e2ecdc507047dda60e2fadfaab',
        inputLabel: 'Your API Token',
        alert: 'This is the only time you will see this token. Make sure to copy it and store it securely. You will not be able to retrieve it later.',
        alertTitle: 'Important: Copy your token now',
        confirmText: 'Confirm',
        onConfirm: () => {
            synapse.toast('Token saved to clipboard', 'success');
        }
    });
}

function demoCustomDialog() {
    synapse.dialog.open({
        title: 'Custom HTML body',
        body: `
            <p>This dialog demonstrates that the <code>body</code> field accepts raw HTML, not just plain text.</p>
            <div style="background: var(--primary-50); border-radius: 8px; padding: 0.85rem; margin-top: 0.85rem;">
                <strong style="color: var(--primary-800);">Heads up:</strong>
                <span style="color: var(--gray-700);">You're responsible for escaping any user-supplied content you inject here.</span>
            </div>
        `,
        confirmText: 'Got it',
        cancelText: 'Cancel'
    });
}

function demoDangerDialog() {
    synapse.dialog.confirm({
        title: 'Permanently delete this record?',
        body: 'This action cannot be undone. The record and all its associated history will be permanently removed from the system.',
        confirmText: 'Delete permanently',
        onConfirm: () => {
            synapse.toast({ title: 'Record deleted', message: 'The record was permanently removed.', type: 'success' });
        }
    });
}

function demoButtonSpinner(btn) {
    const stop = synapse.spinner.button(btn);
    setTimeout(() => {
        stop();
        synapse.toast('Operation completed', 'success');
    }, 1800);
}

function demoPageLoader() {
    const hide = synapse.spinner.page('Loading records...');
    setTimeout(hide, 2000);
}

function demoSkeleton() {
    const target = document.getElementById('skeleton-demo-target');
    synapse.skeleton(target, () => {
        return new Promise(resolve => {
            setTimeout(() => {
                resolve(`
                    <div class="syn-skel-card" style="background: white; border-color: var(--primary-200);">
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.65rem;">
                            <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--primary-500), var(--primary-700)); display: grid; place-items: center; color: white; font-weight: 700;">JM</div>
                            <div>
                                <div style="font-weight: 600; color: var(--gray-900);">Juan Dela Cruz</div>
                                <div style="font-size: 0.75rem; color: var(--gray-500);">BS Computer Science · Year 2</div>
                            </div>
                        </div>
                        <div style="font-size: 0.85rem; color: var(--gray-700); line-height: 1.5;">
                            Real content loaded successfully. The skeleton was replaced with this card.
                        </div>
                    </div>
                `);
            }, 1400);
        });
    });
}
</script>

<?= $this->endSection() ?>
