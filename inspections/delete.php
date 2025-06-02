<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/config/config.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/auth.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/functions.php';

// Check if user is logged in
requireLogin();

// Check if user has an active company
requireActiveCompany();

// Get inspection ID from URL
$inspectionId = sanitizeInput($_GET['id'] ?? '');

if (empty($inspectionId)) {
    addError("Inspection ID is required.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Get inspection data
$inspection = getInspectionById($inspectionId);

// Check if inspection exists and belongs to this company
if (!$inspection || $inspection['company_id'] !== getActiveCompanyId()) {
    addError("Inspection not found or you don't have permission to delete it.");
    redirect(url: "/inspectia/inspections/index.php");
}

// Confirm deletion if needed
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    // Delete the inspection
    if (deleteInspection($inspectionId)) {
        addSuccessMessage("Inspection deleted successfully!");
    } else {
        addError("Failed to delete inspection. Please try again.");
    }
    
    redirect(url: "/inspectia/inspections/index.php");
} else {
    // Display confirmation message
?>

<?php include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card mt-5">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Confirm Deletion</h5>
            </div>
            <div class="card-body">
                <p>Are you sure you want to delete the inspection:</p>
                <p class="lead fw-bold"><?= htmlspecialchars($inspection['title']) ?></p>
                <p>This action cannot be undone. All questions and responses associated with this inspection will be permanently deleted.</p>
                
                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= BASE_URL ?>/inspections/index.php" class="btn btn-outline-secondary">
                        Cancel
                    </a>
                    <a href="<?= BASE_URL ?>/inspections/delete.php?id=<?= $inspectionId ?>&confirm=yes" class="btn btn-danger">
                        Delete Inspection
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
    include_once $_SERVER['DOCUMENT_ROOT'] . '/inspectia/includes/footer.php';
    exit; // Stop execution after showing confirmation
}
?>
