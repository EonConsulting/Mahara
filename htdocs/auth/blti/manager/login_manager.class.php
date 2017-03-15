<?php

/**
 * Login manager. The login manager is responsible to coordinate the various activities and responses during the login process.
 * The main - entry - method is "process" which dispatch the work to helper methods as needed.
 *
 *
 * @copyright (c) 2010 University of Geneva
 * @license GNU General Public License - http://www.gnu.org/copyleft/gpl.html
 * @author antoni@tresipunt.com
 *
 */
class login_manager_blti {

    protected $context = null;

    public function __construct() {
    	global $SESSION, $USER;
	    $SESSION->destroy_session();
    	$required_class = dirname(__FILE__).'/../IMSBasicLTI/uoc-blti/bltiUocWrapper.php';
		$exists=fopen ( $required_class, "r",1 );
		if (!$exists) {
			
			throw new AuthUnknownUserException('Required classes BASIC LTI not exists check the include_path is correct');
		         
		} else {
			require_once $required_class;
			require_once dirname(__FILE__).'/../IMSBasicLTI/utils/UtilsPropertiesBLTI.php';
			if ( ! is_basic_lti_request() ) {
				$good_message_type = $_REQUEST["lti_message_type"] == "basic-lti-launch-request";
				$good_lti_version = $_REQUEST["lti_version"] == "LTI-1p0";
				$resource_link_id = $_REQUEST["resource_link_id"];
				if ($good_message_type && $good_lti_version && !isset($resource_link_id) ) {
					$launch_presentation_return_url = $_REQUEST["launch_presentation_return_url"];
					if (isset($launch_presentation_return_url)) {
						header('Location: '.$launch_presentation_return_url);
					}
				}
				throw new AuthUnknownUserException('BASIC LTI Authentication Failed, not valid request ');
			}
			// See if we get a context, do not set session, do not redirect
		    $this->context = new bltiUocWrapper(false, false);
		    if ( ! $this->context->valid ) {
		    	$return = '';
		    	if (isset($_REQUEST["launch_presentation_return_url"])) {
		    		$return = ' Return to .'.$_REQUEST["launch_presentation_return_url"];
		    	}
		        throw new AuthUnknownUserException('BASIC LTI Authentication Failed, not valid request (make sure that consumer is authorized and secret is correct) '.$return);
		    }
		}
    }

    /**
     * Returns the blti user's user name.
     */
    public function username() {
        $username_no_prefix = 0;	
		try {
			//we have a configuration file on indicates which fields are mapping
			require_once dirname(__FILE__).'/../IMSBasicLTI/utils/UtilsPropertiesBLTI.php';
			$conf_file = (dirname(__FILE__).'/../configuration/mappingFields.cfg');
			$prop = new UtilsPropertiesBLTI($conf_file);
			$username_no_prefix = $prop->getProperty('username_no_prefix');
		} catch (Exception $e) {
			//Problem in configuration 
			$username_no_prefix = 0;
			$SESSION->add_error_msg($e->getMessage());
		}
		if ($username_no_prefix && $this->context->info['custom_username']) {
		   $username = $this->context->info['custom_username'];	
		} else {
		   $username = $this->context->getUserKey();
		}
	    $username = str_replace(':','_',$username);  // TO make it past sanitize_user
		return $username;
    }

    /**
     * If true,  user account data are updated on each login. That is first name, last name, email
     * are overwritten by the blti data.
     * If false, user account data are taken from the blti fields only during account creation.
     */
   public function update_user_data() {
        return get_config_plugin('auth', 'blti', 'update_user_data');
    }

    /**
     * Returns true if the shibboleth user is site administrator. False otherwise.
     */
    public function is_admin() {
        return strpos(strtolower($this->context->info['roles']), 'administrator')!==false;
    }

    /**
     * Returns a plugin config value.
     * @param string $name 		the name of the plugin value to read
     * @param mixed $default 	the default value to return if the plugin value is not set
     */
    public function config($name = '', $default = '') {
        static $config = null;
        if (is_null($config)) {
            $config = auth_config_get('blti');
        }
        if (empty($name)) {
            $result = $config;
        } else if (isset($config->$name)) {
            $result = $config->$name;
        } else {
            $result = $default;
        }
        return $result;
    }

    /**
     * 
     * Get the GroupName
     */
    public function getGroupName() {
    	$groupName = $this->context->getCourseName();
    	$concat_resource_link_id = 0;
    	try {
    		//we have a configuration file on indicates which fields are mapping
    		require_once dirname(__FILE__).'/../IMSBasicLTI/utils/UtilsPropertiesBLTI.php';
    		$conf_file = (dirname(__FILE__).'/../configuration/mappingFields.cfg');
    		$prop = new UtilsPropertiesBLTI($conf_file);
    		$concat_resource_link_id = $prop->getProperty('concat_resource_link_id');
    		if ($concat_resource_link_id == '1') {
    			$groupName .= ' '.$this->context->info['resource_link_id'];
    		}
    	} catch (Exception $e) {
    		//Problem in configuration
    		$concat_resource_link_id = 0;
		$SESSION->add_error_msg($e->getMessage());
    	}
    	
    	return $groupName;
    	
    }
    
    /**
     * If instance is not empty read the institution user data from $SERVER and returns them in an object. Otherwise returns false.
     *
     * @param $instance the instance the user is associated with.
     * @return boolean|StdClass false if instance is empty or an object made of user instance data.
     * @return
     */
    public function institution_data($admin=false) {
        $result = new StdClass();
        
        $result->institution = $this->context->getCourseName();
        //Required because institution key should be only param_alpha @see web.php param_alpha function
        $result->institution_key = strtolower($this->set_param_alpha($this->context->getCourseKey())); 
        $result->admin = $admin;
        if (!empty($field)) {
            $result->staff = $this->context->isInstructor();
        }

        return $result;
    }

    /**
     * Converts string to param_alpha
     * @param [type] $key [description]
     */
    private function set_param_alpha($key) {
    	$key = str_replace(':','dot',$key);

    	$array = array('1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five'
    		, '6' => 'six', '7' => 'seven', '8' => 'eight', '9' => 'nine', '0' => 'zero'
    		, ':' => 'colon', '.' => 'dot', ',' => 'comma', '-' => 'script', ';' => 'semicolon'
    		, '/' => 'slash', '\\' => 'backslash');
    	foreach ($array as $keyA => $replace) {
    		$key = str_replace($keyA, $replace, $key);	
    	}
    	
    	return $key;
    }

    /**
     * Main method. Dispatch work to helper methods as needed.
     */
    public function process() {
        global $SESSION;

        switch  ($this->login()) {
        	case 1:
            	redirect();
            	break;
        	case -2:
        		$SESSION->add_error_msg(get_string('accessforbiddentoadminsection'));
		        redirect();
		        break;
        	default:
	            $SESSION->add_error_msg(get_string('error_internal_login_failed', 'auth.blti'));
	            redirect();
        }
    }

    /**
     * 
     * Possibles values
     * 
	 * "course.controlled"=Course: Controlled Membership
	 * "course.request"=Course: Request Membership
	 * "standard.open"=Standard: Open Membership
	 * "standard.request"=Standard: Request Membership
	 * "standard.invite"=Standard: Invite Only
	 * "standard.controlled"=Standard: Controlled Membership
     */
    function getGroupType() {
		$array_values = array("course.controlled","course.request","standard.open","standard.request","standard.invite","standard.controlled");
		
		$default_group = get_config_plugin('auth', 'blti', 'default_group');
		if (empty($default_group))
			$default_group = 0;
		
		
		return $array_values[$default_group];
    }
    
    /**
     * 
     * Return if group is public or not
     */
    function getGroupIsPublic() {

    	$group_is_public = get_config_plugin('auth', 'blti', 'group_is_public');
		if (empty($group_is_public))
			$group_is_public = 0;
		return intval($group_is_public);
		
    }
    
    /**
     * 
     * Return if users can auto add or not
     */
    function getUserAutoAddGroup() {
		
    	$group_usersautoadded = get_config_plugin('auth', 'blti', 'group_usersautoadded');
		if (empty($group_usersautoadded))
			$group_usersautoadded = 0;
		return intval($group_usersautoadded);
		
    }  
    
    /**
     * 
     * Return If checked, a notification will be sent to every group member 
     * whenever a member shares one of their Views with the group. 
     * Enabling this setting in very large groups can produce a lot of notifications.
     */
    function getSendNotifyGroup() {
		
    	$group_viewnotify = get_config_plugin('auth', 'blti', 'group_viewnotify');
		if (empty($group_viewnotify))
			$group_viewnotify = 0;
		return intval($group_viewnotify);
		
    }
    
    /**
     * 
     * The categories listed here can be assigned to groups during group creation, and used to filter groups during searches.
	 * If not set you have to put 0
     */
    function getDefaultCategoryGroup() {
		
    	$group_defaultcategory = get_config_plugin('auth', 'blti', 'group_defaultcategory');
		if (empty($group_defaultcategory) || intval($group_defaultcategory)<=0)
			$group_defaultcategory = null;
		return $group_defaultcategory;
		
    }
    
    
    
    /**
     * Log in the blti user.
     * @return integer: 1 its ok, -2 error user is not admin and requires admin role, -1 error
     */
    public function login() {
    	global $SESSION;
        $admin = strpos(strtolower($this->context->info['roles']), 'administrator')!==false;
	    $student = strpos(strtolower($this->context->info['roles']), 'student')!==false;
	    $instructor = $this->context->isInstructor();
	    $institution_data = $this->institution_data($admin);
        $username = $this->username();
        
    	$name = $this->context->getUserName();
    	$email = $this->context->getUserEmail();
    	$user = new LiveUser();
	    $isnew = false;
	    try {
    		$user = $user->find_by_username($username);
	    } catch (AuthUnknownUserException $e) {
	    	$isnew = true;
		$user->password = sha1(uniqid('', true));
	        $user->authinstance = null;
		$SESSION->add_error_msg($e->getMessage());
    	} catch (Exception $e) {
	    	$isnew = true;
		$user->password = sha1(uniqid('', true));
	        $user->authinstance = null;
		$SESSION->add_error_msg($e->getMessage());
	    }
    	
	    if ($user) {
	    	
		    $user->passwordchange = false;
		    $user->active = true;
		    $user->deleted = false;
		    $user->expiry = null;
		    $user->expirymailsent = false;
		    $user->inactivemailsent = false;
		    $user->suspendedctime = null;
		    $user->suspendedreason = null;
		    $user->suspendedcusr = null;
			$user->username = $username;
	        $user->staff = $instructor;
	        $user->admin = $admin;
	        $user->firstname = $this->context->getUserFirstName();
			$user->lastname = $this->context->getUserLastName();
			$user->email = $this->context->getUserEmail();
			
		    $user->authinstance = isset($autoring_instance->id) ? $autoring_instance->id : 1;
		
		    try {
		        if ($isnew) {
		            $user->id = create_user($user, array(), null, isset($institution_data->institution) ? $institution_data->institution_key : null);
		        } else {
		            $user->commit();
		        }
		    } catch (Exception $e) {
			$SESSION->add_error_msg($e->getMessage());
		        db_rollback();
		        throw new AuthUnknownUserException('User registration error '.$e->getMessage());
		    }
            try {
			    $id = $user->id;
			    if (!empty($id) && $student) {
			        include_once get_config('docroot') . 'artefact/lib.php';
			        include_once get_config('docroot') . 'artefact/internal/lib.php';
			        $profile = new ArtefactTypeStudentid(0, array('owner' => $id));
			        $profile->set('title', $name);
			        $profile->commit();
			    }
			    
				 /*
			     * If we have a preffered name we need to update both the usr table and the artifact table.
			     * The usr table is used to read the value for login blocks, etc.
			     * The artifact table is used when displaying the user preference such as in the update profile form.
			     */
			    $preferredname = $user->preferredname;
			    if (!empty($id) && !empty($preferredname)) {
			        include_once get_config('docroot') . 'artefact/lib.php';
			        include_once get_config('docroot') . 'artefact/internal/lib.php';
			        $profile = new ArtefactTypePreferredname(0, array('owner' => $id));
			        $profile->set('title', $preferredname);
			        $profile->commit();
			    }
			    $USER = $user;
		    
			    include_once get_config('docroot') . '/lib/institution.php';
			    $list_institutions = Institution::count_members( array($institution_data->institution_key), true);
			    $current_insitution = null;
			    if (!$list_institutions || count($list_institutions)==0)
			    	$current_insitution = $this->createInstitution($institution_data, $user);
				else {
					if (is_array($list_institutions) && count($list_institutions)>0) {
						$current_insitution = $list_institutions[$institution_data->institution_key];		    	
					}
				}
			    	
			    //Do the institution mapping
			    if (!$user->in_institution($institution_data->institution_key)) {
			        $user->join_institution($institution_data->institution_key);
			    }
			    $update_user_data = $this->update_user_data();
			    if (($update_user_data || $isnew) && (isset($institution_data->staff) || isset($institution_data->admin))) {
			        $result = update_record('usr_institution', $institution_data, array('usr' => $user->id, 'institution' => $institution_data->institution_key));
			    }
	
			    $USER = $user;
			
		      	$result = $USER->reanimate($USER->id, /*isset($authoring_instance->id) ? $authoring_instance->id :*/ 1);
			    
		      	list($grouptype, $jointype) = explode('.', $this->getGroupType());
				    
		      	if ($result) {
			    
			    	//Creates a Group
				    include_once get_config('docroot') . '/lib/group.php';
				    include_once get_config('docroot') . '/lib/searchlib.php';
				    $groupName = $this->getGroupName();
				    $array_groups = search_group($groupName, 0, 0, '');
				    $can_join_course = true;
				    $id_group = -1;
				    if ($array_groups==false || $array_groups['count']==0) {
				    	try {
						    $id_group = group_create(array(
						        'name'           => $groupName,
						        'description'    => $groupName,
						        'grouptype'      => $grouptype,
						        'category'       => $this->getDefaultCategoryGroup(),
						        'jointype'       => $jointype,
						        'public'         => $this->getGroupIsPublic(),
						        'usersautoadded' => $this->getUserAutoAddGroup(),
						        'members'        => array($user->get('id') => 'admin'),
						        'viewnotify'     => $this->getSendNotifyGroup(),
				    			));
				    	}catch (InvalidArgumentException $e){
				    		$can_join_course = false;
				    		if (strpos($e->getMessage(),'jointype specified is not allowed by the grouptype specified')===FALSE) {
								$SESSION->add_error_msg($e->getMessage());
				    		} else {
				    			$SESSION->add_info_msg(get_string('error_teacher_has_to_configure_course', 'auth.blti'));
				    		}
				    	}
				    } else {
				    	if (is_array($array_groups['data']) && count($array_groups['data'])>0) {
				    		$id_group = $array_groups['data'][0]->id;
				    	}
				    }
				    if ($can_join_course) {
					    if (group_user_access($id_group, $user->id)) {
					    	
					    	group_remove_user($id_group, $user->id, true);
					    	
					    }
				    	$role = 'member';
				    	if ($admin)
				    		$role = 'admin';
				    	elseif ($grouptype=='course') {
				    		if ($instructor)
				    			$role = 'tutor';	
				    	}
				    	
				    	group_add_user($id_group, $user->id, $role);
			
			    	  	$USER->reset_grouproles();
					
				    }
				    
				    // Only admins in the admin section!
				    if (!$USER->get('admin') &&
				            (defined('ADMIN') || defined('INSTITUTIONALADMIN') && !$USER->is_institutional_admin())) {
				            return -2;	
				    }
				    
			    	return 1;
		      	} else {
	      			return 0;
	      		}
	      	} catch (Exception $e){
	      		$SESSION->add_error_msg($e->getMessage());
	      		$SESSION->add_error_msg(' Return to .'.$_REQUEST["launch_presentation_return_url"]);
	      		
	      		return -1;
	      	}
	    }
        return -1;
    }
    
    function createInstitution($institution_data, $user) {
	    $newinstitution = new StdClass;
	    $institution = $newinstitution->name = $institution_data->institution_key;
	
	    $newinstitution->displayname                  = $institution_data->institution;
	    //TODO
	    //if (get_config('usersuniquebyusername')) {
	        // Registering absolutely not allowed when this setting is on, it's a 
	        // security risk. See the documentation for the usersuniquebyusername 
	        // setting for more information
	        $newinstitution->registerallowed = 0;
	    //}
	    //else {
	     //   $newinstitution->registerallowed              = ($values['registerallowed']) ? 1 : 0;
	    //}
	    //$newinstitution->theme                        = (empty($values['theme']) || $values['theme'] == 'sitedefault') ? null : $values['theme'];
	    if (isset($values) && $institution != 'mahara') {
	        $newinstitution->defaultmembershipperiod  = ($values['defaultmembershipperiod']) ? intval($values['defaultmembershipperiod']) : null;
	        if ($user->get('admin')) {
	            $newinstitution->maxuseraccounts      = ($values['maxuseraccounts']) ? intval($values['maxuseraccounts']) : null;
	            $newinstitution->expiry               = db_format_timestamp($values['expiry']);
	        }
	    }
    	insert_record('institution', $newinstitution);
    
        $authinstance = (object)array(
            'instancename' => 'Basic LTI',
            'priority'     => 0,
            'institution'  => $newinstitution->name,
            'authname'     => 'blti',
        );
        insert_record('auth_instance', $authinstance);

	    $array_institution_config = array('sitepages_about' => 'mahara', 'sitepages_home' => 'mahara'
        	,'sitepages_loggedouthome' => 'mahara', 'sitepages_privacy' => 'mahara', 'sitepages_termsandconditions' => 'mahara');
        foreach ($array_institution_config as $key => $value) {
        	$institution_config = (object)array(
	                'institution'  => $newinstitution->name,
	                'field'     => $key,
	                'value'		=> $value
	            );
        	insert_record('institution_config', $institution_config);
        }
        
	    
       return; 
    }

    /**
     * Suspend $USER
     * @param string $reason	reason for susending the user. Defaults to "creation of account".
     * TODO Repassar
     */
    public function suspend_user($reason = '') {
        global $USER;

        if (!$this->create_active()) {
            $reason = empty($reason) ? get_string('creation_of_account', 'auth.blti') : $reason;
            suspend_user($USER->get('id'), $reason);
            $USER->logout(); //logout after suspenstion !!
        }

        return true;
    }

}

