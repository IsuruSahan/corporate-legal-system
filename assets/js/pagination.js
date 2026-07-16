// A global state object to keep track of pages for different tables
// A global state object to keep track of pages for different tables
const paginationState = {};

function initPagination(table, containerId) {
    // Locate target table body container node
    const tbody = document.getElementById(containerId);
    if (!tbody) return;
    
    // Safely look up the outer card wrapper layout element structure
    const parentCard = tbody.closest('.data-ledger-card');
    if (!parentCard) return;

    // Create the controls container cleanly
    const controlsWrapper = document.createElement('div');
    controlsWrapper.style.cssText = "text-align: center; margin: 20px;";
    controlsWrapper.innerHTML = `
        <button onclick="paginate('${table}', '${containerId}', -1)" class="btn btn-secondary">Prev</button>
        <span id="page-${containerId}" style="margin: 0 15px; font-weight: 600;">Page 1</span>
        <button onclick="paginate('${table}', '${containerId}', 1)" class="btn btn-secondary">Next</button>
    `;
    
    // Append it safely below the layout table grid
    parentCard.appendChild(controlsWrapper);
}

function paginate(table, containerId, pageNumber) {
    // 1. Calculate the target page number cleanly
    let newPage;
    if (typeof pageNumber === 'number' && Math.abs(pageNumber) === 1 && !paginationState[containerId]) {
        newPage = 1;
    } else if (pageNumber === 1 || pageNumber === -1) {
        newPage = (paginationState[containerId] || 1) + pageNumber;
    } else {
        newPage = pageNumber;
    }

    if (newPage < 1) return;

    // --- READ ALL ACTIVE URL BAR FILTERS ---
    const urlParams = new URLSearchParams(window.location.search);
    
    // Core parameters shared across multiple templates
    const activeEntityFilter   = urlParams.get('entity') || '';
    const activeOfficerFilter  = urlParams.get('officer') || '';
    const activeStatusFilter   = urlParams.get('status') || '';
    const activeSearchFilter   = urlParams.get('search') || '';
    
    // Agreement specific parameters
    const activeCategoryFilter = urlParams.get('category') || '';
    const activeCabinetFilter  = urlParams.get('cabinet') || '';
    
    // Litigation (Court Cases) specific parameters
    const activeCourtFilter    = urlParams.get('court') || '';
    const activeCaseNumFilter  = urlParams.get('case_number') || '';
    // Payment specific URL filters
    const activePaRefFilter    = urlParams.get('pa_ref') || '';
    const activeEcfRefFilter   = urlParams.get('ecf_ref') || '';
    const activeSourceFilter   = urlParams.get('source_type') || '';

    const fd = new FormData();
    fd.append('action', 'fetch_paginated_data');
    fd.append('table', table);
    fd.append('page', newPage);

    // 1. Map global entity and structural filters
    if (activeEntityFilter)   fd.append('group_company_id', activeEntityFilter);
    if (activeOfficerFilter)  fd.append('assigned_officer_id', activeOfficerFilter);
    if (activeSearchFilter)   fd.append('search_term', activeSearchFilter);

    // 2. Map Agreement modular payload variants
    if (activeCategoryFilter) fd.append('category_id', activeCategoryFilter);
    if (activeCabinetFilter)  fd.append('cabinet_id', activeCabinetFilter);

    // 3. Map Litigation context variables accurately depending on routing paths
    if (activeCourtFilter)    fd.append('court_id', activeCourtFilter);
    if (activeCaseNumFilter)  fd.append('case_number', activeCaseNumFilter);
    // Map Payment modular payload variants
    if (activePaRefFilter)  fd.append('pa_ref_number', activePaRefFilter);
    if (activeEcfRefFilter) fd.append('ecf_ref_number', activeEcfRefFilter);
    if (activeSourceFilter) fd.append('source_type', activeSourceFilter);

    // 4. Status mapping logic across distinct tables
    if (activeStatusFilter) {
        if (table === 'court_cases') {
            fd.append('case_status', activeStatusFilter);
        } else {
            fd.append('initial_status', activeStatusFilter);
        }
    }

    fetch('/corporate-legal-system/config/router.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(res => {
        const body = document.getElementById(containerId);
        
        if (res.success && res.data.length > 0) {
            // Update structural page states only when valid rows return
            paginationState[containerId] = newPage; 
            document.getElementById(`page-${containerId}`).textContent = `Page ${newPage}`;
            
            body.innerHTML = ''; 
            res.data.forEach(row => {
                body.innerHTML += renderRow(table, row);
            });
        } else {
            alert("No more records to display.");
            // If the initial page request (page 1) comes back completely empty due to strict filtering filters
            if (newPage === 1) {
                body.innerHTML = '<tr><td colspan="100%" style="text-align: center; padding: 20px; color: #64748b;">No matching records found.</td></tr>';
            }
        }
    })
    .catch(err => console.error("Pagination networking processing failure:", err));
}

// [ Centralized Renderer Function Switch remains exactly as you wrote it ]


// Centralized Renderer
function renderRow(table, r) {
    switch(table) {
        case 'court_cases': {
            let st = r.case_status || 'Filing Stage';
            let badge = 'progress';
            if (st === 'Settled') badge = 'linked';
            if (st === 'Filing Stage') badge = 'pending';
            if (st === 'Appealed') badge = 'error';
            if (st === 'In Progress') badge = 'progress';

            let fileHtmlCourt = '<span class="file-link-trigger none" style="color: #ccc; font-style: italic;">None</span>';
            try {
                let files = JSON.parse(r.file_attachment_path || '[]');
                if (Array.isArray(files) && files.length > 0) {
                    fileHtmlCourt = files.map(path => `
                        <a href="../${path.replace(/^\//, '')}" target="_blank" 
                           class="file-link-trigger active" 
                           style="color: var(--primary-brand); text-decoration: none; font-weight: 600; margin-right: 5px;">📁</a>
                    `).join('');
                }
            } catch(e) { console.error("File parse error", e); }

            return `<tr id="case-row-${r.id || ''}" onclick="openDetailDrawer(${r.id || 0})" style="cursor: pointer;">
                <td>
                    <div class="primary-line" style="font-weight: 700; color: var(--text-dark); font-size: 14px;">${r.case_parties || ''}</div>
                    <div style="font-size: 12px; color: var(--text-light); font-weight: 500; margin-top: 2px;">
                        <strong style="color: #475569;">${r.company_name || ''}</strong> 
                        <span style="margin: 0 4px; color: #cbd5e1;">|</span> ${(r.case_description || '').substring(0, 75)}${(r.case_description && r.case_description.length > 75) ? '...' : ''}
                    </div>
                </td>
                <td><span class="court-cell-text" style="font-size: 13px; font-weight: 600; color: var(--text-muted);">${r.room_name || ''}</span></td>
                <td>
                    <div class="officer-cell-text" style="font-size: 13px; font-weight: 700; color: var(--text-dark);">${r.officer_name || ''}</div>
                    <div style="font-size: 11px; color: var(--text-light); font-weight: 500; margin-top: 1px;">Attn: ${r.instructing_attorney || ''}</div>
                </td>
                <td><span style="font-family: monospace; font-size: 12px; font-weight: 700; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; color: #1e293b;">${r.case_number || ''}</span></td>
                <td>
                    <div style="font-size: 13px; font-weight: 700; color: #0f172a;">${r.next_hearing_date ? new Date(r.next_hearing_date).toLocaleDateString('en-US', {month:'short', day:'2-digit', year:'numeric'}) : 'Not Scheduled'}</div>
                    ${r.next_step_description ? `<div style="font-size: 11px; color: var(--text-light); font-weight: 500; margin-top: 1px; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Next Action: ${r.next_step_description}</div>` : ''}
                </td>
                <td><span class="status-badge ${badge}" style="font-weight: 700;">${st}</span></td>
                <td style="text-align: center;">${fileHtmlCourt}</td>
            </tr>`;
        } // End court_cases

        case 'audit_logs': {
            let logBadge = 'pending';
            if(r.action_type === 'INSERT') logBadge = 'linked';
            if(r.action_type === 'UPDATE') logBadge = 'progress';
            if(r.action_type === 'DELETE') logBadge = 'error';

            return `<tr>
                <td><span class="text-data-regular" style="font-family: monospace; font-size: 12px; font-weight: 600; color: var(--text-dark);">${r.timestamp}</span></td>
                <td>
                    <div class="primary-line" style="font-size: 13px; font-weight: 600;">${r.user_name}</div>
                    <div style="font-size: 11px; color: var(--text-light); font-weight: 500; margin-top: 1px;">Role: ${r.user_role}</div>
                </td>
                <td><span class="status-badge ${logBadge}" style="display: block; text-align: center; font-size: 10px; padding: 2px 0;">${r.action_type}</span></td>
                <td><span class="text-data-bold" style="font-size: 12px; color: var(--primary-brand);">📁 ${r.module_target}</span></td>
                <td>
                    <div style="font-size: 13px; font-weight: 500; color: var(--text-muted); line-height: 1.4;">${r.meta_description} <span style="font-size: 11px; color: var(--text-light); font-family: monospace; margin-left: 6px;">(Ref ID: #${r.record_id})</span></div>
                </td>
            </tr>`;
        } // End audit_logs

case 'agreements': {
            let fileHtmlAgree = '<span class="file-link-trigger none" style="color: #ccc; font-style: italic;">None</span>';
            try {
                let files = JSON.parse(r.file_attachment_path || '[]');
                if (Array.isArray(files) && files.length > 0) {
                    // Clean asset path trailing calculation logic maps smoothly
                    fileHtmlAgree = files.map(path => `
                        <a href="..${path}" target="_blank" class="file-link-trigger active" style="color: var(--primary-brand); text-decoration: none; font-weight: 600; margin-right: 5px;">📁</a>
                    `).join('');
                }
            } catch(e) { console.error("File parse error", e); }

            return `<tr id="agreement-row-${r.id || ''}">
                <td class="title-meta-cell" style="cursor: pointer;" onclick="openDetailDrawer(${r.id || 0})">
                    <div class="primary-line" style="color: var(--primary-brand); font-weight: 700; text-decoration: underline;">${r.title || ''}</div>
                    <div class="secondary-sub">${r.company_name || ''} <span>| Party B: ${r.party_b || ''}</span></div>
                </td>
                <td><span class="text-data-regular">${r.category_name || ''}</span></td>
                <td><span class="text-data-regular">${r.officer_name || ''}</span></td>
                <td><span class="text-data-bold">${r.cabinet_location || ''}</span></td>
                <td><span class="text-data-regular">${r.expiry_date ? new Date(r.expiry_date).toLocaleDateString('en-US', {month:'short', day:'2-digit', year:'numeric'}) : ''}</span></td>
                <td><span class="status-badge ${(r.initial_status || '').toLowerCase()}">${r.initial_status || ''}</span></td>
                <td>${fileHtmlAgree}</td>
            </tr>`;
        } // End agreements

        case 'payments': {
    // Determine status badge class based on the 'status' column
    let statusClass = (r.status === 'Linked') ? 'linked' : 'error';
    let displayStatus = (r.status === 'Linked') ? 'Linked' : 'Unlinked ECF';

    return `<tr id="payment-row-${r.id}" onclick="openDetailDrawer(${r.id})" style="cursor: pointer;">
        <td>
            <div class="primary-line" style="font-weight: 700; color: var(--text-dark);">${r.description || ''}</div>
            <div style="font-size: 12px; color: var(--text-light);">${r.company_name || ''} | Linked Source: ${r.source_type || 'N/A'} (#${r.linked_source_id || '0'})</div>
        </td>
        <td>${r.pa_ref_number || '<span style="color:red;">[ No Input ]</span>'}</td>
        <td>${r.ecf_ref_number || '<span style="color:red;">[ No Input ]</span>'}</td>
        <td>${r.due_date ? new Date(r.due_date).toLocaleDateString('en-US', {month:'short', day:'numeric', year:'numeric'}) : ''}</td>
        <td style="font-weight: 700;">${r.currency || 'Rs.'} ${parseFloat(r.amount || 0).toLocaleString()}</td>
        <td><span class="status-badge ${statusClass}" style="font-weight: 700;">${displayStatus}</span></td>
    </tr>`;
}

case 'physical_archives_master': {
            // Check raw file attachment contents for valid paths, avoiding blank configurations
            let fileData = r.raw_file_field;
            let hasScan = (fileData && fileData !== '[]' && fileData !== 'null' && fileData !== '');
            
            let statusClass = hasScan ? 'text-success' : 'text-danger';
            let statusText = hasScan ? '✔ Digital Scanned' : '⚠ Upload Pending';
            let statusColor = hasScan ? '#22c55e' : '#ef4444';

            return `<tr>
                <td><span class="text-data-bold" style="font-weight: 700; color: var(--text-dark);">${r.physical_location || ''}</span></td>
                <td><span class="text-data-regular" style="font-family: monospace; font-weight: 700; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; color: #1e293b;">${r.system_ref_no || ''}</span></td>
                <td class="title-meta-cell">
                    <div class="primary-line" style="font-weight: 700; color: var(--text-dark); font-size: 14px;">${r.primary_title || ''}</div>
                    <div class="secondary-sub" style="font-size: 12px; color: var(--text-light); margin-top: 2px;">
                        <strong class="entity-marker-text" style="font-weight: 600; color: #475569;">${r.group_company || ''}</strong> 
                        <span style="margin: 0 4px; color: #cbd5e1;">|</span> ${r.structural_subtext || ''}
                    </div>
                </td>
                <td><span class="text-data-regular" style="color: var(--text-light); font-size: 13px; font-weight: 600;">${r.module_type || ''}</span></td>
                <td>
                    <span class="${statusClass}" style="font-weight: 700; font-size: 13px; color: ${statusColor};">
                        ${statusText}
                    </span>
                </td>
            </tr>`;
        }

        default:
            return `<tr><td colspan="100%">Unknown table structure</td></tr>`;
    }
}