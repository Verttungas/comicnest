<?php
require_once '../includes/functions.php';

flashMessage();

// Pagination
$per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Filters
$search = $_GET['search'] ?? '';
$filter_publisher = $_GET['publisher'] ?? '';
$filter_format = $_GET['format'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Get supplementary data
$all_publishers = $pdo->query("SELECT id, name FROM publishers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$statuses = getStatusOptions();
$formats = ['strip', 'tpb', 'hc', 'omnibus'];

// Build query filters
$search_sql = [];
$params = [];

if ($search !== '') {
    $search_sql[] = "(LOWER(i.issue_label) LIKE :q OR LOWER(s.title) LIKE :q OR LOWER(p.name) LIKE :q OR LOWER(t.name) LIKE :q)";
    $params['q'] = '%' . strtolower($search) . '%';
}
if ($filter_publisher) {
    $search_sql[] = "p.id = :publisher_id";
    $params['publisher_id'] = $filter_publisher;
}
if ($filter_format) {
    $search_sql[] = "s.format = :format";
    $params['format'] = $filter_format;
}
if ($filter_status) {
    $search_sql[] = "rs.status = :status";
    $params['status'] = $filter_status;
}

$where_clause = $search_sql ? 'WHERE ' . implode(' AND ', $search_sql) : '';

// Count total issues for pagination
$count_sql = "
    SELECT COUNT(*) FROM issues i
    JOIN series s ON i.series_id = s.id
    JOIN publishers p ON s.publisher_id = p.id
    JOIN trademarks t ON p.trademark_id = t.id
    LEFT JOIN reading_status rs ON rs.issue_id = i.id
    $where_clause
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_issues = $count_stmt->fetchColumn();
$total_pages = ceil($total_issues / $per_page);

// Fetch paginated results
$sql = "
    SELECT 
        i.id AS issue_id,
        t.name AS trademark,
        p.name AS publisher,
        s.title AS series_title,
        s.format,
        i.issue_label,
        i.publication_date,
        i.cover_image_url,
        rs.status,
        r.rating
    FROM issues i
    JOIN series s ON i.series_id = s.id
    JOIN publishers p ON s.publisher_id = p.id
    JOIN trademarks t ON p.trademark_id = t.id
    LEFT JOIN reading_status rs ON rs.issue_id = i.id
    LEFT JOIN reviews r ON r.issue_id = i.id
    $where_clause
    ORDER BY t.name, p.name, s.title, i.issue_label
    LIMIT $per_page OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$library = $stmt->fetchAll(PDO::FETCH_ASSOC);

// CSV Export: If export is requested, output CSV and exit before any HTML
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="comicnest_export.csv"');
    $out = fopen('php://output', 'w');
    if (!empty($library)) {
        // Output header row with column names
        fputcsv($out, array_keys($library[0]));
        foreach ($library as $row) {
            fputcsv($out, $row);
        }
    }
    fclose($out);
    exit;
}

// Group library entries by trademark > publisher > series for display
$grouped = [];
foreach ($library as $row) {
    $t = $row['trademark'];
    $p = $row['publisher'];
    $s = $row['series_title'] . ' [' . ucfirst($row['format']) . ']';
    $grouped[$t][$p][$s][] = $row;
}

// Include header AFTER the CSV export logic so that no HTML output interferes with CSV
include '../includes/header.php';
?>
<h1 class="mb-4">📚 Comic Book Library</h1>
<p class="tagline fst-italic">Organize. Enjoy. Nest.</p>

<!-- Filters -->
<form method="get" class="mb-4">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label">Search</label>
      <input type="text" name="search" class="form-control" value="<?= cleanInput($search) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label">Publisher</label>
      <select name="publisher" class="form-select">
        <option value="">All</option>
        <?php foreach ($all_publishers as $pub): ?>
          <option value="<?= $pub['id'] ?>" <?= $filter_publisher == $pub['id'] ? 'selected' : '' ?>>
            <?= cleanInput($pub['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Format</label>
      <select name="format" class="form-select">
        <option value="">All</option>
        <?php foreach ($formats as $f): ?>
          <option value="<?= $f ?>" <?= $filter_format == $f ? 'selected' : '' ?>>
            <?= ucfirst($f) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label">Status</label>
      <select name="status" class="form-select">
        <option value="">All</option>
        <?php foreach ($statuses as $key => $label): ?>
          <option value="<?= $key ?>" <?= $filter_status == $key ? 'selected' : '' ?>>
            <?= $label ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-1 d-grid">
      <button type="submit" class="btn btn-outline-secondary">🔍 Filter</button>
    </div>
    <div class="col-md-2 d-grid">
      <a href="index.php" class="btn btn-outline-danger">❌ Clear</a>
    </div>
  </div>
</form>

<!-- Export & View Toggle -->
<div class="mb-4 d-flex justify-content-between">
  <a href="index.php?export=csv" class="btn btn-outline-success btn-sm">⬇️ Export CSV</a>
  <a href="gallery.php" class="btn btn-outline-info btn-sm">🖼️ Switch to Gallery View</a>
</div>

<!-- Accordion Display -->
<div class="accordion" id="libraryAccordion">
<?php $accIndex = 0; ?>
<?php foreach ($grouped as $trademark => $publishers): ?>
  <div class="accordion-item">
    <h2 class="accordion-header" id="heading<?= $accIndex ?>">
      <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $accIndex ?>">
        <?= cleanInput($trademark) ?>
      </button>
    </h2>
    <div id="collapse<?= $accIndex ?>" class="accordion-collapse collapse show">
      <div class="accordion-body">
        <?php foreach ($publishers as $publisher => $seriesList): ?>
          <h5 class="mt-3 text-primary"><?= cleanInput($publisher) ?></h5>
          <?php foreach ($seriesList as $series => $issues): ?>
            <div class="card mb-3">
              <div class="card-header fw-bold"><?= cleanInput($series) ?></div>
              <ul class="list-group list-group-flush">
                <?php foreach ($issues as $issue): ?>
                  <li class="list-group-item d-flex justify-content-between align-items-center">
                    <div>
                      <?php if ($issue['cover_image_url']): ?>
                        <img src="<?= cleanInput($issue['cover_image_url']) ?>" alt="Cover" style="height:50px;margin-right:10px;">
                      <?php endif; ?>
                      <?php
                      $label = cleanInput($issue['issue_label']);
                      $is_number = preg_match('/^\d+$/', ltrim($label, '#'));
                      ?>
                      <strong><?= $is_number ? '#' . $label : $label ?></strong>
                      <?php if ($issue['publication_date']): ?>
                        <small class="text-muted">(<?= date('M Y', strtotime($issue['publication_date'])) ?>)</small>
                      <?php endif; ?>
                      <?php if ($issue['status']): ?>
                        <span class="badge <?= 'badge ' . strtolower($issue['status']) ?>">
                          <?= ucfirst($issue['status']) ?>
                        </span>
                      <?php endif; ?>
                      <?php if ($issue['rating']): ?>
                        <span class="ms-2 text-warning"><?= str_repeat("★", $issue['rating']) ?></span>
                      <?php endif; ?>
                    </div>
                    <div>
                      <a href="edit_issue.php?id=<?= $issue['issue_id'] ?>" class="btn btn-sm btn-outline-primary">✏️ Edit</a>
                      <a href="delete_issue.php?id=<?= $issue['issue_id'] ?>" class="btn btn-sm btn-outline-danger">🗑 Delete</a>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            </div>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php $accIndex++; ?>
<?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($total_pages > 1): ?>
  <nav class="mt-4">
    <ul class="pagination justify-content-center">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
          <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>">
            <?= $i ?>
          </a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
