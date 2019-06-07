<?php

require_once(DIR_SYSTEM . 'library/age_gate_vendor/autoload.php');

use EighteenPlus\AgeGate\AgeGate;
use EighteenPlus\AgeGate\Utils;

class ControllerExtensionModuleAgeGate extends Controller {
    
    public function hook()
    {
        $base = '';
        if (isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1'))) {
			$base = $this->config->get('config_ssl');
		} else {
			$base = $this->config->get('config_url');
		}
        
        $query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "module` WHERE `code` = 'age_gate' ORDER BY `name`");
        $setting = $query->row;
        if ($setting) {
            $setting = json_decode($setting['setting'], true);
            
            $gate = new AgeGate($base);
            $gate->setTitle(isset($setting['agegate_title']) ? $setting['agegate_title'] : '');
            
            $this->load->model('tool/image');
            if (isset($setting['agegate_logo']) && is_file(DIR_IMAGE . $setting['agegate_logo'])) {
                $logo = $this->model_tool_image->resize($setting['agegate_logo'], 100, 100);
                $gate->setLogo($logo);
            }
            
            $gate->setSiteName(isset($setting['agegate_site_name']) ? $setting['agegate_site_name'] : '');
            $gate->setCustomText(isset($setting['agegate_custom_text']) ? $setting['agegate_custom_text'] : '');
            $gate->setCustomLocation(isset($setting['agegate_custom_text_location']) ? $setting['agegate_custom_text_location'] : '');
            
            $gate->setBackgroundColor(isset($setting['agegate_background_color']) ? $setting['agegate_background_color'] : '');
            $gate->setTextColor(isset($setting['agegate_text_color']) ? $setting['agegate_text_color'] : '');
            
            $gate->setRemoveReference(isset($setting['agegate_remove_reference']) ? $setting['agegate_remove_reference'] : '');
            $gate->setRemoveVisiting(isset($setting['agegate_remove_visiting']) ? $setting['agegate_remove_visiting'] : '');
            
            $gate->setTestMode(isset($setting['agegate_test_mode']) ? $setting['agegate_test_mode'] : '');
            $gate->setTestAnyIp(isset($setting['agegate_test_anyip']) ? $setting['agegate_test_anyip'] : '');
            $gate->setTestIp(isset($setting['agegate_test_ip']) ? $setting['agegate_test_ip'] : '');
            
            $gate->setStartFrom(isset($setting['agegate_start_from']) ? $setting['agegate_start_from'] : '');
            
            
            $desktop = Utils::toHours($setting['agegate_desktop_session_lifetime']);
            $mobile = Utils::toHours($setting['agegate_mobile_session_lifetime']);
            $gate->setDesktopSessionLifetime($desktop);
            $gate->setMobileSessionLifetime($mobile);
            
            
            if ($setting['status']) {                
                $gate->run();
            }
        }
    }
}