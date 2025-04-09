<?php
require_once '../includes/functions.php';
include '../includes/header.php';

// Pagination setup
$per_page = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $per_page;

// Filters
$search = $_GET['search'] ?? '';
$filter_publisher = $_GET['publisher'] ?? '';
$filter_format = $_GET['format'] ?? '';

$formats = ['strip', 'tpb', 'hc', 'omnibus'];
$publishers = $pdo->query("SELECT id, name FROM publishers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$search_sql = [];
$params = [];

if ($search !== '') {
  $search_sql[] = "(LOWER(i.issue_label) LIKE :q OR LOWER(s.title) LIKE :q OR LOWER(p.name) LIKE :q)";
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

$where = $search_sql ? "WHERE " . implode(" AND ", $search_sql) : "";

// Count total results
$count_sql = "
  SELECT COUNT(*) FROM issues i
  JOIN series s ON i.series_id = s.id
  JOIN publishers p ON s.publisher_id = p.id
  JOIN trademarks t ON p.trademark_id = t.id
  $where
";
$count_stmt = $pdo->prepare($count_sql);
$count_stmt->execute($params);
$total_issues = $count_stmt->fetchColumn();
$total_pages = ceil($total_issues / $per_page);

// Fetch paginated comics
$sql = "
  SELECT 
    i.id AS issue_id,
    i.issue_label,
    i.cover_image_url,
    i.publication_date,
    s.title AS series_title,
    s.format,
    p.name AS publisher,
    t.name AS trademark
  FROM issues i
  JOIN series s ON i.series_id = s.id
  JOIN publishers p ON s.publisher_id = p.id
  JOIN trademarks t ON p.trademark_id = t.id
  $where
  ORDER BY t.name, p.name, s.title, i.issue_label
  LIMIT $per_page OFFSET $offset
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$comics = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="mb-4">🖼️ Cover Gallery</h1>
<p class="tagline fst-italic">Organize. Enjoy. Nest.</p>

<!-- Filter Bar -->
<form method="get" class="row g-2 align-items-end mb-4">
  <div class="col-md-3">
    <label class="form-label">Search</label>
    <input type="text" name="search" class="form-control" placeholder="Search..."
           value="<?= htmlspecialchars($search) ?>">
  </div>
  <div class="col-md-3">
    <label class="form-label">Publisher</label>
    <select name="publisher" class="form-select">
      <option value="">All</option>
      <?php foreach ($publishers as $p): ?>
        <option value="<?= $p['id'] ?>" <?= ($p['id'] == $filter_publisher) ? 'selected' : '' ?>>
          <?= htmlspecialchars($p['name']) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2">
    <label class="form-label">Format</label>
    <select name="format" class="form-select">
      <option value="">All</option>
      <?php foreach ($formats as $f): ?>
        <option value="<?= $f ?>" <?= ($f == $filter_format) ? 'selected' : '' ?>>
          <?= ucfirst($f) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="col-md-2 d-grid">
    <button type="submit" class="btn btn-outline-secondary">🔍 Filter</button>
  </div>
  <div class="col-md-2 d-grid">
    <a href="gallery.php" class="btn btn-outline-danger">❌ Clear</a>
  </div>
</form>

<div class="mb-3 d-flex justify-content-end">
  <a href="index.php" class="btn btn-outline-info btn-sm">📚 Switch to List View</a>
</div>

<!-- Gallery Grid -->
<div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-4">
  <?php foreach ($comics as $comic): ?>
    <div class="col">
      <div class="card h-100 shadow-sm">
        <?php if (!empty($comic['cover_image_url'])): ?>
          <img src="<?= cleanInput($comic['cover_image_url']) ?>" class="card-img-top" alt="Cover"
               style="height:300px; object-fit: cover;" loading="lazy">
        <?php else: ?>
          <div class="bg-secondary text-white d-flex align-items-center justify-content-center" style="height:300px;">
            No Image
          </div>
        <?php endif; ?>
        <div class="card-body d-flex flex-column justify-content-between">
          <h6 class="card-title mb-1"><?= cleanInput($comic['series_title']) ?></h6>
          <p class="card-text small text-muted mb-1">
            <?= cleanInput($comic['issue_label']) ?> · <?= ucfirst($comic['format']) ?><br>
            <?= cleanInput($comic['publisher']) ?> (<?= cleanInput($comic['trademark']) ?>)
          </p>
        </div>
        <div class="card-footer text-center">
          <a href="edit_issue.php?id=<?= $comic['issue_id'] ?>" class="btn btn-sm btn-outline-primary me-1">✏️</a>
          <a href="delete_issue.php?id=<?= $comic['issue_id'] ?>" class="btn btn-sm btn-outline-danger">🗑️</a>
        </div>
      </div>
    </div>
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
