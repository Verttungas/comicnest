<?php
require_once '../includes/functions.php';
include '../includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid issue ID.");
}

$issue_id = (int) $_GET['id'];

// Fetch full issue data (volume removed, issue_label used)
$stmt = $pdo->prepare("
    SELECT 
        i.*, 
        s.title AS series_title, 
        s.format, 
        s.id AS series_id,
        p.id AS publisher_id,
        p.name AS publisher_name,
        t.id AS trademark_id,
        t.name AS trademark_name
    FROM issues i
    JOIN series s ON i.series_id = s.id
    JOIN publishers p ON s.publisher_id = p.id
    JOIN trademarks t ON p.trademark_id = t.id
    WHERE i.id = :id
");
$stmt->execute(['id' => $issue_id]);
$issue = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$issue) {
    die("Issue not found.");
}

// Fetch creators
$creator_stmt = $pdo->prepare("
    SELECT c.name, ic.role
    FROM issue_creators ic
    JOIN creators c ON ic.creator_id = c.id
    WHERE ic.issue_id = :id
");
$creator_stmt->execute(['id' => $issue_id]);
$creators = $creator_stmt->fetchAll(PDO::FETCH_ASSOC);

$writers = array_column(array_filter($creators, fn($c) => $c['role'] === 'writer'), 'name');
$artists = array_column(array_filter($creators, fn($c) => $c['role'] === 'artist'), 'name');

// Fetch reading status
$status_stmt = $pdo->prepare("SELECT status FROM reading_status WHERE issue_id = :id LIMIT 1");
$status_stmt->execute(['id' => $issue_id]);
$reading_status = $status_stmt->fetchColumn();

// Fetch review
$review_stmt = $pdo->prepare("SELECT rating, review_text FROM reviews WHERE issue_id = :id LIMIT 1");
$review_stmt->execute(['id' => $issue_id]);
$review = $review_stmt->fetch(PDO::FETCH_ASSOC);

// Pre-fill the form (volume removed, issue_label used)
$_POST = [
    'issue_id'         => $issue['id'],
    'issue_label'      => $issue['issue_label'],
    'publication_date' => substr($issue['publication_date'], 0, 7),
    'series_title'     => $issue['series_title'],
    'format'           => $issue['format'],
    'publisher_id'     => $issue['publisher_id'],
    'trademark_id'     => $issue['trademark_id'],
    'writers'          => $writers,
    'artists'          => $artists,
    'reading_status'   => $reading_status,
    'rating'           => $review['rating'] ?? '',
    'review_text'      => $review['review_text'] ?? '',
    'cover_image_url'  => $issue['cover_image_url'] ?? ''
];

$form_action = "update_issue.php";
$form_mode = "edit";

include '../templates/form.php';
include '../includes/footer.php';
