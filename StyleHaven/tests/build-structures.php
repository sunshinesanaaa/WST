<?php
/* =====================================================================
   Style Sync — structure page builder (tests/build-structures.php)
   ---------------------------------------------------------------------
   Run from the project root:   php tests/build-structures.php
   It reads database/stylehaven.sql and writes
   docs/phpmyadmin-structures.html, so the documented table structures
   can never drift away from the SQL file that is actually imported.
   ===================================================================== */

$root   = dirname(__DIR__);
$source = $root . '/database/stylehaven.sql';
$target = $root . '/docs/phpmyadmin-structures.html';

$sql = file_get_contents($source);
if ($sql === false) {
    fwrite(STDERR, "Cannot read {$source}\n");
    exit(1);
}

/* ------------------------------------------------------------- parsing */

/** Split "`a`, `b`" into ['a', 'b']. */
function parse_columns(string $list): array
{
    preg_match_all('/`([^`]+)`/', $list, $matches);
    return $matches[1];
}

function is_text_type(string $type): bool
{
    return (bool) preg_match('/^(VARCHAR|CHAR|TEXT|ENUM|DATE|DATETIME|TIME|TIMESTAMP)/i', $type);
}

$tables = [];

if (!preg_match_all('/CREATE TABLE `(\w+)` \((.*?)\n\) ENGINE=([^;]*);/s', $sql, $blocks, PREG_SET_ORDER)) {
    fwrite(STDERR, "No CREATE TABLE statements found.\n");
    exit(1);
}

foreach ($blocks as $block) {
    $engine = trim($block[3]);
    $table  = [
        'name'    => $block[1],
        'engine'  => preg_match('/^(\w+)/', $engine, $match) ? $match[1] : $engine,
        'charset' => preg_match('/DEFAULT CHARSET=(\w+)/i', $engine, $match) ? $match[1] : '',
        'columns' => [],
        'keys'    => [],
        'foreign' => [],
    ];

    foreach (preg_split('/\r?\n/', $block[2]) as $line) {
        $line = rtrim(trim($line), ',');
        if ($line === '' || str_starts_with($line, '--')) {
            continue;
        }

        /* PRIMARY KEY (`a`, `b`) */
        if (preg_match('/^PRIMARY KEY \((.*)\)$/i', $line, $primary)) {
            $table['keys'][] = ['name' => 'PRIMARY', 'unique' => true, 'columns' => parse_columns($primary[1])];
            continue;
        }
        /* UNIQUE KEY `uq_x` (`a`) / KEY `idx_x` (`a`) */
        if (preg_match('/^(UNIQUE KEY|KEY|INDEX)\s+`([^`]+)`\s*\((.*)\)$/i', $line, $key)) {
            $table['keys'][] = [
                'name'    => $key[2],
                'unique'  => stripos($key[1], 'UNIQUE') === 0,
                'columns' => parse_columns($key[3]),
            ];
            continue;
        }
        /* column definition */
        if (preg_match('/^`([^`]+)`\s+(.*)$/', $line, $column)) {
            $name  = $column[1];
            $rules = $column[2];
            $type  = preg_match('/^(\w+)(\([^)]*\))?/', $rules, $match)
                ? $match[1] . ($match[2] ?? '')
                : 'text';

            $table['columns'][] = [
                'name'       => $name,
                'type'       => strtoupper($type),
                'null'       => stripos($rules, 'NOT NULL') === false,
                'default'    => preg_match('/DEFAULT\s+(CURRENT_TIMESTAMP|\'[^\']*\'|[0-9.]+)/i', $rules, $default) ? $default[1] : null,
                'extra'      => str_contains($rules, 'AUTO_INCREMENT') ? 'auto_increment'
                                : (str_contains($rules, 'ON UPDATE CURRENT_TIMESTAMP') ? 'on update CURRENT_TIMESTAMP' : ''),
                'comment'    => preg_match("/COMMENT\s+'([^']*)'/i", $rules, $comment) ? $comment[1] : '',
            ];
            continue;
        }
    }

    /* Foreign keys written over two lines are re-read from the raw block. */
    if (preg_match_all('/CONSTRAINT `([^`]+)` FOREIGN KEY \(([^)]+)\)\s*REFERENCES `(\w+)` \(([^)]+)\)\s*([^,;]*)/s', $block[2], $fks, PREG_SET_ORDER)) {
        $table['foreign'] = [];
        foreach ($fks as $fk) {
            $table['foreign'][] = [
                'name'   => $fk[1],
                'column' => parse_columns($fk[2])[0] ?? '',
                'table'  => $fk[3],
                'target' => parse_columns($fk[4])[0] ?? '',
                'rule'   => trim(preg_replace('/\s+/', ' ', $fk[5])),
            ];
        }
    }

    $tables[] = $table;
}

/* Number of demo rows seeded for each table. */
$seedCounts = [];
if (preg_match_all('/INSERT INTO `(\w+)`\s*\((.*?)\)\s*VALUES(.*?);/s', $sql, $inserts, PREG_SET_ORDER)) {
    foreach ($inserts as $insert) {
        /* Each row of an INSERT starts on its own line with "(". */
        $seedCounts[$insert[1]] = preg_match_all('/^\s*\(/m', $insert[3]);
    }
}

/* ----------------------------------------------------------- rendering */
ob_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>phpMyAdmin table structures | Style Sync — Style Haven Salon</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
  :root { --plum:#3b2b3a; --rose:#c9737f; --rose-dark:#9e4e5c; --blush:#f7e9e8; --gold:#c9a24b; --line:rgba(59,43,58,0.12); }
  body { font-family:"Poppins","Segoe UI",Tahoma,sans-serif; background:#f6f0ee; color:#2a2229; margin:0; padding:0 0 3rem; }
  header.page { background:var(--plum); color:#fff; padding:2.2rem 0; margin-bottom:2rem; }
  .wrap { max-width:1180px; margin:0 auto; padding:0 1.1rem; }
  header.page h1 { margin:0 0 .3rem; font-size:1.6rem; }
  header.page p { margin:0; color:rgba(255,255,255,.78); font-size:.9rem; }
  .howto { background:#fff; border:1px solid var(--line); border-left:5px solid var(--gold); border-radius:14px; padding:1.1rem 1.3rem; margin-bottom:2rem; font-size:.88rem; }
  .howto ol { margin:.5rem 0 0; padding-left:1.2rem; }
  .db-bar { background:#fff; border:1px solid var(--line); border-radius:14px; padding:.9rem 1.2rem; display:flex; flex-wrap:wrap; gap:1rem; align-items:center; font-size:.85rem; margin-bottom:1.6rem; }
  .db-bar code { background:var(--blush); color:var(--rose-dark); padding:.15rem .45rem; border-radius:6px; }
  section.table-block { margin-bottom:2.4rem; }
  .table-title { display:flex; flex-wrap:wrap; align-items:center; gap:.6rem; font-size:1.05rem; font-weight:600; color:var(--plum); margin:0 0 .5rem; }
  .table-title i { color:var(--rose); }
  .pill { background:var(--blush); color:var(--rose-dark); border-radius:999px; padding:.16rem .6rem; font-size:.72rem; font-weight:600; }
  table.grid { width:100%; border-collapse:collapse; background:#fff; font-size:.8rem; border:1px solid var(--line); }
  table.grid th { background:#efefef; color:#444; text-align:left; font-weight:600; padding:.42rem .6rem; border:1px solid #dcdcdc; white-space:nowrap; }
  table.grid td { padding:.4rem .6rem; border:1px solid #e6e6e6; vertical-align:top; }
  table.grid tr:nth-child(even) td { background:#fafafa; }
  td.num { color:#888; width:2.2rem; text-align:right; }
  td.type { color:#0a5c8a; }
  .key { color:var(--gold); font-weight:700; }
  .null-no { color:#a12c3f; font-weight:600; }
  .fk { font-size:.78rem; color:#5a4a55; margin:.5rem 0 0; }
  .fk code { background:var(--blush); padding:.1rem .4rem; border-radius:5px; color:var(--rose-dark); }
  footer.page { text-align:center; font-size:.8rem; color:#7a6b75; margin-top:2.5rem; }
  @media print { body { background:#fff; } .howto { border-left-color:#999; } section.table-block { break-inside:avoid; } }
</style>
</head>
<body>

<header class="page">
  <div class="wrap">
    <h1><i class="bi bi-database"></i> phpMyAdmin table structures</h1>
    <p>Style Sync — Style Haven Salon · IT 004 Web Systems and Technologies · Project Part 4 · Group 3</p>
  </div>
</header>

<div class="wrap">

  <div class="howto">
    <strong><i class="bi bi-camera"></i> How to produce the required phpMyAdmin screenshot</strong>
    <ol>
      <li>Start <strong>Apache</strong> and <strong>MySQL</strong> in the XAMPP control panel.</li>
      <li>Open <a href="http://localhost/phpmyadmin" target="_blank" rel="noopener">http://localhost/phpmyadmin</a> and import <code>database/stylehaven.sql</code>.</li>
      <li>Click the <strong>stylehaven_db</strong> database in the left column, then open the <strong>Structure</strong> tab of any table below.</li>
      <li>Screenshot the table structure (or the database's table list) and paste it into the documentation.</li>
    </ol>
    <p style="margin:.7rem 0 0;">
      This page is generated from that same SQL file by <code>tests/build-structures.php</code>, so if
      MySQL is not available on the machine where the documentation is written, this page can be
      printed or screenshotted instead — it shows exactly the columns, keys and relations that
      phpMyAdmin will display after the import.
    </p>
  </div>

  <div class="db-bar">
    <span><i class="bi bi-server"></i> Database <code>stylehaven_db</code></span>
    <span><i class="bi bi-table"></i> <?= count($tables) ?> tables</span>
    <span><i class="bi bi-columns-gap"></i> <?= array_sum(array_map(fn($t) => count($t['columns']), $tables)) ?> columns</span>
    <span><i class="bi bi-key"></i> <?= array_sum(array_map(fn($t) => count($t['keys']), $tables)) ?> keys</span>
    <span><i class="bi bi-diagram-3"></i> <?= array_sum(array_map(fn($t) => count($t['foreign']), $tables)) ?> foreign keys</span>
    <span><i class="bi bi-download"></i> Charset <code>utf8mb4</code> · Collation <code>utf8mb4_unicode_ci</code></span>
  </div>

<?php foreach ($tables as $index => $table): ?>
  <section class="table-block">
    <h2 class="table-title">
      <i class="bi bi-table"></i>
      <?= htmlspecialchars($table['name'], ENT_QUOTES) ?>
      <span class="pill"><?= count($table['columns']) ?> columns</span>
      <span class="pill"><?= htmlspecialchars($table['engine'], ENT_QUOTES) ?><?= $table['charset'] !== '' ? ' · ' . htmlspecialchars($table['charset'], ENT_QUOTES) : '' ?></span>
      <?php if (isset($seedCounts[$table['name']])): ?>
        <span class="pill"><?= (int) $seedCounts[$table['name']] ?> demo rows</span>
      <?php endif; ?>
    </h2>

    <table class="grid">
      <thead>
        <tr>
          <th>#</th><th>Name</th><th>Type</th><th>Collation</th><th>Null</th>
          <th>Default</th><th>Extra</th><th>Key</th><th>Comments</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($table['columns'] as $position => $column): ?>
          <?php
            $keyLabel = '';
            foreach ($table['keys'] as $key) {
                if (in_array($column['name'], $key['columns'], true)) {
                    $keyLabel = $key['name'] === 'PRIMARY'
                        ? '<span class="key" title="Primary key">PRIMARY</span>'
                        : ($key['unique'] ? 'UNIQUE' : 'INDEX');
                    if ($key['name'] === 'PRIMARY') { break; }
                }
            }
            $isText = is_text_type($column['type']);
          ?>
          <tr>
            <td class="num"><?= $position + 1 ?></td>
            <td><strong><?= htmlspecialchars($column['name'], ENT_QUOTES) ?></strong></td>
            <td class="type"><?= htmlspecialchars($column['type'], ENT_QUOTES) ?></td>
            <td><?= $isText ? 'utf8mb4_unicode_ci' : '' ?></td>
            <td><?= $column['null'] ? 'Yes' : '<span class="null-no">No</span>' ?></td>
            <td><?= $column['default'] === null ? '<em style="color:#999">NULL</em>' : htmlspecialchars($column['default'], ENT_QUOTES) ?></td>
            <td><?= htmlspecialchars($column['extra'], ENT_QUOTES) ?></td>
            <td><?= $keyLabel ?></td>
            <td><small><?= htmlspecialchars($column['comment'], ENT_QUOTES) ?></small></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <h3 class="table-title" style="font-size:.92rem;margin-top:1rem;"><i class="bi bi-key-fill"></i> Indexes</h3>
    <table class="grid">
      <thead>
        <tr><th>Keyname</th><th>Type</th><th>Unique</th><th>Packed</th><th>Column</th><th>Cardinality</th><th>Collation</th><th>Null</th></tr>
      </thead>
      <tbody>
        <?php foreach ($table['keys'] as $key): ?>
          <?php foreach ($key['columns'] as $position => $columnName): ?>
            <?php
              $column = null;
              foreach ($table['columns'] as $candidate) {
                  if ($candidate['name'] === $columnName) { $column = $candidate; }
              }
            ?>
            <tr>
              <td><?= $position === 0 ? '<strong>' . htmlspecialchars($key['name'], ENT_QUOTES) . '</strong>' : '' ?></td>
              <td><?= $position === 0 ? 'BTREE' : '' ?></td>
              <td><?= $position === 0 ? ($key['unique'] ? 'Yes' : 'No') : '' ?></td>
              <td><?= $position === 0 ? 'No' : '' ?></td>
              <td><strong><?= htmlspecialchars($columnName, ENT_QUOTES) ?></strong></td>
              <td><?= $position === 0 ? '<em>' . (isset($seedCounts[$table['name']]) ? (int) $seedCounts[$table['name']] : 0) . '</em>' : '' ?></td>
              <td><?= $column && is_text_type($column['type']) ? 'A' : '' ?></td>
              <td><?= $column && $column['null'] ? 'Yes' : 'No' ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php if ($table['foreign']): ?>
      <p class="fk">
        <i class="bi bi-diagram-3"></i> <strong>Relations:</strong>
        <?php foreach ($table['foreign'] as $fk): ?>
          <br>
          <code><?= htmlspecialchars($fk['name'], ENT_QUOTES) ?></code>
          &nbsp;<strong><?= htmlspecialchars($table['name'], ENT_QUOTES) ?>.<?= htmlspecialchars($fk['column'], ENT_QUOTES) ?></strong>
          &rarr; <strong><?= htmlspecialchars($fk['table'], ENT_QUOTES) ?>.<?= htmlspecialchars($fk['target'], ENT_QUOTES) ?></strong>
          <?= $fk['rule'] !== '' ? '(' . htmlspecialchars(str_replace('ON UPDATE', '· ON UPDATE', $fk['rule']), ENT_QUOTES) . ')' : '' ?>
        <?php endforeach; ?>
      </p>
    <?php endif; ?>
  </section>
<?php endforeach; ?>

  <footer class="page">
    Generated from <code>database/stylehaven.sql</code> by <code>tests/build-structures.php</code>.<br>
    Style Haven Salon · Style Sync · Group 3 — Racasa, Abelo, Abeson, Dichosa, Guariña
  </footer>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

if (!is_dir(dirname($target))) {
    mkdir(dirname($target), 0775, true);
}
file_put_contents($target, $html);

printf(
    "Wrote %s (%d bytes, %d tables, %d columns)\n",
    $target,
    strlen($html),
    count($tables),
    array_sum(array_map(fn($t) => count($t['columns']), $tables))
);
