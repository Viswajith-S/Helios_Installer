<!-- Job Card Partial - used by installer.php -->
<div @click="openJob(<?php echo $job['id']; ?>)" 
     class="bg-white rounded-xl p-4 border border-gray-200 hover:border-brand-300 hover:shadow-md transition-all cursor-pointer group active:scale-[0.99]">
    <div class="flex justify-between items-start mb-3">
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1 flex-wrap">
                <span class="inline-flex items-center gap-1 bg-brand-50 text-brand-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border border-brand-100">
                    <span class="material-symbols-rounded" style="font-size:12px">schedule</span> <?php echo htmlspecialchars($job['installation_time_slot'] ?? 'TBC'); ?>
                </span>
                <?php if (!empty($job['installation_date']) && $job['installation_date'] !== date('Y-m-d')): ?>
                <span class="inline-flex items-center gap-1 bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider border border-blue-100">
                    <span class="material-symbols-rounded" style="font-size:12px">calendar_month</span> <?php echo date('d M', strtotime($job['installation_date'])); ?>
                </span>
                <?php endif; ?>
                <?php 
                    $installer_display = $job['assigned_installer_name'] ?? $job['installation_team'] ?? '';
                    if (!empty($installer_display)): 
                ?>
                <span class="inline-flex items-center gap-1 bg-gray-50 text-gray-600 px-2 py-0.5 rounded text-[10px] font-bold uppercase border border-gray-200">
                    <span class="material-symbols-rounded" style="font-size:12px">person</span> <?php echo htmlspecialchars($installer_display); ?>
                </span>
                <?php endif; ?>
                <span class="px-2 py-0.5 bg-green-50 text-green-700 font-bold rounded text-[10px] border border-green-100 items-center gap-1 hidden" x-bind:class="getJobState(<?php echo $job['id']; ?>).swms_status === 'verified' ? '!inline-flex' : 'hidden'">
                    <span class="material-symbols-rounded" style="font-size:12px">check_circle</span> SWMS
                </span>
            </div>
            <h2 class="text-base font-bold text-gray-900 group-hover:text-brand-600 transition-colors truncate"><?php echo htmlspecialchars($job['customer_name'] ?? 'Unknown'); ?></h2>
            <?php if (!empty($job['comments'])): ?>
            <p class="text-xs text-gray-500 font-medium mt-0.5 truncate flex items-center gap-1">
                <span class="material-symbols-rounded" style="font-size:14px">notes</span> <?php echo htmlspecialchars($job['comments']); ?>
            </p>
            <?php endif; ?>
        </div>
        <span class="text-[10px] font-bold text-gray-400 bg-gray-50 px-2 py-1 rounded border border-gray-100 flex-none">#<?php echo $job['id']; ?></span>
    </div>
    <div class="grid grid-cols-3 gap-3 bg-gray-50 rounded-lg p-3 border border-gray-100">
        <div>
            <span class="text-[10px] font-semibold text-gray-400 uppercase block">Panels</span>
            <p class="text-xs font-semibold text-gray-800 truncate" title="<?php echo htmlspecialchars($job['panels'] ?? 'N/A'); ?>"><?php echo htmlspecialchars($job['panels'] ?? 'N/A'); ?></p>
        </div>
        <div>
            <span class="text-[10px] font-semibold text-gray-400 uppercase block">Inverter</span>
            <p class="text-xs font-semibold text-gray-800 truncate" title="<?php echo htmlspecialchars($job['inverter'] ?? 'N/A'); ?>"><?php echo htmlspecialchars($job['inverter'] ?? 'N/A'); ?></p>
        </div>
        <div>
            <span class="text-[10px] font-semibold text-gray-400 uppercase block">Battery</span>
            <p class="text-xs font-semibold text-gray-800 truncate" title="<?php echo htmlspecialchars($job['battery'] ?? 'N/A'); ?>"><?php echo htmlspecialchars($job['battery'] ?? 'N/A'); ?></p>
        </div>
    </div>
</div>
