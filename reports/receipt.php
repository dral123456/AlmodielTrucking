<?php
session_start();
require_once '../vendor/autoload.php';
require_once '../controllers/booking.controller.php';
require_once '../models/booking.model.php';

class BookingReceipt {

    const GREEN  = array(39, 174, 96);
    const DARK   = array(30, 30, 30);
    const MUTED  = array(120, 120, 120);
    const LIGHT  = array(245, 247, 245);
    const WHITE  = array(255, 255, 255);
    const BORDER = array(200, 200, 200);
    const YELLOW = array(255, 243, 205);  // sub-total highlight

    // Withholding tax rate (2% per the sample receipt)
    const WITHHOLDING_RATE = 0.02;
    // Penalty rate (if applicable — set 0 if none)
    const PENALTY_RATE = 0.00;

    public function printReceipt(int $bookingID): void {

        // ── 1. Fetch data ─────────────────────────────────────────────────────
        $booking = ControllerBooking::ctrReceiptBooking($bookingID);
        if (!$booking) {
            die('Booking #' . $bookingID . ' not found.');
        }

        $cargo = ControllerBooking::ctrReceiptCargoItems($bookingID);
        $crew  = ControllerBooking::ctrReceiptTripCrew((int) $booking['tripID']);

        // ── 2. Computed breakdown (mirroring physical receipt) ────────────────
        $deliveryTrucking  = (float) $booking['price'];
        $tollAndParking    = (float) ($booking['extraAmount'] ?? 0);   // hauling/others = toll & parking
        $extraTypes        = $booking['extraTypes'] ?? '';

        $withholdingTax    = round($deliveryTrucking * self::WITHHOLDING_RATE, 2);
        $subTotal1         = $deliveryTrucking - $withholdingTax;
        $penalty           = round($subTotal1 * self::PENALTY_RATE, 2);
        $subTotal2         = $subTotal1 + $penalty;
        $netAmount         = $subTotal2 + $tollAndParking;

        // ── 3. Header computed values ─────────────────────────────────────────
        $receiptNo   = 'REC-' . str_pad($bookingID, 6, '0', STR_PAD_LEFT);
        $printedDate = date('m/d/Y');
        $pickupDate  = $booking['pickupDateTime']
            ? date('m/d/Y', strtotime($booking['pickupDateTime']))
            : '—';

        $customerName = trim($booking['customerFName'] . ' ' . $booking['customerLName']);
        if ($customerName === '') {
            $customerName = $booking['contactPerson'] ?? 'Customer';
        }

        $address = $this->formatAddress(
            $booking['pickupStreet']   ?? '',
            $booking['pickupBarangay'] ?? '',
            $booking['pickupCity']     ?? '',
            $booking['pickupProvince'] ?? ''
        );
        $destAddr = $this->formatAddress(
            $booking['destinationStreet']   ?? '',
            $booking['destinationBarangay'] ?? '',
            $booking['destinationCity']     ?? '',
            $booking['destinationProvince'] ?? ''
        );

        $driverName = '';
        $assistants = array();
        foreach ($crew as $member) {
            $name = trim($member['empFName'] . ' ' . $member['empLName']);
            if ($member['role'] === 'driver') {
                $driverName = $name;
            } else {
                $assistants[] = $name;
            }
        }
        $plateNumber = $crew[0]['plateNumber'] ?? '—';

        // ── 4. PDF setup ──────────────────────────────────────────────────────
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 20);
        $pdf->AddPage();

        $pw = $pdf->getPageWidth() - 30;  // 190mm usable

        // ═════════════════════════════════════════════════════════════════════
        // SECTION A — Company header (matches physical receipt layout)
        // ═════════════════════════════════════════════════════════════════════
        $pdf->SetFont('helvetica', 'B', 14);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell(0, 7, 'ALMODIEL TRUCKING SERVICES', 0, 1, 'C');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(0, 4, 'Sulpicio M. Almodiel Jr. - Prop.', 0, 1, 'C');
        $pdf->Cell(0, 4, 'Non-VAT Reg. TIN: 185-476-158-0000', 0, 1, 'C');
        $pdf->Cell(0, 4, 'Prk. Guanzon, Brgy. Mansilingan, Bacolod City', 0, 1, 'C');

        $pdf->Ln(2);

        // "SERVICE INVOICE / OFFICIAL RECEIPT" badge (top right of physical receipt)
        $badgeY = 15;
        $pdf->SetXY(15 + $pw - 45, $badgeY);
        $pdf->SetFillColor(...self::GREEN);
        $pdf->SetTextColor(...self::WHITE);
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->Cell(45, 5, 'SERVICE INVOICE', 0, 2, 'C', true);
        $pdf->SetX(15 + $pw - 45);
        $pdf->Cell(45, 5, 'OFFICIAL RECEIPT', 0, 1, 'C', true);

        $pdf->SetY(max($pdf->GetY(), 42));
        $this->hRule($pdf, $pw);

        // ═════════════════════════════════════════════════════════════════════
        // SECTION B — Receipt No. + Date
        // ═════════════════════════════════════════════════════════════════════
        $pdf->SetTextColor(...self::DARK);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($pw - 50, 6, 'No. ' . $receiptNo, 0, 0, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(50, 6, 'DATE: ' . $printedDate, 0, 1, 'R');

        // ═════════════════════════════════════════════════════════════════════
        // SECTION C — Customer info box (bordered, like physical receipt)
        // ═════════════════════════════════════════════════════════════════════
        $pdf->SetDrawColor(...self::BORDER);
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $rightColW = $pw - ($pw * 0.55); 

        // Row 1: Customer Name + Date of pickup
        $pdf->Cell(25, 6, 'CUSTOMER NAME:', 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell($rightColW, 6, $customerName, 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(20, 6, 'PICKUP DATE:', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell($rightColW - 27, 6, $pickupDate, 1, 1, 'L');

        // Row 2: Address + Plate No
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(25, 6, 'ADDRESS:', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell($rightColW, 6, $address, 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(20, 6, 'PLATE NO.:', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell($rightColW - 27, 6, $plateNumber, 1, 1, 'L');

        // Row 3: Destination + Driver
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(25, 6, 'DESTINATION:', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell($rightColW, 6, $destAddr, 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(20, 6, 'DRIVER:', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell($rightColW - 27, 6, $driverName ?: '—', 1, 1, 'L');

        // Row 4: Cargo summary + Assistants
        $cargoSummary = implode(', ', array_map(function ($c) {
            return $c['cargoType'] . ' x' . $c['quantity'];
        }, $cargo));
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(25, 6, 'CARGO:', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell($rightColW, 6, $cargoSummary, 1, 0, 'L');
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(20, 6, 'ASSISTANTS:', 1, 0, 'L');
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell($rightColW - 27, 6, implode(', ', $assistants) ?: '—', 1, 1, 'L');

        $pdf->Ln(4);

        // ═════════════════════════════════════════════════════════════════════
        // SECTION D — "IN PAYMENT OF THE FOLLOWING SERVICE" table
        //              Matches columns: TRANSACTION/DESCRIPTION | QTY | UNIT PRICE | AMOUNT
        // ═════════════════════════════════════════════════════════════════════
        $dW  = $pw - 30 - 30 - 35;  // description column
        $qW  = 30;                   // qty
        $upW = 30;                   // unit price
        $amW = 35;                   // amount

        // Table header
        $pdf->SetFillColor(...self::GREEN);
        $pdf->SetTextColor(...self::WHITE);
        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->Cell($dW,  6, 'IN PAYMENT OF THE FOLLOWING SERVICE', 1, 0, 'L', true);
        $pdf->Cell($qW,  6, 'QTY.',       1, 0, 'C', true);
        $pdf->Cell($upW, 6, 'UNIT PRICE', 1, 0, 'R', true);
        $pdf->Cell($amW, 6, 'AMOUNT',     1, 1, 'R', true);

        $pdf->SetFont('helvetica', 'B', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetFillColor(...self::WHITE);
        $pdf->Cell($dW,  5, 'TRANSACTION / DESCRIPTION', 1, 0, 'L');
        $pdf->Cell($qW,  5, '',  1, 0, 'C');
        $pdf->Cell($upW, 5, '',  1, 0, 'R');
        $pdf->Cell($amW, 5, '',  1, 1, 'R');

        $pdf->SetTextColor(...self::DARK);
        $pdf->SetFont('helvetica', '', 8);

        // ── Row: Delivery Trucking ────────────────────────────────────────────
        $pdf->Cell($dW,  6, 'Delivery Trucking', 1, 0, 'L');
        $pdf->Cell($qW,  6, '1',                 1, 0, 'C');
        $pdf->Cell($upW, 6, number_format($deliveryTrucking, 2), 1, 0, 'R');
        $pdf->Cell($amW, 6, number_format($deliveryTrucking, 2), 1, 1, 'R');

        // ── Row: Withholding Tax ──────────────────────────────────────────────
        $wtLabel = 'Withholding Tax ' . (self::WITHHOLDING_RATE * 100) . '%';
        $pdf->Cell($dW,  6, $wtLabel, 1, 0, 'L');
        $pdf->Cell($qW,  6, '',       1, 0, 'C');
        $pdf->Cell($upW, 6, '',       1, 0, 'R');
        $pdf->Cell($amW, 6, '(' . number_format($withholdingTax, 2) . ')', 1, 1, 'R');

        // ── Sub-Total 1 (highlighted) ─────────────────────────────────────────
        $pdf->SetFillColor(...self::YELLOW);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(...self::DARK);
        $pdf->Cell($dW,  6, 'Sub-Total', 1, 0, 'L', true);
        $pdf->Cell($qW,  6, '',          1, 0, 'C', true);
        $pdf->Cell($upW, 6, '',          1, 0, 'R', true);
        $pdf->Cell($amW, 6, number_format($subTotal1, 2), 1, 1, 'R', true);

        // ── Row: Penalty ─────────────────────────────────────────────────────
        $pdf->SetFillColor(...self::WHITE);
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell($dW,  6, 'Penalty', 1, 0, 'L');
        $pdf->Cell($qW,  6, '',        1, 0, 'C');
        $pdf->Cell($upW, 6, '',        1, 0, 'R');
        $pdf->Cell($amW, 6, $penalty > 0 ? number_format($penalty, 2) : '—', 1, 1, 'R');

        // ── Sub-Total 2 (highlighted) ─────────────────────────────────────────
        $pdf->SetFillColor(...self::YELLOW);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell($dW,  6, 'Sub-Total', 1, 0, 'L', true);
        $pdf->Cell($qW,  6, '',          1, 0, 'C', true);
        $pdf->Cell($upW, 6, '',          1, 0, 'R', true);
        $pdf->Cell($amW, 6, number_format($subTotal2, 2), 1, 1, 'R', true);

        // ── Row: Toll and Parking Fee ─────────────────────────────────────────
        $pdf->SetFillColor(...self::WHITE);
        $pdf->SetFont('helvetica', '', 8);
        $tollLabel = 'Toll and Parking Fee';
        if ($extraTypes) {
            $tollLabel .= ' (' . $this->e($extraTypes) . ')';
        }
        $pdf->Cell($dW,  6, $tollLabel, 1, 0, 'L');
        $pdf->Cell($qW,  6, '',         1, 0, 'C');
        $pdf->Cell($upW, 6, '',         1, 0, 'R');
        $pdf->Cell($amW, 6, $tollAndParking > 0 ? number_format($tollAndParking, 2) : '—', 1, 1, 'R');

        // ── Net Amount (green total row) ──────────────────────────────────────
        $pdf->SetFillColor(...self::GREEN);
        $pdf->SetTextColor(...self::WHITE);
        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->Cell($dW,  7, 'Net Amount', 1, 0, 'L', true);
        $pdf->Cell($qW,  7, '',           1, 0, 'C', true);
        $pdf->Cell($upW, 7, '',           1, 0, 'R', true);
        $pdf->Cell($amW, 7, 'PHP ' . number_format($netAmount, 2), 1, 1, 'R', true);

        $pdf->Ln(4);

        // ═════════════════════════════════════════════════════════════════════
        // SECTION E — Total Sales summary block (right side, like physical)
        // ═════════════════════════════════════════════════════════════════════
        $labelW = 50;
        $valueW = 35;
        $totalX = 15 + $pw - $labelW - $valueW;

        $summaryRows = array(
            array('TOTAL SALES',   number_format($deliveryTrucking, 2), false),
            array('LESS: WITHHOLDING', '(' . number_format($withholdingTax, 2) . ')', false),
            array('TOTAL AMOUNT DUE', number_format($netAmount, 2), true),
        );

        $pdf->SetTextColor(...self::DARK);
        foreach ($summaryRows as [$label, $value, $isTotal]) {
            $pdf->SetX($totalX);
            if ($isTotal) {
                $pdf->SetFillColor(...self::GREEN);
                $pdf->SetTextColor(...self::WHITE);
                $pdf->SetFont('helvetica', 'B', 8.5);
                $pdf->Cell($labelW, 6, $label,          1, 0, 'L', true);
                $pdf->Cell($valueW, 6, 'PHP ' . $value, 1, 1, 'R', true);
            } else {
                $pdf->SetTextColor(...self::DARK);
                $pdf->SetFont('helvetica', '', 8);
                $pdf->Cell($labelW, 5, $label,  1, 0, 'L');
                $pdf->Cell($valueW, 5, $value,  1, 1, 'R');
            }
        }

        $pdf->Ln(6);
        $this->hRule($pdf, $pw);

        // ═════════════════════════════════════════════════════════════════════
        // SECTION F — Notes
        // ═════════════════════════════════════════════════════════════════════
        $pdf->SetFont('helvetica', 'B', 7);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(0, 4, 'NOTES', 0, 1, 'L');

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetTextColor(...self::DARK);
        $notesText = trim($booking['cargoCondition'] ?? '') !== ''
            ? 'Cargo condition: ' . $booking['cargoCondition']
            : 'No additional notes.';
        $pdf->MultiCell(0, 5, $notesText, 0, 'L');

        $pdf->Ln(8);

        // ═════════════════════════════════════════════════════════════════════
        // SECTION G — Signature lines
        // ═════════════════════════════════════════════════════════════════════
        $sigW  = ($pw / 2) - 10;
        $sigX2 = 15 + ($pw / 2) + 10;
        $sigY  = $pdf->GetY() + 12;

        $pdf->SetDrawColor(...self::DARK);
        $pdf->Line(15,     $sigY, 15 + $sigW,     $sigY);
        $pdf->Line($sigX2, $sigY, $sigX2 + $sigW, $sigY);

        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->SetX(15);
        $pdf->Cell($sigW, 16, 'Cashier / Authorized Person', 0, 0, 'L');
        $pdf->SetX($sigX2);
        $pdf->Cell($sigW, 16, 'Customer Signature', 0, 1, 'R');

        // ═════════════════════════════════════════════════════════════════════
        // SECTION H — Footer
        // ═════════════════════════════════════════════════════════════════════
        $this->hRule($pdf, $pw);
        $pdf->SetFont('helvetica', 'I', 7.5);
        $pdf->SetTextColor(...self::MUTED);
        $pdf->Cell(0, 4, 'This document is not valid for claim of input tax.', 0, 1, 'C');
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->SetTextColor(...self::GREEN);
        $pdf->Cell(0, 5, 'Thank you for your payment!', 0, 1, 'C');

        $pdf->Output('receipt_booking' . $bookingID . '.pdf', 'I');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function hRule(TCPDF $pdf, float $pw): void {
        $pdf->SetDrawColor(...self::BORDER);
        $pdf->Line(15, $pdf->GetY(), 15 + $pw, $pdf->GetY());
        $pdf->Ln(3);
    }

    private function formatAddress(string $street, string $barangay, string $city, string $province): string {
        return implode(', ', array_filter(array($street, $barangay, $city, $province))) ?: '—';
    }

    private function e(string $value): string {
        return htmlspecialchars_decode(strip_tags($value));
    }
}

// ── Router ────────────────────────────────────────────────────────────────────
$bookingID = (int) ($_GET['bookingID'] ?? 0);
if ($bookingID <= 0) {
    die('Missing or invalid bookingID. Usage: receipt.php?bookingID=1');
}

$receipt = new BookingReceipt();
$receipt->printReceipt($bookingID);