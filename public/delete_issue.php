<?php
require_once '../includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid issue ID.");
}

$issue_id = (int) $_GET['id'];

// Confirm first if not POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    include '../includes/header.php';
    echo "<h2>⚠️ Confirm Delete</h2>
    <p>Are you sure you want to delete this issue?</p>
    <form method='POST'>
        <input type='hidden' name='confirm' value='1'>
        <button type='submit' class='btn btn-danger'>Yes, Delete</button>
        <a href='index.php' class='btn btn-secondary'>Cancel</a>
    </form>";
    include '../includes/footer.php';
    exit;
}

// Proceed with delete
try {
    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM reviews WHERE issue_id = :id")->execute(['id' => $issue_id]);
    $pdo->prepare("DELETE FROM reading_status WHERE issue_id = :id")->execute(['id' => $issue_id]);
    $pdo->prepare("DELETE FROM issue_creators WHERE issue_id = :id")->execute(['id' => $issue_id]);
    $pdo->prepare("DELETE FROM issues WHERE id = :id")->execute(['id' => $issue_id]);

    $pdo->commit();
    header("Location: index.php?deleted=1");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    die("Delete failed: " . $e->getMessage());
}
