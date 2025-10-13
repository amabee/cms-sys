<?php
class PrescriptionController {
    private $conn;
    private $logger;
    
    public function __construct($database) {
        $this->conn = $database;
        $this->logger = new SystemLogger();
    }
    
    /**
     * Create a new prescription
     */
    public function createPrescription($prescriptionData, $userId) {
        try {
            $this->conn->beginTransaction();
            
            // Validate required fields
            $requiredFields = ['patient_id', 'doctor_id', 'prescription_date', 'items'];
            foreach ($requiredFields as $field) {
                if (!isset($prescriptionData[$field]) || empty($prescriptionData[$field])) {
                    throw new Exception("Required field missing: {$field}");
                }
            }
            
            // Check for patient allergies if requested
            if (isset($prescriptionData['check_allergies']) && $prescriptionData['check_allergies']) {
                $allergyCheck = $this->checkPatientAllergies($prescriptionData['patient_id'], $prescriptionData['items']);
                if (!$allergyCheck['safe']) {
                    throw new Exception("Allergy conflict detected: " . $allergyCheck['message']);
                }
            }
            
            // Check for drug interactions if requested
            if (isset($prescriptionData['check_interactions']) && $prescriptionData['check_interactions']) {
                $interactionCheck = $this->checkDrugInteractions($prescriptionData['items']);
                if (!$interactionCheck['safe']) {
                    throw new Exception("Drug interaction detected: " . $interactionCheck['message']);
                }
            }
            
            // Insert prescription record
            $sql = "INSERT INTO prescriptions (patient_id, doctor_id, appointment_id, prescription_date, 
                                            total_cost, insurance_covered, patient_copay, pharmacy_id, 
                                            notes, allergies_checked, interactions_checked, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $prescriptionData['patient_id'],
                $prescriptionData['doctor_id'],
                $prescriptionData['appointment_id'] ?? null,
                $prescriptionData['prescription_date'],
                $prescriptionData['total_cost'] ?? 0.00,
                $prescriptionData['insurance_covered'] ?? 0.00,
                $prescriptionData['patient_copay'] ?? 0.00,
                $prescriptionData['pharmacy_id'] ?? null,
                $prescriptionData['notes'] ?? '',
                isset($prescriptionData['check_allergies']) ? 1 : 0,
                isset($prescriptionData['check_interactions']) ? 1 : 0,
                $userId
            ]);
            
            $prescriptionId = $this->conn->lastInsertId();
            
            // Insert prescription items
            foreach ($prescriptionData['items'] as $item) {
                $this->addPrescriptionItem($prescriptionId, $item);
            }
            
            $this->conn->commit();
            
            $this->logger->log($userId, 'CREATE', "Created prescription ID: {$prescriptionId}");
            
            return ['success' => true, 'prescription_id' => $prescriptionId, 'message' => 'Prescription created successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            $this->logger->log($userId, 'ERROR', "Failed to create prescription: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Add item to prescription
     */
    private function addPrescriptionItem($prescriptionId, $itemData) {
        $sql = "INSERT INTO prescription_items (prescription_id, medication_id, quantity, unit, 
                                              dosage_instruction, frequency, duration_days, 
                                              refills_allowed, refills_remaining, item_cost, 
                                              generic_substitution_allowed, special_instructions, 
                                              start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute([
            $prescriptionId,
            $itemData['medication_id'],
            $itemData['quantity'],
            $itemData['unit'] ?? 'tablets',
            $itemData['dosage_instruction'],
            $itemData['frequency'],
            $itemData['duration_days'] ?? null,
            $itemData['refills_allowed'] ?? 0,
            $itemData['refills_allowed'] ?? 0, // Initially same as allowed
            $itemData['item_cost'] ?? 0.00,
            $itemData['generic_substitution_allowed'] ?? true,
            $itemData['special_instructions'] ?? '',
            $itemData['start_date'] ?? date('Y-m-d'),
            $itemData['end_date'] ?? null
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Get prescriptions with filtering and pagination
     */
    public function getPrescriptions($filters = [], $page = 1, $limit = 20) {
        try {
            $offset = ($page - 1) * $limit;
            
            $sql = "SELECT p.*, 
                           CONCAT(pt.first_name, ' ', pt.last_name) as patient_name,
                           CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                           ph.pharmacy_name,
                           COUNT(pi.id) as item_count
                    FROM prescriptions p 
                    JOIN patients pt ON p.patient_id = pt.id 
                    JOIN users u ON p.doctor_id = u.id 
                    LEFT JOIN pharmacies ph ON p.pharmacy_id = ph.id
                    LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
                    WHERE 1=1";
            
            $params = [];
            
            // Apply filters
            if (!empty($filters['patient_id'])) {
                $sql .= " AND p.patient_id = ?";
                $params[] = $filters['patient_id'];
            }
            
            if (!empty($filters['doctor_id'])) {
                $sql .= " AND p.doctor_id = ?";
                $params[] = $filters['doctor_id'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND p.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['date_from'])) {
                $sql .= " AND p.prescription_date >= ?";
                $params[] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $sql .= " AND p.prescription_date <= ?";
                $params[] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (p.prescription_number LIKE ? OR CONCAT(pt.first_name, ' ', pt.last_name) LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " GROUP BY p.id ORDER BY p.prescription_date DESC, p.id DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count for pagination
            $countSql = str_replace("SELECT p.*, CONCAT(pt.first_name, ' ', pt.last_name) as patient_name, CONCAT(u.first_name, ' ', u.last_name) as doctor_name, ph.pharmacy_name, COUNT(pi.id) as item_count", "SELECT COUNT(DISTINCT p.id)", $sql);
            $countSql = str_replace(" GROUP BY p.id ORDER BY p.prescription_date DESC, p.id DESC LIMIT ? OFFSET ?", "", $countSql);
            array_pop($params); // Remove offset
            array_pop($params); // Remove limit
            
            $countStmt = $this->conn->prepare($countSql);
            $countStmt->execute($params);
            $totalCount = $countStmt->fetchColumn();
            
            return [
                'success' => true,
                'data' => $prescriptions,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalCount,
                    'total_pages' => ceil($totalCount / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to retrieve prescriptions: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get detailed prescription information
     */
    public function getPrescriptionDetails($prescriptionId) {
        try {
            // Get prescription basic info
            $sql = "SELECT p.*, 
                           CONCAT(pt.first_name, ' ', pt.last_name) as patient_name,
                           pt.date_of_birth, pt.phone_number,
                           CONCAT(u.first_name, ' ', u.last_name) as doctor_name,
                           u.specialization,
                           ph.pharmacy_name, ph.phone_number as pharmacy_phone,
                           ph.address_line1, ph.city, ph.state
                    FROM prescriptions p 
                    JOIN patients pt ON p.patient_id = pt.id 
                    JOIN users u ON p.doctor_id = u.id 
                    LEFT JOIN pharmacies ph ON p.pharmacy_id = ph.id
                    WHERE p.id = ?";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$prescriptionId]);
            $prescription = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$prescription) {
                return ['success' => false, 'message' => 'Prescription not found'];
            }
            
            // Get prescription items
            $sql = "SELECT pi.*, m.medication_name, m.generic_name, m.brand_name, 
                           m.strength, m.dosage_form, m.controlled_substance_schedule
                    FROM prescription_items pi 
                    JOIN medications m ON pi.medication_id = m.id 
                    WHERE pi.prescription_id = ? 
                    ORDER BY pi.id";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$prescriptionId]);
            $prescription['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get refill history
            $sql = "SELECT pr.*, pi.medication_id, m.medication_name
                    FROM prescription_refills pr
                    JOIN prescription_items pi ON pr.prescription_item_id = pi.id
                    JOIN medications m ON pi.medication_id = m.id
                    WHERE pi.prescription_id = ?
                    ORDER BY pr.refill_date DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$prescriptionId]);
            $prescription['refills'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get patient allergies
            $sql = "SELECT * FROM patient_allergies 
                    WHERE patient_id = ? AND is_active = 1 
                    ORDER BY severity DESC";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$prescription['patient_id']]);
            $prescription['patient_allergies'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $prescription];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to retrieve prescription details: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check patient allergies against prescribed medications
     */
    public function checkPatientAllergies($patientId, $medicationItems) {
        try {
            $medicationIds = array_column($medicationItems, 'medication_id');
            
            if (empty($medicationIds)) {
                return ['safe' => true, 'allergies' => []];
            }
            
            $placeholders = str_repeat('?,', count($medicationIds) - 1) . '?';
            $sql = "SELECT pa.*, m.medication_name 
                    FROM patient_allergies pa 
                    JOIN medications m ON pa.medication_id = m.id 
                    WHERE pa.patient_id = ? AND pa.medication_id IN ({$placeholders}) AND pa.is_active = 1";
            
            $params = array_merge([$patientId], $medicationIds);
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $allergies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($allergies)) {
                $allergyList = array_map(function($allergy) {
                    return $allergy['medication_name'] . ' (' . $allergy['severity'] . ')';
                }, $allergies);
                
                return [
                    'safe' => false,
                    'message' => 'Patient has allergies to: ' . implode(', ', $allergyList),
                    'allergies' => $allergies
                ];
            }
            
            return ['safe' => true, 'allergies' => []];
            
        } catch (Exception $e) {
            return ['safe' => false, 'message' => 'Error checking allergies: ' . $e->getMessage()];
        }
    }
    
    /**
     * Check for drug interactions
     */
    public function checkDrugInteractions($medicationItems) {
        try {
            $medicationIds = array_column($medicationItems, 'medication_id');
            
            if (count($medicationIds) < 2) {
                return ['safe' => true, 'interactions' => []];
            }
            
            $interactions = [];
            
            // Check all medication pairs for interactions
            for ($i = 0; $i < count($medicationIds); $i++) {
                for ($j = $i + 1; $j < count($medicationIds); $j++) {
                    $med1 = $medicationIds[$i];
                    $med2 = $medicationIds[$j];
                    
                    $sql = "SELECT di.*, 
                                   m1.medication_name as med1_name,
                                   m2.medication_name as med2_name
                            FROM drug_interactions di
                            JOIN medications m1 ON di.medication1_id = m1.id
                            JOIN medications m2 ON di.medication2_id = m2.id
                            WHERE ((di.medication1_id = ? AND di.medication2_id = ?) 
                                   OR (di.medication1_id = ? AND di.medication2_id = ?))
                                  AND di.is_active = 1";
                    
                    $stmt = $this->conn->prepare($sql);
                    $stmt->execute([$med1, $med2, $med2, $med1]);
                    $interaction = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($interaction) {
                        $interactions[] = $interaction;
                    }
                }
            }
            
            if (!empty($interactions)) {
                $majorInteractions = array_filter($interactions, function($int) {
                    return $int['interaction_type'] === 'major' || $int['interaction_type'] === 'contraindicated';
                });
                
                if (!empty($majorInteractions)) {
                    $interactionList = array_map(function($int) {
                        return $int['med1_name'] . ' + ' . $int['med2_name'] . ' (' . $int['interaction_type'] . ')';
                    }, $majorInteractions);
                    
                    return [
                        'safe' => false,
                        'message' => 'Major drug interactions found: ' . implode(', ', $interactionList),
                        'interactions' => $interactions
                    ];
                }
                
                return [
                    'safe' => true,
                    'message' => 'Minor interactions detected - review recommended',
                    'interactions' => $interactions
                ];
            }
            
            return ['safe' => true, 'interactions' => []];
            
        } catch (Exception $e) {
            return ['safe' => false, 'message' => 'Error checking interactions: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get medications list with search
     */
    public function getMedications($search = '', $limit = 50) {
        try {
            $sql = "SELECT * FROM medications WHERE is_active = 1";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (medication_name LIKE ? OR generic_name LIKE ? OR brand_name LIKE ?)";
                $searchTerm = '%' . $search . '%';
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            $sql .= " ORDER BY medication_name LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $medications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $medications];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to retrieve medications: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get pharmacies list
     */
    public function getPharmacies() {
        try {
            $sql = "SELECT * FROM pharmacies WHERE is_active = 1 ORDER BY pharmacy_name";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute();
            $pharmacies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $pharmacies];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to retrieve pharmacies: ' . $e->getMessage()];
        }
    }
    
    /**
     * Process prescription refill
     */
    public function processRefill($prescriptionItemId, $refillData, $userId) {
        try {
            $this->conn->beginTransaction();
            
            // Check if refills are available
            $sql = "SELECT * FROM prescription_items WHERE id = ? AND refills_remaining > 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$prescriptionItemId]);
            $item = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$item) {
                throw new Exception("No refills available for this prescription item");
            }
            
            // Get next refill number
            $sql = "SELECT COALESCE(MAX(refill_number), 0) + 1 as next_refill 
                    FROM prescription_refills WHERE prescription_item_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$prescriptionItemId]);
            $refillNumber = $stmt->fetchColumn();
            
            // Insert refill record
            $sql = "INSERT INTO prescription_refills (prescription_item_id, refill_number, refill_date,
                                                    quantity_dispensed, pharmacist_id, pharmacy_id,
                                                    refill_cost, insurance_claim_number, patient_copay,
                                                    notes, dispensed_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $prescriptionItemId,
                $refillNumber,
                $refillData['refill_date'] ?? date('Y-m-d'),
                $refillData['quantity_dispensed'],
                $refillData['pharmacist_id'] ?? null,
                $refillData['pharmacy_id'] ?? null,
                $refillData['refill_cost'] ?? 0.00,
                $refillData['insurance_claim_number'] ?? '',
                $refillData['patient_copay'] ?? 0.00,
                $refillData['notes'] ?? '',
                $refillData['dispensed_by'] ?? ''
            ]);
            
            $refillId = $this->conn->lastInsertId();
            
            // Update refills remaining (trigger will handle this automatically)
            
            $this->conn->commit();
            
            $this->logger->log($userId, 'REFILL', "Processed refill for prescription item ID: {$prescriptionItemId}");
            
            return ['success' => true, 'refill_id' => $refillId, 'message' => 'Refill processed successfully'];
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update prescription status
     */
    public function updatePrescriptionStatus($prescriptionId, $status, $userId, $reason = '') {
        try {
            $validStatuses = ['active', 'completed', 'cancelled', 'expired', 'discontinued'];
            if (!in_array($status, $validStatuses)) {
                return ['success' => false, 'message' => 'Invalid status'];
            }
            
            $sql = "UPDATE prescriptions SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$status, $prescriptionId]);
            
            // Log the status change
            $sql = "INSERT INTO prescription_history (prescription_id, action, performed_by, new_values, reason)
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([
                $prescriptionId,
                'modified',
                $userId,
                json_encode(['status' => $status]),
                $reason
            ]);
            
            $this->logger->log($userId, 'UPDATE', "Updated prescription ID {$prescriptionId} status to {$status}");
            
            return ['success' => true, 'message' => 'Prescription status updated successfully'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to update prescription status: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get prescription statistics
     */
    public function getPrescriptionStatistics($doctorId = null, $dateFrom = null, $dateTo = null) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_prescriptions,
                        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_prescriptions,
                        COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_prescriptions,
                        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_prescriptions,
                        AVG(total_cost) as avg_cost,
                        SUM(total_cost) as total_cost
                    FROM prescriptions 
                    WHERE 1=1";
            
            $params = [];
            
            if ($doctorId) {
                $sql .= " AND doctor_id = ?";
                $params[] = $doctorId;
            }
            
            if ($dateFrom) {
                $sql .= " AND prescription_date >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND prescription_date <= ?";
                $params[] = $dateTo;
            }
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get most prescribed medications
            $sql = "SELECT m.medication_name, COUNT(*) as prescription_count
                    FROM prescription_items pi
                    JOIN medications m ON pi.medication_id = m.id
                    JOIN prescriptions p ON pi.prescription_id = p.id
                    WHERE 1=1";
            
            $params = [];
            if ($doctorId) {
                $sql .= " AND p.doctor_id = ?";
                $params[] = $doctorId;
            }
            
            if ($dateFrom) {
                $sql .= " AND p.prescription_date >= ?";
                $params[] = $dateFrom;
            }
            
            if ($dateTo) {
                $sql .= " AND p.prescription_date <= ?";
                $params[] = $dateTo;
            }
            
            $sql .= " GROUP BY m.id ORDER BY prescription_count DESC LIMIT 10";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            $stats['top_medications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return ['success' => true, 'data' => $stats];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to retrieve statistics: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get prescription templates for a doctor
     */
    public function getPrescriptionTemplates($doctorId) {
        try {
            $sql = "SELECT * FROM prescription_templates 
                    WHERE (doctor_id = ? OR is_public = 1) AND is_active = 1 
                    ORDER BY usage_count DESC, template_name";
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute([$doctorId]);
            $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode JSON template data
            foreach ($templates as &$template) {
                $template['template_data'] = json_decode($template['template_data'], true);
            }
            
            return ['success' => true, 'data' => $templates];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to retrieve templates: ' . $e->getMessage()];
        }
    }
    
    /**
     * Add or update patient allergy
     */
    public function managePatientAllergy($allergyData, $userId) {
        try {
            if (isset($allergyData['id']) && $allergyData['id']) {
                // Update existing allergy
                $sql = "UPDATE patient_allergies 
                        SET allergen_name = ?, allergen_type = ?, medication_id = ?, 
                            reaction_type = ?, severity = ?, symptoms = ?, notes = ?,
                            verified_by = ?, verified_date = CURRENT_TIMESTAMP
                        WHERE id = ?";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    $allergyData['allergen_name'],
                    $allergyData['allergen_type'],
                    $allergyData['medication_id'] ?? null,
                    $allergyData['reaction_type'] ?? '',
                    $allergyData['severity'],
                    $allergyData['symptoms'] ?? '',
                    $allergyData['notes'] ?? '',
                    $userId,
                    $allergyData['id']
                ]);
                
                $message = 'Allergy updated successfully';
            } else {
                // Add new allergy
                $sql = "INSERT INTO patient_allergies (patient_id, allergen_type, allergen_name, 
                                                     medication_id, reaction_type, severity, 
                                                     symptoms, onset_date, notes, verified_by)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $stmt = $this->conn->prepare($sql);
                $stmt->execute([
                    $allergyData['patient_id'],
                    $allergyData['allergen_type'],
                    $allergyData['allergen_name'],
                    $allergyData['medication_id'] ?? null,
                    $allergyData['reaction_type'] ?? '',
                    $allergyData['severity'],
                    $allergyData['symptoms'] ?? '',
                    $allergyData['onset_date'] ?? null,
                    $allergyData['notes'] ?? '',
                    $userId
                ]);
                
                $message = 'Allergy added successfully';
            }
            
            return ['success' => true, 'message' => $message];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to manage allergy: ' . $e->getMessage()];
        }
    }
}
?>
