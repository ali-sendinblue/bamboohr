<?php
$tools = [
    [
        'href' => 'leaves',
        'title' => 'BambooHR team leaves',
        'text' => 'Pick a month and a manager, see everyone’s leave, export it as CSV.',
        'tag' => 'HR',
    ],
    [
        'href' => 'oncall',
        'title' => 'On-call payroll',
        'text' => 'On-call days and intervention hours per person, split into weekdays and weekends/holidays.',
        'tag' => 'Payroll',
    ],
];
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Internal tools</title>
<style>
  :root { --bg:#f6f7f9; --card:#fff; --text:#16202a; --muted:#6a7683; --line:#e4e8ec; --brand:#0b996e; }
  @media (prefers-color-scheme: dark) {
    :root { --bg:#0f1418; --card:#171e24; --text:#e8edf1; --muted:#8a96a2; --line:#27313a; --brand:#2cc597; }
  }
  * { box-sizing:border-box }
  body { margin:0; background:var(--bg); color:var(--text); font:16px/1.5 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif }
  main { max-width:880px; margin:0 auto; padding:64px 16px }
  h1 { font-size:28px; margin:0 0 4px }
  p.sub { color:var(--muted); margin:0 0 32px }
  .grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px }
  a.card { display:block; background:var(--card); border:1px solid var(--line); border-radius:14px; padding:20px; color:inherit; text-decoration:none; transition:transform .15s, box-shadow .15s, border-color .15s }
  a.card:hover { transform:translateY(-2px); border-color:var(--brand); box-shadow:0 8px 24px rgba(0,0,0,.08) }
  .tag { display:inline-block; font-size:12px; font-weight:600; color:var(--brand); background:color-mix(in srgb,var(--brand) 12%,transparent); padding:2px 10px; border-radius:999px; margin-bottom:10px }
  .card h2 { font-size:18px; margin:0 0 6px }
  .card p { margin:0; color:var(--muted); font-size:14px }
  p.source { color:var(--muted); font-size:14px; margin:32px 0 0 }
  p.source a { color:var(--brand) }
</style>
</head>
<body>
<main>
  <h1>Internal tools</h1>
  <p class="sub">Small utilities that run in your browser.</p>
  <div class="grid">
    <?php foreach ($tools as $tool): ?>
      <a class="card" href="<?= htmlspecialchars($tool['href']) ?>">
        <span class="tag"><?= htmlspecialchars($tool['tag']) ?></span>
        <h2><?= htmlspecialchars($tool['title']) ?></h2>
        <p><?= htmlspecialchars($tool['text']) ?></p>
      </a>
    <?php endforeach; ?>
  </div>
  <p class="source">The source of this project can be accessed at <a href="https://github.com/ali-sendinblue/bamboohr">github.com/ali-sendinblue/bamboohr</a>.</p>
</main>
</body>
</html>
