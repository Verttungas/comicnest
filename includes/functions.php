<?php
require_once __DIR__ . '/../config/db.php';

// Fetch all series
function getAllSeries(PDO $pdo) {
    $sql = "SELECT s.id, s.title, s.volume, s.format, p.name AS publisher
            FROM series s
            JOIN publishers p ON s.publisher_id = p.id
            ORDER BY s.title";
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch all creators
function getAllCreators(PDO $pdo) {
    return $pdo->query("SELECT id, name FROM creators ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
}

// Reading status enum
function getStatusOptions() {
    return [
        'unread' => 'Unread',
        'reading' => 'Reading',
        'read' => 'Read'
    ];
}

// Sanitize output
function cleanInput($value) {
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// Show flash message
function flashMessage() {
    $messages = [
        'success' => '✅ Comic added successfully!',
        'updated' => '✅ Comic updated successfully!',
        'deleted' => '🗑️ Comic deleted.',
    ];

    foreach ($messages as $key => $text) {
        if (isset($_GET[$key])) {
            echo "<div class='alert alert-success'>$text</div>";
        }
    }
}

function insertIfNotExists($pdo, $table, $column, $value) {
    if (!$value) return null;
    $stmt = $pdo->prepare("SELECT id FROM $table WHERE $column = :value LIMIT 1");
    $stmt->execute(['value' => $value]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row['id'];
    } else {
        $stmt = $pdo->prepare("INSERT INTO $table ($column) VALUES (:value) RETURNING id");
        $stmt->execute(['value' => $value]);
        return $stmt->fetchColumn();
    }
}
