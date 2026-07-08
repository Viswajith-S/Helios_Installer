<?php
require_once 'includes/db.php';

$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$success = isset($_GET['success']) ? $_GET['success'] : 0;

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

// Fetch compliance record
$compliance = ['final_sign_off_status' => 'Pending'];
if ($db_installs) {
    try {
        $stmt = $db_installs->prepare("SELECT * FROM job_compliance WHERE crm_project_id = :id");
        $stmt->execute(['id' => $job['id']]);
        if ($row = $stmt->fetch()) {
            $compliance = $row;
        }
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html class="light" lang="en">
<head>
    <meta charset="utf-8"/>
    <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
    <title>Compliance - Job #SLR-<?php echo htmlspecialchars($job['id']); ?></title>
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
      .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 300, 'GRAD' 0, 'opsz' 24; }
      .helios-glow { box-shadow: 0 0 20px rgba(231, 176, 8, 0.4); }
    </style>
</head>
<body class="bg-background-slate font-body-md text-on-surface min-h-screen overflow-x-hidden">
    
    <!-- TopAppBar -->
    <header class="flex justify-between items-center px-8 sticky top-0 z-40 h-16 w-full border-b border-border-slate bg-surface-white">
        <div class="flex items-center gap-4 cursor-pointer" onclick="window.location.href='index.php'">
            <span class="material-symbols-outlined text-primary text-3xl" style="font-variation-settings: 'FILL' 1;">solar_power</span>
            <span class="font-headline-md text-xl font-bold text-primary tracking-tight">Precision Fleet Pro</span>
        </div>
        <div class="flex items-center gap-6">
            <a href="job_detail.php?id=<?php echo $job['id']; ?>" class="flex items-center gap-2 text-text-muted hover:text-primary transition-colors">
                <span class="material-symbols-outlined text-[20px]">arrow_back</span>
                <span class="font-bold text-sm">Back to Job Detail</span>
            </a>
        </div>
    </header>

    <main class="p-8 max-w-[1200px] mx-auto">
        <!-- Page Header -->
        <div class="mb-10 flex justify-between items-end">
            <div>
                <div class="flex items-center gap-2 mb-2">
                    <span class="bg-primary text-on-primary px-3 py-1 rounded text-[10px] font-bold tracking-widest uppercase">Compliance Level: High</span>
                    <span class="text-text-muted text-xs font-bold uppercase">JOB ID: SLR-<?php echo htmlspecialchars($job['id']); ?></span>
                </div>
                <h2 class="text-3xl font-bold text-on-surface">Advanced SWMS & Final Sign-off</h2>
                <p class="mt-2 text-on-surface-variant max-w-2xl font-medium">Final structural and electrical validation terminal. Ensure all field personnel have acknowledged safety protocols before site lockdown.</p>
            </div>
            <?php if ($compliance['final_sign_off_status'] === 'Locked'): ?>
                <div class="flex items-center gap-2 bg-green-100 text-green-800 px-4 py-2 rounded-lg font-bold">
                    <span class="material-symbols-outlined">lock</span> SITE LOCKED
                </div>
            <?php endif; ?>
        </div>

        <div class="grid grid-cols-12 gap-6 mb-10">
            <!-- SWMS Checklist & Matrix -->
            <div class="col-span-12 lg:col-span-8 bg-surface-white border border-border-slate p-6 shadow-sm rounded-xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-xl font-bold flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary">fact_check</span>
                        Crew SWMS Acknowledgement Matrix
                    </h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-background-slate border-b border-border-slate">
                            <tr>
                                <th class="py-4 px-4 text-xs font-bold text-text-muted uppercase">Personnel</th>
                                <th class="py-4 px-4 text-xs font-bold text-text-muted uppercase">High Risk Work</th>
                                <th class="py-4 px-4 text-xs font-bold text-text-muted uppercase text-right">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border-slate">
                            <tr>
                                <td colspan="3" class="py-4 px-4 text-sm text-text-muted italic text-center">
                                    No crew assigned to this job.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Live Risk Feed -->
            <div class="col-span-12 lg:col-span-4 flex flex-col gap-6">
                <div class="bg-surface-white border border-border-slate p-6 shadow-sm rounded-xl flex-1">
                    <h3 class="font-bold text-lg flex items-center gap-2 mb-4">
                        <span class="material-symbols-outlined text-error">warning</span>
                        Critical Risk Mitigations
                    </h3>
                    <div class="space-y-4">
                        <p class="text-sm text-text-muted italic py-2">No critical risks logged for this site.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Final Confirmation Action -->
        <?php if ($compliance['final_sign_off_status'] !== 'Locked'): ?>
        <section class="mt-8 bg-surface-white border border-border-slate p-8 rounded-2xl shadow-xl flex flex-col items-center text-center max-w-4xl mx-auto border-t-8 border-t-primary-container">
            <span class="material-symbols-outlined text-primary text-[48px] mb-4">gavel</span>
            <h3 class="text-2xl font-bold mb-2">Final Installation Lockdown</h3>
            <p class="font-medium text-on-surface-variant mb-8 px-8">
                By selecting 'Confirm & Lock', you certify that all SWMS protocols were followed, structural integrity is verified, and evidence photos represent the actual site state. This action creates a permanent, immutable legal record.
            </p>
            
            <form action="api/save_compliance.php" method="POST" class="w-full flex justify-center">
                <input type="hidden" name="crm_project_id" value="<?php echo $job['id']; ?>">
                <input type="hidden" name="type" value="final_lock">
                
                <button type="submit" class="px-10 py-5 bg-primary-container text-on-primary-container font-bold text-lg rounded-2xl helios-glow hover:bg-primary transition-all duration-300 flex items-center justify-center gap-3 group">
                    Confirm & Lock Installation
                    <span class="material-symbols-outlined group-hover:translate-x-1 transition-transform">lock_person</span>
                </button>
            </form>
        </section>
        <?php endif; ?>

    </main>

    <!-- Success Modal Interaction Overlay -->
    <div class="fixed inset-0 bg-black/60 backdrop-blur-sm z-[100] flex items-center justify-center <?php echo $success ? '' : 'hidden'; ?>" id="success-overlay">
        <div class="bg-surface-white max-w-lg w-full p-10 rounded-3xl text-center shadow-2xl transition-transform duration-500 scale-100">
            <div class="w-20 h-20 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-6">
                <span class="material-symbols-outlined text-primary text-[48px]" style="font-variation-settings: 'FILL' 1;">task_alt</span>
            </div>
            <h2 class="text-3xl font-bold text-on-surface mb-2">Site Locked</h2>
            <p class="text-lg font-medium text-text-muted mb-8">Installation SLR-<?php echo htmlspecialchars($job['id']); ?> has been successfully archived and reported to the main fleet dashboard.</p>
            <button class="w-full py-4 bg-primary text-on-primary rounded-xl font-bold hover:bg-primary-hover transition-colors" onclick="window.location.href='index.php'">Return to Dashboard</button>
        </div>
    </div>
</body>
</html>
