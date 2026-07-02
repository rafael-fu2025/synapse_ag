<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<!-- Search + Actions -->
<div class="card syn-search-card">
    <div class="card-body">
        <form method="GET" action="/inventory" role="search"
              data-synapse-search
              class="syn-search-bar"
              autocomplete="off">
            <div class="syn-search-row">
                <div class="syn-search-input-wrap">
                    <i class="fas fa-search syn-search-icon" aria-hidden="true"></i>
                    <label for="medicineSearch" class="sr-only">Search medicines</label>
                    <input type="search" id="medicineSearch" name="q" value="<?= esc($search ?? '') ?>"
                           placeholder="Search medicines by name, brand, or category…"
                           autocomplete="off" spellcheck="false"
                           data-synapse-search-trigger>
                </div>
            </div>
            <div class="syn-search-actions">
                <?php if (!empty($search)): ?>
                    <a href="/inventory" class="syn-search-chip" aria-label="Clear search">
                        <i class="fas fa-xmark"></i> Clear
                    </a>
                <?php endif; ?>
                <a href="/inventory/medicines/create" class="syn-btn syn-btn--success"
                   data-synapse-form-link data-dialog-title="Add Medicine" data-dialog-width="700">
                    <i class="fas fa-plus"></i> Add Medicine
                </a>
                <a href="/inventory/low-stock" class="syn-btn syn-btn--danger-ghost">
                    <i class="fas fa-triangle-exclamation"></i> Low Stock
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Medicine Table -->
<div class="card">
    <div class="card-body" style="padding: 0;">
        <?php if (empty($medicines)): ?>
            <div class="empty-state">
                <i class="fas fa-pills" aria-hidden="true" style="font-size: 1.5rem; color: var(--gray-400); display: block; margin-bottom: 0.5rem;"></i>
                <?= $search ? 'No medicines match your search.' : 'No medicines found.' ?>
                <?php if ($search): ?>
                    <a href="/inventory" class="syn-search-clear-link" style="display: inline-block; margin-top: 0.75rem; font-size: 0.8rem;">
                        <i class="fas fa-xmark"></i> Clear search
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <table class="table-mini">
                <thead>
                    <tr>
                        <th scope="col">Medicine</th>
                        <th scope="col">Category</th>
                        <th scope="col">Dosage</th>
                        <th scope="col" class="syn-cell-center">Stock</th>
                        <th scope="col" class="syn-cell-center">Threshold</th>
                        <th scope="col" class="syn-cell-center">Status</th>
                        <th scope="col" class="syn-cell-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($medicines as $m): ?>
                        <?php $stock = (int)($m['total_stock'] ?? 0); $threshold = (int)$m['reorder_threshold']; ?>
                        <tr>
                            <td>
                                <div style="font-weight: 600;"><?= search_highlight($m['generic_name'], $search) ?></div>
                                <?php if ($m['brand_name']): ?>
                                    <div class="syn-cell-muted" style="font-size: 0.75rem;"><?= search_highlight($m['brand_name'], $search) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="syn-cell-muted"><?= search_highlight($m['category'] ?? '—', $search) ?></td>
                            <td style="font-size: 0.8rem;">
                                <?= search_highlight(($m['dosage_form'] ?? '') . ' ' . ($m['dosage_strength'] ?? ''), $search) ?>
                            </td>
                            <td class="syn-cell-center <?= $stock <= $threshold ? 'syn-cell-low' : 'syn-cell-ok' ?>">
                                <?= $stock ?> <?= esc($m['unit']) ?>
                            </td>
                            <td class="syn-cell-center syn-cell-muted"><?= $threshold ?></td>
                            <td class="syn-cell-center">
                                <?php if ($stock === 0): ?>
                                    <span class="syn-badge syn-badge--stock-out">Out of Stock</span>
                                <?php elseif ($stock <= $threshold): ?>
                                    <span class="syn-badge syn-badge--stock-low">Low Stock</span>
                                <?php else: ?>
                                    <span class="syn-badge syn-badge--stock-ok">In Stock</span>
                                <?php endif; ?>

                                <?php if (isset($forecasts[$m['id']])): ?>
                                    <?php $f = $forecasts[$m['id']]; ?>
                                    <?php if ($stock > 0 && $f['predicted_daily_usage'] > 0): ?>
                                        <?php
                                            $daysToStockout = (strtotime($f['predicted_stockout_date']) - time()) / 86400;
                                            if ($daysToStockout <= 14):
                                        ?>
                                            <div class="syn-badge syn-badge--stock-out" style="margin-top: 0.25rem;" title="AI prediction: stockout in <?= round($daysToStockout) ?> days">
                                                <i class="fas fa-triangle-exclamation"></i> Stockout: <?= date('M d', strtotime($f['predicted_stockout_date'])) ?>
                                            </div>
                                        <?php elseif ($daysToStockout <= 60): ?>
                                            <div class="syn-badge syn-badge--stock-low" style="margin-top: 0.25rem;" title="AI prediction: stockout in <?= round($daysToStockout) ?> days">
                                                <i class="fas fa-clock"></i> Stockout: <?= date('M d', strtotime($f['predicted_stockout_date'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="syn-cell-center">
                                <a href="/inventory/medicines/<?= $m['id'] ?>" aria-label="View <?= esc($m['generic_name']) ?>" class="syn-cell-action syn-cell-action--view">View</a>
                                <a href="/inventory/medicines/<?= $m['id'] ?>/batch" data-synapse-form-link data-dialog-title="Receive Batch — <?= esc($m['generic_name']) ?>" data-dialog-width="650" aria-label="Receive new batch for <?= esc($m['generic_name']) ?>" class="syn-cell-action syn-cell-action--success">+ Batch</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php if (($totalPages ?? 1) > 1): ?>
        <?= pagination_links([
            'current'  => (int) $page,
            'total'    => (int) $totalPages,
            'perPage'  => (int) $perPage,
            'totalRec' => (int) $total,
        ], '/inventory', ['q' => $search ?? ''], [10, 25, 50, 100]) ?>
    <?php endif; ?>
</div>
<?= $this->endSection() ?>
