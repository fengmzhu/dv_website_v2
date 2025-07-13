<?php
/**
 * IP Detail Modal Component
 * Reusable modal for displaying detailed IP/project information
 * Can be included in both IT Domain and NX Domain pages
 */
?>

<!-- IP Detail Modal -->
<div class="modal fade" id="ipDetailModal" tabindex="-1" aria-labelledby="ipDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ipDetailModalLabel">IP Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ipDetailContent">
                    <div class="text-center py-3">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading IP details...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editProjectBtn" style="display: none;">Edit Project</button>
            </div>
        </div>
    </div>
</div>

<script>
// IP Detail Modal JavaScript functions
function showIPDetails(projectId, domain = 'it') {
    const modal = new bootstrap.Modal(document.getElementById('ipDetailModal'));
    const modalTitle = document.getElementById('ipDetailModalLabel');
    const modalContent = document.getElementById('ipDetailContent');
    const editBtn = document.getElementById('editProjectBtn');
    
    // Show loading state
    modalContent.innerHTML = `
        <div class="text-center py-3">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading IP details...</p>
        </div>
    `;
    
    // Show modal
    modal.show();
    
    // Load content via AJAX
    const endpoint = domain === 'it' ? 'api/get-project-details.php' : '/nx-domain/api/get-project-details.php';
    
    fetch(`${endpoint}?id=${projectId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                modalTitle.textContent = `${data.project.ip || data.project.project_name || 'IP'} - Detailed Information`;
                modalContent.innerHTML = renderIPDetails(data.project, domain);
                
                // Show edit button for IT domain
                if (domain === 'it') {
                    editBtn.style.display = 'inline-block';
                    editBtn.onclick = () => {
                        window.location.href = `?action=edit&id=${projectId}`;
                    };
                } else {
                    editBtn.style.display = 'none';
                }
            } else {
                modalContent.innerHTML = `
                    <div class="alert alert-danger">
                        <h5>Error Loading Details</h5>
                        <p>${data.error || 'Unable to load project details.'}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading IP details:', error);
            modalContent.innerHTML = `
                <div class="alert alert-danger">
                    <h5>Connection Error</h5>
                    <p>Unable to connect to the server. Please try again later.</p>
                </div>
            `;
        });
}

function renderIPDetails(project, domain) {
    const sections = [];
    
    // Basic Project Information
    sections.push(`
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Basic Project Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2"><strong>Project Name:</strong> ${project.project_name || project.project || 'N/A'}</div>
                                <div class="mb-2"><strong>Task Index:</strong> <code>${project.task_index || 'N/A'}</code></div>
                                <div class="mb-2"><strong>SPIP IP:</strong> ${project.spip_ip || 'N/A'}</div>
                                <div class="mb-2"><strong>IP:</strong> ${project.ip || 'N/A'}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2"><strong>IP Postfix:</strong> ${project.ip_postfix || 'N/A'}</div>
                                <div class="mb-2"><strong>IP Subtype:</strong> 
                                    ${project.ip_subtype ? `<span class="badge bg-${project.ip_subtype === 'default' ? 'secondary' : 'primary'}">${project.ip_subtype}</span>` : 'N/A'}
                                </div>
                                <div class="mb-2"><strong>Alternative Name:</strong> ${project.alternative_name || 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
    
    // Personnel Information
    sections.push(`
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0"><i class="fas fa-users me-2"></i>Personnel Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2"><strong>DV Engineer:</strong> ${project.dv_engineer || 'N/A'}</div>
                                <div class="mb-2"><strong>Digital Designer:</strong> ${project.digital_designer || 'N/A'}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2"><strong>Business Unit:</strong> 
                                    ${project.business_unit ? `<span class="badge bg-info">${project.business_unit}</span>` : 'N/A'}
                                </div>
                                <div class="mb-2"><strong>Analog Designer:</strong> ${project.analog_designer || 'N/A'}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
    
    // Documentation & Links
    sections.push(`
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0"><i class="fas fa-book me-2"></i>Documentation & Links</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-2"><strong>SPIP URL:</strong> 
                                    ${project.spip_url ? `<a href="${project.spip_url}" target="_blank" class="btn btn-sm btn-outline-primary">View SPIP</a>` : 'N/A'}
                                </div>
                                <div class="mb-2"><strong>Wiki URL:</strong> 
                                    ${project.wiki_url ? `<a href="${project.wiki_url}" target="_blank" class="btn btn-sm btn-outline-info">View Wiki</a>` : 'N/A'}
                                </div>
                                <div class="mb-2"><strong>Spec Version:</strong> ${project.spec_version || 'N/A'}</div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-2"><strong>Spec Path:</strong> <small class="text-muted">${project.spec_path || 'N/A'}</small></div>
                                <div class="mb-2"><strong>Inherit from IP:</strong> ${project.inherit_from_ip || 'N/A'}</div>
                                <div class="mb-2"><strong>Re-use IP:</strong> 
                                    ${project.reuse_ip === 'Y' ? '<span class="badge bg-success">Yes</span>' : 
                                      project.reuse_ip === 'N' ? '<span class="badge bg-danger">No</span>' : 'N/A'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
    
    // Coverage Information (for NX domain or if coverage data exists)
    if (domain === 'nx' || project.line_coverage !== undefined) {
        sections.push(`
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Coverage Metrics</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h6>Line Coverage</h6>
                                        ${formatCoverageBadgeJS(project.line_coverage)}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h6>FSM Coverage</h6>
                                        ${formatCoverageBadgeJS(project.fsm_coverage)}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h6>Interface Toggle</h6>
                                        ${formatCoverageBadgeJS(project.interface_toggle_coverage)}
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="text-center">
                                        <h6>Toggle Coverage</h6>
                                        ${formatCoverageBadgeJS(project.toggle_coverage)}
                                    </div>
                                </div>
                            </div>
                            ${project.coverage_report_path ? `
                                <div class="row mt-3">
                                    <div class="col-12 text-center">
                                        <a href="${project.coverage_report_path}" target="_blank" class="btn btn-success">
                                            <i class="fas fa-external-link-alt me-2"></i>View Full Coverage Report
                                        </a>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            </div>
        `);
    }
    
    // Version Control Information (for NX domain)
    if (domain === 'nx' || project.sanity_svn !== undefined) {
        sections.push(`
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h6 class="mb-0"><i class="fas fa-code-branch me-2"></i>Version Control Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2"><strong>Sanity SVN:</strong> <small class="text-muted">${project.sanity_svn || 'N/A'}</small></div>
                                    <div class="mb-2"><strong>Sanity Version:</strong> ${project.sanity_svn_ver || 'N/A'}</div>
                                    <div class="mb-2"><strong>Release SVN:</strong> <small class="text-muted">${project.release_svn || 'N/A'}</small></div>
                                    <div class="mb-2"><strong>Release Version:</strong> ${project.release_svn_ver || 'N/A'}</div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2"><strong>Git Path:</strong> <small class="text-muted">${project.git_path || 'N/A'}</small></div>
                                    <div class="mb-2"><strong>Git Version:</strong> 
                                        ${project.git_version ? `<code class="git-hash">${project.git_version}</code>` : 'N/A'}
                                    </div>
                                    <div class="mb-2"><strong>Golden Checklist:</strong> <small class="text-muted">${project.golden_checklist || 'N/A'}</small></div>
                                    <div class="mb-2"><strong>Checklist Version:</strong> ${project.golden_checklist_version || 'N/A'}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
    }
    
    // Timestamps
    sections.push(`
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0"><i class="fas fa-clock me-2"></i>Timestamps</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-2"><strong>TO Date:</strong> ${formatDateJS(project.to_date)}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2"><strong>RTL Last Update:</strong> ${formatDateTimeJS(project.rtl_last_update)}</div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-2"><strong>Report Creation:</strong> ${formatDateTimeJS(project.to_report_creation)}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `);
    
    return sections.join('');
}

// Helper functions for formatting
function formatCoverageBadgeJS(coverage) {
    if (coverage === null || coverage === undefined || coverage === '') {
        return '<span class="badge bg-secondary">N/A</span>';
    }
    
    const numCoverage = parseFloat(coverage);
    if (isNaN(numCoverage)) {
        return '<span class="badge bg-secondary">N/A</span>';
    }
    
    let badgeClass = 'bg-danger';
    if (numCoverage >= 90) badgeClass = 'bg-success';
    else if (numCoverage >= 70) badgeClass = 'bg-warning';
    
    return `<span class="badge ${badgeClass}">${numCoverage.toFixed(1)}%</span>`;
}

function formatDateJS(dateStr) {
    if (!dateStr) return 'N/A';
    try {
        return new Date(dateStr).toLocaleDateString();
    } catch (e) {
        return dateStr;
    }
}

function formatDateTimeJS(dateStr) {
    if (!dateStr) return 'N/A';
    try {
        return new Date(dateStr).toLocaleString();
    } catch (e) {
        return dateStr;
    }
}
</script>

<style>
.git-hash {
    font-family: 'Courier New', monospace;
    font-size: 0.85em;
    background-color: #f8f9fa;
    padding: 0.2em 0.4em;
    border-radius: 0.25em;
}

.card-header h6 {
    font-weight: 600;
}

#ipDetailModal .modal-body {
    max-height: 80vh;
    overflow-y: auto;
}
</style>