<?php
require_once 'includes/db.php';

// Fetch manager tasks from Installs database
$tasks = [];
if ($db_installs) {
    try {
        $stmt = $db_installs->query("SELECT * FROM manager_tasks ORDER BY due_date ASC");
        $tasks = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// Fetch basic stats from Inventory database
$active_jobs_count = 0;
if ($db_crm) {
    try {
        $stmt = $db_crm->query("SELECT COUNT(*) FROM projects WHERE status = 'In Progress'");
        $active_jobs_count = $stmt->fetchColumn();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Precision Fleet Pro - Command Terminal</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                "outline-variant": "#d3c5ac",
                "surface-container-highest": "#ece1d1",
                "surface-container-high": "#f2e7d7",
                "secondary-container": "#dae2fd",
                "on-primary": "#ffffff",
                "inverse-on-surface": "#faefdf",
                "inverse-surface": "#353025",
                "text-muted": "#64748B",
                "primary-container": "#e7b008",
                "surface": "#fff8f2",
                "secondary": "#565e74",
                "border-slate": "#E2E8F0",
                "surface-variant": "#ece1d1",
                "on-surface": "#201b11",
                "background": "#fff8f2",
                "primary-fixed": "#ffdf9c",
                "on-surface-variant": "#4f4633",
                "surface-container": "#f7ecdc",
                "primary": "#e7b008",
                "text-main": "#0F172A",
                "on-error-container": "#93000a",
                "surface-container-low": "#fdf2e2",
                "on-error": "#ffffff",
                "surface-white": "#FFFFFF",
                "background-slate": "#F8FAFC",
                "error-container": "#ffdad6",
                "surface-container-lowest": "#ffffff",
                "tertiary": "#006491",
                "danger": "#EF4444",
                "error": "#ba1a1a"
            },
            "fontFamily": {
                "body-md": ["Plus Jakarta Sans"],
                "body-sm": ["Plus Jakarta Sans"],
                "title-sm": ["Plus Jakarta Sans"],
                "title-lg": ["Plus Jakarta Sans"],
                "headline-md": ["Plus Jakarta Sans"],
                "display-lg": ["Plus Jakarta Sans"],
                "label-caps": ["Plus Jakarta Sans"],
                "label-xs": ["Plus Jakarta Sans"]
            }
          }
        }
      }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
        .custom-scrollbar::-webkit-scrollbar { width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #E2E8F0; border-radius: 10px; }
        .bento-grid { display: grid; grid-template-columns: repeat(12, 1fr); gap: 1.5rem; }
    </style>
</head>
<body class="bg-background-slate text-on-surface">

    <!-- Side Navigation Shell -->
    <aside class="fixed left-0 top-0 h-full w-64 border-r border-border-slate bg-surface-white flex flex-col py-6 z-50">
        <div class="px-6 mb-8 cursor-pointer" onclick="window.location.href='index.php'">
            <h1 class="font-headline-md text-2xl font-bold text-primary tracking-tight">Solar Command</h1>
            <p class="font-body-sm text-sm text-text-muted">Admin Terminal</p>
        </div>
        <nav class="flex-1 px-4 space-y-1">
            <a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-highest transition-colors duration-200 rounded-lg" href="index.php">
                <span class="material-symbols-outlined">dashboard</span>
                <span class="font-bold text-sm">Dashboard</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-primary font-bold border-r-4 border-primary bg-surface-container-low transition-colors duration-200 rounded-lg" href="manager.php">
                <span class="material-symbols-outlined">admin_panel_settings</span>
                <span class="font-bold text-sm">Command Terminal</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-highest transition-colors duration-200 rounded-lg" href="analytics.php">
                <span class="material-symbols-outlined">assessment</span>
                <span class="font-bold text-sm">Analytics</span>
            </a>
        </nav>
    </aside>

    <!-- Top App Bar -->
    <header class="fixed top-0 left-64 right-0 h-16 bg-background-slate border-b border-border-slate flex items-center justify-between px-8 z-40">
        <div class="flex items-center gap-6">
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 material-symbols-outlined text-text-muted">search</span>
                <input class="bg-surface-white border border-border-slate py-2 pl-10 pr-4 w-80 text-sm focus:border-primary outline-none transition-colors rounded-lg" placeholder="Search fleet or serial..." type="text"/>
            </div>
        </div>
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2 px-3 py-1 bg-surface-container rounded-full">
                <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span class="font-bold text-[10px] uppercase text-primary">System Status: Active</span>
            </div>
            <div class="flex items-center gap-4">
                <button class="material-symbols-outlined text-text-muted hover:text-primary transition-colors">notifications</button>
            </div>
        </div>
    </header>

    <!-- Main Content Canvas -->
    <main class="ml-64 mt-16 p-8 min-h-screen">
        <!-- Telemetry Stats Row -->
        <div class="grid grid-cols-4 gap-6 mb-8">
            <div class="bg-surface-white p-6 border border-border-slate rounded-xl shadow-sm flex flex-col justify-between hover:border-primary transition-colors group">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-text-muted font-bold text-[10px] uppercase tracking-wider">Active Jobs</span>
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">engineering</span>
                </div>
                <div class="flex items-end justify-between">
                    <h2 class="text-3xl font-bold"><?php echo $active_jobs_count; ?></h2>
                </div>
            </div>
            <div class="bg-surface-white p-6 border border-border-slate rounded-xl shadow-sm flex flex-col justify-between hover:border-primary transition-colors group">
                <div class="flex justify-between items-start mb-2">
                    <span class="text-text-muted font-bold text-[10px] uppercase tracking-wider">Fleet Efficiency</span>
                    <span class="material-symbols-outlined text-primary group-hover:scale-110 transition-transform">speed</span>
                </div>
                <div class="flex items-end justify-between">
                    <h2 class="text-3xl font-bold">94.8<span class="text-lg">%</span></h2>
                    <span class="text-green-600 font-bold text-[10px] mb-1">OPTIMAL</span>
                </div>
            </div>
        </div>

        <div class="bento-grid">
            <!-- OPERATION QUEUE (Secondary - 4 columns) -->
            <section class="col-span-6 bg-surface-white border border-border-slate shadow-sm rounded-xl flex flex-col h-[520px]">
                <header class="p-6 border-b border-border-slate">
                    <h3 class="font-bold text-lg text-main">Manager Operation Queue</h3>
                    <p class="text-sm text-text-muted">Your active tasks</p>
                </header>
                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar space-y-4">
                    <?php if (empty($tasks)): ?>
                        <p class="text-text-muted text-sm text-center py-4">No tasks in queue.</p>
                    <?php else: ?>
                        <?php foreach($tasks as $t): ?>
                        <div class="flex gap-4 p-4 hover:bg-surface-container-low transition-colors border border-transparent hover:border-border-slate rounded-lg">
                            <div class="flex flex-col items-center justify-center bg-background-slate w-12 h-16 shrink-0 border border-border-slate rounded-md">
                                <span class="font-bold text-[10px] text-text-muted uppercase">
                                    <?php echo $t['due_date'] ? date('M', strtotime($t['due_date'])) : 'N/A'; ?>
                                </span>
                                <span class="font-bold text-xl leading-none">
                                    <?php echo $t['due_date'] ? date('d', strtotime($t['due_date'])) : '--'; ?>
                                </span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <h5 class="font-bold text-sm truncate text-main"><?php echo htmlspecialchars($t['title']); ?></h5>
                                    <span class="material-symbols-outlined text-text-muted text-sm">drag_handle</span>
                                </div>
                                <p class="text-xs text-text-muted mt-1"><?php echo htmlspecialchars($t['description']); ?></p>
                                <div class="mt-2 flex gap-1">
                                    <span class="text-[10px] font-bold bg-surface-container-highest px-1.5 py-0.5 rounded-sm uppercase"><?php echo htmlspecialchars($t['status']); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
</body>
</html>
