<?php
require_once  '../../config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/functions.php';

// Check if user is logged in
requireLogin();

// Check if user has an active company
requireActiveCompany();

// Get inspection ID from URL
$inspectionId = sanitizeInput($_GET['id'] ?? '');

if (empty($inspectionId)) {
    addError("Inspection ID is required.");
    redirect(url: "/inspections/index.php");
}

// Get inspection data
$inspection = getInspectionById($inspectionId);

// Check if inspection exists and belongs to this company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    addError("Inspection not found or you don't have permission to manage responses.");
    redirect(url: "/inspections/index.php");
}

// Ask for confirmation if not already confirmed
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Delete all responses for this inspection
    if (deleteAllResponses($inspectionId)) {
        addSuccessMessage("All responses for this inspection have been deleted successfully!");
    } else {
        addError("Failed to delete responses. Please try again.");
    }
    
    redirect(url: "/inspections/responses/index.php?id=" . $inspectionId);
} else {
    // Show confirmation page
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mt-5">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Confirm Delete All Responses</h5>
            </div>
            <div class="card-body">
                <p>Are you sure you want to delete ALL responses for the inspection:</p>
                <p class="lead fw-bold"><?= htmlspecialchars($inspection['title']) ?></p>
                <p class="mb-0">This will permanently delete all <?= $inspection['response_count'] ?> responses and their answers.</p>
                <p class="text-danger fw-bold">This action cannot be undone.</p>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= BASE_URL ?>/inspections/responses/index.php?id=<?= $inspectionId ?>" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <a href="<?= BASE_URL ?>/inspections/responses/delete-all.php?id=<?= $inspectionId ?>&confirm=yes" class="btn btn-danger">
                        Delete All Responses
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
    include_once $_SERVER['DOCUMENT_ROOT'] . BASE_URL . '/includes/footer.php';
    exit; // Stop execution after showing confirmation
}
?>
