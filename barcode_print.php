<?php
require_once 'includes/functions.php';
requireLogin();

$error = '';
$success = '';
$equipment_list = [];

// Handle single equipment barcode print
if (isset($_GET['equipment_id'])) {
    $equipment_id = (int)$_GET['equipment_id'];
    $equipment = $db->fetch("SELECT * FROM equipment WHERE id = ? AND is_active = 1", [$equipment_id]);
    if ($equipment) {
        $equipment_list = [$equipment];
    } else {
        $error = 'Equipment not found.';
    }
}

// Handle multiple equipment selection
if (isset($_POST['print_selected'])) {
    $selected_ids = $_POST['selected_equipment'] ?? [];
    if (!empty($selected_ids)) {
        $placeholders = str_repeat('?,', count($selected_ids) - 1) . '?';
        $equipment_list = $db->fetchAll("SELECT * FROM equipment WHERE id IN ($placeholders) AND is_active = 1 ORDER BY item_name", $selected_ids);
    } else {
        $error = 'Please select at least one equipment item.';
    }
}

// Handle print all equipment
if (isset($_POST['print_all'])) {
    $equipment_list = $db->fetchAll("SELECT * FROM equipment WHERE is_active = 1 ORDER BY item_name");
}

// Get all equipment for selection if no specific equipment is loaded
$all_equipment = [];
if (empty($equipment_list) && empty($error)) {
    $all_equipment = $db->fetchAll("SELECT id, item_name, barcode, category FROM equipment WHERE is_active = 1 ORDER BY item_name");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barcode Label Printing - Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .print-container {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        .print-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(10px);
        }
        
        /* Label styles for printing */
        .barcode-label {
            width: 4in;
            height: 2in;
            border: 1px solid #000;
            margin: 0.1in;
            padding: 0.15in;
            display: inline-block;
            vertical-align: top;
            background: white;
            page-break-inside: avoid;
            font-family: 'Courier New', monospace;
        }
        
        .barcode-label .header {
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 5px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        
        .barcode-label .item-name {
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
            height: 22px;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 11px;
        }
        
        .barcode-label .barcode-section {
            text-align: center;
            margin: 8px 0;
        }
        
        .barcode-label .barcode-image {
            max-width: 100%;
            height: 40px;
            margin-bottom: 3px;
        }
        
        .barcode-label .barcode-text {
            font-size: 10px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .barcode-label .details {
            font-size: 9px;
            margin-top: 5px;
        }
        
        .barcode-label .details div {
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        /* Print styles */
        @media print {
            body * {
                visibility: hidden;
            }
            .print-area, .print-area * {
                visibility: visible;
            }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            .no-print {
                display: none !important;
            }
            .barcode-label {
                margin: 0.05in;
                border: 1px solid #000;
            }
            @page {
                margin: 0.25in;
                size: letter;
            }
        }
        
        .equipment-selection {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .label-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .print-options {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="container">
            <!-- Navigation -->
            <div class="row mb-4 no-print">
                <div class="col-12">
                    <nav class="navbar navbar-expand-lg navbar-dark bg-transparent">
                        <div class="container-fluid">
                            <a class="navbar-brand" href="dashboard.php">
                                <i class="fas fa-print me-2"></i>Barcode Label Printing
                            </a>
                            <div class="navbar-nav ms-auto">
                                <a class="nav-link" href="dashboard.php">
                                    <i class="fas fa-home me-1"></i>Dashboard
                                </a>
                                <a class="nav-link" href="equipment.php">
                                    <i class="fas fa-boxes me-1"></i>Equipment
                                </a>
                                <a class="nav-link" href="barcode_scanner.php">
                                    <i class="fas fa-qrcode me-1"></i>Scanner
                                </a>
                            </div>
                        </div>
                    </nav>
                </div>
            </div>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show no-print" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Equipment Selection (if no equipment selected) -->
            <?php if (empty($equipment_list) && !empty($all_equipment)): ?>
                <div class="print-card p-4 mb-4 no-print">
                    <h3 class="text-center mb-4">
                        <i class="fas fa-tags me-2"></i>Select Equipment for Label Printing
                    </h3>
                    
                    <form method="POST">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <button type="button" class="btn btn-outline-primary" onclick="selectAll()">
                                    <i class="fas fa-check-square me-1"></i>Select All
                                </button>
                                <button type="button" class="btn btn-outline-secondary ms-2" onclick="selectNone()">
                                    <i class="fas fa-square me-1"></i>Select None
                                </button>
                            </div>
                            <div class="col-md-6 text-end">
                                <button type="submit" name="print_selected" class="btn btn-success">
                                    <i class="fas fa-print me-1"></i>Print Selected
                                </button>
                                <button type="submit" name="print_all" class="btn btn-primary ms-2">
                                    <i class="fas fa-print me-1"></i>Print All
                                </button>
                            </div>
                        </div>
                        
                        <div class="equipment-selection">
                            <div class="row">
                                <?php foreach ($all_equipment as $equipment): ?>
                                    <div class="col-md-6 col-lg-4 mb-3">
                                        <div class="card">
                                            <div class="card-body">
                                                <div class="form-check">
                                                    <input class="form-check-input equipment-checkbox" type="checkbox" 
                                                           name="selected_equipment[]" value="<?php echo $equipment['id']; ?>" 
                                                           id="equipment_<?php echo $equipment['id']; ?>">
                                                    <label class="form-check-label" for="equipment_<?php echo $equipment['id']; ?>">
                                                        <strong><?php echo htmlspecialchars($equipment['item_name']); ?></strong><br>
                                                        <small class="text-muted">
                                                            <?php echo $equipment['barcode']; ?> | 
                                                            <?php echo htmlspecialchars($equipment['category'] ?? 'N/A'); ?>
                                                        </small>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Print Options -->
            <?php if (!empty($equipment_list)): ?>
                <div class="print-options no-print">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5><i class="fas fa-cog me-2"></i>Print Options</h5>
                            <p class="text-muted mb-0">Ready to print <?php echo count($equipment_list); ?> label(s)</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <button type="button" class="btn btn-success" onclick="window.print()">
                                <i class="fas fa-print me-1"></i>Print Labels
                            </button>
                            <button type="button" class="btn btn-secondary ms-2" onclick="window.location.href='barcode_print.php'">
                                <i class="fas fa-arrow-left me-1"></i>Back to Selection
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Label Preview -->
                <div class="label-preview no-print">
                    <h6 class="mb-3"><i class="fas fa-eye me-2"></i>Label Preview</h6>
                    <div class="text-center">
                        <?php if (!empty($equipment_list)): ?>
                            <div class="barcode-label" style="display: inline-block; transform: scale(0.8);">
                                <div class="header">INVENTORY SYSTEM</div>
                                <div class="item-name"><?php echo htmlspecialchars($equipment_list[0]['item_name']); ?></div>
                                <div class="barcode-section">
                                    <img src="<?php echo generateBarcodeImage($equipment_list[0]['barcode']); ?>" 
                                         alt="Barcode" class="barcode-image">
                                    <div class="barcode-text"><?php echo $equipment_list[0]['barcode']; ?></div>
                                </div>
                                <div class="details">
                                    <div><strong>Cat:</strong> <?php echo htmlspecialchars($equipment_list[0]['category'] ?? 'N/A'); ?></div>
                                    <div><strong>Loc:</strong> <?php echo htmlspecialchars($equipment_list[0]['location'] ?? 'TBD'); ?></div>
                                    <div><strong>Date:</strong> <?php echo date('m/d/Y'); ?></div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted text-center mt-3">
                        <small>Label size: 4" × 2" | Scale: 80% for preview</small>
                    </p>
                </div>
            <?php endif; ?>

            <!-- Print Area -->
            <?php if (!empty($equipment_list)): ?>
                <div class="print-area">
                    <?php foreach ($equipment_list as $equipment): ?>
                        <div class="barcode-label">
                            <div class="header">INVENTORY SYSTEM</div>
                            <div class="item-name"><?php echo htmlspecialchars($equipment['item_name']); ?></div>
                            <div class="barcode-section">
                                <img src="<?php echo generateBarcodeImage($equipment['barcode']); ?>" 
                                     alt="Barcode" class="barcode-image">
                                <div class="barcode-text"><?php echo $equipment['barcode']; ?></div>
                            </div>
                            <div class="details">
                                <div><strong>Category:</strong> <?php echo htmlspecialchars($equipment['category'] ?? 'N/A'); ?></div>
                                <div><strong>Location:</strong> <?php echo htmlspecialchars($equipment['location'] ?? 'TBD'); ?></div>
                                <div><strong>Condition:</strong> <?php echo $equipment['condition_status']; ?></div>
                                <div><strong>Print Date:</strong> <?php echo date('m/d/Y'); ?></div>
                            </div>
                        </div>
                        
                        <?php 
                        // Add page break every 8 labels (2 rows of 4 labels each)
                        static $label_count = 0;
                        $label_count++;
                        if ($label_count % 8 == 0 && $label_count < count($equipment_list)) {
                            echo '<div style="page-break-after: always;"></div>';
                        }
                        ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function selectAll() {
            const checkboxes = document.querySelectorAll('.equipment-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
        }

        function selectNone() {
            const checkboxes = document.querySelectorAll('.equipment-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
        }

        // Print functionality
        function printLabels() {
            window.print();
        }

        // Auto-print if coming from direct equipment link
        <?php if (isset($_GET['equipment_id']) && !empty($equipment_list)): ?>
            // Show print dialog after page loads
            window.addEventListener('load', function() {
                setTimeout(function() {
                    if (confirm('Print label for <?php echo addslashes($equipment_list[0]['item_name']); ?>?')) {
                        window.print();
                    }
                }, 500);
            });
        <?php endif; ?>

        // Handle print button styling
        window.addEventListener('beforeprint', function() {
            console.log('Preparing to print labels...');
        });

        window.addEventListener('afterprint', function() {
            console.log('Print dialog closed');
        });
    </script>
</body>
</html>