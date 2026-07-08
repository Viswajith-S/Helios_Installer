<?php
require_once 'includes/db.php';

$today_date = date('Y-m-d');
$today_jobs = [];
$scheduled_jobs = [];

$base_query = "
    SELECT p.id, p.customer_name, p.customer_email, p.dc_output_kw, p.storage_kwh, 
           p.installation_date, p.installation_time_slot, p.installation_team,
           p.assigned_installer_id, p.comments, p.status, p.total_price, p.job_sheet_url, p.pylon_project_id,
           i.name AS assigned_installer_name,
           pai.address,
           GROUP_CONCAT(DISTINCT CASE WHEN c.component_type = 'Solar Panel' THEN CONCAT(pc.quantity_required, 'x ', c.model_name) END SEPARATOR ', ') AS panels,
           GROUP_CONCAT(DISTINCT CASE WHEN c.component_type = 'Inverter' THEN c.model_name END SEPARATOR ', ') AS inverter,
           GROUP_CONCAT(DISTINCT CASE WHEN c.component_type = 'Battery' THEN c.model_name END SEPARATOR ', ') AS battery
    FROM projects p
    LEFT JOIN project_components pc ON pc.project_id = p.id
    LEFT JOIN components c ON c.id = pc.component_id
    LEFT JOIN installers i ON i.id = p.assigned_installer_id
    LEFT JOIN project_additional_info pai ON pai.project_id = p.id
";

if ($db_crm) {
    try {
        $stmt = $db_crm->prepare($base_query . " WHERE p.installation_date = :today AND p.status != 'Cancelled' GROUP BY p.id ORDER BY p.installation_time_slot ASC");
        $stmt->execute(['today' => $today_date]);
        $today_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt = $db_crm->prepare($base_query . " WHERE p.installation_date > :today AND p.status != 'Cancelled' GROUP BY p.id ORDER BY p.installation_date ASC, p.installation_time_slot ASC");
        $stmt->execute(['today' => $today_date]);
        $scheduled_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {}
}

// Attach photos from disk
function attachPhotos(&$jobs, $base_dir) {
    foreach ($jobs as &$job) {
        $job['pre_photos'] = [];
        $job['post_photos'] = [];
        $pre_dir = $base_dir . '/uploads/pre/' . $job['id'];
        $post_dir = $base_dir . '/uploads/post/' . $job['id'];
        if (is_dir($pre_dir)) {
            foreach (array_diff(scandir($pre_dir), ['.','..']) as $f) {
                $job['pre_photos'][] = 'uploads/pre/' . $job['id'] . '/' . $f;
            }
        }
        if (is_dir($post_dir)) {
            foreach (array_diff(scandir($post_dir), ['.','..']) as $f) {
                $job['post_photos'][] = 'uploads/post/' . $job['id'] . '/' . $f;
            }
        }
        // Get notes and compliance data from installs DB
        $job['pre_notes'] = '';
        $job['post_notes'] = '';
        $job['swms_status'] = '';
        $job['swms_contractor_name'] = '';
        $job['swms_signature_date'] = '';
        $job['swms_signature_data'] = '';

        global $db_installs;
        $job['swms_signatures'] = [];
        if ($db_installs) {
            try {
                $stmt = $db_installs->prepare("SELECT swms_completed, photo_data, swms_contractor_name, swms_signature_date, swms_signature_data FROM job_compliance WHERE crm_project_id = :id");
                $stmt->execute(['id' => $job['id']]);
                $comp = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($comp) {
                    $job['swms_status'] = $comp['swms_completed'] ? 'verified' : '';
                    if ($comp['photo_data']) {
                        $data = json_decode($comp['photo_data'], true);
                        if (is_array($data)) {
                            $job['pre_notes'] = $data['pre_notes'] ?? '';
                            $job['post_notes'] = $data['post_notes'] ?? '';
                        }
                    }
                }
                // Fetch new multiple signatures
                $sig_stmt = $db_installs->prepare("SELECT id, contractor_name, signature_date, signature_data FROM swms_signatures WHERE crm_project_id = :id ORDER BY created_at ASC");
                $sig_stmt->execute(['id' => $job['id']]);
                $job['swms_signatures'] = $sig_stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        }
    }
}
attachPhotos($today_jobs, __DIR__);
attachPhotos($scheduled_jobs, __DIR__);

$all_jobs = array_merge($today_jobs, $scheduled_jobs);
$jobs_json = json_encode($all_jobs, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title>Helios Installer Admin</title>
    <meta name="apple-mobile-web-app-capable" content="yes"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent"/>
    <meta name="mobile-web-app-capable" content="yes"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'system-ui', 'sans-serif'] },
                    colors: {
                        brand: { 50:'#fffbeb',100:'#fef3c7',200:'#fde68a',300:'#fcd34d',400:'#fbbf24',500:'#f59e0b',600:'#d97706',700:'#b45309',800:'#92400e' },
                        slate: { 25:'#fcfcfd',50:'#f8fafc',100:'#f1f5f9',150:'#e9eef4',200:'#e2e8f0',300:'#cbd5e1',400:'#94a3b8',500:'#64748b',600:'#475569',700:'#334155',800:'#1e293b',900:'#0f172a' },
                    },
                }
            }
        }
    </script>
    <style>
        body { background:#f1f5f9; -webkit-tap-highlight-color:transparent; }
        ::-webkit-scrollbar { width:5px; }
        ::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:6px; }
        ::-webkit-scrollbar-thumb:hover { background:#94a3b8; }
        .job-card { transition: all 0.15s ease; }
        .job-card:active { transform: scale(0.98); }
        .job-card.selected { border-color: #f59e0b !important; background: #fffbeb !important; }
        .photo-thumb { transition: all 0.2s ease; }
        .photo-thumb:hover { transform: scale(1.08); box-shadow: 0 4px 15px rgba(0,0,0,0.15); }
        .slide-enter { animation: slideIn 0.2s ease-out; }
        @keyframes slideIn { from { opacity:0; transform:translateX(12px); } to { opacity:1; transform:translateX(0); } }
        .carousel-container { scroll-behavior: smooth; -webkit-overflow-scrolling: touch; }
        .section-card { background: white; border: 1px solid #e2e8f0; border-radius: 12px; }
        
        .custom-scrollbar::-webkit-scrollbar { height: 4px; width: 4px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background-color: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar:hover::-webkit-scrollbar-thumb { background-color: #94a3b8; }
    </style>
</head>

<body class="text-slate-800 antialiased h-screen flex flex-col overflow-hidden" x-data="installerApp()">

<!-- ============= FULLSCREEN IMAGE VIEWER ============= -->
<div x-show="viewer.open" x-transition.opacity.duration.200ms 
     class="fixed inset-0 z-[80] bg-black/95 flex flex-col" style="display:none"
     @keydown.escape.window="viewer.open = false"
     @keydown.arrow-left.window="viewerPrev()"
     @keydown.arrow-right.window="viewerNext()">
    <!-- Viewer Toolbar -->
    <div class="flex items-center justify-between p-3 flex-none">
        <span class="text-white/60 text-sm font-medium" x-text="(viewer.index+1) + ' / ' + viewer.photos.length"></span>
        <div class="flex items-center gap-2">
            <button @click="viewer.zoom = Math.max(0.5, viewer.zoom - 0.5)" class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20">
                <span class="material-symbols-rounded" style="font-size:20px">zoom_out</span>
            </button>
            <span class="text-white/60 text-xs font-mono w-10 text-center" x-text="Math.round(viewer.zoom * 100) + '%'"></span>
            <button @click="viewer.zoom = Math.min(4, viewer.zoom + 0.5)" class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/20">
                <span class="material-symbols-rounded" style="font-size:20px">zoom_in</span>
            </button>
            <div class="w-px h-6 bg-white/20 mx-1"></div>
            <button @click="viewer.open = false" class="w-9 h-9 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-red-500/80">
                <span class="material-symbols-rounded" style="font-size:20px">close</span>
            </button>
        </div>
    </div>
    <!-- Image + Nav Arrows -->
    <div class="flex-1 flex items-center justify-center relative overflow-hidden">
        <button @click="viewerPrev()" x-show="viewer.photos.length > 1" class="absolute left-3 z-10 w-11 h-11 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/25 backdrop-blur-sm">
            <span class="material-symbols-rounded" style="font-size:24px">chevron_left</span>
        </button>
        <img :src="viewer.photos[viewer.index]" 
             class="max-w-[92vw] max-h-[85vh] object-contain transition-transform duration-200 select-none" 
             :style="'transform: scale(' + viewer.zoom + ')'"
             @dblclick="viewer.zoom = viewer.zoom === 1 ? 2.5 : 1" 
             draggable="false" />
        <button @click="viewerNext()" x-show="viewer.photos.length > 1" class="absolute right-3 z-10 w-11 h-11 rounded-full bg-white/10 text-white flex items-center justify-center hover:bg-white/25 backdrop-blur-sm">
            <span class="material-symbols-rounded" style="font-size:24px">chevron_right</span>
        </button>
    </div>
</div>

<!-- ============= TOAST ============= -->
<div x-show="toast.msg" x-transition.duration.300ms class="fixed bottom-6 left-1/2 -translate-x-1/2 bg-slate-900 text-white px-5 py-2.5 rounded-xl shadow-2xl z-[70] flex items-center gap-2 text-sm font-medium" style="display:none">
    <span class="material-symbols-rounded" style="font-size:16px" :class="toast.spin && 'animate-spin'" x-text="toast.icon"></span>
    <span x-text="toast.msg"></span>
</div>

<!-- ============= HEADER ============= -->
<header class="bg-white border-b border-slate-200 flex-none z-30">
    <div class="w-full px-4 h-[52px] flex items-center justify-between">
        <div class="flex items-center gap-2.5">
            <a href="index.php" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center hover:bg-slate-200 border border-slate-200 flex-none" title="Home">
                <span class="material-symbols-rounded" style="font-size:16px">arrow_back</span>
            </a>
            <img src="Helios_Logo.png" alt="Helios Logo" class="h-8 w-auto object-contain" />
            <div>
                <h1 class="text-sm font-bold text-slate-900 leading-none">Helios Installer Admin</h1>
                <p class="text-[10px] font-medium text-slate-400 leading-none mt-0.5"><?php echo date('l, d M Y'); ?></p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <button @click="toggleFullscreen" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-400 flex items-center justify-center hover:bg-slate-200 border border-slate-200" title="Fullscreen">
                <span class="material-symbols-rounded" style="font-size:16px" x-text="isFullscreen ? 'fullscreen_exit' : 'fullscreen'">fullscreen</span>
            </button>
            <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-2 py-1 rounded-md border border-slate-200 ml-1">
                <?php echo count($today_jobs); ?> Today · <?php echo count($scheduled_jobs); ?> Upcoming
            </span>
            <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-200"></span>
        </div>
    </div>
</header>

<!-- ============= MAIN SPLIT LAYOUT ============= -->
<div class="flex-1 flex overflow-hidden">

    <!-- ===== LEFT PANEL: JOB LIST (GRID LAYOUT) ===== -->
    <div class="flex-none bg-slate-50/80 border-r border-slate-200 flex flex-col overflow-hidden transition-all duration-300" 
         :class="!selectedJob ? 'w-full' : 'hidden lg:flex w-[360px] xl:w-[420px]'">
        
        <!-- Search -->
        <div class="p-4 border-b border-slate-200 flex-none bg-white z-10 shadow-sm transition-all" :class="!selectedJob ? 'px-8' : ''">
            <div class="relative mx-auto transition-all" :class="!selectedJob ? 'max-w-4xl' : 'max-w-full'">
                <span class="material-symbols-rounded absolute left-4 top-1/2 -translate-y-1/2 text-slate-400" style="font-size:20px">search</span>
                <input type="text" x-model="searchQuery" placeholder="Search jobs by name or ID..." 
                       class="w-full pl-11 pr-4 py-3 bg-slate-100 border-none rounded-xl text-sm font-semibold text-slate-700 focus:ring-2 focus:ring-amber-500 focus:bg-white outline-none placeholder:text-slate-400 transition-all shadow-inner">
            </div>
        </div>

        <!-- Job List Scrollable Grid -->
        <div class="flex-1 overflow-y-auto p-4 md:p-5 transition-all" :class="!selectedJob ? 'px-8' : ''">
            <div class="mx-auto transition-all" :class="!selectedJob ? 'max-w-7xl space-y-8' : 'max-w-full space-y-8'">
                
                <!-- Search Results -->
                <template x-if="searchQuery">
                    <div>
                        <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-4 px-1">Search Results</h2>
                        <div class="grid gap-3 transition-all" :class="!selectedJob ? 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4' : 'grid-cols-1'">
                            <template x-for="job in filteredJobs" :key="job.id">
                                <div @click="openJob(job.id)" 
                                     :class="selectedJob?.id == job.id ? 'ring-4 ring-blue-600 shadow-xl bg-blue-50/50 border-blue-500' : 'border-slate-300 bg-white hover:border-blue-400 hover:shadow-lg'"
                                     class="job-card p-4 rounded-2xl border-2 transition-all flex flex-col gap-4 relative overflow-hidden group cursor-pointer shadow-md">
                                    
                                    <!-- Header -->
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0 flex-1">
                                            <h3 class="text-lg font-black text-slate-900 truncate leading-tight" x-text="job.customer_name"></h3>
                                            <!-- Address -->
                                            <div class="flex items-start gap-1.5 mt-2">
                                                <span class="material-symbols-rounded text-red-600 shrink-0" style="font-size:18px">location_on</span>
                                                <span class="text-[13px] font-bold text-slate-700 leading-snug" x-text="job.address || 'Address not provided'"></span>
                                            </div>
                                            
                                            <div class="text-[12px] font-extrabold text-slate-600 mt-2.5 flex items-center gap-2 flex-wrap">
                                                <div class="flex items-center gap-1 bg-slate-100 px-2 py-1 rounded-md border border-slate-300">
                                                    <span class="material-symbols-rounded text-slate-600" style="font-size:16px">calendar_today</span>
                                                    <span x-text="new Date(job.installation_date || new Date()).toLocaleDateString('en-US', {weekday: 'short', month: 'short', day: 'numeric'})"></span>
                                                </div>
                                                <div class="flex items-center gap-1 bg-amber-100 text-amber-900 px-2 py-1 rounded-md border border-amber-400 shadow-sm">
                                                    <span class="material-symbols-rounded text-amber-700" style="font-size:16px">schedule</span>
                                                    <span x-text="job.installation_time_slot || 'TBC'"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <span class="text-[12px] font-black text-white bg-slate-800 px-2.5 py-1.5 rounded-lg shrink-0 shadow-md" x-text="'ID: ' + job.id"></span>
                                    </div>
                                    
                                    <!-- System Info (High Contrast Badges) -->
                                    <div class="flex flex-col gap-2 pt-3 border-t-2 border-slate-100">
                                        <!-- Components -->
                                        <div class="flex flex-col gap-1.5">
                                            <template x-if="job.panels">
                                                <div class="flex items-start gap-2 bg-blue-50 p-2 rounded-lg border border-blue-200">
                                                    <span class="material-symbols-rounded text-blue-700 shrink-0" style="font-size:18px">solar_power</span>
                                                    <span class="text-[11px] font-bold text-blue-900 leading-tight" x-text="job.panels"></span>
                                                </div>
                                            </template>
                                            <template x-if="job.inverter">
                                                <div class="flex items-start gap-2 bg-indigo-50 p-2 rounded-lg border border-indigo-200">
                                                    <span class="material-symbols-rounded text-indigo-700 shrink-0" style="font-size:18px">electric_bolt</span>
                                                    <span class="text-[11px] font-bold text-indigo-900 leading-tight" x-text="job.inverter"></span>
                                                </div>
                                            </template>
                                            <template x-if="job.battery">
                                                <div class="flex items-start gap-2 bg-teal-50 p-2 rounded-lg border border-teal-200">
                                                    <span class="material-symbols-rounded text-teal-700 shrink-0" style="font-size:18px">battery_charging_full</span>
                                                    <span class="text-[11px] font-bold text-teal-900 leading-tight" x-text="job.battery"></span>
                                                </div>
                                            </template>
                                        </div>
                                        
                                        <!-- Installer -->
                                        <template x-if="job.assigned_installer_name">
                                            <div class="flex items-center gap-1.5 bg-slate-800 text-white px-3 py-2 rounded-lg shadow-sm mt-1">
                                                <span class="material-symbols-rounded text-amber-400 shrink-0" style="font-size:16px">engineering</span>
                                                <span class="text-[12px] font-bold truncate" x-text="'Installer: ' + job.assigned_installer_name"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <template x-if="filteredJobs.length === 0">
                            <div class="text-sm text-slate-400 font-bold text-center py-10 bg-white rounded-3xl border border-dashed border-slate-300">No leads found</div>
                        </template>
                    </div>
                </template>

                <!-- Normal View -->
                <template x-if="!searchQuery">
                    <div>
                        <!-- Today's Jobs -->
                        <div class="mb-8" x-show="groupedToday.length > 0">
                            <div class="flex items-center justify-between mb-4 px-1">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-amber-500" style="font-size:22px">today</span>
                                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Today's Jobs</h2>
                                </div>
                                <span class="text-xs font-bold text-slate-500 bg-white shadow-sm px-3 py-1 rounded-full border border-slate-200" x-text="groupedToday.length"></span>
                            </div>
                            
                            <div class="grid gap-3 transition-all" :class="!selectedJob ? 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4' : 'grid-cols-1'">
                                <template x-for="job in groupedToday" :key="job.id">
                                    <div @click="openJob(job.id)" 
                                         :class="selectedJob?.id == job.id ? 'ring-4 ring-amber-500 shadow-xl bg-amber-50/30 border-amber-400' : 'border-slate-300 bg-white hover:border-amber-400 hover:shadow-lg'"
                                         class="job-card p-4 rounded-2xl border-2 transition-all flex flex-col gap-4 relative overflow-hidden group cursor-pointer shadow-md">
                                        
                                        <!-- Header -->
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <h3 class="text-lg font-black text-slate-900 truncate leading-tight" x-text="job.customer_name"></h3>
                                                <!-- Address -->
                                                <div class="flex items-start gap-1.5 mt-2">
                                                    <span class="material-symbols-rounded text-red-600 shrink-0" style="font-size:18px">location_on</span>
                                                    <span class="text-[13px] font-bold text-slate-700 leading-snug" x-text="job.address || 'Address not provided'"></span>
                                                </div>
                                                
                                                <div class="text-[12px] font-extrabold text-slate-600 mt-2.5 flex items-center gap-2 flex-wrap">
                                                    <div class="flex items-center gap-1 bg-amber-100 text-amber-900 px-2 py-1 rounded-md border border-amber-300 shadow-sm">
                                                        <span class="material-symbols-rounded text-amber-700" style="font-size:16px">calendar_today</span>
                                                        <span x-text="'Today'"></span>
                                                    </div>
                                                    <div class="flex items-center gap-1 bg-amber-100 text-amber-900 px-2 py-1 rounded-md border border-amber-300 shadow-sm">
                                                        <span class="material-symbols-rounded text-amber-700" style="font-size:16px">schedule</span>
                                                        <span x-text="job.installation_time_slot || 'TBC'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="text-[12px] font-black text-white bg-slate-800 px-2.5 py-1.5 rounded-lg shrink-0 shadow-md" x-text="'ID: ' + job.id"></span>
                                        </div>
                                        
                                        <!-- System Info (High Contrast Badges) -->
                                        <div class="flex flex-col gap-2 pt-3 border-t-2 border-slate-100">
                                            <!-- Components -->
                                            <div class="flex flex-col gap-1.5">
                                                <template x-if="job.panels">
                                                    <div class="flex items-start gap-2 bg-blue-50 p-2 rounded-lg border border-blue-200">
                                                        <span class="material-symbols-rounded text-blue-700 shrink-0" style="font-size:18px">solar_power</span>
                                                        <span class="text-[11px] font-bold text-blue-900 leading-tight" x-text="job.panels"></span>
                                                    </div>
                                                </template>
                                                <template x-if="job.inverter">
                                                    <div class="flex items-start gap-2 bg-indigo-50 p-2 rounded-lg border border-indigo-200">
                                                        <span class="material-symbols-rounded text-indigo-700 shrink-0" style="font-size:18px">electric_bolt</span>
                                                        <span class="text-[11px] font-bold text-indigo-900 leading-tight" x-text="job.inverter"></span>
                                                    </div>
                                                </template>
                                                <template x-if="job.battery">
                                                    <div class="flex items-start gap-2 bg-teal-50 p-2 rounded-lg border border-teal-200">
                                                        <span class="material-symbols-rounded text-teal-700 shrink-0" style="font-size:18px">battery_charging_full</span>
                                                        <span class="text-[11px] font-bold text-teal-900 leading-tight" x-text="job.battery"></span>
                                                    </div>
                                                </template>
                                            </div>
                                            
                                            <!-- Installer -->
                                            <template x-if="job.assigned_installer_name">
                                                <div class="flex items-center gap-1.5 bg-slate-800 text-white px-3 py-2 rounded-lg shadow-sm mt-1">
                                                    <span class="material-symbols-rounded text-amber-400 shrink-0" style="font-size:16px">engineering</span>
                                                    <span class="text-[12px] font-bold truncate" x-text="'Installer: ' + job.assigned_installer_name"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Scheduled Jobs -->
                        <div x-show="groupedUpcoming.length > 0">
                            <div class="flex items-center justify-between mb-4 px-1">
                                <div class="flex items-center gap-2">
                                    <span class="material-symbols-rounded text-blue-500" style="font-size:22px">event_upcoming</span>
                                    <h2 class="text-sm font-black text-slate-800 uppercase tracking-widest">Upcoming Jobs</h2>
                                </div>
                                <span class="text-xs font-bold text-slate-500 bg-white shadow-sm px-3 py-1 rounded-full border border-slate-200" x-text="groupedUpcoming.length"></span>
                            </div>
                            
                            <div class="grid gap-3 transition-all" :class="!selectedJob ? 'grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4' : 'grid-cols-1'">
                                <template x-for="job in groupedUpcoming" :key="job.id">
                                    <div @click="openJob(job.id)" 
                                         :class="selectedJob?.id == job.id ? 'ring-4 ring-blue-600 shadow-xl bg-blue-50/50 border-blue-500' : 'border-slate-300 bg-white hover:border-blue-400 hover:shadow-lg'"
                                         class="job-card p-4 rounded-2xl border-2 transition-all flex flex-col gap-4 relative overflow-hidden group cursor-pointer shadow-md">
                                        
                                        <!-- Header -->
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <h3 class="text-lg font-black text-slate-900 truncate leading-tight" x-text="job.customer_name"></h3>
                                                <!-- Address -->
                                                <div class="flex items-start gap-1.5 mt-2">
                                                    <span class="material-symbols-rounded text-red-600 shrink-0" style="font-size:18px">location_on</span>
                                                    <span class="text-[13px] font-bold text-slate-700 leading-snug" x-text="job.address || 'Address not provided'"></span>
                                                </div>
                                                
                                                <div class="text-[12px] font-extrabold text-slate-600 mt-2.5 flex items-center gap-2 flex-wrap">
                                                    <div class="flex items-center gap-1 bg-slate-100 px-2 py-1 rounded-md border border-slate-300">
                                                        <span class="material-symbols-rounded text-slate-600" style="font-size:16px">calendar_today</span>
                                                        <span x-text="new Date(job.installation_date).toLocaleDateString('en-US', {weekday: 'short', month: 'short', day: 'numeric'})"></span>
                                                    </div>
                                                    <div class="flex items-center gap-1 bg-amber-100 text-amber-900 px-2 py-1 rounded-md border border-amber-400 shadow-sm">
                                                        <span class="material-symbols-rounded text-amber-700" style="font-size:16px">schedule</span>
                                                        <span x-text="job.installation_time_slot || 'TBC'"></span>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="text-[12px] font-black text-white bg-slate-800 px-2.5 py-1.5 rounded-lg shrink-0 shadow-md" x-text="'ID: ' + job.id"></span>
                                        </div>
                                        
                                        <!-- System Info (High Contrast Badges) -->
                                        <div class="flex flex-col gap-2 pt-3 border-t-2 border-slate-100">
                                            <!-- Components -->
                                            <div class="flex flex-col gap-1.5">
                                                <template x-if="job.panels">
                                                    <div class="flex items-start gap-2 bg-blue-50 p-2 rounded-lg border border-blue-200">
                                                        <span class="material-symbols-rounded text-blue-700 shrink-0" style="font-size:18px">solar_power</span>
                                                        <span class="text-[11px] font-bold text-blue-900 leading-tight" x-text="job.panels"></span>
                                                    </div>
                                                </template>
                                                <template x-if="job.inverter">
                                                    <div class="flex items-start gap-2 bg-indigo-50 p-2 rounded-lg border border-indigo-200">
                                                        <span class="material-symbols-rounded text-indigo-700 shrink-0" style="font-size:18px">electric_bolt</span>
                                                        <span class="text-[11px] font-bold text-indigo-900 leading-tight" x-text="job.inverter"></span>
                                                    </div>
                                                </template>
                                                <template x-if="job.battery">
                                                    <div class="flex items-start gap-2 bg-teal-50 p-2 rounded-lg border border-teal-200">
                                                        <span class="material-symbols-rounded text-teal-700 shrink-0" style="font-size:18px">battery_charging_full</span>
                                                        <span class="text-[11px] font-bold text-teal-900 leading-tight" x-text="job.battery"></span>
                                                    </div>
                                                </template>
                                            </div>
                                            
                                            <!-- Installer -->
                                            <template x-if="job.assigned_installer_name">
                                                <div class="flex items-center gap-1.5 bg-slate-800 text-white px-3 py-2 rounded-lg shadow-sm mt-1">
                                                    <span class="material-symbols-rounded text-amber-400 shrink-0" style="font-size:16px">engineering</span>
                                                    <span class="text-[12px] font-bold truncate" x-text="'Installer: ' + job.assigned_installer_name"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Empty State if no jobs at all -->
                        <template x-if="groupedToday.length === 0 && groupedUpcoming.length === 0">
                            <div class="flex flex-col items-center justify-center py-20">
                                <div class="w-20 h-20 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                    <span class="material-symbols-rounded text-slate-300" style="font-size:40px">inbox</span>
                                </div>
                                <h3 class="text-lg font-bold text-slate-700">No Leads Available</h3>
                                <p class="text-sm text-slate-400 mt-1">There are currently no jobs assigned.</p>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- ===== RIGHT PANEL: JOB DETAIL ===== -->
    <div class="flex-1 flex flex-col overflow-hidden bg-slate-50/50" :class="!selectedJob && 'hidden lg:flex'">
        
        <!-- Empty State -->
        <template x-if="!selectedJob">
            <div class="flex-1 flex items-center justify-center p-6 md:p-12">
                <div class="w-full h-full max-h-[600px] max-w-[800px] bg-white rounded-[2rem] border-2 border-dashed border-slate-200 shadow-sm flex flex-col items-center justify-center text-center p-8 transition-all hover:border-amber-300 hover:bg-amber-50/10">
                    <div class="w-32 h-32 bg-gradient-to-br from-slate-50 to-slate-100 rounded-full flex items-center justify-center mx-auto mb-8 shadow-inner border border-slate-200/60">
                        <span class="material-symbols-rounded text-slate-300" style="font-size:64px">touch_app</span>
                    </div>
                    <h2 class="text-3xl font-extrabold text-slate-800 mb-4 tracking-tight">Workspace Ready</h2>
                    <p class="text-slate-500 font-medium text-base leading-relaxed max-w-md mx-auto">
                        Select a job from the list on the left to view the installation details, access job sheets, upload compliance photos, and generate SWMS reports.
                    </p>
                </div>
            </div>
        </template>

        <!-- Job Detail Content -->
        <template x-if="selectedJob">
            <div class="flex-1 flex flex-col h-full bg-white relative">
                
                <!-- Detail Header -->
                <div class="p-5 border-b border-slate-200 bg-white sticky top-0 z-20 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <button @click="selectedJob = null" class="lg:hidden flex items-center gap-1 text-slate-400 hover:text-slate-600 font-semibold text-xs mb-3 bg-slate-50 px-2 py-1 rounded-lg border border-slate-200">
                                <span class="material-symbols-rounded" style="font-size:16px">arrow_back</span> Back to list
                            </button>
                            <h2 class="text-lg font-bold text-slate-900" x-text="selectedJob.customer_name"></h2>
                            <div class="flex items-center gap-2 mt-1 flex-wrap">
                                <span class="text-[10px] font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-md border border-amber-100" x-text="'#SLR-' + selectedJob.id"></span>
                                <span class="text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200 flex items-center gap-0.5">
                                    <span class="material-symbols-rounded" style="font-size:11px">schedule</span>
                                    <span x-text="selectedJob.installation_time_slot || 'TBC'"></span>
                                </span>
                                <span x-show="selectedJob.assigned_installer_name" class="text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200 flex items-center gap-0.5">
                                    <span class="material-symbols-rounded" style="font-size:11px">person</span>
                                    <span x-text="selectedJob.assigned_installer_name || selectedJob.installation_team"></span>
                                </span>
                            </div>
                        </div>
                        <!-- Action Buttons -->
                        <div class="flex items-center gap-2">
                            <a :href="selectedJob.job_sheet_url" target="_blank" x-show="selectedJob.job_sheet_url" class="px-3 py-2 rounded-lg bg-blue-50 text-blue-600 font-semibold text-xs hover:bg-blue-100 border border-blue-200 flex items-center gap-1.5 transition-colors">
                                <span class="material-symbols-rounded" style="font-size:14px">description</span> Job Sheet
                            </a>
                            <button @click="saveDraft()" :disabled="isSaving" class="px-3 py-2 rounded-lg bg-slate-100 text-slate-600 font-semibold text-xs hover:bg-slate-200 border border-slate-200 flex items-center gap-1.5 transition-colors">
                                <span class="material-symbols-rounded" style="font-size:14px">draft</span> Draft
                            </button>
                            <button @click="saveJob()" :disabled="isSaving" 
                                    :class="isSaving ? 'opacity-60' : 'hover:bg-amber-600 active:scale-[0.98]'" 
                                    class="px-4 py-2 rounded-lg bg-amber-500 text-white font-semibold text-xs transition-all flex items-center gap-1.5 shadow-sm">
                                <span class="material-symbols-rounded" style="font-size:14px" :class="isSaving && 'animate-spin'" x-text="isSaving ? 'sync' : 'save'"></span>
                                <span x-text="isSaving ? 'Saving...' : 'Save'"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Scrollable Detail Body -->
                <div class="flex-1 overflow-y-auto p-5 space-y-4">

                    <!-- Job Details Card -->
                    <div class="section-card p-4">
                        <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-blue-500" style="font-size:16px">info</span> Job Details
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                            <div class="bg-amber-50/70 p-2.5 rounded-lg border border-amber-100" x-show="selectedJob.panels">
                                <span class="text-[9px] font-bold text-amber-600 uppercase block">Panels</span>
                                <span class="text-xs font-semibold text-amber-900" x-text="selectedJob.panels"></span>
                            </div>
                            <div class="bg-violet-50/70 p-2.5 rounded-lg border border-violet-100" x-show="selectedJob.inverter">
                                <span class="text-[9px] font-bold text-violet-600 uppercase block">Inverter</span>
                                <span class="text-xs font-semibold text-violet-900" x-text="selectedJob.inverter"></span>
                            </div>
                            <div class="bg-teal-50/70 p-2.5 rounded-lg border border-teal-100" x-show="selectedJob.battery">
                                <span class="text-[9px] font-bold text-teal-600 uppercase block">Battery</span>
                                <span class="text-xs font-semibold text-teal-900" x-text="selectedJob.battery"></span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                <span class="text-[9px] font-bold text-slate-400 uppercase block">System</span>
                                <span class="text-sm font-bold text-slate-800" x-text="(selectedJob.dc_output_kw || '0') + ' kW'"></span>
                            </div>
                            <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                                <span class="text-[9px] font-bold text-slate-400 uppercase block">Storage</span>
                                <span class="text-sm font-bold text-slate-800" x-text="(selectedJob.storage_kwh || '0') + ' kWh'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Job Sheet (Pylon) -->
                    <div class="section-card p-4">
                        <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-amber-500" style="font-size:16px">description</span> Job Sheet
                        </h3>
                        <template x-if="selectedJob.pylon_project_id">
                            <a :href="'https://app.getpylon.com/app/projects/' + selectedJob.pylon_project_id" target="_blank" 
                               class="flex items-center gap-3 bg-amber-50 p-3 rounded-lg border border-amber-200 hover:bg-amber-100 transition-colors group">
                                <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
                                    <span class="material-symbols-rounded text-amber-600" style="font-size:20px">open_in_new</span>
                                </div>
                                <div class="min-w-0">
                                    <span class="text-sm font-bold text-amber-700 group-hover:text-amber-800 block">Open Pylon Job Sheet</span>
                                    <span class="text-[10px] text-amber-500 block truncate" x-text="'https://app.getpylon.com/app/projects/' + selectedJob.pylon_project_id"></span>
                                </div>
                            </a>
                        </template>
                        <template x-if="!selectedJob.pylon_project_id">
                            <div class="bg-slate-50 p-4 rounded-lg border border-slate-100 text-center">
                                <span class="material-symbols-rounded text-slate-300" style="font-size:28px">description</span>
                                <p class="text-xs text-slate-400 font-medium mt-1">No Pylon Job Sheet linked</p>
                            </div>
                        </template>
                    </div>

                    <!-- Pre-Install Section -->
                    <div class="section-card p-4">
                        <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-orange-500" style="font-size:16px">photo_camera</span> Pre-Install
                            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded-full ml-1" x-text="prePhotos.length + ' photos'"></span>
                        </h3>
                        
                        <!-- Photo Carousel -->
                        <div class="relative mb-3" x-show="prePhotos.length > 0">
                            <div class="carousel-container flex gap-2 overflow-x-auto pb-2 px-1" x-ref="preCarousel">
                                <template x-for="(photo, idx) in prePhotos" :key="'pre-'+idx">
                                    <div class="w-[72px] h-[72px] flex-none rounded-lg overflow-hidden border-2 border-slate-200 cursor-pointer photo-thumb hover:border-amber-400" 
                                         @click="openViewer(prePhotos, idx)">
                                        <img :src="photo" class="w-full h-full object-cover" loading="lazy"/>
                                    </div>
                                </template>
                            </div>
                            <!-- Carousel Arrows -->
                            <button x-show="prePhotos.length > 4" @click="$refs.preCarousel.scrollBy({left:-200,behavior:'smooth'})" 
                                    class="absolute left-0 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white shadow-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 z-10 -ml-2">
                                <span class="material-symbols-rounded" style="font-size:18px">chevron_left</span>
                            </button>
                            <button x-show="prePhotos.length > 4" @click="$refs.preCarousel.scrollBy({left:200,behavior:'smooth'})" 
                                    class="absolute right-0 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white shadow-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 z-10 -mr-2">
                                <span class="material-symbols-rounded" style="font-size:18px">chevron_right</span>
                            </button>
                        </div>

                        <!-- Upload -->
                        <div class="relative mb-3">
                            <input type="file" accept="image/*" multiple capture="environment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="handleUpload($event, 'pre')">
                            <div class="w-full bg-slate-50 border-2 border-dashed border-slate-300 rounded-lg p-3 flex items-center justify-center gap-2 text-slate-400 hover:bg-amber-50 hover:border-amber-300 hover:text-amber-500 transition-all">
                                <span class="material-symbols-rounded" style="font-size:18px">add_a_photo</span>
                                <span class="text-xs font-semibold" x-text="preUploadStatus"></span>
                            </div>
                        </div>

                        <!-- Pre-Install Notes -->
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Pre-Install Notes</label>
                            <textarea x-model="form.pre_notes" rows="2" placeholder="Roof type, access, meter box..." 
                                class="w-full bg-slate-50 border border-slate-200 text-xs font-medium text-slate-700 rounded-lg p-2.5 focus:ring-2 focus:ring-amber-500/20 focus:border-amber-400 outline-none resize-none placeholder:text-slate-400"></textarea>
                        </div>
                    </div>

                    <!-- Post-Install Section -->
                    <div class="section-card p-4">
                        <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3 flex items-center gap-1.5">
                            <span class="material-symbols-rounded text-emerald-500" style="font-size:16px">check_circle</span> Post-Install
                            <span class="text-[10px] font-bold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded-full ml-1" x-text="postPhotos.length + ' photos'"></span>
                        </h3>

                        <!-- Photo Carousel -->
                        <div class="relative mb-3" x-show="postPhotos.length > 0">
                            <div class="carousel-container flex gap-2 overflow-x-auto pb-2 px-1" x-ref="postCarousel">
                                <template x-for="(photo, idx) in postPhotos" :key="'post-'+idx">
                                    <div class="w-[72px] h-[72px] flex-none rounded-lg overflow-hidden border-2 border-slate-200 cursor-pointer photo-thumb hover:border-emerald-400" 
                                         @click="openViewer(postPhotos, idx)">
                                        <img :src="photo" class="w-full h-full object-cover" loading="lazy"/>
                                    </div>
                                </template>
                            </div>
                            <button x-show="postPhotos.length > 4" @click="$refs.postCarousel.scrollBy({left:-200,behavior:'smooth'})" 
                                    class="absolute left-0 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white shadow-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 z-10 -ml-2">
                                <span class="material-symbols-rounded" style="font-size:18px">chevron_left</span>
                            </button>
                            <button x-show="postPhotos.length > 4" @click="$refs.postCarousel.scrollBy({left:200,behavior:'smooth'})" 
                                    class="absolute right-0 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white shadow-lg border border-slate-200 flex items-center justify-center text-slate-600 hover:bg-slate-50 z-10 -mr-2">
                                <span class="material-symbols-rounded" style="font-size:18px">chevron_right</span>
                            </button>
                        </div>

                        <!-- Upload -->
                        <div class="relative mb-3">
                            <input type="file" accept="image/*" multiple capture="environment" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" @change="handleUpload($event, 'post')">
                            <div class="w-full bg-slate-50 border-2 border-dashed border-slate-300 rounded-lg p-3 flex items-center justify-center gap-2 text-slate-400 hover:bg-emerald-50 hover:border-emerald-300 hover:text-emerald-500 transition-all">
                                <span class="material-symbols-rounded" style="font-size:18px">add_a_photo</span>
                                <span class="text-xs font-semibold" x-text="postUploadStatus"></span>
                            </div>
                        </div>

                        <!-- Post-Install Notes -->
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1">Post-Install Notes</label>
                            <textarea x-model="form.post_notes" rows="2" placeholder="Completion, issues, sign-off..." 
                                class="w-full bg-slate-50 border border-slate-200 text-xs font-medium text-slate-700 rounded-lg p-2.5 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-400 outline-none resize-none placeholder:text-slate-400"></textarea>
                        </div>
                    </div>

                    <!-- SWMS (Digital Signature) -->
                    <div class="section-card p-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="material-symbols-rounded text-red-500" style="font-size:16px">shield</span> SWMS Signatures
                            </h3>
                            <button @click="addNewSignature()" x-show="!showNewSignatureForm" class="text-[10px] font-bold text-blue-600 bg-blue-50 px-2 py-1 rounded border border-blue-200 hover:bg-blue-100">+ Add Contractor</button>
                        </div>
                        <p class="text-[10px] text-slate-400 mb-4">Safe Work Method Statement must be acknowledged and signed by all sub-contractors.</p>
                        
                        <!-- List of Saved Signatures -->
                        <div class="space-y-3 mb-4" x-show="signatures.length > 0">
                            <template x-for="(sig, index) in signatures" :key="index">
                                <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 flex items-center justify-between">
                                    <div>
                                        <div class="text-[11px] font-bold text-slate-700 uppercase" x-text="sig.contractor_name"></div>
                                        <div class="text-[10px] text-slate-500" x-text="sig.signature_date"></div>
                                    </div>
                                    <div class="flex flex-col items-end">
                                        <img :src="sig.signature_data" class="h-10 object-contain bg-white border border-slate-200 rounded" />
                                        <span class="text-[9px] font-bold text-emerald-600 flex items-center gap-0.5 mt-1">
                                            <span class="material-symbols-rounded" style="font-size:11px">check_circle</span> Verified
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- New Signature Form -->
                        <div class="space-y-4 bg-white border-2 border-slate-100 rounded-lg p-3" x-show="showNewSignatureForm">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[10px] font-bold text-slate-600 uppercase">New Signature</span>
                                <button x-show="signatures.length > 0" @click="cancelSignature()" class="text-[10px] text-slate-400 hover:text-slate-600">Cancel</button>
                            </div>
                            
                            <!-- Input Fields -->
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Contractor Name</label>
                                    <input type="text" x-model="swmsForm.contractor_name" placeholder="Enter full name"
                                        class="w-full bg-slate-50 border border-slate-200 text-xs font-medium text-slate-700 rounded-lg p-2 focus:ring-2 focus:ring-red-500/20 focus:border-red-400 outline-none">
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Date</label>
                                    <input type="date" x-model="swmsForm.signature_date"
                                        class="w-full bg-slate-50 border border-slate-200 text-xs font-medium text-slate-700 rounded-lg p-2 focus:ring-2 focus:ring-red-500/20 focus:border-red-400 outline-none">
                                </div>
                            </div>

                            <!-- Canvas Signature Pad -->
                            <div>
                                <label class="text-[10px] font-bold text-slate-500 uppercase block mb-1">Signature</label>
                                <div class="w-full h-32 bg-slate-50 border border-slate-200 rounded-lg relative touch-none shadow-inner" style="touch-action: none;">
                                    <canvas x-ref="sigCanvas" class="absolute inset-0 w-full h-full cursor-crosshair rounded-lg"></canvas>
                                    <div class="absolute bottom-2 left-2 text-[10px] text-slate-300 pointer-events-none">Sign here</div>
                                    <button @click="clearSignature()" type="button" class="absolute top-2 right-2 text-[10px] bg-slate-200 hover:bg-slate-300 text-slate-600 px-2 py-0.5 rounded shadow-sm z-10 transition-colors">Clear</button>
                                </div>
                            </div>

                            <div class="flex justify-between items-center pt-2">
                                <span class="text-xs font-semibold text-slate-400">Pending Signature</span>
                                <button @click="saveSignature()" type="button" class="px-4 py-1.5 bg-red-500 hover:bg-red-600 text-white text-xs font-bold rounded-lg shadow-sm transition-colors flex items-center gap-1">
                                    <span class="material-symbols-rounded" style="font-size:14px">save</span> Save SWMS
                                </button>
                            </div>
                        </div>
                        
                        <!-- Generate SWMS Report Button -->
                        <div class="mt-4 pt-3 border-t border-slate-200" x-show="signatures.length > 0">
                            <button @click="generateSWMSPDF()" :disabled="isGeneratingPDF" class="w-full py-2.5 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2">
                                <span class="material-symbols-rounded" style="font-size:16px" :class="isGeneratingPDF && 'animate-spin'" x-text="isGeneratingPDF ? 'sync' : 'picture_as_pdf'"></span>
                                <span x-text="isGeneratingPDF ? 'Generating...' : 'Generate SWMS PDF Report'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Finalize Installation -->
                    <div class="section-card p-4 mt-4">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-1.5">
                                <span class="material-symbols-rounded text-emerald-500" style="font-size:16px">task_alt</span> Finalize Installation
                            </h3>
                        </div>
                        
                        <div class="flex flex-col gap-3">
                            <button @click="generatePhotosPDF()" :disabled="isGeneratingPhotos" class="w-full py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2">
                                <span class="material-symbols-rounded" style="font-size:16px" :class="isGeneratingPhotos && 'animate-spin'" x-text="isGeneratingPhotos ? 'sync' : 'photo_library'"></span>
                                <span x-text="isGeneratingPhotos ? 'Generating...' : 'Download Photos PDF'"></span>
                            </button>

                            <button @click="completeJob()" :disabled="isCompleting" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg shadow-sm transition-colors flex items-center justify-center gap-2">
                                <span class="material-symbols-rounded" style="font-size:16px" :class="isCompleting && 'animate-spin'" x-text="isCompleting ? 'sync' : 'check_circle'"></span>
                                <span x-text="isCompleting ? 'Completing...' : 'Complete Installation'"></span>
                            </button>
                        </div>
                    </div>

                    <!-- Bottom spacer for scroll comfort -->
                    <div class="h-4"></div>
                </div>
            </div>
        </template>
    </div>
</div>
<script>
const allJobsData = <?php echo $jobs_json; ?>;

function installerApp() {
    return {
        toast: { msg:'', icon:'check_circle', spin:false },
        viewer: { open:false, photos:[], index:0, zoom:1 },
        selectedJob: null,
        isSaving: false,
        isGeneratingPDF: false,
        isGeneratingPhotos: false,
        isCompleting: false,
        searchQuery: '',
        
        prePhotos: [],
        postPhotos: [],
        preUploadStatus: 'Upload Pre-Install',
        postUploadStatus: 'Upload Post-Install',

        form: { swms:'', pre_notes:'', post_notes:'' },
        
        swmsForm: {
            contractor_name: '',
            signature_date: new Date().toISOString().split('T')[0],
            signature_data: '',
            is_signed: false
        },

        jobs: allJobsData,
        isFullscreen: false,
        activeDate: null,

        get groupedToday() {
            if (!this.searchQuery) {
                const todayStr = new Date().toISOString().split('T')[0];
                return this.jobs.filter(j => {
                    if (!j.installation_date) return false;
                    const d = new Date(j.installation_date);
                    return d.toISOString().split('T')[0] === todayStr;
                });
            }
            return [];
        },

        get groupedUpcoming() {
            if (!this.searchQuery) {
                const todayStr = new Date().toISOString().split('T')[0];
                return this.jobs.filter(j => {
                    if (!j.installation_date) return false;
                    const d = new Date(j.installation_date);
                    return d.toISOString().split('T')[0] > todayStr;
                });
            }
            return [];
        },

        get filteredJobs() {
            if (!this.searchQuery) return [];
            const q = this.searchQuery.toLowerCase();
            return this.jobs.filter(j => 
                (j.customer_name && j.customer_name.toLowerCase().includes(q)) || 
                (j.id && j.id.toString().includes(q))
            );
        },

        init() {
            document.addEventListener('fullscreenchange', () => {
                this.isFullscreen = !!document.fullscreenElement;
            });
            
            // Initialize activeDate
            const sorted = this.groupedJobs;
            if (sorted.length > 0) {
                const today = new Date();
                today.setHours(0,0,0,0);
                const todayStr = today.toISOString().split('T')[0];
                const hasToday = sorted.find(g => g.date === todayStr);
                this.activeDate = hasToday ? todayStr : sorted[0].date;
            }

            this.$watch('swmsForm.is_signed', val => {
                if (!val) {
                    this.$nextTick(() => {
                        this.swmsForm.signature_data = '';
                        setTimeout(() => this.initSignaturePad(), 350);
                    });
                }
            });
        },

        toggleFullscreen() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen().catch(e => {
                    this.showToast('Fullscreen not supported here', 'error');
                });
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                }
            }
        },

        showToast(msg, icon='check_circle', spin=false, dur=2500) {
            this.toast = { msg, icon, spin };
            if (!spin) setTimeout(() => this.toast.msg='', dur);
        },

        openJob(id) {
            const job = this.jobs.find(j => j.id === id);
            if (job) {
                this.selectedJob = job;
                this.prePhotos = job.pre_photos || [];
                this.postPhotos = job.post_photos || [];
                this.form.pre_notes = job.pre_notes || '';
                this.form.post_notes = job.post_notes || '';
                this.form.swms = job.swms_status || '';
                this.preUploadStatus = 'Upload Pre-Install';
                this.postUploadStatus = 'Upload Post-Install';
                
                this.swmsForm = {
                    contractor_name: '',
                    signature_date: new Date().toISOString().split('T')[0],
                    signature_data: ''
                };
                this.signatures = job.swms_signatures || [];
                this.showNewSignatureForm = this.signatures.length === 0;

                if (this.showNewSignatureForm) {
                    this.$nextTick(() => {
                        setTimeout(() => this.initSignaturePad(), 350);
                    });
                }
            }
        },

        closeJob() {
            this.selectedJob = null;
        },

        initSignaturePad() {
            if (this.$refs.sigCanvas) {
                const canvas = this.$refs.sigCanvas;
                const ctx = canvas.getContext('2d');
                let isDrawing = false;
                
                // Resize canvas to match display size
                const rect = canvas.parentElement.getBoundingClientRect();
                canvas.width = rect.width;
                canvas.height = rect.height;
                ctx.lineWidth = 3;
                ctx.lineCap = 'round';
                ctx.strokeStyle = '#0f172a'; // slate-900

                const startDrawing = (e) => {
                    isDrawing = true;
                    draw(e);
                };
                const stopDrawing = () => {
                    isDrawing = false;
                    ctx.beginPath();
                };
                const draw = (e) => {
                    if (!isDrawing) return;
                    e.preventDefault(); // Prevent scrolling on touch devices
                    const rect = canvas.getBoundingClientRect();
                    const clientX = e.touches ? e.touches[0].clientX : e.clientX;
                    const clientY = e.touches ? e.touches[0].clientY : e.clientY;
                    ctx.lineTo(clientX - rect.left, clientY - rect.top);
                    ctx.stroke();
                    ctx.beginPath();
                    ctx.moveTo(clientX - rect.left, clientY - rect.top);
                };

                canvas.addEventListener('mousedown', startDrawing);
                canvas.addEventListener('mousemove', draw);
                canvas.addEventListener('mouseup', stopDrawing);
                canvas.addEventListener('mouseout', stopDrawing);
                
                canvas.addEventListener('touchstart', startDrawing, {passive:false});
                canvas.addEventListener('touchmove', draw, {passive:false});
                canvas.addEventListener('touchend', stopDrawing);
            }
        },

        clearSignature() {
            if (this.$refs.sigCanvas) {
                const canvas = this.$refs.sigCanvas;
                const ctx = canvas.getContext('2d');
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        },

        saveSignature() {
            if (!this.swmsForm.contractor_name) {
                this.showToast('Please enter contractor name', 'error');
                return;
            }
            if (this.$refs.sigCanvas) {
                const canvas = this.$refs.sigCanvas;
                
                // Check if canvas is empty
                const ctx = canvas.getContext('2d');
                const pixels = ctx.getImageData(0,0,canvas.width,canvas.height).data;
                let isEmpty = true;
                for(let i=3; i<pixels.length; i+=4) { if(pixels[i] !== 0) { isEmpty=false; break; } }
                
                if (isEmpty) {
                    this.showToast('Please provide a signature', 'error');
                    return;
                }
                
                this.swmsForm.signature_data = canvas.toDataURL('image/png');
                this.isSaving = true;
                fetch('api/save_swms.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        project_id: this.selectedJob.id,
                        contractor_name: this.swmsForm.contractor_name,
                        signature_date: this.swmsForm.signature_date,
                        signature_data: this.swmsForm.signature_data
                    })
                }).then(res=>res.json()).then(data => {
                    this.isSaving = false;
                    if(data.success) {
                        this.showToast('Signature saved!');
                        // Add to list
                        this.signatures.push({
                            id: data.new_id || Date.now(),
                            contractor_name: this.swmsForm.contractor_name,
                            signature_date: this.swmsForm.signature_date,
                            signature_data: this.swmsForm.signature_data
                        });
                        // Reset form
                        this.swmsForm.contractor_name = '';
                        this.swmsForm.signature_data = '';
                        this.showNewSignatureForm = false;
                    } else {
                        this.showToast(data.message || 'Error saving signature', 'error');
                    }
                }).catch(err => {
                    this.isSaving = false;
                    this.showToast('Network error', 'error');
                });
            }
        },

        cancelSignature() {
            this.showNewSignatureForm = false;
        },

        addNewSignature() {
            this.showNewSignatureForm = true;
            this.swmsForm.contractor_name = '';
            this.swmsForm.signature_data = '';
            this.$nextTick(() => {
                setTimeout(() => this.initSignaturePad(), 50);
            });
        },

        generateSWMSPDF() {
            this.isGeneratingPDF = true;
            fetch('api/generate_swms.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ project_id: this.selectedJob.id })
            }).then(res=>res.json()).then(data => {
                this.isGeneratingPDF = false;
                if(data.success) {
                    this.showToast('PDF Generated successfully!');
                    window.open(data.url, '_blank');
                } else {
                    this.showToast(data.message || 'Error generating PDF', 'error');
                }
            }).catch(err => {
                this.isGeneratingPDF = false;
                this.showToast('Network error', 'error');
            });
        },

        generatePhotosPDF() {
            this.isGeneratingPhotos = true;
            fetch('api/generate_photos_pdf.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ project_id: this.selectedJob.id })
            }).then(res=>res.json()).then(data => {
                this.isGeneratingPhotos = false;
                if(data.success) {
                    this.showToast('Photos PDF Generated!');
                    window.open(data.url, '_blank');
                } else {
                    this.showToast(data.message || 'Error generating Photos PDF', 'error');
                }
            }).catch(err => {
                this.isGeneratingPhotos = false;
                this.showToast('Network error', 'error');
            });
        },

        completeJob() {
            if(!confirm('Are you sure you want to complete this installation? An email will be sent to the office.')) return;
            
            this.isCompleting = true;
            fetch('api/complete_job.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({ project_id: this.selectedJob.id })
            }).then(res=>res.json()).then(data => {
                this.isCompleting = false;
                if(data.success) {
                    this.showToast('Job marked as completed!');
                    // Optionally remove from list or mark as done
                    setTimeout(() => window.location.reload(), 1500);
                } else {
                    this.showToast(data.message || 'Error completing job', 'error');
                }
            }).catch(err => {
                this.isCompleting = false;
                this.showToast('Network error', 'error');
            });
        },

        openViewer(photos, index) {
            this.viewer = { open:true, photos:[...photos], index, zoom:1 };
        },
        viewerPrev() {
            if (this.viewer.index > 0) { this.viewer.index--; this.viewer.zoom = 1; }
        },
        viewerNext() {
            if (this.viewer.index < this.viewer.photos.length - 1) { this.viewer.index++; this.viewer.zoom = 1; }
        },

        async handleUpload(event, type) {
            const files = event.target.files;
            if (!files || !files.length) return;
            
            if (type === 'pre') this.preUploadStatus = 'Uploading ' + files.length + '...';
            else this.postUploadStatus = 'Uploading ' + files.length + '...';

            const fd = new FormData();
            fd.append('crm_project_id', this.selectedJob.id);
            fd.append('type', type);
            for (let f of files) fd.append('photos[]', f);

            try {
                const resp = await fetch('api/upload_photos.php', { method:'POST', body:fd });
                const result = await resp.json();
                if (result.success) {
                    for (let f of files) {
                        const url = URL.createObjectURL(f);
                        if (type === 'pre') this.prePhotos.push(url);
                        else this.postPhotos.push(url);
                    }
                    const msg = result.files.length + ' saved ✓';
                    if (type === 'pre') this.preUploadStatus = msg;
                    else this.postUploadStatus = msg;
                    this.showToast(result.message);
                } else {
                    if (type === 'pre') this.preUploadStatus = 'Failed';
                    else this.postUploadStatus = 'Failed';
                    this.showToast('Upload failed', 'error');
                }
            } catch (e) {
                if (type === 'pre') this.preUploadStatus = 'Error';
                else this.postUploadStatus = 'Error';
                this.showToast('Network error', 'error');
            }
            event.target.value = '';
        },

        async saveDraft() {
            this.isSaving = true;
            await this._saveData();
            this.showToast('Draft saved');
            this.isSaving = false;
        },

        async saveJob() {
            if (!this.form.swms) {
                this.showToast('Select SWMS status first', 'warning', false, 2500);
                return;
            }
            this.isSaving = true;
            const ok = await this._saveData();
            if (ok) {
                this.selectedJob.swms_status = 'verified';
                this.selectedJob.pre_notes = this.form.pre_notes;
                this.selectedJob.post_notes = this.form.post_notes;
                this.showToast('Saved ✓');
            }
            this.isSaving = false;
        },

        async _saveData() {
            const payload = {
                crm_project_id: this.selectedJob.id,
                swms: this.form.swms,
                photos: { pre_notes: this.form.pre_notes, post_notes: this.form.post_notes }
            };
            try {
                const resp = await fetch('api/save_installer_data.php', {
                    method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(payload)
                });
                const result = await resp.json();
                return result.success;
            } catch (e) {
                this.showToast('Network error', 'error');
                return false;
            }
        }
    }
}
</script>
</body>
</html>
