<?php
require_once __DIR__ . '/../shared/config.php';

class CompanyController
{
    public static function getCompanyInfo()
    {
        $defaults = [
            'name' => defined('APP_NAME') ? APP_NAME : 'Clinic Management System',
            'email' => '',
            'contact_number' => '',
            'website' => '',
            'address' => '',
            'logo' => ''
        ];

        if (!function_exists('getDBConnection')) {
            return $defaults;
        }

        $db = @getDBConnection();
        if (!$db) {
            return $defaults;
        }

        try {
            $stmt = $db->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('clinic_name','clinic_email','clinic_phone','clinic_website','clinic_address','clinic_logo')");
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                switch ($r['setting_key']) {
                    case 'clinic_name':
                        $defaults['name'] = $r['setting_value'];
                        break;
                    case 'clinic_email':
                        $defaults['email'] = $r['setting_value'];
                        break;
                    case 'clinic_phone':
                        $defaults['contact_number'] = $r['setting_value'];
                        break;
                    case 'clinic_website':
                        $defaults['website'] = $r['setting_value'];
                        break;
                    case 'clinic_address':
                        $defaults['address'] = $r['setting_value'];
                        break;
                    case 'clinic_logo':
                        $defaults['logo'] = $r['setting_value'];
                        break;
                }
            }
        } catch (Exception $e) {
            // ignore and return defaults
        }

        return $defaults;
    }

    public static function upsertSetting($key, $value)
    {
        if (!function_exists('getDBConnection')) {
            return false;
        }

        $db = @getDBConnection();
        if (!$db) {
            return false;
        }

        try {
            $stmt = $db->prepare('SELECT COUNT(*) as c FROM settings WHERE setting_key = :k');
            $stmt->execute([':k' => $key]);
            $count = (int) $stmt->fetchColumn();

            if ($count > 0) {
                $upd = $db->prepare('UPDATE settings SET setting_value = :v WHERE setting_key = :k');
                return $upd->execute([':v' => $value, ':k' => $key]);
            } else {
                $ins = $db->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v)');
                return $ins->execute([':k' => $key, ':v' => $value]);
            }
        } catch (Exception $e) {
            return false;
        }
    }

    public static function updateCompanyInfo($data, $file = null)
    {
        $result = ['success' => false, 'message' => 'Unable to update settings', 'company' => null];

        if (!function_exists('getDBConnection')) {
            $result['message'] = 'Database not available';
            return $result;
        }

        $db = @getDBConnection();
        if (!$db) {
            $result['message'] = 'Database not available';
            return $result;
        }

        try {
            $db->beginTransaction();

            // Map incoming field names to setting keys
            $map = [
                'company_name' => 'clinic_name',
                'email' => 'clinic_email',
                'contact_number' => 'clinic_phone',
                'website' => 'clinic_website',
                'address' => 'clinic_address'
            ];

            foreach ($map as $field => $key) {
                if (isset($data[$field])) {
                    self::upsertSetting($key, trim($data[$field]));
                }
            }

            // Handle logo upload
            if ($file && isset($file['tmp_name']) && is_uploaded_file($file['tmp_name'])) {
                // validate size
                $maxBytes = 5 * 1024 * 1024; // 5MB
                if ($file['size'] > $maxBytes) {
                    $db->rollBack();
                    $result['message'] = 'Logo file is too large (max 5MB)';
                    return $result;
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif'];
                if (!isset($allowed[$mime])) {
                    $db->rollBack();
                    $result['message'] = 'Unsupported logo file type';
                    return $result;
                }

                $ext = $allowed[$mime];
                $uploadsDir = __DIR__ . '/../uploads/company';
                if (!is_dir($uploadsDir)) {
                    @mkdir($uploadsDir, 0755, true);
                }

                $filename = 'clinic_logo_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                $dest = $uploadsDir . '/' . $filename;

                if (!move_uploaded_file($file['tmp_name'], $dest)) {
                    $db->rollBack();
                    $result['message'] = 'Failed to save uploaded logo';
                    return $result;
                }

                // Persist filename (relative path)
                self::upsertSetting('clinic_logo', $filename);
            }

            $db->commit();

            $result['success'] = true;
            $result['message'] = 'Settings updated successfully';
            $result['company'] = self::getCompanyInfo();
            return $result;
        } catch (Exception $e) {
            if ($db && $db->inTransaction()) {
                $db->rollBack();
            }
            $result['message'] = 'Error while updating settings';
            return $result;
        }
    }
}

?>
