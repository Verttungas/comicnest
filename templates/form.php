<?php
require_once '../includes/functions.php';

$form_action = $form_action ?? "insert_issue.php";
$form_mode = $form_mode ?? "add";

$trademarks = $pdo->query("SELECT id, name FROM trademarks ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$publishers = $pdo->query("SELECT id, name FROM publishers ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$statuses = getStatusOptions();
?>

<h1 class="mb-4"><?= $form_mode === 'edit' ? '✏️ Edit Comic Issue' : '➕ Add New Comic Issue' ?></h1>

<form action="<?= $form_action ?>" method="POST">
  <?php if ($form_mode === 'edit'): ?>
    <input type="hidden" name="issue_id" value="<?= htmlspecialchars($_POST['issue_id']) ?>">
  <?php endif; ?>

  <div class="row g-4">
    <!-- LEFT COLUMN -->
    <div class="col-md-6">
      <!-- Trademark -->
      <div class="mb-3">
        <label class="form-label">Trademark</label>
        <select name="trademark_id" class="form-select">
          <option value="">-- Select --</option>
          <?php foreach ($trademarks as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($t['id'] == ($_POST['trademark_id'] ?? '')) ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($form_mode === 'add'): ?>
          <input type="text" name="new_trademark" class="form-control mt-2" placeholder="Or add new trademark">
        <?php endif; ?>
      </div>

      <!-- Publisher -->
      <div class="mb-3">
        <label class="form-label">Publisher</label>
        <select name="publisher_id" class="form-select">
          <option value="">-- Select --</option>
          <?php foreach ($publishers as $p): ?>
            <option value="<?= $p['id'] ?>" <?= ($p['id'] == ($_POST['publisher_id'] ?? '')) ? 'selected' : '' ?>>
              <?= htmlspecialchars($p['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <?php if ($form_mode === 'add'): ?>
          <input type="text" name="new_publisher" class="form-control mt-2" placeholder="Or add new publisher">
        <?php endif; ?>
      </div>

      <!-- Series Title -->
      <div class="mb-3">
        <label class="form-label">Series Title</label>
        <input type="text" name="series_title" class="form-control" value="<?= htmlspecialchars($_POST['series_title'] ?? '') ?>" required>
      </div>

      <!-- Issue Label -->
      <div class="mb-3">
        <label class="form-label">Volume / Issue (Optional)</label>
        <input type="text" name="issue_label" class="form-control"
               value="<?= htmlspecialchars($_POST['issue_label'] ?? '') ?>"
               placeholder="#001, Vol. 3, Omnibus, Special, etc.">
      </div>

      <!-- Format -->
      <div class="mb-3">
        <label class="form-label">Format</label>
        <select name="format" class="form-select">
          <?php foreach (['strip', 'tpb', 'hc', 'omnibus'] as $format): ?>
            <option value="<?= $format ?>" <?= ($format === ($_POST['format'] ?? '')) ? 'selected' : '' ?>>
              <?= ucfirst($format) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Publication Date -->
      <div class="mb-3">
        <label class="form-label">Publication Date</label>
        <input type="month" name="publication_date" class="form-control"
               value="<?= htmlspecialchars($_POST['publication_date'] ?? '') ?>">
      </div>

      <!-- Cover Image URL -->
      <div class="mb-3">
        <label class="form-label">Cover Image URL</label>
        <input type="url" name="cover_image_url" id="cover_image_url" class="form-control"
               value="<?= cleanInput($_POST['cover_image_url'] ?? '') ?>"
               placeholder="https://example.com/cover.jpg"
               oninput="previewCover(this.value)">
      </div>

      <!-- Live Preview -->
      <div id="cover_preview" class="text-center mb-3">
        <?php if (!empty($_POST['cover_image_url'])): ?>
          <img src="<?= cleanInput($_POST['cover_image_url']) ?>" alt="Cover Preview"
               style="max-height: 200px; border-radius: 0.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        <?php endif; ?>
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="col-md-6">
      <!-- Writers -->
      <div class="mb-3">
        <label class="form-label">Writer(s)</label>
        <?php foreach ($_POST['writers'] ?? [''] as $writer): ?>
          <input type="text" name="writers[]" class="form-control mb-2" value="<?= htmlspecialchars($writer) ?>" placeholder="Writer">
        <?php endforeach; ?>
        <input type="text" name="writers[]" class="form-control mb-2" placeholder="Add more">
      </div>

      <!-- Artists -->
      <div class="mb-3">
        <label class="form-label">Artist(s)</label>
        <?php foreach ($_POST['artists'] ?? [''] as $artist): ?>
          <input type="text" name="artists[]" class="form-control mb-2" value="<?= htmlspecialchars($artist) ?>" placeholder="Artist">
        <?php endforeach; ?>
        <input type="text" name="artists[]" class="form-control mb-2" placeholder="Add more">
      </div>

      <!-- Reading Status -->
      <div class="mb-3">
        <label class="form-label">Reading Status</label>
        <select name="reading_status" class="form-select">
          <?php foreach ($statuses as $key => $label): ?>
            <option value="<?= $key ?>" <?= ($key === ($_POST['reading_status'] ?? '')) ? 'selected' : '' ?>>
              <?= $label ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <!-- Rating -->
      <div class="mb-3">
        <label class="form-label">Rating</label>
        <select name="rating" class="form-select">
          <option value="">No rating</option>
          <?php for ($i = 1; $i <= 5; $i++): ?>
            <option value="<?= $i ?>" <?= ($i == ($_POST['rating'] ?? '')) ? 'selected' : '' ?>>
              <?= $i ?> ★
            </option>
          <?php endfor; ?>
        </select>
      </div>

      <!-- Review -->
      <div class="mb-3">
        <label class="form-label">Review</label>
        <textarea name="review_text" class="form-control" rows="4"><?= htmlspecialchars($_POST['review_text'] ?? '') ?></textarea>
      </div>
    </div>
  </div>

  <div class="text-end mt-4">
    <button type="submit" class="btn btn-<?= $form_mode === 'edit' ? 'primary' : 'success' ?>">
      <?= $form_mode === 'edit' ? '💾 Update' : '➕ Save Comic' ?>
    </button>
  </div>
</form>
