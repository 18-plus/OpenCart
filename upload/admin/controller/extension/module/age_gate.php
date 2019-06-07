<?php

require_once(DIR_SYSTEM . 'library/age_gate_vendor/autoload.php');

use EighteenPlus\AgeGate\AgeGate;
use EighteenPlus\AgeGate\Utils;

class ControllerExtensionModuleAgeGate extends Controller {
    private $error = array();
    
    const DEFAULT_MODULE_SETTINGS = [
        'name' => 'Age Gateway 18+',
        
		'agegate_file' => '', 
		'agegate_title' => '', 
        'agegate_logo' => '',
        'agegate_site_name' => '',
        'agegate_custom_text' => '',
        'agegate_custom_text_location' => 'top',
        'agegate_background_color' => '',
        'agegate_text_color' => '',
        'agegate_remove_reference' => false,
        'agegate_remove_visiting' => false,
        'agegate_test_mode' => false,
        'agegate_test_anyip' => false,
        'agegate_test_ip' => '',
        'agegate_start_from' => '2019-07-15 12:00',
        'agegate_desktop_session_lifetime' => array(
            'd' => 0,
            'h' => 1,
            'm' => 0,
        ),
        'agegate_mobile_session_lifetime' => array(
            'd' => 0,
            'h' => 2,
            'm' => 0,
        ),
		'status' => 1 /* Enabled by default*/
	];
    
    public function index()
    {
        $this->document->addScript('view/javascript/jquery.datetimepicker.full.min.js');
        $this->document->addStyle('view/stylesheet/jquery.datetimepicker.min.css');
        
        $this->document->addStyle('view/stylesheet/bootstrap-colorpicker.min.css');
        $this->document->addScript('view/javascript/bootstrap-colorpicker.js');
        
        $this->document->addStyle('view/stylesheet/age_gate_style.css');
            
        if (isset($this->request->get['module_id'])) {
			$this->configure($this->request->get['module_id']);
		} else {
			$this->load->model('setting/module');
			$this->model_setting_module->addModule('age_gate', self::DEFAULT_MODULE_SETTINGS); /* Because modules are being deleted by extension name */
			$this->response->redirect($this->url->link('extension/module/age_gate','&user_token='.$this->session->data['user_token'].'&module_id=' . $this->db->getLastId()));
		}
    }
    
    protected function configure($module_id) {
		$this->load->model('setting/module');
		$this->load->language('extension/module/age_gate');
		
		$this->document->setTitle($this->language->get('heading_title'));

		if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
			$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			
			$this->session->data['success'] = $this->language->get('text_success');
						
			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}
		
		$data = array();

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/age_gate', 'user_token=' . $this->session->data['user_token'], true)
		);

        
		$module_setting = $this->model_setting_module->getModule($module_id);
        foreach ($module_setting as $key => $value) {
            if (isset($this->request->post[$key])) {
                $data[$key] = $this->request->post[$key];
            } else {
                $data[$key] = $module_setting[$key];
            }
        }
        
        $this->load->model('tool/image');
        if (isset($data['agegate_logo']) && is_file(DIR_IMAGE . $data['agegate_logo'])) {
			$data['agegate_logo_thumb'] = $this->model_tool_image->resize($data['agegate_logo'], 100, 100);
		} else {
			$data['agegate_logo_thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}
        $data['agegate_logo_placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
        
		
		$data['action']['cancel'] = $this->url->link('marketplace/extension', 'user_token='.$this->session->data['user_token'].'&type=module');
		$data['action']['save'] = "";

		$data['error'] = $this->error;	
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
        
        
        
        
        $data['sessionLifeTime'] = ini_get("session.gc_maxlifetime") / 3600;
        
        $desktop = $this->toHours($data['agegate_desktop_session_lifetime']);
        $mobile = $this->toHours($data['agegate_mobile_session_lifetime']);
        
        if ( $data['sessionLifeTime'] < $desktop ||  $data['sessionLifeTime'] < $mobile) {
            $data['warning'] = true;
        } else {
            $data['warning'] = false;
        }
        
        $data['ip'] = Utils::getClientIP();
        
		
		$this->response->setOutput($this->load->view('extension/module/age_gate', $data));
	}
    
    public function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/age_restriction')) {
			$this->error['permission'] = true;
			return false;
		}
		
        
		if ($lifetime = $this->toHours($this->request->post['agegate_desktop_session_lifetime'])) {
            if (1 > $lifetime || $lifetime > 2 * 24) {                
                $this->error['agegate_desktop_session_lifetime'] = true;
            }
		}
        
        if ($lifetime = $this->toHours($this->request->post['agegate_mobile_session_lifetime'])) {
            if (1 > $lifetime || $lifetime > 7 * 24) {                
                $this->error['agegate_mobile_session_lifetime'] = true;
            }
		}
		
		return empty($this->error);
	}
    
    public function install()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->addEvent('agegate_hook', 'catalog/view/product/product/after', 'Extension/Module/Age_Gate/hook');
        $this->model_setting_event->addEvent('agegate_hook', 'catalog/view/product/category/after', 'Extension/Module/Age_Gate/hook');
        $this->model_setting_event->addEvent('agegate_hook', 'catalog/view/product/search/after', 'Extension/Module/Age_Gate/hook');
        $this->model_setting_event->addEvent('agegate_hook', 'catalog/view/common/home/after', 'Extension/Module/Age_Gate/hook');
        
        $this->load->model('setting/setting');
        $this->load->model('setting/module');
        $this->model_setting_module->addModule('age_gate', self::DEFAULT_MODULE_SETTINGS); 
        $this->model_setting_setting->editSetting('module_age_gate', ['module_age_gate_status' => 1]);
    }
    
    public function uninstall()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode('agegate_hook');
        
        $this->load->model('setting/setting');
		$this->model_setting_setting->deleteSetting('module_age_gate');
    }
    
    private function toHours($options)
    {
        if (empty($options) || !is_array($options)) {
            return null;
        }
        
        return $options['d'] * 24 + $options['h'] + $options['m'] / 60;
    }
}