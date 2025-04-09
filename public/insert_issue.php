<?php
require_once '../includes/functions.php';

try {
    $pdo->beginTransaction();

    // Get or create trademark
    $trademark_id = $_POST['trademark_id'] ?: insertIfNotExists($pdo, 'trademarks', 'name', $_POST['new_trademark']);

    // Get or create publisher
    $publisher_id = $_POST['publisher_id'] ?: insertIfNotExists($pdo, 'publishers', 'name', $_POST['new_publisher']);

    // Link publisher to trademark if new
    if (!$_POST['publisher_id'] && $publisher_id && $trademark_id) {
        $stmt = $pdo->prepare("UPDATE publishers SET trademark_id = :trademark_id WHERE id = :publisher_id");
        $stmt->execute([
            'trademark_id' => $trademark_id,
            'publisher_id' => $publisher_id
        ]);
    }

    // Check or insert series (volume removed)
    $stmt = $pdo->prepare("SELECT id FROM series WHERE title = :title AND format = :format AND publisher_id = :publisher_id LIMIT 1");
    $stmt->execute([
        'title' => $_POST['series_title'],
        'format' => $_POST['format'],
        'publisher_id' => $publisher_id
    ]);
    $series_id = $stmt->fetchColumn();

    if (!$series_id) {
        $stmt = $pdo->prepare("INSERT INTO series (title, format, publisher_id) VALUES (:title, :format, :publisher_id) RETURNING id");
        $stmt->execute([
            'title' => $_POST['series_title'],
            'format' => $_POST['format'],
            'publisher_id' => $publisher_id
        ]);
        $series_id = $stmt->fetchColumn();
    }

    // ✅ Insert issue with issue_label instead of issue_number
    $stmt = $pdo->prepare("
        INSERT INTO issues (series_id, issue_label, publication_date, cover_image_url)
        VALUES (:series_id, :issue_label, :publication_date, :cover_image_url)
        RETURNING id
    ");
    $stmt->execute([
        'series_id' => $series_id,
        'issue_label' => $_POST['issue_label'] ?? null,
        'publication_date' => $_POST['publication_date'] ? $_POST['publication_date'] . "-01" : null,
        'cover_image_url' => $_POST['cover_image_url'] ?? null
    ]);
    $issue_id = $stmt->fetchColumn();

    // Add creators
    foreach ($_POST['writers'] as $writer) {
        if ($writer) {
            $cid = insertIfNotExists($pdo, 'creators', 'name', trim($writer));
            $pdo->prepare("INSERT INTO issue_creators (issue_id, creator_id, role) VALUES (:i, :c, 'writer')")
                ->execute(['i' => $issue_id, 'c' => $cid]);
        }
    }

    foreach ($_POST['artists'] as $artist) {
        if ($artist) {
            $cid = insertIfNotExists($pdo, 'creators', 'name', trim($artist));
            $pdo->prepare("INSERT INTO issue_creators (issue_id, creator_id, role) VALUES (:i, :c, 'artist')")
                ->execute(['i' => $issue_id, 'c' => $cid]);
        }
    }

    // Add reading status
    if (!empty($_POST['reading_status'])) {
        $stmt = $pdo->prepare("INSERT INTO reading_status (issue_id, status) VALUES (:issue_id, :status)");
        $stmt->execute(['issue_id' => $issue_id, 'status' => $_POST['reading_status']]);
    }

    // Add review
    if (!empty($_POST['rating'])) {
        $stmt = $pdo->prepare("INSERT INTO reviews (issue_id, rating, review_text) VALUES (:issue_id, :rating, :text)");
        $stmt->execute([
            'issue_id' => $issue_id,
            'rating' => $_POST['rating'],
            'text' => $_POST['review_text'] ?? null
        ]);
    }

    $pdo->commit();
    header("Location: index.php?success=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Insert failed: " . $e->getMessage());
}
