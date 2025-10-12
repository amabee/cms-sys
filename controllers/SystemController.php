<?php
require_once __DIR__ . '/../shared/config.php';

class SystemController
{
    public function getSystemDetails()
    {
        $defaults = [
            'name' => defined('APP_NAME') ? APP_NAME : 'Clinic Management System',
            'logo' => '',
            'address' => '',
            'phone' => '',
            'email' => ''
        ];

        if (function_exists('getDBConnection')) {
            $db = @getDBConnection();
            if ($db) {
                try {
                    $stmt = $db->query("SELECT setting_key, setting_value FROM settings");
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($rows as $r) {
                        if ($r['setting_key'] === 'clinic_name') $defaults['name'] = $r['setting_value'];
                        if ($r['setting_key'] === 'clinic_address') $defaults['address'] = $r['setting_value'];
                        if ($r['setting_key'] === 'clinic_phone') $defaults['phone'] = $r['setting_value'];
                        if ($r['setting_key'] === 'clinic_email') $defaults['email'] = $r['setting_value'];
                        if ($r['setting_key'] === 'clinic_logo') $defaults['logo'] = $r['setting_value'];
                    }
                } catch (Exception $e) {
                    error_log('Error fetching system details: ' . $e->getMessage());
                   
                }
            }
        }

        return $defaults;
    }
}

?>
