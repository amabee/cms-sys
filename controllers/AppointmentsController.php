<?php
require_once __DIR__ . '/../shared/config.php';

class AppointmentsController {
    protected $db;

    public function __construct() {
        if (function_exists('getDBConnection')) $this->db = getDBConnection(); else $this->db = null;
    }

    public function listForDataTable($request) {
        $draw = isset($request['draw']) ? (int)$request['draw'] : null;
        $start = isset($request['start']) ? (int)$request['start'] : 0;
        $length = isset($request['length']) ? (int)$request['length'] : 10;

        $where = '';
        $params = [];
        if (!empty($request['search']) && is_array($request['search']) && isset($request['search']['value'])) {
            $q = trim($request['search']['value']);
            if ($q !== '') {
                $where = "WHERE (a.appointment_id LIKE :q OR p.first_name LIKE :q OR p.last_name LIKE :q OR u.first_name LIKE :q OR u.last_name LIKE :q OR a.status LIKE :q)";
                $params[':q'] = "%$q%";
            }
        }

        if (!$this->db) return ['draw'=>$draw,'recordsTotal'=>0,'recordsFiltered'=>0,'data'=>[]];

        $total = (int)$this->db->query('SELECT COUNT(*) FROM appointments')->fetchColumn();
        if ($where !== '') {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id JOIN users u ON d.user_id = u.id $where");
            foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
            $stmt->execute();
            $recordsFiltered = (int)$stmt->fetchColumn();
        } else $recordsFiltered = $total;

        $orderSql = 'ORDER BY a.appointment_date DESC, a.appointment_time DESC';
        if (isset($request['order']) && isset($request['columns'])) {
            $orderParts = [];
            foreach ($request['order'] as $ord) {
                $ci = (int)$ord['column']; $dir = strtoupper($ord['dir'])==='DESC'?'DESC':'ASC';
                $col = $request['columns'][$ci]['data'] ?? null;
                $map = ['appointment_id'=>'a.appointment_id','patient'=>'p.first_name','doctor'=>'u.first_name','appointment_date'=>'a.appointment_date','appointment_time'=>'a.appointment_time','status'=>'a.status'];
                if ($col && isset($map[$col])) $orderParts[] = $map[$col].' '.$dir;
            }
            if (count($orderParts)) $orderSql = 'ORDER BY '.implode(',',$orderParts);
        }

        $sql = "SELECT a.id, a.appointment_id, a.patient_id, a.doctor_id, a.appointment_date, a.appointment_time, a.status, a.reason, p.patient_id as patient_code, p.first_name as p_first, p.last_name as p_last, u.first_name as d_first, u.last_name as d_last, d.doctor_id as doctor_code FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id JOIN users u ON d.user_id = u.id $where $orderSql LIMIT :start, :length";
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->bindValue(':start', (int)$start, PDO::PARAM_INT);
        $stmt->bindValue(':length', (int)$length, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $data = [];
        foreach ($rows as $r) {
            $data[] = [
                'id'=>$r['id'],
                'appointment_id'=>$r['appointment_id'],
                'patient'=>($r['p_first'].' '.$r['p_last']),
                'patient_code'=>$r['patient_code'],
                'doctor'=>($r['d_first'].' '.$r['d_last']),
                'doctor_code'=>$r['doctor_code'],
                'appointment_date'=>$r['appointment_date'],
                'appointment_time'=>$r['appointment_time'],
                'status'=>$r['status'],
                'reason'=>$r['reason']
            ];
        }

        return ['draw'=>$draw,'recordsTotal'=>$total,'recordsFiltered'=>$recordsFiltered,'data'=>$data];
    }

    public function getById($id) {
        if (!$this->db) return null;
        $id = (int)$id;
        $stmt = $this->db->prepare('SELECT a.*, p.patient_id as patient_code, p.first_name as p_first, p.last_name as p_last, d.doctor_id as doctor_code, u.first_name as d_first, u.last_name as d_last FROM appointments a JOIN patients p ON a.patient_id = p.id JOIN doctors d ON a.doctor_id = d.id JOIN users u ON d.user_id = u.id WHERE a.id = :id LIMIT 1');
        $stmt->bindValue(':id',$id,PDO::PARAM_INT);
        $stmt->execute();
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    public function create($data, $user_id=null) {
        if (!$this->db) return ['success'=>false,'message'=>'DB unavailable'];
        $patient_id = isset($data['patient_id']) ? (int)$data['patient_id'] : 0;
        $doctor_id = isset($data['doctor_id']) ? (int)$data['doctor_id'] : 0;
        $date = $data['appointment_date'] ?? '';
        $time = $data['appointment_time'] ?? '';
        $reason = $data['reason'] ?? '';
        if ($patient_id<=0 || $doctor_id<=0 || !$date || !$time) return ['success'=>false,'message'=>'Invalid input'];

        // reuse existing validations: clinic hours and doctor schedule
        // settings
        $settingsStmt = $this->db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('working_hours_start','working_hours_end','working_days')");
        $settingsStmt->execute(); $settings = [];
        foreach ($settingsStmt->fetchAll(PDO::FETCH_ASSOC) as $s) $settings[$s['setting_key']] = $s['setting_value'];
        $working_start = $settings['working_hours_start'] ?? '08:00';
        $working_end = $settings['working_hours_end'] ?? '20:00';
        $working_days = json_decode($settings['working_days'] ?? '[]', true) ?: [];
        $apptDay = strtolower(date('l', strtotime($date)));
        if (!in_array($apptDay, $working_days)) return ['success'=>false,'message'=>'Clinic is closed on selected day'];
        if ($time < $working_start || $time > $working_end) return ['success'=>false,'message'=>'Selected time is outside clinic working hours'];

        $scheduleStmt = $this->db->prepare('SELECT start_time, end_time FROM doctor_schedules WHERE doctor_id = :did AND day_of_week = :dow LIMIT 1');
        $scheduleStmt->bindValue(':did',$doctor_id,PDO::PARAM_INT);
        $scheduleStmt->bindValue(':dow',$apptDay,PDO::PARAM_STR);
        $scheduleStmt->execute(); $sched = $scheduleStmt->fetch(PDO::FETCH_ASSOC);
        if (!$sched) return ['success'=>false,'message'=>'Doctor is not scheduled on selected day'];
        if ($time < $sched['start_time'] || $time > $sched['end_time']) return ['success'=>false,'message'=>'Selected time is outside doctor working hours'];

        // overlapping check
        $overlapStmt = $this->db->prepare('SELECT COUNT(*) FROM appointments WHERE doctor_id = :did AND appointment_date = :adate AND appointment_time = :atime AND status IN ("scheduled","in_progress")');
        $overlapStmt->bindValue(':did',$doctor_id,PDO::PARAM_INT);
        $overlapStmt->bindValue(':adate',$date,PDO::PARAM_STR);
        $overlapStmt->bindValue(':atime',$time,PDO::PARAM_STR);
        $overlapStmt->execute(); if ((int)$overlapStmt->fetchColumn()>0) return ['success'=>false,'message'=>'Doctor already has an appointment at this time'];

        try {
            $stmt = $this->db->prepare('INSERT INTO appointments (appointment_id, patient_id, doctor_id, appointment_date, appointment_time, status, reason, created_by) VALUES (:aid,:pid,:did,:adate,:atime,:status,:reason,:created_by)');
            $stmt->bindValue(':aid', null, PDO::PARAM_NULL);
            $stmt->bindValue(':pid', $patient_id, PDO::PARAM_INT);
            $stmt->bindValue(':did', $doctor_id, PDO::PARAM_INT);
            $stmt->bindValue(':adate', $date, PDO::PARAM_STR);
            $stmt->bindValue(':atime', $time, PDO::PARAM_STR);
            $stmt->bindValue(':status', 'scheduled', PDO::PARAM_STR);
            $stmt->bindValue(':reason', $reason?:null, PDO::PARAM_NULL);
            $stmt->bindValue(':created_by', $user_id?:null, PDO::PARAM_INT);
            $stmt->execute();
            return ['success'=>true,'id'=>$this->db->lastInsertId()];
        } catch (Exception $e) {
            error_log('[AppointmentsController::create] '.$e->getMessage());
            return ['success'=>false,'message'=>'Insert failed'];
        }
    }

    public function update($id, $data) {
        if (!$this->db) return ['success'=>false,'message'=>'DB unavailable'];
        $id = (int)$id; if ($id<=0) return ['success'=>false,'message'=>'Invalid id'];
        // allow updating date, time, doctor, status, reason
        $fields = [];
        $bind = [];
        if (isset($data['appointment_date'])) { $fields[] = 'appointment_date = :adate'; $bind[':adate']=$data['appointment_date']; }
        if (isset($data['appointment_time'])) { $fields[] = 'appointment_time = :atime'; $bind[':atime']=$data['appointment_time']; }
        if (isset($data['doctor_id'])) { $fields[] = 'doctor_id = :did'; $bind[':did']=(int)$data['doctor_id']; }
        if (isset($data['status'])) { $fields[] = 'status = :status'; $bind[':status']=$data['status']; }
        if (isset($data['reason'])) { $fields[] = 'reason = :reason'; $bind[':reason']=$data['reason']; }
        if (count($fields)===0) return ['success'=>false,'message'=>'No changes'];
        $sql = 'UPDATE appointments SET '.implode(',', $fields).', updated_at = NOW() WHERE id = :id';
        try {
            $stmt = $this->db->prepare($sql);
            foreach ($bind as $k=>$v) $stmt->bindValue($k,$v);
            $stmt->bindValue(':id',$id,PDO::PARAM_INT);
            $stmt->execute();
            return ['success'=>true,'id'=>$id];
        } catch (Exception $e) {
            error_log('[AppointmentsController::update] '.$e->getMessage());
            return ['success'=>false,'message'=>'Update failed'];
        }
    }
}
