<?php
/**
 * Mahara: Electronic portfolio, weblog, resume builder and social networking
 * Copyright (C) 2006-2009 Catalyst IT Ltd and others; see:
 *                         http://wiki.mahara.org/Contributors
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    mahara
 * @subpackage auth-blti
 * @author     Antoni Bertran Bellido (antoni@tresipunt.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL
 * @copyright 2010 Universitat Oberta de Catalunya
 * @license GPL
 * @version 1.0.0
 *
 */


defined('INTERNAL') || die();
require_once(get_config('docroot') . 'auth/lib.php');
require_once(dirname(__FILE__) . '/manager/login_manager.class.php');

/**
 * This authentication method allows ANY user access to Mahara. It's useful for 
 * testing ONLY!
 */
class AuthBLTI extends Auth {


    public function __construct($id = null) {
        $this->has_instance_config = false;
        $this->type       = 'blti';
        if (!empty($id)) {
            return $this->init($id);
        }
        return true;
    }

    public function init($id) {
        $this->ready = parent::init($id);
        return true;
    }

    /**
     * Attempt to authenticate user
     *
     * @param object $user     As returned from the usr table
     * @param string $password The password being used for authentication
     * @return bool            True/False based on whether the user
     *                         authenticated successfully
     * @throws AuthUnknownUserException If the user does not exist
     */
    public function authenticate_user_account($user, $password) {
        $this->must_be_ready();
        return true;
        //return $this->validate_password($password, $user->password, $user->salt);
    }

    public function can_auto_create_users() {
        return true;
    }

    public function get_user_info($username) {
        $user = new stdClass;
        $user->firstname = ucfirst(strtolower($username));
        $user->lastname  = 'McAuthentication';
        $user->email     = 'test@example.org';
        return $user;
    }

    /**
     * Any old password is valid
     *
     * @param string $password The password to check
     * @return bool            True, always
     */
    public function is_password_valid($password) {
        return true;
    }
}

/**
 * Plugin configuration class
 */
class PluginAuthBLTI extends PluginAuth {

    private static $default_config = array(
        'update_user_data' => 0,
    );


    public static function has_config() {
        return true;
    }

    public static function get_config_options() {
    	
    	$group_options = array();
    	$i = 0;
        $group_options[$i++] = get_string('course.controlled', 'auth.blti');
    	$group_options[$i++] = get_string('course.request', 'auth.blti');
    	$group_options[$i++] = get_string('standard.open', 'auth.blti');
    	$group_options[$i++] = get_string('standard.request', 'auth.blti');
    	$group_options[$i++] = get_string('standard.invite', 'auth.blti');
    	$group_options[$i++] = get_string('standard.controlled', 'auth.blti');
    	
    
		$groupcategories_temp = get_records_array('group_category','','','displayorder');
		
		$groupcategories = array();
		$groupcategories[0] = get_string('no.use.cats', 'auth.blti');
    	
		if ($groupcategories_temp) {
		    foreach ($groupcategories_temp as $i) {
		        $groupcategories[$i->id] = $i->title;
		    }
		}
    	
		$elements = array(
            'authname' => array(
                'type'  => 'hidden',
                'value' => 'blti',
            ),
            'authglobalconfig' => array(
                'type'  => 'hidden',
                'value' => 'blti',
            ),
            'update_user_data' => array(
                'type' => 'checkbox',
                'title' => get_string('update_user_data', 'auth.blti'),
                'defaultvalue' => get_config_plugin('auth', 'blti', 'update_user_data'),
                'help'  => true,
            ),
            'default_group' => array(
                'type'  => 'select',
                'size' => 1,
                'title' => get_string('default_group', 'auth.blti'),
                'rules' => array(
                    'required' => true,
                ),
                'options' => $group_options,
                'defaultvalue' => get_config_plugin('auth', 'blti', 'default_group'),
                'help'  => true,
            ),
            'group_is_public' => array(
                'type' => 'checkbox',
                'title' => get_string('group_is_public', 'auth.blti'),
                'defaultvalue' => get_config_plugin('auth', 'blti', 'group_is_public'),
                'help'  => true,
            ),
            'group_usersautoadded' => array(
                'type' => 'checkbox',
                'title' => get_string('group_usersautoadded', 'auth.blti'),
                'defaultvalue' => get_config_plugin('auth', 'blti', 'group_usersautoadded'),
                'help'  => true,
            ),
            'group_viewnotify' => array(
                'type' => 'checkbox',
                'title' => get_string('group_viewnotify', 'auth.blti'),
                'defaultvalue' => get_config_plugin('auth', 'blti', 'group_viewnotify'),
                'help'  => true,
            ),
            'group_defaultcategory' => array(
                'type' => 'select',
                 'options' => $groupcategories,
                'title' => get_string('group_defaultcategory', 'auth.blti'),
                'defaultvalue' => get_config_plugin('auth', 'blti', 'group_defaultcategory'),
                'help'  => true,
            ),
        );

        return array(
            'elements' => $elements,
            'renderer' => 'table'
        );    
    }

    public static function has_instance_config() {
        return true;
    }

    public static function is_usable() {
        return true;
    }

    public static function get_instance_config_options($institution, $instance = 0) {
        
        $group_options = array();
        $i = 0;
        $group_options[$i++] = get_string('course.controlled', 'auth.blti');
        $group_options[$i++] = get_string('course.request', 'auth.blti');
        $group_options[$i++] = get_string('standard.open', 'auth.blti');
        $group_options[$i++] = get_string('standard.request', 'auth.blti');
        $group_options[$i++] = get_string('standard.invite', 'auth.blti');
        $group_options[$i++] = get_string('standard.controlled', 'auth.blti');
        
    
        $groupcategories_temp = get_records_array('group_category','','','displayorder');
        
        $groupcategories = array();
        $groupcategories[0] = get_string('no.use.cats', 'auth.blti');
        
        if ($groupcategories_temp) {
            foreach ($groupcategories_temp as $i) {
                $groupcategories[$i->id] = $i->title;
            }
        }
        
        $elements = array(
            'authname' => array(
                'type'  => 'hidden',
                'value' => 'blti',
            ),
            'instancename' => array(
            'type' => 'text',
            'title' => 'Name',//get_string('name','auth.blti'),
            'rules' => array(
                'required' => true
                ),
            'defaultvalue' => $default->instancename?$default->instancename:get_string('title','auth.blti')
            ),
            'institution' => array(
                'type' => 'hidden',
                'value' => $institution
            ),            
            'authglobalconfig' => array(
                'type'  => 'hidden',
                'value' => 'blti',
            ),
            'update_user_data' => array(
                'type' => 'checkbox',
                'title' => get_string('update_user_data', 'auth.blti'),
                'defaultvalue' => get_config_plugin('auth', 'blti', 'update_user_data'),
                'help'  => true,
            ),
            'default_group' => array(
                'type'  => 'select',
                'size' => 1,
                'title' => get_string('default_group', 'auth.blti'),
                'rules' => array(
                    'required' => true,
                ),
                'options' => $group_options,
                'defaultvalue' => get_config_plugin('auth', 'blti', 'default_group'),
                'help'  => true,
            ),
            'group_is_public' => array(
                'type' => 'checkbox',
                'title' => get_string('group_is_public', 'auth.blti'),
                'value' => 1,
                'defaultvalue' => get_config_plugin('auth', 'blti', 'group_is_public'),
                'help'  => true,
            ),
            'group_usersautoadded' => array(
                'type' => 'checkbox',
                'title' => get_string('group_usersautoadded', 'auth.blti'),
                'defaultvalue' => get_config_plugin('auth', 'blti', 'group_usersautoadded'),
                'help'  => true,
            ),
            'group_viewnotify' => array(
                'type' => 'checkbox',
                'title' => get_string('group_viewnotify', 'auth.blti'),
                'defaultvalue' => get_config_plugin('auth', 'blti', 'group_viewnotify'),
                'help'  => true,
            ),
            'group_defaultcategory' => array(
                'type' => 'select',
                 'options' => $groupcategories,
                'title' => get_string('group_defaultcategory', 'auth.blti'),
                'defaultvalue' => get_config_plugin('auth', 'blti', 'group_defaultcategory'),
                'help'  => true,
            ),
        );

        return array(
            'elements' => $elements,
            'renderer' => 'table'
        );     
    }
    public static function save_instance_config_options($values, Pieform $form) {

        $authinstance = new stdClass();
    
            if ($values['instance'] > 0) {
                $values['create'] = false;
                $current = get_records_assoc('auth_instance_config', 'instance', $values['instance'], '', 'field, value');
                $authinstance->id = $values['instance'];
            }
            else {
                $values['create'] = true;
                $lastinstance = get_records_array('auth_instance', 'institution', $values['institution'], 'priority DESC', '*', '0', '1');
    
                if ($lastinstance == false) {
                    $authinstance->priority = 0;
                }
                else {
                    $authinstance->priority = $lastinstance[0]->priority + 1;
                }
            }
    
            $authinstance->institution  = $values['institution'];
            $authinstance->authname     = $values['authname'];
            $authinstance->instancename = $values['instancename'];
            
            if ($values['create']) {
                $values['instance'] = insert_record('auth_instance', $authinstance, 'id', true);
            }
            else {
                update_record('auth_instance', $authinstance, array('id' => $values['instance']));
            }
    
            if (empty($current)) {
                $current = array();
            }
    
            self::$default_config =   array('user_attribute' => $values['user_attribute'],
                                            'weautocreateusers' => $values['weautocreateusers'],
                                            'remoteuser' => $values['remoteuser'],
                                            'firstnamefield' => $values['firstnamefield'],
                                            'surnamefield' => $values['surnamefield'],
                                            'emailfield' => $values['emailfield'],
                                            'updateuserinfoonlogin' => $values['updateuserinfoonlogin'],
                                            'institutionattribute' => $values['institutionattribute'],
                                            'institutionvalue' => $values['institutionvalue'],
                                            'institutionregex' => $values['institutionregex'],
                                            );
    
            foreach(self::$default_config as $field => $value) {
                $record = new stdClass();
                $record->instance = $values['instance'];
                $record->field    = $field;
                $record->value    = $value;
    
                if ($values['create'] || !array_key_exists($field, $current)) {
                    insert_record('auth_instance_config', $record);
                }
                else {
                    update_record('auth_instance_config', $record, array('instance' => $values['instance'], 'field' => $field));
                }
            }

        return $values;
    }


//    public static function validate_config_options(Pieform $form, $values) {

//        // fix problems with config validation interface incorrect between site/institution
//        if (get_class($values) == 'Pieform') {
//            $tmp = $form;
//            $form = $values;
//            $values = $tmp;
//        }
//        if (isset($values['authglobalconfig'])) {
//            // SimplebltiPHP lib directory must have right things
//            if (! file_exists($values['simplebltiphplib'].'/lib/_autoload.php')) {
//                $form->set_error('simplebltiphplib', get_string('errorbadlib', 'auth.blti', $values['simplebltiphplib']));
//            }
//            // SimplebltiPHP config directory must shape up
//            if (! file_exists($values['simplebltiphpconfig'].'/config.php')) {
//                $form->set_error('simplebltiphpconfig', get_string('errorbadconfig', 'auth.blti', $values['simplebltiphpconfig']));
//            }
//        }
//        if (isset($values['weautocreateusers'])) {
//            if ($values['weautocreateusers'] && $values['remoteuser']) {
//                $form->set_error('weautocreateusers', get_string('errorbadcombo', 'auth.blti'));
//            }
//        }
//		return true;
//    }
    
    public static function validate_config_options(Pieform $form, $values) {
        // code expects an array; convert to array if we get a Pieform object
       if ($values instanceof Pieform) {
            $new_values = array();
            foreach ($values->get_elements() as $element) {
                if (isset($element['name'])) {
                    $new_values[$element['name']] = $values->get_value($element);
                }              
            }
            $values = $new_values;
        } 
    }    
    
    public static function save_config_options(Pieform $form, $values) {

         // code expects an array; convert to array if we get a Pieform object
       if ($values instanceof Pieform) {
            $new_values = array();
            foreach ($values->get_elements() as $element) {
                if (isset($element['name'])) {
                    $new_values[$element['name']] = $values->get_value($element);
                }              
            }
            $values = $new_values;
        } 

        $configs = array('update_user_data', 'default_group', 'group_is_public', 'group_usersautoadded', 'group_viewnotify', 'group_defaultcategory');
        if (isset($values['authglobalconfig'])) {
            foreach ($configs as $config) {
                set_config_plugin('auth', 'blti', $config, $values[$config]);
            }
        }
        else {
//            $authinstance = new stdClass();
//    
//            if ($values['instance'] > 0) {
//                $values['create'] = false;
//                $current = get_records_assoc('auth_instance_config', 'instance', $values['instance'], '', 'field, value');
//                $authinstance->id = $values['instance'];
//            }
//            else {
//                $values['create'] = true;
//                $lastinstance = get_records_array('auth_instance', 'institution', $values['institution'], 'priority DESC', '*', '0', '1');
//    
//                if ($lastinstance == false) {
//                    $authinstance->priority = 0;
//                }
//                else {
//                    $authinstance->priority = $lastinstance[0]->priority + 1;
//                }
//            }
//    
//            $authinstance->institution  = $values['institution'];
//            $authinstance->authname     = $values['authname'];
//            $authinstance->instancename = $values['authname'];
//            
//            if ($values['create']) {
//                $values['instance'] = insert_record('auth_instance', $authinstance, 'id', true);
//            }
//            else {
//                update_record('auth_instance', $authinstance, array('id' => $values['instance']));
//            }
//    
//            if (empty($current)) {
//                $current = array();
//            }
//    
//            self::$default_config =   array('user_attribute' => $values['user_attribute'],
//                                            'weautocreateusers' => $values['weautocreateusers'],
//                                            'remoteuser' => $values['remoteuser'],
//                                            'firstnamefield' => $values['firstnamefield'],
//                                            'surnamefield' => $values['surnamefield'],
//                                            'emailfield' => $values['emailfield'],
//                                            'updateuserinfoonlogin' => $values['updateuserinfoonlogin'],
//                                            'institutionattribute' => $values['institutionattribute'],
//                                            'institutionvalue' => $values['institutionvalue'],
//                                            'institutionregex' => $values['institutionregex'],
//                                            );
//    
//            foreach(self::$default_config as $field => $value) {
//                $record = new stdClass();
//                $record->instance = $values['instance'];
//                $record->field    = $field;
//                $record->value    = $value;
//    
//                if ($values['create'] || !array_key_exists($field, $current)) {
//                    insert_record('auth_instance_config', $record);
//                }
//                else {
//                    update_record('auth_instance_config', $record, array('instance' => $values['instance'], 'field' => $field));
//                }
//            }
        }
        foreach ($configs as $config) {
            self::$default_config[$config] = get_config_plugin('auth', 'blti', $config);
        }
        return $values;
    }
}

?>
