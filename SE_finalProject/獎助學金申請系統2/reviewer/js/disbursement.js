// Reviewer disbursement module owned by Tsai Bo-Yu.
// Scope: award disbursement records, payout status updates, result export, and completion notices.
// The applications page owns the active disbursement UI because it has the
// tab state and table container. Keep this file non-invasive for pages that
// still include it, such as reviewer-dashboard.html.
window.reviewerDisbursement = window.reviewerDisbursement || {};
