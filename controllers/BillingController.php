<?php
require_once __DIR__ . '/../shared/config.php';

class BillingController {
    protected $db;

    public function __construct() {
        $this->db = function_exists('getDBConnection') ? getDBConnection() : null;
    }

    /**
     * List bills for DataTable with server-side processing
     */
    public function listForDataTable($request) {
        if (!$this->db) return ['draw' => 0, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];

        $draw = isset($request['draw']) ? (int)$request['draw'] : 0;
        $start = isset($request['start']) ? (int)$request['start'] : 0;
        $length = isset($request['length']) ? (int)$request['length'] : 10;
        
        $where = '';
        $params = [];
        
        // Search functionality
        if (!empty($request['search']['value'])) {
            $search = trim($request['search']['value']);
            $where = "WHERE (b.bill_id LIKE :search OR CONCAT(p.first_name, ' ', p.last_name) LIKE :search OR b.payment_status LIKE :search)";
            $params[':search'] = "%$search%";
        }

        try {
            // Get total count
            $totalStmt = $this->db->query('SELECT COUNT(*) FROM billing');
            $total = (int)$totalStmt->fetchColumn();

            // Get filtered count
            if ($where) {
                $filteredStmt = $this->db->prepare("SELECT COUNT(*) FROM billing b JOIN patients p ON b.patient_id = p.id $where");
                foreach ($params as $key => $value) {
                    $filteredStmt->bindValue($key, $value);
                }
                $filteredStmt->execute();
                $recordsFiltered = (int)$filteredStmt->fetchColumn();
            } else {
                $recordsFiltered = $total;
            }

            // Get data with ordering
            $orderSql = 'ORDER BY b.bill_date DESC, b.created_at DESC';
            if (isset($request['order'][0])) {
                $columnIndex = (int)$request['order'][0]['column'];
                $direction = $request['order'][0]['dir'] === 'desc' ? 'DESC' : 'ASC';
                
                $columns = ['b.bill_id', 'patient_name', 'b.total_amount', 'b.payment_status', 'b.bill_date'];
                if (isset($columns[$columnIndex])) {
                    $orderSql = "ORDER BY {$columns[$columnIndex]} $direction";
                }
            }

            $sql = "
                SELECT 
                    b.id,
                    b.bill_id,
                    b.patient_id,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    p.patient_id as patient_code,
                    b.consultation_fee,
                    b.lab_charges,
                    b.medication_charges,
                    b.other_charges,
                    b.total_amount,
                    b.discount_amount,
                    b.tax_amount,
                    b.paid_amount,
                    b.balance_amount,
                    b.payment_status,
                    b.payment_method,
                    b.bill_date,
                    b.due_date,
                    b.payment_date,
                    b.appointment_id
                FROM billing b
                JOIN patients p ON b.patient_id = p.id
                $where
                $orderSql
                LIMIT :start, :length
            ";

            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':start', $start, PDO::PARAM_INT);
            $stmt->bindValue(':length', $length, PDO::PARAM_INT);
            $stmt->execute();

            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $recordsFiltered,
                'data' => $data
            ];

        } catch (Exception $e) {
            error_log('[BillingController::listForDataTable] ' . $e->getMessage());
            return ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }
    }

    /**
     * Get bill by ID
     */
    public function getById($id) {
        if (!$this->db) return null;

        try {
            $sql = "
                SELECT 
                    b.*,
                    CONCAT(p.first_name, ' ', p.last_name) as patient_name,
                    p.patient_id as patient_code,
                    p.phone as patient_phone,
                    p.email as patient_email,
                    a.appointment_date,
                    a.reason as appointment_reason,
                    CONCAT(u.first_name, ' ', u.last_name) as doctor_name
                FROM billing b
                JOIN patients p ON b.patient_id = p.id
                LEFT JOIN appointments a ON b.appointment_id = a.id
                LEFT JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN users u ON d.user_id = u.id
                WHERE b.id = :id
                LIMIT 1
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

        } catch (Exception $e) {
            error_log('[BillingController::getById] ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create new bill
     */
    public function create($data, $user_id = null) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];

        $required = ['patient_id', 'total_amount', 'bill_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field $field is required"];
            }
        }

        try {
            // Generate bill ID
            $bill_id = $this->generateBillId();

            // Calculate amounts
            $consultation_fee = (float)($data['consultation_fee'] ?? 0);
            $lab_charges = (float)($data['lab_charges'] ?? 0);
            $medication_charges = (float)($data['medication_charges'] ?? 0);
            $other_charges = (float)($data['other_charges'] ?? 0);
            $total_amount = $consultation_fee + $lab_charges + $medication_charges + $other_charges;
            
            $discount_amount = (float)($data['discount_amount'] ?? 0);
            $tax_amount = (float)($data['tax_amount'] ?? 0);
            $final_total = $total_amount - $discount_amount + $tax_amount;
            
            $paid_amount = (float)($data['paid_amount'] ?? 0);
            $balance_amount = $final_total - $paid_amount;

            // Determine payment status
            $payment_status = 'pending';
            if ($paid_amount >= $final_total) {
                $payment_status = 'paid';
            } elseif ($paid_amount > 0) {
                $payment_status = 'partial';
            }

            $sql = "
                INSERT INTO billing (
                    bill_id, patient_id, appointment_id, consultation_fee, lab_charges, 
                    medication_charges, other_charges, total_amount, discount_amount, 
                    tax_amount, paid_amount, balance_amount, payment_status, 
                    payment_method, bill_date, due_date, payment_date, created_by
                ) VALUES (
                    :bill_id, :patient_id, :appointment_id, :consultation_fee, :lab_charges,
                    :medication_charges, :other_charges, :total_amount, :discount_amount,
                    :tax_amount, :paid_amount, :balance_amount, :payment_status,
                    :payment_method, :bill_date, :due_date, :payment_date, :created_by
                )
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':bill_id', $bill_id);
            $stmt->bindValue(':patient_id', (int)$data['patient_id'], PDO::PARAM_INT);
            $stmt->bindValue(':appointment_id', !empty($data['appointment_id']) ? (int)$data['appointment_id'] : null, PDO::PARAM_INT);
            $stmt->bindValue(':consultation_fee', $consultation_fee);
            $stmt->bindValue(':lab_charges', $lab_charges);
            $stmt->bindValue(':medication_charges', $medication_charges);
            $stmt->bindValue(':other_charges', $other_charges);
            $stmt->bindValue(':total_amount', $final_total);
            $stmt->bindValue(':discount_amount', $discount_amount);
            $stmt->bindValue(':tax_amount', $tax_amount);
            $stmt->bindValue(':paid_amount', $paid_amount);
            $stmt->bindValue(':balance_amount', $balance_amount);
            $stmt->bindValue(':payment_status', $payment_status);
            $stmt->bindValue(':payment_method', $data['payment_method'] ?? 'cash');
            $stmt->bindValue(':bill_date', $data['bill_date']);
            $stmt->bindValue(':due_date', $data['due_date'] ?? null);
            $stmt->bindValue(':payment_date', ($payment_status === 'paid') ? date('Y-m-d') : null);
            $stmt->bindValue(':created_by', $user_id, PDO::PARAM_INT);
            
            $stmt->execute();
            $id = $this->db->lastInsertId();

            return ['success' => true, 'id' => $id, 'bill_id' => $bill_id];

        } catch (Exception $e) {
            error_log('[BillingController::create] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create bill'];
        }
    }

    /**
     * Update bill
     */
    public function update($id, $data) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];

        try {
            // Get current bill
            $current = $this->getById($id);
            if (!$current) {
                return ['success' => false, 'message' => 'Bill not found'];
            }

            // Calculate amounts
            $consultation_fee = (float)($data['consultation_fee'] ?? $current['consultation_fee']);
            $lab_charges = (float)($data['lab_charges'] ?? $current['lab_charges']);
            $medication_charges = (float)($data['medication_charges'] ?? $current['medication_charges']);
            $other_charges = (float)($data['other_charges'] ?? $current['other_charges']);
            $total_amount = $consultation_fee + $lab_charges + $medication_charges + $other_charges;
            
            $discount_amount = (float)($data['discount_amount'] ?? $current['discount_amount']);
            $tax_amount = (float)($data['tax_amount'] ?? $current['tax_amount']);
            $final_total = $total_amount - $discount_amount + $tax_amount;
            
            $paid_amount = (float)($data['paid_amount'] ?? $current['paid_amount']);
            $balance_amount = $final_total - $paid_amount;

            // Determine payment status
            $payment_status = $data['payment_status'] ?? $current['payment_status'];
            if ($paid_amount >= $final_total) {
                $payment_status = 'paid';
            } elseif ($paid_amount > 0) {
                $payment_status = 'partial';
            } else {
                $payment_status = 'pending';
            }

            $sql = "
                UPDATE billing SET 
                    consultation_fee = :consultation_fee,
                    lab_charges = :lab_charges,
                    medication_charges = :medication_charges,
                    other_charges = :other_charges,
                    total_amount = :total_amount,
                    discount_amount = :discount_amount,
                    tax_amount = :tax_amount,
                    paid_amount = :paid_amount,
                    balance_amount = :balance_amount,
                    payment_status = :payment_status,
                    payment_method = :payment_method,
                    bill_date = :bill_date,
                    due_date = :due_date,
                    payment_date = :payment_date,
                    updated_at = NOW()
                WHERE id = :id
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':consultation_fee', $consultation_fee);
            $stmt->bindValue(':lab_charges', $lab_charges);
            $stmt->bindValue(':medication_charges', $medication_charges);
            $stmt->bindValue(':other_charges', $other_charges);
            $stmt->bindValue(':total_amount', $final_total);
            $stmt->bindValue(':discount_amount', $discount_amount);
            $stmt->bindValue(':tax_amount', $tax_amount);
            $stmt->bindValue(':paid_amount', $paid_amount);
            $stmt->bindValue(':balance_amount', $balance_amount);
            $stmt->bindValue(':payment_status', $payment_status);
            $stmt->bindValue(':payment_method', $data['payment_method'] ?? $current['payment_method']);
            $stmt->bindValue(':bill_date', $data['bill_date'] ?? $current['bill_date']);
            $stmt->bindValue(':due_date', $data['due_date'] ?? $current['due_date']);
            $stmt->bindValue(':payment_date', ($payment_status === 'paid' && !$current['payment_date']) ? date('Y-m-d') : $current['payment_date']);
            $stmt->bindValue(':id', (int)$id, PDO::PARAM_INT);
            
            $stmt->execute();

            return ['success' => true, 'id' => $id];

        } catch (Exception $e) {
            error_log('[BillingController::update] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to update bill'];
        }
    }

    /**
     * Get billing statistics
     */
    public function getStatistics($period = 'month') {
        if (!$this->db) return [];

        try {
            $where = '';
            if ($period === 'today') {
                $where = "WHERE DATE(bill_date) = CURDATE()";
            } elseif ($period === 'week') {
                $where = "WHERE bill_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            } elseif ($period === 'month') {
                $where = "WHERE bill_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            }

            $sql = "
                SELECT 
                    COUNT(*) as total_bills,
                    SUM(total_amount) as total_billed,
                    SUM(paid_amount) as total_paid,
                    SUM(balance_amount) as total_outstanding,
                    SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_bills,
                    SUM(CASE WHEN payment_status = 'pending' THEN 1 ELSE 0 END) as pending_bills,
                    SUM(CASE WHEN payment_status = 'overdue' THEN 1 ELSE 0 END) as overdue_bills
                FROM billing 
                $where
            ";

            $stmt = $this->db->query($sql);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        } catch (Exception $e) {
            error_log('[BillingController::getStatistics] ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Generate unique bill ID
     */
    private function generateBillId() {
        $prefix = 'BILL';
        $date = date('Ymd');
        
        // Get next sequence number for today
        $sql = "SELECT COUNT(*) FROM billing WHERE DATE(created_at) = CURDATE()";
        $stmt = $this->db->query($sql);
        $count = (int)$stmt->fetchColumn() + 1;
        
        return $prefix . $date . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Create bill from appointment
     */
    public function createFromAppointment($appointment_id, $consultation_fee, $user_id = null) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];

        try {
            // Get appointment details
            $sql = "
                SELECT a.*, d.consultation_fee as doctor_fee
                FROM appointments a
                LEFT JOIN doctors d ON a.doctor_id = d.id
                WHERE a.id = :appointment_id
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':appointment_id', (int)$appointment_id, PDO::PARAM_INT);
            $stmt->execute();
            
            $appointment = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$appointment) {
                return ['success' => false, 'message' => 'Appointment not found'];
            }

            // Check if bill already exists for this appointment
            $checkSql = "SELECT id FROM billing WHERE appointment_id = :appointment_id LIMIT 1";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bindValue(':appointment_id', (int)$appointment_id, PDO::PARAM_INT);
            $checkStmt->execute();
            
            if ($checkStmt->fetchColumn()) {
                return ['success' => false, 'message' => 'Bill already exists for this appointment'];
            }

            // Create bill data
            $billData = [
                'patient_id' => $appointment['patient_id'],
                'appointment_id' => $appointment_id,
                'consultation_fee' => $consultation_fee ?: ($appointment['doctor_fee'] ?: 0),
                'total_amount' => $consultation_fee ?: ($appointment['doctor_fee'] ?: 0),
                'bill_date' => date('Y-m-d'),
                'due_date' => date('Y-m-d', strtotime('+30 days')),
                'payment_method' => 'cash'
            ];

            return $this->create($billData, $user_id);

        } catch (Exception $e) {
            error_log('[BillingController::createFromAppointment] ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to create bill from appointment'];
        }
    }
}
?>
