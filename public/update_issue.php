<?php
require_once '../includes/functions.php';

try {
    $pdo->beginTransaction();

    $issue_id = $_POST['issue_id'];

    // ✅ Update issue_label (instead of issue_number)
    $stmt = $pdo->prepare("
        UPDATE issues SET 
            issue_label = :issue_label,
            publication_date = :pub_date,
            cover_image_url = :cover
        WHERE id = :id
    ");
    $stmt->execute([
        'issue_label' => $_POST['issue_label'] ?? null,
        'pub_date' => $_POST['publication_date'] ? $_POST['publication_date'] . "-01" : null,
        'cover' => $_POST['cover_image_url'] ?? null,
        'id' => $issue_id
    ]);

    // Clear and re-insert creators
    $pdo->prepare("DELETE FROM issue_creators WHERE issue_id = :id")->execute(['id' => $issue_id]);

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

    // Update reading status
    $pdo->prepare("DELETE FROM reading_status WHERE issue_id = :id")->execute(['id' => $issue_id]);
    if (!empty($_POST['reading_status'])) {
        $stmt = $pdo->prepare("INSERT INTO reading_status (issue_id, status) VALUES (:i, :status)");
        $stmt->execute([
            'i' => $issue_id,
            'status' => $_POST['reading_status']
        ]);
    }

    // Update review
    $pdo->prepare("DELETE FROM reviews WHERE issue_id = :id")->execute(['id' => $issue_id]);
    if (!empty($_POST['rating'])) {
        $stmt = $pdo->prepare("INSERT INTO reviews (issue_id, rating, review_text) VALUES (:i, :rating, :text)");
        $stmt->execute([
            'i' => $issue_id,
            'rating' => $_POST['rating'],
            'text' => $_POST['review_text'] ?? null
        ]);
    }

    $pdo->commit();
    header("Location: index.php?updated=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Update failed: " . $e->getMessage());
}
