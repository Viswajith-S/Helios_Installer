<?php
require_once 'includes/db.php';

// Calculate some derived metrics
$total_jobs = 0;
$in_progress = 0;
$completed = 0;
$swms_completed = 0;

if ($db_crm) {
    try {
        $stmt = $db_crm->query("SELECT COUNT(*) FROM projects");
        $total_jobs = $stmt->fetchColumn();

        $stmt = $db_crm->query("SELECT COUNT(*) FROM projects WHERE status = 'In Progress'");
        $in_progress = $stmt->fetchColumn();
        
        $stmt = $db_crm->query("SELECT COUNT(*) FROM projects WHERE status = 'Completed'");
        $completed = $stmt->fetchColumn();
    } catch (Exception $e) {}
}

if ($db_installs) {
    try {
        $stmt = $db_installs->query("SELECT COUNT(*) FROM job_compliance WHERE swms_completed = 1");
        $swms_completed = $stmt->fetchColumn();
    } catch (Exception $e) {}
}

$completion_rate = $total_jobs > 0 ? round(($completed / $total_jobs) * 100, 1) : 0;
$swms_compliance = $total_jobs > 0 ? round(($swms_completed / $total_jobs) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Precision Fleet Pro | Analytics & Reports</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
      tailwind.config = {
        darkMode: "class",
        theme: {
          extend: {
            "colors": {
                "primary-container": "#e7b008",
                "on-primary-container": "#5d4500",
                "primary": "#e7b008",
                "on-primary": "#ffffff",
                "surface-white": "#FFFFFF",
                "border-slate": "#E2E8F0",
                "text-muted": "#64748B",
                "on-surface": "#201b11",
                "on-surface-variant": "#4f4633",
                "surface-container-highest": "#ece1d1",
                "surface-container-low": "#fdf2e2",
                "surface-container-lowest": "#ffffff",
                "background-slate": "#F8FAFC",
                "error": "#ba1a1a",
                "outline-variant": "#d3c5ac",
                "secondary": "#565e74",
                "secondary-container": "#dae2fd",
                "primary-fixed-dim": "#f7be21"
            },
            "fontFamily": {
                "body-md": ["Plus Jakarta Sans"],
                "headline-md": ["Plus Jakarta Sans"],
                "display-lg": ["Plus Jakarta Sans"],
                "title-sm": ["Plus Jakarta Sans"],
                "title-lg": ["Plus Jakarta Sans"]
            }
          }
        }
      }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; vertical-align: middle; }
    </style>
</head>
<body class="bg-background-slate text-on-surface">
    <!-- Sidebar Navigation -->
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
            <a class="flex items-center gap-3 px-4 py-3 text-secondary hover:bg-surface-container-highest transition-colors duration-200 rounded-lg" href="manager.php">
                <span class="material-symbols-outlined">admin_panel_settings</span>
                <span class="font-bold text-sm">Command Terminal</span>
            </a>
            <a class="flex items-center gap-3 px-4 py-3 text-primary font-bold border-r-4 border-primary bg-surface-container-low transition-colors duration-200 rounded-lg" href="analytics.php">
                <span class="material-symbols-outlined">assessment</span>
                <span class="font-bold text-sm">Analytics</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <main class="ml-64 min-h-screen">
        <header class="flex justify-between items-center px-8 h-16 bg-surface-white sticky top-0 z-40 border-b border-border-slate">
            <h2 class="font-bold text-xl text-text-main">Analytics & Reports</h2>
            <div class="flex items-center space-x-4">
                <button class="material-symbols-outlined text-text-muted hover:text-primary transition-all">notifications</button>
                <button class="material-symbols-outlined text-text-muted hover:text-primary transition-all">account_circle</button>
            </div>
        </header>

        <div class="p-8 max-w-[1400px] mx-auto space-y-8">
            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-surface-white p-6 border border-border-slate rounded-xl shadow-sm hover:border-primary transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-bold text-text-muted uppercase tracking-wider">Total Active Jobs</span>
                        <span class="material-symbols-outlined text-primary">engineering</span>
                    </div>
                    <div class="flex items-baseline space-x-2">
                        <h3 class="text-3xl font-bold"><?php echo $in_progress; ?></h3>
                        <span class="text-sm font-bold text-text-muted">JOBS</span>
                    </div>
                </div>

                <div class="bg-surface-white p-6 border border-border-slate rounded-xl shadow-sm hover:border-primary transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-bold text-text-muted uppercase tracking-wider">Total Completed</span>
                        <span class="material-symbols-outlined text-primary">done_all</span>
                    </div>
                    <div class="flex items-baseline space-x-2">
                        <h3 class="text-3xl font-bold"><?php echo $completed; ?></h3>
                        <span class="text-sm font-bold text-text-muted">JOBS</span>
                    </div>
                </div>

                <div class="bg-surface-white p-6 border border-border-slate rounded-xl shadow-sm hover:border-primary transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-bold text-text-muted uppercase tracking-wider">Fleet Efficiency</span>
                        <span class="material-symbols-outlined text-primary">speed</span>
                    </div>
                    <div class="flex items-baseline space-x-2">
                        <h3 class="text-3xl font-bold"><?php echo $completion_rate; ?>%</h3>
                    </div>
                    <div class="mt-4 w-full bg-border-slate h-1.5 rounded-full overflow-hidden">
                        <div class="bg-primary h-full" style="width: <?php echo $completion_rate; ?>%"></div>
                    </div>
                </div>

                <div class="bg-surface-white p-6 border border-border-slate rounded-xl shadow-sm hover:border-primary transition-colors">
                    <div class="flex justify-between items-start mb-2">
                        <span class="text-xs font-bold text-text-muted uppercase tracking-wider">SWMS Compliance</span>
                        <span class="material-symbols-outlined text-primary">verified_user</span>
                    </div>
                    <div class="flex items-baseline space-x-2">
                        <h3 class="text-3xl font-bold"><?php echo $swms_compliance; ?>%</h3>
                    </div>
                    <div class="mt-4 w-full bg-border-slate h-1.5 rounded-full overflow-hidden">
                        <div class="bg-primary h-full" style="width: <?php echo $swms_compliance; ?>%"></div>
                    </div>
                </div>
            </div>

            <!-- Chart & Compliance -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <!-- Fleet Performance Chart -->
                <div class="lg:col-span-8 bg-surface-white border border-border-slate rounded-xl shadow-sm p-6">
                    <div class="flex justify-between items-center mb-8">
                        <div>
                            <h4 class="font-bold text-lg">Installation Progress</h4>
                            <p class="text-sm text-text-muted">Projected vs Actual performance (Last 30 Days)</p>
                        </div>
                        <div class="flex space-x-2">
                            <span class="flex items-center text-xs font-bold text-text-muted"><span class="w-3 h-3 bg-primary rounded-full mr-1.5"></span> ACTUAL</span>
                            <span class="flex items-center text-xs font-bold text-text-muted"><span class="w-3 h-3 bg-border-slate rounded-full mr-1.5"></span> PROJECTION</span>
                        </div>
                    </div>
                    <div class="relative h-[200px] w-full flex items-end justify-between space-x-2">
                        <div class="w-full bg-border-slate h-[40%] rounded-t opacity-40"></div>
                        <div class="w-full bg-primary h-[45%] rounded-t"></div>
                        <div class="w-full bg-border-slate h-[55%] rounded-t opacity-40"></div>
                        <div class="w-full bg-primary h-[62%] rounded-t"></div>
                        <div class="w-full bg-border-slate h-[60%] rounded-t opacity-40"></div>
                        <div class="w-full bg-primary h-[58%] rounded-t"></div>
                        <div class="w-full bg-border-slate h-[70%] rounded-t opacity-40"></div>
                        <div class="w-full bg-primary h-[85%] rounded-t"></div>
                        <div class="w-full bg-border-slate h-[75%] rounded-t opacity-40"></div>
                        <div class="w-full bg-primary h-[72%] rounded-t"></div>
                        <div class="w-full bg-border-slate h-[80%] rounded-t opacity-40"></div>
                        <div class="w-full bg-primary h-[95%] rounded-t"></div>
                    </div>
                    <div class="flex justify-between mt-4 text-xs font-bold text-text-muted px-1">
                        <span>WK 01</span>
                        <span>WK 02</span>
                        <span>WK 03</span>
                        <span>WK 04</span>
                        <span>WK 05</span>
                        <span>WK 06</span>
                    </div>
                </div>

                <!-- Compliance & Safety Summary -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="bg-surface-white border border-border-slate rounded-xl shadow-sm p-6">
                        <h4 class="font-bold text-lg mb-4">Compliance Status</h4>
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-3xl font-bold text-text-main"><?php echo $swms_compliance; ?>%</p>
                                <p class="text-xs font-bold text-text-muted uppercase">SWMS Completed</p>
                            </div>
                            <div class="relative w-16 h-16">
                                <span class="absolute inset-0 flex items-center justify-center material-symbols-outlined text-primary text-4xl">verified</span>
                            </div>
                        </div>
                        <div class="space-y-2 pt-4 border-t border-border-slate">
                            <div class="flex justify-between text-sm">
                                <span class="text-text-muted font-semibold">Total SWMS Logged</span>
                                <span class="font-bold text-text-main"><?php echo $swms_completed; ?></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-text-muted font-semibold">Pending Review</span>
                                <span class="font-bold text-danger"><?php echo max(0, $total_jobs - $swms_completed); ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Safety Counter -->
                    <div class="bg-text-main p-6 rounded-xl shadow-sm text-surface-white text-center">
                        <span class="material-symbols-outlined text-4xl mb-2 text-primary">shield_with_heart</span>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-text-muted">Incident-Free Days</h4>
                        <p class="text-5xl font-extrabold leading-none my-3 text-white">314</p>
                        <p class="text-sm font-semibold text-text-muted">Record: 420 Days</p>
                    </div>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
