<?php
require_once 'includes/db.php';

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'overview';

// Fetch Job from CRM
$job = null;
if ($db_crm && $job_id > 0) {
    try {
        $stmt = $db_crm->prepare("SELECT * FROM projects WHERE id = :id");
        $stmt->execute(['id' => $job_id]);
        $job = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$job) {
    header("Location: index.php");
    exit;
}

// Ensure job_compliance record exists
if ($db_installs) {
    try {
        $stmt = $db_installs->prepare("INSERT IGNORE INTO job_compliance (crm_project_id) VALUES (:id)");
        $stmt->execute(['id' => $job['id']]);
        
        $stmt = $db_installs->prepare("SELECT * FROM job_compliance WHERE crm_project_id = :id");
        $stmt->execute(['id' => $job['id']]);
        $compliance = $stmt->fetch();
    } catch (Exception $e) {
        $compliance = ['swms_completed' => 0, 'pre_install_liability_signed' => 0];
    }
} else {
    $compliance = ['swms_completed' => 0, 'pre_install_liability_signed' => 0];
}

?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Job #SLR-<?php echo htmlspecialchars($job['id']); ?> - Precision Fleet Pro</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#e7b008",
                        "primary-hover": "#CA8A04",
                        "background-light": "#F8FAFC",
                        "surface": "#FFFFFF",
                        "text-main": "#0F172A",
                        "text-muted": "#64748B",
                        "border-color": "#E2E8F0",
                        "danger": "#EF4444"
                    },
                    fontFamily: {
                        "display": ["Plus Jakarta Sans", "sans-serif"],
                        "sans": ["Plus Jakarta Sans", "sans-serif"]
                    },
                    boxShadow: {
                        'subtle': '0 2px 4px rgba(15, 23, 42, 0.04)',
                    }
                },
            },
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .filled-icon { font-variation-settings: 'FILL' 1; }
    </style>
</head>
<body class="bg-background-light text-text-main h-screen flex overflow-hidden antialiased">
    <!-- Sidebar -->
    <div class="relative flex h-full w-64 flex-col bg-surface border-r border-border-color shrink-0">
        <div class="flex flex-col h-full py-5 px-4 justify-between">
            <div class="flex flex-col gap-6">
                <div class="flex gap-3 items-center cursor-pointer" onclick="window.location.href='index.php'">
                    <div class="size-10 bg-primary rounded-full flex items-center justify-center text-white">
                        <span class="material-symbols-outlined text-2xl font-bold">solar_power</span>
                    </div>
                    <div class="flex flex-col">
                        <h1 class="text-text-main text-base font-bold leading-tight">Precision Fleet</h1>
                        <p class="text-text-muted text-sm font-medium leading-normal">Solar Command</p>
                    </div>
                </div>
                
                <div class="flex flex-col gap-1 mt-4">
                    <a href="job_detail.php?id=<?php echo $job['id']; ?>&tab=overview" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-sm transition-colors group <?php echo $tab === 'overview' ? 'bg-[#FEF9C3] border-l-4 border-primary' : 'hover:bg-background-light'; ?>">
                        <div class="<?php echo $tab === 'overview' ? 'text-primary filled-icon' : 'text-text-muted group-hover:text-text-main'; ?>">
                            <span class="material-symbols-outlined">dataset</span>
                        </div>
                        <p class="<?php echo $tab === 'overview' ? 'text-text-main font-bold' : 'text-text-muted group-hover:text-text-main font-semibold'; ?> text-sm">Overview</p>
                    </a>
                    
                    <a href="job_detail.php?id=<?php echo $job['id']; ?>&tab=pre-install" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-sm transition-colors group <?php echo $tab === 'pre-install' ? 'bg-[#FEF9C3] border-l-4 border-primary' : 'hover:bg-background-light'; ?>">
                        <div class="<?php echo $tab === 'pre-install' ? 'text-primary filled-icon' : 'text-text-muted group-hover:text-text-main'; ?>">
                            <span class="material-symbols-outlined">photo_camera</span>
                        </div>
                        <p class="<?php echo $tab === 'pre-install' ? 'text-text-main font-bold' : 'text-text-muted group-hover:text-text-main font-semibold'; ?> text-sm">Pre-Install</p>
                    </a>
                    
                    <a href="job_detail.php?id=<?php echo $job['id']; ?>&tab=post-install" 
                       class="flex items-center gap-3 px-3 py-2.5 rounded-sm transition-colors group <?php echo $tab === 'post-install' ? 'bg-[#FEF9C3] border-l-4 border-primary' : 'hover:bg-background-light'; ?>">
                        <div class="<?php echo $tab === 'post-install' ? 'text-primary filled-icon' : 'text-text-muted group-hover:text-text-main'; ?>">
                            <span class="material-symbols-outlined">verified</span>
                        </div>
                        <p class="<?php echo $tab === 'post-install' ? 'text-text-main font-bold' : 'text-text-muted group-hover:text-text-main font-semibold'; ?> text-sm">Post-Install / SWMS</p>
                    </a>
                </div>
            </div>
            <div class="flex items-center gap-3 px-3 py-3 border-t border-border-color mt-4">
                <a href="compliance.php?id=<?php echo $job['id']; ?>" class="w-full flex items-center justify-center gap-2 bg-text-main hover:bg-black text-white px-4 py-2 rounded-sm transition-colors font-bold text-sm tracking-wide">
                    <span class="material-symbols-outlined text-[18px]">fact_check</span>
                    Compliance Sign-off
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col h-full overflow-hidden">
        <!-- Header -->
        <header class="flex items-center justify-between border-b border-border-color bg-surface px-8 py-5 shrink-0 z-10 shadow-subtle">
            <div class="flex flex-col">
                <div class="flex items-center gap-2 mb-1">
                    <span class="material-symbols-outlined text-text-muted text-sm">tag</span>
                    <h2 class="text-text-muted text-sm font-bold tracking-wider uppercase">Job SLR-<?php echo htmlspecialchars($job['id']); ?></h2>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-primary/20 text-primary-hover uppercase ml-2"><?php echo htmlspecialchars($job['status'] ?? 'Scheduled'); ?></span>
                </div>
                <h1 class="text-text-main text-2xl font-bold leading-tight"><?php echo htmlspecialchars($job['address'] ?? 'Address Pending'); ?></h1>
            </div>
            <div class="flex items-center gap-4">
                <button class="flex items-center gap-2 h-10 px-4 bg-primary hover:bg-primary-hover text-surface text-sm font-bold uppercase tracking-[0.05em] rounded-sm transition-colors shadow-sm">
                    <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                    View Job Sheet
                </button>
            </div>
        </header>

        <!-- Scrollable Content -->
        <main class="flex-1 overflow-y-auto p-8 relative">
            <div class="max-w-[1000px] mx-auto">
            
            <?php if ($tab === 'overview'): ?>
                <!-- Overview Content -->
                <div class="flex flex-col gap-8 animate-[slideInRight_0.3s_ease-out_forwards]">
                    <section class="flex flex-col gap-4">
                        <h3 class="text-lg font-bold text-text-main tracking-tight">Team Roster</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div class="bg-surface p-4 rounded-sm border border-border-color shadow-subtle flex items-center justify-center text-text-muted text-sm italic">
                                No crew assigned to this job.
                            </div>
                        </div>
                    </section>

                    <section class="flex flex-col gap-4">
                        <h3 class="text-lg font-bold text-text-main tracking-tight">Hardware Manifest</h3>
                        <div class="bg-surface border border-border-color rounded-sm shadow-subtle overflow-hidden">
                            <table class="w-full text-left">
                                <thead class="bg-background-light border-b border-border-color">
                                    <tr>
                                        <th class="px-4 py-3 text-xs font-semibold text-text-muted uppercase">Component</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-text-muted uppercase text-center">Qty</th>
                                        <th class="px-4 py-3 text-xs font-semibold text-text-muted uppercase w-[40%]">Serial Number(s)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-border-color">
                                    <tr class="hover:bg-background-light/50">
                                        <td class="px-4 py-4 text-sm font-semibold text-text-main"><?php echo htmlspecialchars($job['panels'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-4 text-sm font-bold text-center">24</td>
                                        <td class="px-4 py-3">
                                            <input class="w-full h-10 px-3 text-sm border border-border-color rounded-sm focus:ring-1 focus:ring-primary focus:border-primary" placeholder="Scan or enter SN..." type="text"/>
                                        </td>
                                    </tr>
                                    <tr class="hover:bg-background-light/50">
                                        <td class="px-4 py-4 text-sm font-semibold text-text-main"><?php echo htmlspecialchars($job['inverter'] ?? 'N/A'); ?></td>
                                        <td class="px-4 py-4 text-sm font-bold text-center">1</td>
                                        <td class="px-4 py-3">
                                            <input class="w-full h-10 px-3 text-sm border border-border-color rounded-sm focus:ring-1 focus:ring-primary focus:border-primary" placeholder="Scan or enter SN..." type="text"/>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>
                </div>
            
            <?php elseif ($tab === 'pre-install'): ?>
                <!-- Pre-Install Content -->
                <div class="space-y-6 animate-[slideInRight_0.3s_ease-out_forwards]">
                    <div>
                        <h2 class="text-xl font-bold text-text-main mb-1">Pre-Install Assessment</h2>
                        <p class="text-text-muted text-sm">Document existing site conditions and flag any potential liability issues before commencing installation.</p>
                    </div>
                    
                    <div class="bg-surface border-2 border-dashed border-border-color rounded-sm h-[200px] flex flex-col items-center justify-center cursor-pointer hover:bg-slate-50 transition-all group">
                        <div class="bg-[#F1F5F9] rounded-full p-4 mb-3 group-hover:scale-105 transition-transform duration-200">
                            <span class="material-symbols-outlined text-text-muted text-3xl group-hover:text-text-main">cloud_upload</span>
                        </div>
                        <p class="text-text-main font-semibold text-[15px]">Drag & drop photos here</p>
                        <p class="text-text-muted text-sm mt-1">or click to browse your files (JPG, PNG, max 10MB)</p>
                    </div>

                    <div class="bg-surface border border-border-color rounded-sm p-6 shadow-subtle space-y-5 mt-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-[16px] font-bold text-text-main flex items-center gap-2">
                                    <span class="material-symbols-outlined text-danger">warning</span>
                                    Report Pre-existing Damage
                                </h3>
                                <p class="text-text-muted text-[13px] mt-1">Flag any structural or aesthetic issues present before installation begins.</p>
                            </div>
                            <!-- Fake Toggle -->
                            <div class="w-11 h-6 bg-border-color rounded-full relative cursor-pointer" onclick="this.classList.toggle('bg-danger'); this.children[0].classList.toggle('translate-x-5')">
                                <div class="w-5 h-5 bg-white rounded-full absolute top-0.5 left-0.5 transition-transform"></div>
                            </div>
                        </div>
                        <div class="pt-4 border-t border-border-color">
                            <label class="block text-sm font-semibold text-text-main mb-2">Damage Description</label>
                            <textarea class="w-full rounded-sm border-border-color shadow-sm focus:border-danger focus:ring-danger sm:text-sm p-3 h-[100px] resize-none" placeholder="Describe damage..."></textarea>
                        </div>
                    </div>
                    
                    <div class="flex justify-end gap-4 pt-4 pb-8">
                        <button class="px-8 py-2.5 rounded-sm bg-text-main text-primary text-sm font-bold uppercase tracking-[0.05em] hover:bg-black transition-colors shadow-sm flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px]">save</span> Save Assessment
                        </button>
                    </div>
                </div>

            <?php elseif ($tab === 'post-install'): ?>
                <!-- Post-Install Content -->
                <div class="space-y-6 animate-[slideInRight_0.3s_ease-out_forwards]">
                    <section class="bg-surface rounded-sm shadow-subtle border border-border-color overflow-hidden">
                        <div class="px-6 py-5 border-b border-border-color bg-background-light flex items-center gap-3">
                            <span class="material-symbols-outlined text-primary text-[24px]">verified_user</span>
                            <h3 class="text-lg font-bold text-text-main">SWMS Compliance Sign-off</h3>
                        </div>
                        <div class="p-6">
                            <p class="text-[15px] text-text-muted mb-6 max-w-2xl">Ensure all site personnel have read and understood the Safe Work Method Statement for this installation before proceeding with sign-off.</p>
                            
                            <form action="api/save_compliance.php" method="POST" class="flex flex-col gap-4 max-w-2xl">
                                <input type="hidden" name="crm_project_id" value="<?php echo $job['id']; ?>">
                                <input type="hidden" name="type" value="swms">
                                
                                <div class="flex items-center gap-3 p-4 border border-border-color rounded-sm bg-background-light">
                                    <input type="checkbox" id="swms_check" name="swms_completed" value="1" <?php echo $compliance['swms_completed'] ? 'checked' : ''; ?> class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary">
                                    <label for="swms_check" class="text-sm font-bold text-text-main cursor-pointer">I confirm the SWMS has been reviewed and implemented.</label>
                                </div>

                                <button type="submit" class="self-start px-6 py-2.5 rounded-sm bg-text-main text-primary text-sm font-bold uppercase tracking-[0.05em] hover:bg-black transition-colors shadow-sm flex items-center gap-2">
                                    <span class="material-symbols-outlined text-[18px]">task_alt</span> Submit SWMS
                                </button>
                            </form>
                        </div>
                    </section>
                </div>
            <?php endif; ?>

            </div>
        </main>
    </div>
</body>
</html>
