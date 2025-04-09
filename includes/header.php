<!doctype html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>ComicNest</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
  <link href="../assets/css/style.css" rel="stylesheet">
</head>
<body class="<?= isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === 'true' ? 'bg-dark text-light' : '' ?>">

<nav class="navbar <?= isset($_COOKIE['dark_mode']) && $_COOKIE['dark_mode'] === 'true' ? 'navbar-dark bg-dark' : 'navbar-light bg-light' ?> mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="../public/index.php">🏡 ComicNest</a>
    <div class="d-flex gap-2">
      <a href="add_issue.php" class="btn btn-outline-primary btn-sm">+ Add Comic</a>
      <button class="btn btn-outline-secondary btn-sm" onclick="toggleDarkMode()">🌗 Toggle Mode</button>
    </div>
  </div>
</nav>

<div class="container">
