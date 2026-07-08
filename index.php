<?php
require_once 'includes/db.php';

$today_date = date('Y-m-d');
$today_job = null;
$upcoming_jobs = [];

// Component JOIN query base
$base_query = "
    SELECT p.id, p.customer_name, p.customer_email, p.dc_output_kw, p.storage_kwh, 
           p.installation_date, p.installation_time_slot, p.installation_team,
           p.assigned_installer_id, p.comments, p.status, p.total_price,
           i.name AS assigned_installer_name,
           GROUP_CONCAT(DISTINCT CASE WHEN c.component_type = 'Solar Panel' THEN CONCAT(pc.quantity_required, 'x ', c.model_name) END SEPARATOR ', ') AS panels,
           GROUP_CONCAT(DISTINCT CASE WHEN c.component_type = 'Inverter' THEN c.model_name END SEPARATOR ', ') AS inverter,
           GROUP_CONCAT(DISTINCT CASE WHEN c.component_type = 'Battery' THEN c.model_name END SEPARATOR ', ') AS battery
    FROM projects p
    LEFT JOIN project_components pc ON pc.project_id = p.id
    LEFT JOIN components c ON c.id = pc.component_id
    LEFT JOIN installers i ON i.id = p.assigned_installer_id
";

if ($db_crm) {
    try {
        // Today's highlight job
        $stmt = $db_crm->prepare($base_query . " WHERE p.installation_date = :today AND p.status != 'Cancelled' GROUP BY p.id ORDER BY p.installation_time_slot ASC LIMIT 1");
        $stmt->execute(['today' => $today_date]);
        $today_job = $stmt->fetch();

        // Upcoming jobs
        $stmt = $db_crm->prepare($base_query . " WHERE p.installation_date > :today AND p.status != 'Cancelled' GROUP BY p.id ORDER BY p.installation_date ASC, p.installation_time_slot ASC LIMIT 8");
        $stmt->execute(['today' => $today_date]);
        $upcoming_jobs = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// Manager tasks
$tasks = [];
if ($db_installs) {
    try {
        $stmt = $db_installs->query("SELECT * FROM manager_tasks ORDER BY due_date ASC");
        $tasks = $stmt->fetchAll();
    } catch (Exception $e) {}
}

// Greeting based on time
$hour = (int)date('H');
if ($hour < 12) $greeting = 'Good morning';
elseif ($hour < 17) $greeting = 'Good afternoon';
else $greeting = 'Good evening';
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Helios Install Hub - Dashboard</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        "primary": "#e7b008",
                        "primary-hover": "#CA8A04",
                        "background-light": "#F8FAFC",
                        "surface": "#FFFFFF",
                        "text-main": "#0F172A",
                        "text-muted": "#64748B",
                        "border-slate": "#E2E8F0",
                    },
                    fontFamily: { "display": ["Plus Jakarta Sans", "sans-serif"] },
                    borderRadius: { "DEFAULT":"0.125rem","sm":"4px","md":"6px","lg":"0.25rem","xl":"0.5rem","full":"0.75rem" },
                    boxShadow: { 'subtle': '0 4px 6px -1px rgb(0 0 0 / 0.05)' }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    </style>
</head>
<body class="text-text-main min-h-screen overflow-x-hidden flex flex-col">
    <!-- TopNavBar -->
    <div class="relative flex h-auto w-full flex-col bg-surface group/design-root overflow-x-hidden border-b border-border-slate shadow-sm z-10">
        <div class="layout-container flex h-full grow flex-col">
            <div class="px-8 md:px-12 lg:px-40 flex flex-1 justify-center py-0">
                <div class="layout-content-container flex flex-col max-w-[1200px] w-full flex-1">
                    <header class="flex items-center justify-between whitespace-nowrap px-0 py-5 h-[80px]">
                        <div class="flex items-center gap-4 text-text-main">
                            <img src="Helios_Logo.png" alt="Helios Logo" class="h-6 w-auto object-contain" />
                            <h2 class="text-text-main text-xl font-bold leading-tight tracking-[-0.015em]">Helios Install Hub</h2>
                        </div>
                        <nav class="hidden md:flex ml-10 gap-6">
                            <a href="index.php" class="text-text-main font-bold hover:text-primary transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">dashboard</span> Dashboard</a>
                            <a href="manager.php" class="text-text-muted font-bold hover:text-primary transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">admin_panel_settings</span> Command Terminal</a>
                            <a href="analytics.php" class="text-text-muted font-bold hover:text-primary transition-colors flex items-center gap-2"><span class="material-symbols-outlined text-[18px]">assessment</span> Analytics</a>
                            <a href="installer.php" class="text-primary font-bold hover:text-primary-hover transition-colors flex items-center gap-2 ml-4"><span class="material-symbols-outlined text-[18px]">tablet_mac</span> Tablet Mode</a>
                        </nav>
                        <div class="flex flex-1 justify-end gap-8">
                            <div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 border border-border-slate shadow-sm bg-gray-300"></div>
                        </div>
                    </header>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="flex-1 w-full max-w-[1200px] mx-auto px-8 md:px-12 lg:px-40 py-8 grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Greeting -->
        <div class="col-span-1 lg:col-span-12 flex flex-col gap-2 mb-4">
            <h1 class="text-text-main tracking-tight text-3xl font-bold leading-tight"><?php echo $greeting; ?>, Installer.</h1>
            <p class="text-text-muted text-base font-medium leading-normal">You have <?php echo count($upcoming_jobs) + ($today_job ? 1 : 0); ?> active installs scheduled.</p>
        </div>

        <!-- Left Column -->
        <div class="col-span-1 lg:col-span-7 flex flex-col gap-4 w-full">
            <h2 class="text-text-main text-xl font-bold leading-tight tracking-tight px-1">Today's Highlight Job</h2>
            
            <?php if ($today_job): ?>
            <div class="bg-surface rounded-sm shadow-subtle border border-border-slate flex flex-col overflow-hidden group hover:border-primary transition-colors duration-200 cursor-pointer" onclick="window.location.href='job_detail.php?id=<?php echo $today_job['id']; ?>'">
                <!-- Card Header -->
                <div class="p-6 border-b border-border-slate flex justify-between items-start gap-4">
                    <div class="flex flex-col gap-1">
                        <div class="flex items-center gap-3 mb-2 flex-wrap">
                            <span class="bg-primary text-surface text-xs font-bold px-2 py-1 rounded-sm uppercase tracking-wider">Scheduled Today</span>
                            <span class="text-text-muted text-sm font-semibold">#SLR-<?php echo htmlspecialchars($today_job['id'] ?? ''); ?></span>
                            <span class="text-text-muted text-sm font-semibold flex items-center gap-1">
                                <span class="material-symbols-outlined text-sm">schedule</span> <?php echo htmlspecialchars($today_job['installation_time_slot'] ?? 'TBC'); ?>
                            </span>
                        </div>
                        <h3 class="text-2xl font-bold text-text-main leading-tight"><?php echo htmlspecialchars($today_job['customer_name'] ?? 'Unknown'); ?></h3>
                        <?php 
                            $installer_name = $today_job['assigned_installer_name'] ?? $today_job['installation_team'] ?? '';
                        ?>
                        <?php if (!empty($installer_name)): ?>
                        <p class="text-text-muted text-base font-medium flex items-center gap-1 mt-1">
                            <span class="material-symbols-outlined text-lg" style="font-variation-settings: 'FILL' 1;">person</span>
                            Assigned Installer: <?php echo htmlspecialchars($installer_name); ?>
                        </p>
                        <?php endif; ?>
                        <?php if (!empty($today_job['comments'])): ?>
                        <p class="text-text-muted text-sm font-medium flex items-center gap-1 mt-1">
                            <span class="material-symbols-outlined text-sm">notes</span>
                            <?php echo htmlspecialchars($today_job['comments']); ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="hidden sm:flex flex-col items-center gap-1">
                        <div class="w-16 h-16 rounded-sm bg-background-light border border-border-slate flex items-center justify-center text-text-muted">
                            <span class="material-symbols-outlined text-3xl">home</span>
                        </div>
                        <span class="text-[11px] font-bold text-text-muted"><?php echo $today_job['dc_output_kw']; ?> kW</span>
                    </div>
                </div>
                
                <!-- System Specs Grid -->
                <div class="p-6 bg-background-light/30">
                    <h4 class="text-text-main text-sm font-bold uppercase tracking-wider mb-4 border-b border-border-slate pb-2">System Specifications</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6">
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted text-[13px] font-semibold uppercase tracking-wide">Panels</span>
                            <span class="text-text-main text-[15px] font-bold"><?php echo htmlspecialchars($today_job['panels'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted text-[13px] font-semibold uppercase tracking-wide">Inverter</span>
                            <span class="text-text-main text-[15px] font-bold"><?php echo htmlspecialchars($today_job['inverter'] ?? 'N/A'); ?></span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted text-[13px] font-semibold uppercase tracking-wide">Battery</span>
                            <span class="text-text-main text-[15px] font-bold"><?php echo htmlspecialchars($today_job['battery'] ?? 'N/A'); ?></span>
                        </div>
                    </div>
                </div>

                <div class="p-6 border-t border-border-slate flex justify-end items-center bg-surface">
                    <a href="job_detail.php?id=<?php echo $today_job['id']; ?>" class="bg-primary hover:bg-primary-hover text-surface font-semibold text-[14px] uppercase tracking-[0.05em] py-3 px-6 rounded-sm transition-colors duration-200 flex items-center gap-2 shadow-sm">
                        Manage Job Compliance
                        <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
                    </a>
                </div>
            </div>
            <?php else: ?>
                <p class="text-text-muted">No jobs scheduled for today.</p>
            <?php endif; ?>

            <!-- Manager Tasks -->
            <h2 class="text-text-main text-xl font-bold leading-tight tracking-tight px-1 mt-6">Manager Tasks</h2>
            <div class="bg-surface rounded-sm border border-border-slate p-4 shadow-subtle">
                <?php if (empty($tasks)): ?>
                    <p class="text-text-muted text-sm italic py-2">No active tasks.</p>
                <?php else: ?>
                    <ul class="flex flex-col gap-3">
                        <?php foreach($tasks as $t): ?>
                            <li class="flex items-center justify-between border-b border-border-slate pb-2 last:border-0 last:pb-0">
                                <div>
                                    <h4 class="font-bold text-text-main text-sm"><?php echo htmlspecialchars($t['title']); ?></h4>
                                    <p class="text-xs text-text-muted"><?php echo htmlspecialchars($t['description']); ?></p>
                                </div>
                                <span class="text-xs font-bold px-2 py-1 uppercase rounded-sm bg-background-light border border-border-slate">
                                    <?php echo htmlspecialchars($t['status']); ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Column: Upcoming Schedule -->
        <div class="col-span-1 lg:col-span-5 flex flex-col gap-4 w-full">
            <div class="flex justify-between items-end px-1">
                <h2 class="text-text-main text-xl font-bold leading-tight tracking-tight">Upcoming Schedule</h2>
                <span class="text-text-muted text-sm font-semibold flex items-center gap-1">
                    <?php echo count($upcoming_jobs); ?> jobs
                    <span class="material-symbols-outlined text-sm">calendar_month</span>
                </span>
            </div>
            <div class="flex flex-col gap-3">
                <?php foreach($upcoming_jobs as $job): ?>
                <div class="bg-surface rounded-sm border border-border-slate p-4 flex items-start gap-4 shadow-subtle hover:bg-[#F1F5F9] transition-colors duration-200 cursor-pointer group" onclick="window.location.href='job_detail.php?id=<?php echo $job['id']; ?>'">
                    <div class="flex flex-col items-center justify-center min-w-[60px] p-2 bg-background-light rounded-sm border border-border-slate text-center">
                        <?php 
                            $dateStr = $job['installation_date'] ?? '';
                            $day = $dateStr ? strtoupper(date('d M', strtotime($dateStr))) : 'TBD';
                        ?>
                        <span class="text-text-muted text-[10px] font-bold uppercase"><?php echo $day; ?></span>
                        <span class="text-text-main text-base font-bold leading-tight"><?php echo htmlspecialchars($job['installation_time_slot'] ?? 'TBC'); ?></span>
                    </div>
                    <div class="flex flex-col gap-1 flex-1 min-w-0">
                        <div class="flex justify-between items-start gap-2">
                            <h4 class="text-text-main text-base font-bold leading-tight group-hover:text-primary transition-colors truncate"><?php echo htmlspecialchars($job['customer_name'] ?? 'Unknown'); ?></h4>
                            <span class="bg-background-light text-text-muted text-[10px] font-bold px-2 py-0.5 rounded-sm uppercase tracking-wider border border-border-slate flex-none">#SLR-<?php echo htmlspecialchars($job['id'] ?? ''); ?></span>
                        </div>
                        <?php 
                            $installer_name = $job['assigned_installer_name'] ?? $job['installation_team'] ?? '';
                        ?>
                        <?php if (!empty($installer_name)): ?>
                        <p class="text-text-muted text-sm font-medium flex items-center gap-1">
                            <span class="material-symbols-outlined text-sm" style="font-variation-settings: 'FILL' 1;">person</span>
                            <?php echo htmlspecialchars($installer_name); ?>
                        </p>
                        <?php endif; ?>
                        <!-- Equipment tags -->
                        <div class="flex flex-wrap gap-1 mt-1 border-t border-border-slate/50 pt-1.5">
                            <span class="text-[10px] font-semibold text-text-muted"><?php echo htmlspecialchars($job['dc_output_kw'] ?? '0'); ?> kW</span>
                            <?php if (!empty($job['storage_kwh']) && $job['storage_kwh'] > 0): ?>
                            <span class="text-[10px] text-text-muted">·</span>
                            <span class="text-[10px] font-semibold text-text-muted"><?php echo htmlspecialchars($job['storage_kwh']); ?> kWh</span>
                            <?php endif; ?>
                            <?php if (!empty($job['comments'])): ?>
                            <span class="text-[10px] text-text-muted">·</span>
                            <span class="text-[10px] font-medium text-text-muted truncate max-w-[150px]"><?php echo htmlspecialchars($job['comments']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </main>
</body>
</html>
