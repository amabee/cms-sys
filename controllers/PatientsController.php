<?php
require_once __DIR__ . '/../shared/config.php';

class PatientsController {
    protected $db;

    public function __construct() {
        if (function_exists('getDBConnection')) {
            $this->db = getDBConnection();
        } else {
            $this->db = null;
        }
    }

    public function listForDataTable($request) {
        // $request is the $_GET array from DataTables
        $draw = isset($request['draw']) ? (int)$request['draw'] : null;
        $start = isset($request['start']) ? (int)$request['start'] : 0;
        $length = isset($request['length']) ? (int)$request['length'] : 10;

        $searchValue = '';
        if (isset($request['search']) && is_array($request['search']) && isset($request['search']['value'])) {
            $searchValue = trim($request['search']['value']);
        }

        $where = '';
        $params = [];
        if ($searchValue !== '') {
            $where = "WHERE (patient_id LIKE :q OR first_name LIKE :q OR last_name LIKE :q OR phone LIKE :q OR email LIKE :q OR id LIKE :q)";
            $params[':q'] = "%$searchValue%";
        }

        // total records
        $total = 0;
        $recordsFiltered = 0;
        $data = [];

        if (!$this->db) {
            return ['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []];
        }

        $countSql = "SELECT COUNT(*) FROM patients";
        $stmt = $this->db->query($countSql);
        $total = (int)$stmt->fetchColumn();

        // filtered count
        if ($where !== '') {
            $countSql = "SELECT COUNT(*) FROM patients $where";
            $stmt = $this->db->prepare($countSql);
            foreach ($params as $k => $v) $stmt->bindValue($k, $v);
            $stmt->execute();
            $recordsFiltered = (int)$stmt->fetchColumn();
        } else {
            $recordsFiltered = $total;
        }

        // ordering
        $orderSql = 'ORDER BY last_name ASC, first_name ASC';
        if (isset($request['order']) && is_array($request['order']) && isset($request['columns'])) {
            $orderParts = [];
            $columns = $request['columns'];
            foreach ($request['order'] as $ord) {
                $colIdx = (int)$ord['column'];
                $dir = strtoupper($ord['dir']) === 'DESC' ? 'DESC' : 'ASC';
                if (isset($columns[$colIdx]) && isset($columns[$colIdx]['data'])) {
                    $col = $columns[$colIdx]['data'];
                    if (in_array($col, ['id','patient_id','first_name','last_name','phone','date_of_birth','email'])) {
                        $orderParts[] = "$col $dir";
                    }
                }
            }
            if (count($orderParts) > 0) $orderSql = 'ORDER BY ' . implode(', ', $orderParts);
        }

        $sql = "SELECT id, patient_id, first_name, last_name, email, phone, date_of_birth, gender, is_active FROM patients $where $orderSql LIMIT :start, :length";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
        $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $r) {
            // normalize keys to match front-end expectations
            $data[] = [
                'id' => $r['id'],
                'patient_id' => $r['patient_id'],
                'first_name' => $r['first_name'],
                'last_name' => $r['last_name'],
                'email' => $r['email'],
                'phone' => $r['phone'],
                'date_of_birth' => $r['date_of_birth'],
                'gender' => $r['gender'],
                'is_active' => $r['is_active']
            ];
        }

        return ['draw' => $draw, 'recordsTotal' => $total, 'recordsFiltered' => $recordsFiltered, 'data' => $data];
    }

    public function create($data) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        $first_name = trim($data['first_name'] ?? '');
        $last_name = trim($data['last_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $date_of_birth = trim($data['date_of_birth'] ?? '');
        $gender = trim($data['gender'] ?? '');
        $address = trim($data['address'] ?? '');
        $emergency_contact_name = trim($data['emergency_contact_name'] ?? '');
        $emergency_contact_phone = trim($data['emergency_contact_phone'] ?? '');
        $blood_group = trim($data['blood_group'] ?? '');
        $allergies = trim($data['allergies'] ?? '');
        $insurance_info = trim($data['insurance_info'] ?? '');

        if ($first_name === '' || $last_name === '') return ['success' => false, 'message' => 'First and last name required'];

        try {
            $stmt = $this->db->prepare('INSERT INTO patients (patient_id, first_name, last_name, email, phone, date_of_birth, gender, address, emergency_contact_name, emergency_contact_phone, blood_group, allergies, insurance_info) VALUES (:patient_id, :first, :last, :email, :phone, :dob, :gender, :address, :ec_name, :ec_phone, :blood_group, :allergies, :insurance)');
            // patient_id should be NULL so DB trigger generates it if not provided
            if (!empty($data['patient_id'])) {
                $stmt->bindValue(':patient_id', $data['patient_id'], PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':patient_id', null, PDO::PARAM_NULL);
            }
            $stmt->bindValue(':first', $first_name, PDO::PARAM_STR);
            $stmt->bindValue(':last', $last_name, PDO::PARAM_STR);
            if ($email !== '') {
                $stmt->bindValue(':email', $email, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':email', null, PDO::PARAM_NULL);
            }
            if ($phone !== '') {
                $stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':phone', null, PDO::PARAM_NULL);
            }
            if ($date_of_birth !== '') {
                $stmt->bindValue(':dob', $date_of_birth, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':dob', null, PDO::PARAM_NULL);
            }
            if ($gender !== '') {
                $stmt->bindValue(':gender', $gender, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':gender', null, PDO::PARAM_NULL);
            }
            if ($address !== '') {
                $stmt->bindValue(':address', $address, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':address', null, PDO::PARAM_NULL);
            }
            if ($emergency_contact_name !== '') {
                $stmt->bindValue(':ec_name', $emergency_contact_name, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':ec_name', null, PDO::PARAM_NULL);
            }
            if ($emergency_contact_phone !== '') {
                $stmt->bindValue(':ec_phone', $emergency_contact_phone, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':ec_phone', null, PDO::PARAM_NULL);
            }
            if ($blood_group !== '') {
                $stmt->bindValue(':blood_group', $blood_group, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':blood_group', null, PDO::PARAM_NULL);
            }
            if ($allergies !== '') {
                $stmt->bindValue(':allergies', $allergies, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':allergies', null, PDO::PARAM_NULL);
            }
            if ($insurance_info !== '') {
                $stmt->bindValue(':insurance', $insurance_info, PDO::PARAM_STR);
            } else {
                $stmt->bindValue(':insurance', null, PDO::PARAM_NULL);
            }
            $stmt->execute();
            $id = $this->db->lastInsertId();
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            // Log exception for debugging (do not expose details to client)
            error_log('[PatientsController::create] Insert failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Insert failed'];
        }
    }

    public function getById($id) {
        if (!$this->db) return null;
        $id = (int)$id;
        $stmt = $this->db->prepare('SELECT id, patient_id, first_name, last_name, email, phone, date_of_birth, gender, address, emergency_contact_name, emergency_contact_phone, blood_group, allergies, medical_history, insurance_info, is_active FROM patients WHERE id = :id LIMIT 1');
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function update($id, $data) {
        if (!$this->db) return ['success' => false, 'message' => 'Database unavailable'];
        $id = (int)$id;
        if ($id <= 0) return ['success' => false, 'message' => 'Invalid id'];

        $first_name = trim($data['first_name'] ?? '');
        $last_name = trim($data['last_name'] ?? '');
        $email = trim($data['email'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $date_of_birth = trim($data['date_of_birth'] ?? '');
        $gender = trim($data['gender'] ?? '');
        $address = trim($data['address'] ?? '');
        $emergency_contact_name = trim($data['emergency_contact_name'] ?? '');
        $emergency_contact_phone = trim($data['emergency_contact_phone'] ?? '');
        $blood_group = trim($data['blood_group'] ?? '');
        $allergies = trim($data['allergies'] ?? '');
        $insurance_info = trim($data['insurance_info'] ?? '');

        if ($first_name === '' || $last_name === '') return ['success' => false, 'message' => 'First and last name required'];

        try {
            $sql = 'UPDATE patients SET first_name = :first, last_name = :last, email = :email, phone = :phone, date_of_birth = :dob, gender = :gender, address = :address, emergency_contact_name = :ec_name, emergency_contact_phone = :ec_phone, blood_group = :blood_group, allergies = :allergies, insurance_info = :insurance, updated_at = NOW() WHERE id = :id';
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':first', $first_name, PDO::PARAM_STR);
            $stmt->bindValue(':last', $last_name, PDO::PARAM_STR);
            $stmt->bindValue(':email', $email !== '' ? $email : null, $email !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':phone', $phone !== '' ? $phone : null, $phone !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':dob', $date_of_birth !== '' ? $date_of_birth : null, $date_of_birth !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':gender', $gender !== '' ? $gender : null, $gender !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':address', $address !== '' ? $address : null, $address !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':ec_name', $emergency_contact_name !== '' ? $emergency_contact_name : null, $emergency_contact_name !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':ec_phone', $emergency_contact_phone !== '' ? $emergency_contact_phone : null, $emergency_contact_phone !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':blood_group', $blood_group !== '' ? $blood_group : null, $blood_group !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':allergies', $allergies !== '' ? $allergies : null, $allergies !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':insurance', $insurance_info !== '' ? $insurance_info : null, $insurance_info !== '' ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return ['success' => true, 'id' => $id];
        } catch (Exception $e) {
            error_log('[PatientsController::update] Exception: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Update failed'];
        }
    }
}
