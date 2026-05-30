<?php

require_once "../../vendor/autoload.php";

$pdf = new TCPDF('P', 'mm', 'A4');

$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetMargins(15, 10, 15);
$pdf->SetAutoPageBreak(false);

$pdf->AddPage();

$pdf->SetFont('helvetica', '', 8);

// =====================================================
// PAGE WIDTH CONTROL
// =====================================================
// A4 width = 210mm
// margins = 15 left + 15 right
// usable width = 180mm EXACTLY

$totalWidth = 180;

// =====================================================
// HEADER
// =====================================================
$pdf->SetFont('helvetica', 'B', 13);
$pdf->Cell($totalWidth, 6, 'NU-COMSCI MANUFACTURING CORPORATION', 0, 1, 'C');

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell($totalWidth, 4, 'SM City, Bacolod City, Neg. Occ.', 0, 1, 'C');

$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell($totalWidth, 7, 'JOB ORDER', 0, 1, 'C');

$pdf->Ln(1);

$pdf->SetFont('helvetica', '', 9);
$pdf->Cell($totalWidth, 5, 'Incident # : M0000542', 0, 1, 'R');


// =====================================================
// FIRST TABLE
// TOTAL WIDTH = 180
// =====================================================

// ROW 1
$pdf->Cell(14, 5, 'Machine', 1);
$pdf->Cell(64, 5, 'PEANUT JAR MACHINE #2', 1);

$pdf->Cell(16, 5, 'Inc Desc', 1);
$pdf->Cell(40, 5, 'Unplanned Downtime', 1);

$pdf->Cell(14, 5, 'Phase', 1);
$pdf->Cell(30, 5, 'Pending', 1);

$pdf->Ln();

// ROW 2
$pdf->Cell(14, 5, 'State', 1);
$pdf->Cell(24, 5, 'Offline', 1);

$pdf->Cell(18, 5, 'Reporter', 1);
$pdf->Cell(38, 5, 'Juan Dela Cruz', 1);

$pdf->Cell(20, 5, 'Date Rep', 1);
$pdf->Cell(24, 5, '05/23/2026', 1);

$pdf->Cell(20, 5, 'Time Rep', 1);
$pdf->Cell(20, 5, '9:00 PM', 1);

$pdf->Ln();

// ROW 3
$pdf->Cell(14, 5, 'Failure', 1);
$pdf->Cell(40, 5, 'Mechanical', 1);

$pdf->Cell(16, 5, 'Issue', 1);
$pdf->Cell(108, 5, 'Worn or damaged screw and barrel', 1);

$pdf->Ln();

// ROW 4
$pdf->Cell(14, 10, 'Details', 1);
$pdf->Cell(164, 10, 'Loose thread weight adjuster', 1);

$pdf->Ln(10);


// =====================================================
// SECOND TABLE
// TOTAL WIDTH = 180
// =====================================================

// ROW 1
$pdf->Cell(14, 5, 'Shift', 1);
$pdf->Cell(18, 5, 'Night', 1);

$pdf->Cell(34, 5, 'Assigned Tech', 1);
$pdf->Cell(46, 5, '', 1);

$pdf->Cell(16, 5, 'Date Com', 1);
$pdf->Cell(17, 5, '', 1);
$pdf->Cell(16, 5, 'Time Com', 1);
$pdf->Cell(17, 5, '', 1);
$pdf->Ln();

// ROW 2
$pdf->Cell(10, 5, 'Down', 1);
$pdf->Cell(22, 5, '  0.00 hrs', 1);

$pdf->Cell(34, 5, 'Completed by', 1);
$pdf->Cell(46, 5, '', 1);

$pdf->Ln();

// =====================================================
// CAUSE ROW
// Draw cells first, save Y after row, then overlay label + underline, restore cursor
// =====================================================
$xCause = $pdf->GetX();
$yCause = $pdf->GetY();

$pdf->Cell(14, 15, '', 1, 0);
$pdf->Cell(164, 15, '', 1, 0);
$pdf->Ln();
$yAfterCause = $pdf->GetY();

$pdf->SetXY($xCause + 1, $yCause + 1);
$pdf->Cell(12, 4, 'Cause', 0, 0, 'L');
$pdf->Line($xCause + 0, $yCause + 5.5, $xCause + 13.9s, $yCause + 5.5);

// Restore cursor to below Cause row
$pdf->SetXY($pdf->getMargins()['left'], $yAfterCause);

// =====================================================
// ACTION ROW
// =====================================================
$xAction = $pdf->GetX();
$yAction = $pdf->GetY();

$pdf->Cell(14, 15, '', 1, 0);
$pdf->Cell(164, 15, '', 1, 0);
$pdf->Ln();
$yAfterAction = $pdf->GetY();

$pdf->SetXY($xAction + 1, $yAction + 1);
$pdf->Cell(12, 4, 'Action', 0, 0, 'L');
$pdf->Line($xAction + 0, $yAction + 5.5, $xAction + 13.9, $yAction + 5.5);

// Restore cursor to below Action row for signatures
$pdf->SetXY($pdf->getMargins()['left'], $yAfterAction);
$pdf->Ln(5);

// =====================================================
// SIGNATURES
// =====================================================
$pdf->SetFont('helvetica', '', 9);

$pdf->Cell(80, 5, 'Prepared by:', 0, 0);
$pdf->Cell(80, 5, 'Approved by:', 0, 1);

$pdf->Ln(8);

// Signature Lines
$pdf->Cell(45, 0, '', 'T', 0);
$pdf->Cell(35, 0, '', 0, 0);

$pdf->Cell(45, 0, '', 'T', 1);
$pdf->Cell(80, 2, 'Juan Dela Cruz', 0, 0);


// =====================================================
// OUTPUT
// =====================================================
$pdf->Output('job-order.pdf', 'I');

?>