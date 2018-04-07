<?php
namespace App\Model\Table;

use App\Controller\Component\ShellComponent;
use App\Lib\ObjectUtils;
use App\Lib\Utils;
use App\Model\Entity\Company;
use App\Model\Entity\Group;
use App\Model\Entity\Integration;
use App\Model\Entity\SubscriptionProfile;
use App\Model\Entity\User;
use App\Model\Table\AppTable;
use Cake\Auth\WeakPasswordHasher;
use Cake\Core\Configure;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\RulesChecker;
use Cake\Utility\Hash;
use Cake\Validation\Validation;
use Cake\Validation\Validator;

class UsersTable extends AppTable
{
    const PASSWORD_ERROR_MESSAGE = 'Password must be a minimum of 8 characters long and must include at least 1 number and 1 special character.';

    public function initialize(array $config)
    {
        $this->belongsTo('Companies');
        $this->belongsTo('Departments');
        $this->belongsTo('Locations', [
        		'foreignKey' => 'default_location_id'
        	]);
        $this->belongsTo('Approver', [
        		'className' => 'Users',
        		'foreignKey' => 'approver_id'
        	]);
        $this->belongsTo('Approver2', [
        		'className' => 'Users',
        		'foreignKey' => 'approver2_id'
        	]);
        $this->belongsTo('TimeApprover', [
        		'className' => 'Users',
        		'foreignKey' => 'time_approver_id'
        	]);
        $this->belongsTo('TimeApprover2', [
        		'className' => 'Users',
        		'foreignKey' => 'time_approver2_id'
        	]);
        $this->belongsTo('Bookkeeper', [
        		'className' => 'Users',
        		'foreignKey' => 'bookkeeper_id'
        	]);
        $this->belongsTo('SubscriptionProfiles');
        $this->belongsTo('Groups');
        $this->hasOne('PayrollUsers');
        $this->hasOne('TrinetUsers');
        $this->belongsTo('Countries');
        $this->belongsTo('States');
        $this->hasOne('UserReceiptEmails');

        $this->hasOne('CompanyOwers', [
                'className' => 'Companies',
                'foreignKey' => 'owner_id',
                'joinType' => 'INNER',
            ]);
        
        parent::initialize($config);
    }

    public function buildRules(RulesChecker $rules) {
    	$rules->add($rules->isUnique(['email']));
    	return $rules;
    }

    public function validationDefault(Validator $validator)
    {
    	//email
    	$validator
    		->requirePresence('email', 'create', __('Email must be present.'))
    		->add('email', 'valid-email', [
    			'rule' => 'email',
    			'message' => __('Enter a valid email.')
    		])
    		->add('email', 'email-too-long', [
    			'rule' => ['maxLength', 125],
    			'message' => __('Email must be no larger than 125 characters long.')
    		]);
       //password
       $validator->requirePresence('confirm_password', 'create', __('Password must be present.'))
       		->add('confirm_password', 'valid-password', [
       	        'rule' => 'containsNumberic',
                'last' => true,
       			'message' => __(self::PASSWORD_ERROR_MESSAGE),
       			'provider' => 'table'
       		])
            ->add('confirm_password', 'containsNonAlpha-password', [
                'rule' => 'containsNonAlphaNumeric',
                'last' => true,
                'message' => __(self::PASSWORD_ERROR_MESSAGE)
            ])
            ->add('confirm_password', 'minLength-password', [
                'rule' => ['minLength', 8],
                'last' => true,
                'message' => __(self::PASSWORD_ERROR_MESSAGE)
            ])
            ->add('confirm_password', 'maxLength-password', [
                'rule' => ['maxLength', 255],
                'last' => true,
                'message' => __(self::PASSWORD_ERROR_MESSAGE)
            ])
       		->add('confirm_password', 'matched-password', [
       			'rule' => 'matchPassword',
       			'message' => __('Passwords do not match.'),
       			'provider' => 'table'
       		]);
       		
       	$validator->notEmpty('confirm_password', 'Password cannot be left empty');
        return $validator;
    }
    
    public function validationOnlyCheckPassword(Validator $validator) {
        $validator = $this->validationDefault($validator);
        $validator->remove('email');
        return $validator;
    }

    public function validationResetPassword($params) {
        $newUser = $this->newEntity($params, ['validate' => 'onlyCheckPassword']);
        $errors = $newUser->errors();
        if (empty($errors)) {
            return [
                'status' => RESPONSE_STATUS_SUCCESS,
                'flashMessage' => ''
            ];
        }
        $flashMessage = [];
        foreach ($errors as $key => $value) {
            foreach ($value as $key2 => $valueChild) {
                $flashMessage[] = $valueChild;
            }
        }
        // $flashMessage[] is User->invalidFields in cake 2x
        return [
                'status' => RESPONSE_STATUS_FAILED,
                'flashMessage' => $flashMessage
            ];
    }
    /**
     * [validates fields use validationDefault
     * @param  [array] $params ['email']
     * @return [array] true if pass
     */
    public function validates($params)
    {
        $newUser = $this->newEntity($params);
        $errors = $newUser->errors();
        if (empty($errors)) {
            return [
                'status' => RESPONSE_STATUS_SUCCESS,
                'flashMessage' => ''
            ];
        }
        /*
        $errors example
        [
            'email' => [
                'valid-email' => 'Enter a valid email.'
            ],
            'confirm_password' => [
                'valid-password' => 'Password must be a minimum of 8 characters long and must include at least 1 number.',
                'matched-password' => 'Passwords do not match.'
            ]
        ]
         */
        $flashMessage = [];
        foreach ($errors as $key => $value) {
            foreach ($value as $key2 => $valueChild) {
                $flashMessage[] = $valueChild;
            }
        }
        // $flashMessage[] is User->invalidFields in cake 2x
        return [
                'status' => RESPONSE_STATUS_FAILED,
                'flashMessage' => $flashMessage
            ];
    }
    /**
     * validate data before save expense
     * @param array $params is input data in register form
     * @return array  [
     * 'status' => true / false,
     * 'flashMessage' => string
     * ]
     * * array (size=4)
             'email' => string 'email@gmail.com' (length=15)
             'password' =>
             'confirm_password' =>
             'subscribe_newsletter' => true
     */
    public function validateRegisterForm(array $params = [])
    {
        $flashMessage = "";
        $status = RESPONSE_STATUS_SUCCESS;
        $newUser = $this->newEntity($params);
        if ($newUser->errors()) {
            $status = RESPONSE_STATUS_FAILED;
            $errors = $newUser->errors();
            foreach ($errors as $key => &$value) {
                if (is_array($value)) {
                    $flashMessage = implode("<br/>", $value);
                }
            }
        }
        // check if user is locked
        if ($this->isLockUser($params['email'])) {
            $status = RESPONSE_STATUS_FAILED;
            $flashMessage .= (!empty($flashMessage)) ? "<br/>" : "";
            $flashMessage .= "This account was locked, please contact support@trinetcloud.com";
        }
        // check if user is active
        if ($this->isActiveUser($params['email'])) {
            $status = RESPONSE_STATUS_FAILED;
            $flashMessage .= (!empty($flashMessage)) ? "<br/>" : "";
            $flashMessage .= "This email used by another user.";
        }
        return [
                'status' => $status,
                'flashMessage' => $flashMessage
        ];
    }
    /**
     * [is In Active User check user is INACTIVE]
     * @param  [string]  $email of user
     * @return bool      true if in-active
     */
    public function isInActiveUser($email)
    {
        $user = $this->findByEmail($email)->first();
        if (empty($user)) {
            return false;
        }
        if ($user->status == User::STATUS_INACTIVE) {
            return true;
        }
        return false;
    }
    /**
     * is Lock User check user is locked
     * @param  [string]  $email  of user
     * @return boolean       true if is locked
     */
    public function isLockUser($email)
    {
        $user = $this->findByEmail($email)->first();
        if (empty($user)) {
            return false;
        }
        if ($user->status == User::STATUS_LOCK) {
            return true;
        }
        return false;
    }
    /**
     * [is Active User description]
     * @param  [string]  $email of user
     * @return bool      true is ACTIVE user
     */
    public function isActiveUser($email)
    {
        $user = $this->findByEmail($email)->first();
        if (empty($user)) {
            return false;
        }
        if ($user->status == User::STATUS_ACTIVE) {
            return true;
        }
        return false;
    }
    /**
     * [Password policy: ECDEV-16173
     * Minimum Length: 8 Characters
     * Complexity: Minimum 1 special and 1 number.
     * ]
     * @param  [string] $value password
     * @param  array  $context [description]
     * @return [boolean]    true if pass / false if fail to validate
     */
    public function validatePassword($value, array $context)
    {
        $user = $this->newEntity([
                'password' => $value,
                'confirm_password' => $value
            ]);
        $errors = $user->errors();
        if (empty($errors['confirm_password'])) {
            return true;
        }

        return false;
    }

	public function beforeSave(Event $event, EntityInterface $entity, \ArrayObject $options)
	{
		// Change email to all lowercase.
		if (!empty($entity->email)) {
			$entity->email = strtolower($entity->email);
		}
		return true;
	}



	public function beforeValidate($options = array()) {
		parent::beforeValidate($options);
		$this->data->password = Utils::hashPassword($this->data->password);
		return TRUE;
	}

	function isUniqueAndInactive($check) {
		// $data array is passed using the form field name as the key
		// have to extract the value to make the function generic
		$value = array_values($check);
		$email = $value[0];
		$result = false;
		$exised_user = $this->findByEmail( $email );
		if (empty($exised_user)) {
			$result = true;
		} elseif( $exised_user['User']['status'] == User::STATUS_INACTIVE ) {
			$result = true;
		} elseif($exised_user['User']['id'] == $this->id) {
			$result = true;
		}

		return $result;

	}
	function matchPassword($value, array $context){
		$pass = isset($context['data']['password']) ? $context['data']['password'] : '';
		$conf = isset($context['data']['confirm_password']) ? $context['data']['confirm_password'] : '';

		return ( $pass === $conf );
	}


    /**
    * generate a hash string for reseting user's password
    *
    * @param String $password
    * @return string
    */
	function generateResetPaswordCode($password) {
		$currentDate = date("Y-m-d");

		$passwordHasher = new WeakPasswordHasher(array('hashType' => 'sha256'));
		return $passwordHasher->hash($currentDate . Configure::read('Security.salt') . $password);
		//return Utils::hashPassword($currentDate . Configure::read('Security.salt') . $password);
	}

	public function getActiveUsersByCompany($companyId) {
		$params = array(
			'conditions' => array(
				'company_id' => $companyId,
				'status' => User::STATUS_ACTIVE
			),
			'fields' => array('id', 'email'),
			'order' => array('id' => 'asc')
		);

		return $this->find('all', $params)->all();
	}


    /**
     * @deprecated
     * Will be removed soon, use virtual field fullname insteads
     */
	public function getFormalName($firstName, $lastName, $email) {
    	if (!empty($firstName) || !empty($lastName)) {
    		return trim($firstName . ' ' . $lastName);
    	} else {
    		return $email;
    	}
    }

    public function updateDefaultExportIntegration($userId, $export, $defaultIntegration = NULL) {
        $this->updateAll(
            ['default_integration' => $export, 'default_integration_id' => $defaultIntegration],
            ['id' => $userId]
        );
    }

	/**
	 *
	 * Find users of company by email list
	 * @param array $emails : list emails
	 * @param string $fields
	 * @return array users
	 */
	public function findUserActiveCompanyByEmail($emails = array(), $fields = '') {
		$params = array();
		if ($fields == '') {
			$params['fields'] = ['Users.id' , 'Users.email' , 'Users.status', 'Users.license_type', 'Users.subscription_profile_id', 'Companies.id' , 'Companies.lft', 'Companies.rght'];
		} else {
			$params['fields'] = $fields;
		}
		$params['conditions'] = [     'Users.status' => User::STATUS_ACTIVE,
									  'Users.email IN' => $emails ];

		$params['join'] = [
                            [
									'table' => 'companies',
                                    'alias' => 'Companies',
									'type' => 'INNER',
									'conditions' => ['Companies.id = Users.company_id']
                            ]
                        ];

		return $this->find('all', $params);
	}

	/**
	 *
	 * Get users for push to Hubspot
	 * @param int $step
	 * @param int $perStep
	 * @param string $email or array emails
	 *
	 * @return False || array (0 => array(), 1 => array(),...)
	 */

	public function getSubscriberData($step = null, $perStep = null, $email = null, $startDate = null, $condition = array()) {
		$options['contain'] = ['SubscriptionProfiles'];

        $options['conditions'] = ['Users.status' => 'Active'];

        if (! empty($startDate)) {
            $options['conditions']['Users.register_date >= '] = $startDate;
        }

        if (! empty($condition)) {
            $options['conditions'] = $options['conditions'] + $condition;
        }

        if (isset($step)) {
            $options['offset'] = $step;
        }
        if (isset($perStep)) {
            $options['limit'] = $perStep;
        }
        $data = false;
        if (! empty($email)) {
            $options['conditions']['Users.email'] = $email;
        } elseif (empty($step) && empty($perStep)) { // emails or (step & perStep) are required
            return $data;
        }

        $result = $this->find('all', $options)->toArray();
        if (! empty($result)) {
            foreach ($result as $user) {
                if (! empty($user)) {
                    $data[] = $this->_processSubscriberData($user);
                }
            }
        }
        return $data;
    }

    /**
     *
     * Create data of users for push to hubspot
     * @param User $user
     */
    private function _processSubscriberData(User $user)
    {
        ObjectUtils::useTables($this, array(
            'Integrations',
            'Companies'
        ));

        // get company info
        $company = $this->CompaniesTable->get($user->company_id);
        $user->company = $company;

        $fields = Configure::read('Hubspot.fields');
        $loginFrom = '';
        if (! empty($user->login_from)) {
            $loginFrom = $user->login_from;
            if ($user->login_from == 'IWP' || $user->login_from == 'IA OpenId') {
                $loginFrom = 'Intuit';
            }
        }

        $userIntegrations = $this->IntegrationsTable->find()->where([
            'Integrations.user_id' => $user->id,
            'Integrations.type <>' => Integration::INTEGRATION_TYPE_CUSTOM
        ])->first();

        $netsuites = $intacct = $qboe = [];
        if(!empty($userIntegrations)) {
            $userIntegrations = $userIntegrations->toArray();
            $netsuites = array_filter(Hash::extract($userIntegrations, '{n}.netsuite_user_id'));
            $intacct = array_filter(Hash::extract($userIntegrations, '{n}.intacct_user_id'));
            $qboe = array_filter(Hash::extract($userIntegrations, '{n}.qboe_user_id'));
            unset($userIntegrations);
        }

        $data = [
            $fields['email'] => $user->email,
            $fields['firstname'] => $user->firstname,
            $fields['lastname'] => $user->lastname,
            $fields['company-owner'] => ! empty($user->company) && $user->company->owner_id == $user->id ? HUBSPOT_YES : HUBSPOT_NO,
            $fields['paid-user'] => !empty($user->subscription_profile) && $user->subscription_profile->id != null ? HUBSPOT_YES : HUBSPOT_NO,
            $fields['source'] => $loginFrom,
            $fields['register-day'] => !empty($user->register_date) ? $user->register_date : date("Y-m-d"),
            $fields['subscribe-newsletter'] => $user->subscribe_newsletter
        ];
        $data[$fields['netsuite']] = count($netsuites) ? HUBSPOT_YES : HUBSPOT_NO;
        $data[$fields['intacct']] = count($intacct) ? HUBSPOT_YES : HUBSPOT_NO;
        $data[$fields['quickbooks']] = count($qboe) ? HUBSPOT_YES : HUBSPOT_NO;
        $data[$fields['company']] = ! empty($user->company) && $user->company->name != null ?$user->company->name : '';
        $data[$fields['phone']] = ! empty($user->company) && $user->company->work_phone != null ? $user->company->work_phone : '';
        $data[$fields['company-role']] = ! empty($user->company) && $user->company->owner_id == $user->id ? 'Owner' : $user->role;

        $data[$fields['cloud-products']] = Configure::read('Hubspot.cloudProduct');
        $trialDays = Utils::calculateTrialDays($data[$fields['register-day']]);
        if (! empty($user->company) && $user->company->id != null && ! empty($user->subscription_profile) && $user->subscription_profile->id != null) {
            $data[$fields['account-type']] = 'Expense Paid';
        } elseif (Utils::isExpired($trialDays)) {
            $data[$fields['account-type']] = 'Expense Expired';
        } else {
            $data[$fields['account-type']] = 'Expense Trial';
        }
        if ($data[$fields['company-role']] == 'Owner') {
            ObjectUtils::useTables($this, [
                'SubscriptionProfiles'
            ]);

            if ($this->SubscriptionProfilesTable->exists([
                'owner_id' => $user->company->owner_id,
                'profile_status' => SubscriptionProfile::PROFILE_STATUS_CANCELLED
            ])) {
                $data[$fields['account-type']] = 'Expense Cancelled';
            }
        }
        return $data;
	}

	public function getAllInactivedLockedUnsubscribedUsers() {
		$options = array(
			'fields' => array('Users.id', 'Users.email', 'Users.status', 'Users.subscribe_newsletter'),
			'conditions' => array(
				'Or' => array(
					'Users.status <>' => User::STATUS_ACTIVE,
					'Users.subscribe_newsletter' => 'No'
				)
			)
		);
		$result = $this->find('all', $options);
		return $result;
	}

	public function createExportData($params){
		$rows = array();
		//Get User of that company
		$compUsersObj = $this->find('all', array(
			'fields' => array (
				'id',
				'email',
				'firstname',
				'lastname',
				'approver_id',
				'approver2_id',
				'role',
				'license_type',
				'status'
			),
			'conditions' => array (
				'company_id' => $params['companyId']
			)
		));
        $compUsers = $compUsersObj->toArray();
        $compUsersArray = $compUsersObj->hydrate(false)->toArray();
		//Generate Approver ID
		$approveIds = Hash::extract($compUsersArray, '{n}.approver_id');
		$approveId2s = Hash::extract($compUsers, '{n}.approver2_id');
		$approveIds = array_unique(array_merge($approveIds, $approveId2s));
		//Get Approver Email
		$approve_email = $this->find('list', array(
            'keyField' => 'id',
            'valueField' => 'email',
			'conditions' => array (
				'id in' => $approveIds
			)
		))
        ->toArray();
		//Fill Users Information
		foreach($compUsers as $compUser){
			$licenseExpense = 'No';
			$row['email'] = $compUser->email;
			$row['firstname'] = $compUser->firstname;
			$row['lastname'] = $compUser->lastname;
			$row['approver_email'] =  isset ($compUser->approver_id) && isset($approve_email[$compUser->approver_id]) ? $approve_email[$compUser->approver_id] : '';
			$row['next_approver_email'] =  isset ($compUser->approver2_id) && isset($approve_email[$compUser->approver2_id]) ? $approve_email[$compUser->approver2_id] : '';
			$row['status'] = $compUser->status;
			if (in_array($compUser->license_type, array(LICENSE_EXPENSE, LICENSE_EXPENSE_TIME))) {
				$licenseExpense = 'Yes';
			} elseif($compUser->license_type == LICENSE_TRANSACTIONAL) {
				$licenseExpense = 'Activity';
			}
			$row['expense_license'] = $licenseExpense;
			$row['time_license'] =  ( $compUser->license_type == LICENSE_EXPENSE_TIME ) ? 'Yes' : 'No';
			$row['role'] = ($compUser->id == $params['companyOwnerId']) ? 'Owner' : $compUser->role;

			$rows[$row['email']] = $row;
		}
		return $rows;
	}

	public function findActiveUserByEmailAndPassword($email = '', $password = '', $arrFields = array('Users.id')) {
		return $this->find('all', array(
			'fields' => $arrFields,
			'conditions' => array(
				'Users.email'	=> $email,
				'Users.password' => Utils::hashPassword($password),
				'Users.status' 	=> User::STATUS_ACTIVE
			)
		))->first();
	}

    public function findActiveUserByEmailAndOldPassword($email = '', $password = '', $arrFields = array('Users.id')) {
        return $this->find('all', array(
            'fields' => $arrFields,
            'conditions' => array(
                'Users.email'   => $email,
                'Users.old_password' => hash('sha256', Utils::hashPassword($password) ),
                'Users.status'  => User::STATUS_ACTIVE
            )
        ))->first();
    }

	public function findUserByEmailAndPassword($email = '', $password = '', $arrFields = array('Users.id')) {
		return $this->find('all', array(
			'fields' => $arrFields,
			'conditions' => array(
				'Users.email'	=> $email,
				'Users.password' => Utils::hashPassword($password),
			)
		))->first();
	}

	/**
	 * Function get Bookkeeper Time Users.
	 * @param array $params
	 * Ex: $params[user_id] :  get by user id
	 *     $params[email] :  get by email
	 * 	   $field[fields] :  limit fields
	 * @return array
	 * EX get only email fields :
	 * 		array(
	 *				 0 => array(
	 *					'User' => array(
	 *						'email' => 'test@mailinator.com'
	 *					)
	 *				),
	 *				 1 => array(
	 *					'User' => array(
	 *						'email' => 'test1@mailinator.com'
	 *					)
	 *				)
	 *			);
	 */

	public function getBookkeeperTimeUser($params = array()) {
		$conditions = array();
		$fields = 'Users.id, Users.email, Users.firstname, Users.lastname';
		if (isset($params['fields'])) {
			$fields = $params['fields'];
		}
		if (!empty($params['user_id'])) {
			$conditions['Users.id'] = $params['user_id'];
		} elseif (!empty($params['email'])) {
			$conditions['Users.email'] = $params['email'];
		}

		$conditions['Users.role'] = User::ROLE_BOOKKEEPER_TIME;

		return $this->find('all', array('conditions' => $conditions, 'fields' => $fields));
	}

	/**
	 *
	 * Find user with details by user id
	 * (Detail : Company, Group, Country, State, Department)
	 * @param int userId
	 * @param array :  $fields, conditions
	 * @return array
	 * EX :array
	 * array(
	 *			'User' => array(
	 *				'firstname' => null,
	 *				'lastname' => null,
	 *				'email' => xxxx
	 *			),
	 *			'Company' => array(
	 *				'name' => 'Company 2'
	 *			),
	 *			'Group' => array(
	 *				'name' => 'Paid User'
	 *			),
	 *			State,....
	 *		)
	 */
	public function findUserWithDetailsById($userId, $params = array()) {
		$query = array();
		$query['fields'] = ['Users.id', 'Users.firstname', 'Users.lastname', 'Users.email', 'Companies.id', 'Companies.name', 'Groups.id', 'Groups.name', 'Countries.id', 'Countries.name', 'States.id', 'States.name', 'Departments.name'];

		if (!empty($params['fields'])) {
			$query['fields'] = $params['fields'];
		}

		$query['conditions'] = array('Users.id' => $userId);
		if (!empty($params['conditions'])) {
			$query['conditions'] = array_merge($params['conditions'], $query['conditions']);
		}

        $query['contain'] = ['Companies', 'Groups', 'Countries', 'States', 'Departments', 'UserReceiptEmails'];
		return $this->find('all', $query)->first();
	}

	/**
	 * Count user login follow time
	 * @param string $time : "1 days", "-8 hours",...
	 * @return total
	 */
	public function countUsersLoginFollowTime($time) {
		$convertedTime = date('Y-m-d H:i:s', strtotime($time));

		$params = array();
		$params['conditions'] = array('Users.last_login >= ' => $convertedTime);

		return $this->find('all', $params)->count();
	}

	/**
	 * Count total company by profile status and time
	 * @param string $profileStatus
	 * @param string $time : "1 days", "-8 hours",...IF time is null or empty => search by profile status
	 * @return total
	 */
	public function countUsersChangeProfileStatusFollowTime($profileStatus, $time = '') {
		ObjectUtils::useTables($this, array('SubscriptionProfiles', 'Companies'));

		$params = array();
		if (!empty($time)) {
			$convertedTime = date('Y-m-d H:i:s', strtotime($time));
			$params['conditions']['SubscriptionProfiles.created >= '] = $convertedTime;
		}
		$params['conditions']['SubscriptionProfiles.profile_status'] = $profileStatus;
		$params['conditions']['Users.status'] = User::STATUS_ACTIVE;
		$params['conditions']['Users.company_id != '] = Company::EXPENSECLOUD_ID;

		$params['join'] = array(
				array('table' => 'subscription_profiles',
						'alias' => 'SubscriptionProfiles',
						'type' => 'left',
						'conditions' => array('Users.id = SubscriptionProfiles.owner_id')
				),
		);

		return $this->find('all', $params)->count();
	}

	public function getCompanyByUserId($userId, $params = array()) {
	    if(!is_numeric($userId)) {
	        return false;
	    }
	    $query = array();

	    if (!empty($params['fields'])) {
	        $query['fields'] = $params['fields'];
	    }

	    $query['conditions'] = array('Users.id' => $userId);
	    $query['join'] = array(
	        array('table' => 'companies',
	            'alias' => 'Companies',
	            'type' => 'inner',
	            'conditions' => array('Users.id = Companies.owner_id')));

	    return $this->find('all', $query)->first();
	}

	/**
	 * Get all users in company by company_id
	 * @param int $companyId
	 * @param array $params = array('fields' => xxx, 'conditions' => xxx)
	 * @return array
	 */
	public function getAllUsersInCompany($companyId, $params = array()) {
	    ObjectUtils::useTables($this, array('Group'));
	    $query = array();

	    if (!empty($params['fields'])) {
	        $query['fields'] = $params['fields'];
	    }

	    $query['conditions']['Users.company_id'] = $companyId;
	    $query['conditions']['Users.group_id != '] = Group::EXPIRED_USER_ID;

	    return $this->find("all", $query);
	}

	/**
	 * Get free individual free.
	 * @param array $params = array('fields' => xxx, 'conditions' => xxxx)
	 * @return array
	 */
	public function getFreeIndividualUsers($params = array()) {
	    ObjectUtils::useTables($this, array('Company', 'Group'));

	    $query = array();
	    $query['fields'] = 'Users.id, Users.email, Users.company_id';

		if (!empty($params['fields'])) {
			$query['fields'] = $params['fields'];
		}

		$query['conditions'] = array('Users.company_id' => Company::EXPENSECLOUD_ID,
		                             'Users.subscription_profile_id IS ' => NULL,
		                             'Users.group_id != ' => Group::PAID_USER_ID,
		                             'Users.status' => User::STATUS_ACTIVE,
		                             'Users.id != ' => 1);//root

		$query['order'] = 'Users.id ASC';
		if (!empty($params['order'])) {
		    $query['order'] = $params['order'];
		}

		$query['limit'] = 1000;
		if (!empty($params['limit'])) {
		    $query['limit'] = $params['limit'];
		}

		$query['offset'] = 0;
		if (!empty($params['offset'])) {
		    $query['offset'] = $params['offset'];
		}

		$findType = "all";
		if (!empty($params['find_type'])) {
		    $findType = $params['find_type'];
		}

		return $this->find($findType, $query);
	}

	/**
	 * Get Trial Users and Expired Users.
	 * @param array $params = array('fields' => xxx, 'conditions' => xxxx)
	 * @return array
	 */
	public function getTrialExpiredUsers($params = array()) {
	    $query = array();
	    $query['fields'] = 'Users.id, Users.email, Users.company_id';

	    if (!empty($params['fields'])) {
	        $query['fields'] = $params['fields'];
	    }

	    $query['conditions'] = array(
	        'Users.group_id' => array(Group::TRIAL_USER_ID, Group::EXPIRED_USER_ID),
	        'Users.status' => User::STATUS_ACTIVE,
	        'Users.id != ' => 1
        );

	    $query['order'] = 'Users.id ASC';
	    if (!empty($params['order'])) {
	        $query['order'] = $params['order'];
	    }

	    $query['offset'] = 0;
	    if (!empty($params['offset'])) {
	        $query['offset'] = $params['offset'];
	    }

        $builder = $this->find('all', $query);
	    if (!empty($params['find_type'])) {
            if ($params['find_type'] == 'first') {
                return $builder->first();
            } elseif ($params['find_type'] == 'count') {
                return $builder->count();
            }
	    }
        return $builder->toArray();
	}

	/**
	 * get days left on Trial
	 * @param string $userId
	 */
	public function getDaysLeftOnTrial($userId) {
		ObjectUtils::useTables($this, array('Companies'));
		$user = $this->findById($userId)->first();
		$registerDay = Time::parse($user->register_date);
        if(!empty($registerDay)) {
            $registerDay = $registerDay->i18nFormat(DEFAULT_CAKE3_DATE_FORMAT);
        }

		$companyId= $user->company_id;
		if ($companyId > 1)
		{
                    // get days left depend on Company Owner of user
                    $companyInfo = $this->CompaniesTable->findCompanyOwner($companyId);
                    if ($companyInfo->owner_id != $userId)
                    {
                        $companyOwner = $this->findById($companyInfo->owner_id)->first();
                        $registerDay = Time::parse($companyOwner->register_date);
                        if(!empty($companyOwner->register_date)){
                            $registerDay = $registerDay->i18nFormat(DEFAULT_CAKE3_DATE_FORMAT);
                        }
                    }
		}
		$seconds = strtotime("now") - strtotime($registerDay);
		// change to days
		$days = floor($seconds / 60 / 60 / 24);
		$days = ($days >=30) ? 0: (30 - $days);        
		return $days;
	}
	/**
	 *  only company owner and new register user can Upgrade account
	 * @param string $userId
	 */
	 public function canUpgradeSubscription($userId) {
	 	ObjectUtils::useTables($this, array('Companies'));
	 	$user = $this->findById($userId)->first();
		$companyId= $user->company_id;
		// New user not belong to any company
		if ($companyId==1) return true;
		$companyInfo = $this->CompaniesTable->findCompanyOwner($companyId);
		if ($companyInfo->owner_id != $userId)
		{
			return false;
		}else
			return true;
	 }

    public function enableTimeModeForOwnerEvent($params)
    {
        if (isset($params->data['params'])) {
            $params = $params->data['params'];
        }
        $this->enableTimeModeForOwner($params);
    }

    public function enableTimeModeForOwner($params)
    {
        if (! isset($params['owner']) || ! isset($params['licenseType'])) {
            return false;
        }

        $owner = $params['owner'];
        $licenseType = $params['licenseType'];
        if ($licenseType == LICENSE_EXPENSE_TIME) {
            $notIsTimeMode = $this->find('all')
                ->where(array(
                    'id' => $owner->id,
                    'license_type <> ' => $licenseType
                ))
                ->count();
            if ($notIsTimeMode) {
                ObjectUtils::useTables($this, ['Users']);
                $enUser = $this->UsersTable->get($owner->id);
                $this->updateAll(array(
                        'license_type' => $licenseType
                    ), array(
                        'id' => $owner->id
                    ));
                // write to log
                ObjectUtils::useTables($this, ['ProfileLicenseLogs']);
                $paramsLog = $params;
                $paramsLog['userId'] = $owner->id;
                $paramsLog['licenseFrom'] = $enUser->license_type;
                return $this->ProfileLicenseLogsTable->saveData($paramsLog);
            }
        }
        return true;
    }

    /**
     *
     * @param type $user
     * @param type $options
     * @return array array('User' => $user, 'Company' => $company, ...);
     */
    private function _repairDataForUser(array $user, $options = null) {
        $result = array('User' => $user);
        //
        if (array_key_exists('Company',$options)) {
            $company = null;
            if (!empty($options['Company'])) {
                $company = $options['Company'];
            }
            if (empty($company)) {
                if (!empty($user['company'])) {
                    $company = $user['company'];
                } elseif (empty($user['company_id'])) {
                    $user = $this->findById($user['id'])
                        ->hydrate(false)
                        ->first();
                    $company = $this->CompaniesTable->findById($user['company_id'])
                        ->hydrate(false)
                        ->first();
                } else {
                    $company = $this->CompaniesTable->findById($user['company_id'])
                        ->hydrate(false)
                        ->first();
                }
            }
            unset($user['company']);
            $result['User'] = $user;
            $result['Company'] = $company;
        }
        return $result;
    }

    public function isCoHasExpenseTime ($user = null, $owner = null, $ownerParent = null) {
        if (empty($user) && empty($owner) && empty($ownerParent)){
            return false;
        }
        $userIds = array();
        $company = null;
        ObjectUtils::useTables($this, array('Companies'));
        if (!empty($user)) {
            $data = $this->_repairDataForUser($user, array('Company' => $company));
            $company = $data['Company'];
            $user = $data['User'];
            //$userIds[$data['User']['id']] = 1;
        }
        if (!empty($owner)) {
            $data = $this->_repairDataForUser($owner, array('Company' => $company));
            $company = $data['Company'];
            $owner = $data['User'];
            $userIds[$data['User']['id']] = 1;
        } elseif (!empty ($company)) {
            if ($company['owner_id'] == $user['id']) {
                $owner = $user;
            } else {
                $owner = $this->findById($company['owner_id'])
                        ->hydrate(false)
                        ->first();
            }
            $userIds[$owner['id']] = 1;
        }
        if (!empty($ownerParent)) {
            $data = $this->_repairDataForUser($ownerParent);
            $userIds[$data['User']['id']] = 1;
        } elseif (!empty ($company) && !empty($company['parent_id']) && $company['parent_id'] != 1) {
            // has parent
            $companyParent = $this->CompaniesTable->findById($company['parent_id'])
                        ->hydrate(false)
                        ->first();
            $ownerParent = $this->findById($companyParent['owner_id'])
                        ->hydrate(false)
                        ->first();
            if (!empty($owner['subscription_profile_id']) && $owner['subscription_profile_id'] == $ownerParent['subscription_profile_id']) {
                $userIds[$ownerParent['id']] = 1;
            } elseif (empty($owner['subscription_profile_id'])) {
                $userIds[$ownerParent['id']] = 1;
            }
        }
        $keyUserIds = array_keys($userIds);
        $notIsTimeMode = $this->find('all')
            ->where(array(
            'id in' => $keyUserIds,
            'license_type <>' => LICENSE_EXPENSE_TIME
            ))
            ->count();
        return !$notIsTimeMode;
    }

    public function getCompanyInfo($user, $unsetSecurityInfo = true) {
        $result = array();
        ObjectUtils::useTables($this, array('Companies', 'SubscriptionProfiles'));
        // load company
        $company = null;
        if (!empty($user['company'])) {
            $company = $user['company'];
        } else {
            if (empty($user['company_id'])) {
                $user = $this->findById($user['id'])
                    ->hydrate(false)
                    ->first();
                if (empty($user)) {
                    return [];
                }
            }
            $company = $this->CompaniesTable->findById($user['company_id'])
                ->hydrate(false)
                ->first();
            if (empty($company)) {
                return [];
            }
        }
        unset($user['company']);
        // load owner
        $owner = null;
        if (!empty($user['Owner'])) {
            $owner = $user['Owner'];
        } elseif ($company['owner_id'] == $user['id']) {
            $owner = $user;
        } else {
            $owner = $this->findById($company['owner_id'])
                ->hydrate(false)
                ->first();
            if (empty($owner)) {
                return [];
            }
        }
        unset($owner['Company']);
        // load parent company
        $parentCompany = null;
        $parentOwner = null;
        if ( !empty($company['parent_id']) && $company['parent_id'] != 1) {
            // has parent
            $companyParentDb = $this->CompaniesTable->findById($company['parent_id'])
                ->hydrate(false)
                ->first();
            $parentCompany = $companyParentDb;
            $ownerParentDb = $this->findById($parentCompany['owner_id'])
                ->hydrate(false)
                ->first();;
            $parentOwner = $ownerParentDb;
            unset($parentOwner['Company']);
        }
        // load subscription profile
        $subscriptionProfile = null;
        if (!empty($owner['subscription_profile_id'])) {
            $subscriptionProfileDb = $this->SubscriptionProfilesTable->findById($owner['subscription_profile_id'])
                ->hydrate(false)
                ->first();
            $subscriptionProfile = $subscriptionProfileDb;
        } elseif (!empty($parentOwner['subscription_profile_id'])) {
            $subscriptionProfileDb = $this->SubscriptionProfilesTable->findById($parentOwner['subscription_profile_id'])
                ->hydrate(false)
                ->first();
            $subscriptionProfile = $subscriptionProfileDb;
        }
        //
        if ($unsetSecurityInfo) {
            $this->_unsetSecurityInfo($user);
            $this->_unsetSecurityInfo($owner);
            $this->_unsetSecurityInfo($parentOwner);
        }
        if( empty($parentOwner) ) {
            $data['is_parent_profile'] = true;
            $data['co_has_license_time'] = $owner['license_type'] == LICENSE_EXPENSE_TIME;
        } else {
            $data['is_parent_profile'] = empty($owner['subscription_profile_id']) || $owner['subscription_profile_id'] == $parentOwner['subscription_profile_id'];
            $data['co_has_license_time'] = $owner['license_type'] == LICENSE_EXPENSE_TIME;
            if ($data['is_parent_profile']) {
                $data['co_has_license_time'] = $data['co_has_license_time'] && $parentOwner['license_type'] == LICENSE_EXPENSE_TIME;
            }
        }
        $arrUserIds = array($user['id'] => 1);
        $arrUserIds[$owner['id']] = 1;
        if ($data['is_parent_profile'] && !empty($parentOwner)) {
            $arrUserIds[$parentOwner['id']] = 1;
        }
        $data['ids_in_profile'] = array_keys($arrUserIds);
        //
        $result = array(
            'User' => $user,
            'SubscriptionProfile' => $subscriptionProfile,
            'Company' => $company,
            'Owner' => $owner,
            'ParentCompany' => $parentCompany,
            'ParentOwner' => $parentOwner,
            'Data' => $data,
         );
        return $result;
    }

    private function _unsetSecurityInfo(&$user){
        unset($user['old_password']);
        unset($user['password']);
        unset($user['Qboe']);
        unset($user['setupedIntegrations']);
        unset($user['ec_token_id']);
    }
    /**
     * [get ID of sub-CO and parent CO if they have license type EXPENSE]
     * @param  int $companyId is sub companyId or parent companyId
     * @return [array]  ID of sub-CO and parent CO. example:  [12, 13]                
     */
    public function getSubCoAndParentCoHaveLicenseTypeExpense($companyId)
    {
        ObjectUtils::useTables($this, [
                'Companies'
            ]);
        if (empty($companyId)) {
            return [];
        }
        $userIds = [];
        // subdiary 
        $subCompany = $this->CompaniesTable->findById($companyId)->contain('Owners')->first();
        if ($subCompany->owner->license_type == LICENSE_EXPENSE) {
            $userIds[] = $subCompany->owner->id;
        }
        // get parent company
        if (!empty($subCompany->parent_id)) {
            $parentCompany = $this->CompaniesTable->findById($subCompany->parent_id)->contain('Owners')->first();   
            if ($parentCompany->owner->license_type == LICENSE_EXPENSE) {
                $userIds[] = $parentCompany->owner->id;
            }
        }
        return $userIds;
    }
    /**
     * User has setup company
     * @param type $user
     */
    public function enableTimeMode(array $user = null, array $owner = null, array $ownerParent = null) {
        if (empty($user) && empty($owner) && empty($ownerParent)){
            return false;
        }
        $userIds = array();
        $company = null;
        ObjectUtils::useTables($this, array('Companies'));

        if (!empty($user)) {
            $data = $this->_repairDataForUser($user, array('Company' => $company));
            $company = $data['Company'];
            $user = $data['User'];
            $userIds[$data['User']['id']] = 1;

        }
        if (!empty($owner)) {
            $data = $this->_repairDataForUser($owner, array('Company' => $company));
            $company = $data['Company'];
            $owner = $data['User'];
            $userIds[$data['User']['id']] = 1;
        } elseif (!empty ($company)) {
            if ($company['owner_id'] == $user['id']) {
                $owner = $user;
            } else {
                $owner = $this->findById($company['owner_id'])
                    ->hydrate(false)
                    ->first();
            }
            $userIds[$owner['id']] = 1;
        }

        if (!empty($ownerParent)) {
            $data = $this->_repairDataForUser($ownerParent);
            $userIds[$data['User']['id']] = 1;
        } elseif (!empty ($company) && !empty($company['parent_id']) && $company['parent_id'] != 1) {
            // has parent
            $companyParent = $this->CompaniesTable->findById($company['parent_id'])
                    ->hydrate(false)
                    ->first();
            $ownerParent = $this->findById($companyParent['owner_id'])
                    ->hydrate(false)
                    ->first();
            if (!empty($owner['subscription_profile_id']) && $owner['subscription_profile_id'] == $ownerParent['subscription_profile_id']) {
                $userIds[$ownerParent['id']] = 1;
            } elseif (empty($owner['subscription_profile_id'])) {
                $userIds[$ownerParent['id']] = 1;
            }
        }

        $keyUserIds = array_keys($userIds);
        $licenseTime = LICENSE_EXPENSE_TIME;


        $notIsTimeMode = $this->find('all')
            ->where(array(
                'id IN' => $keyUserIds,
                'license_type <> ' => $licenseTime
            ))
            ->count();
        
        if ($notIsTimeMode) {
            ObjectUtils::useTables($this, ['Users']);
            $aUserBefforeSave = $this->find('list', 
                ['keyField' => 'id',
                 'valueField' => 'license_type'])
                ->where(array(
                    'id IN' => $keyUserIds
                ))->toArray();

            $this->updateAll(array(
                'license_type' => $licenseTime
            ), array(
                'id IN' => $keyUserIds
            ));
            return $aUserBefforeSave;
        }
        return false;
    }

    public function isPaidUser($userId) {
        $user = $this->get($userId);	    
        return ($user->group_id == Group::PAID_USER_ID);
	}

    public function updateUserByInviteEmailEvent($params)
    {
        if (isset($params->data['params'])) {
            $params = $params->data['params'];
        }
        $this->updateUserByInviteEmail($params);
    }
	public function updateUserByInviteEmail($params)
    {
        $inviteEmail = isset($params['inviteEmail']) ? $params['inviteEmail'] : null;
        $dataUpdated = isset($params['dataUserUpdated']) ? $params['dataUserUpdated'] : null;

	    if(!Validation::email($inviteEmail) || empty($dataUpdated)) {
	        return false;
	    }

        return $this->updateAll($dataUpdated, array(
            'email' => $inviteEmail
        ));
    }

    /**
	 * update all users Individual trial
	 * @param string $userId
	 */
	public function updateAllUsersIndividualToTrialExpired() {
        $date = date('Y-m-d', strtotime(date('Y-m-d') . ' - ' . EXPIRED_DATE . ' days'));
        $this->updateAll(array(
            'group_id' => Group::EXPIRED_USER_ID
        ), array(
            'group_id' => Group::TRIAL_USER_ID,
            'OR' => array(
                'date(register_date) < ' => $date,
                'register_date is null'
            ),
            'company_id =' => Company::EXPENSECLOUD_ID
        ));
	}

    /*
     * get User Company Owner
     */
    public function getUserCompanyTrialExpired() {
        $arrFields = array('Users.id', 'Users.company_id');
        $date = date('Y-m-d', strtotime(date('Y-m-d') . ' - ' . EXPIRED_DATE . ' days'));
        $aUsers = $this->find('all', array(
            'fields' => $arrFields,
            'conditions' => array(
                'Users.group_id' => Group::TRIAL_USER_ID,
                'OR' => array(
                    'date(Users.register_date) <' => $date,
                    'Users.register_date is null'
                ),
                'Users.company_id >' => Company::EXPENSECLOUD_ID
            ),
            'contain' => [
                'Companies'
            ]
        ));
        return $aUsers;
    }

    /**
     *
     * @param type $companyId
     * @return type
     */
    public function updateAllUserHasCompanyTrialToExpired($companyId){
        if ($companyId <= 0) {
            return;
        }
        $this->updateAll(array(
            'group_id' => Group::EXPIRED_USER_ID
        ), array(
            'company_id =' => $companyId,
            'group_id' => Group::TRIAL_USER_ID
        ));
    }

    /**
     *
     * @param type $companyId
     * @return type
     */
    public function getAllUserCompanyTrial($companyId) {
        if ($companyId <= 0) {
            return;
        }
        return $this->find('all', array(
            'conditions' => array(
                'Users.company_id =' => $companyId,
                'Users.group_id' => Group::TRIAL_USER_ID
            ), 
            'contain' => ['Companies']
        ));
    }

    /**
     * get all users Individual trial is Expired
     * 
     * @param string $userId            
     */
    public function getAllUsersIndividualTrialIsExpired()
    {
        $date = date(DEFAULT_DATE_FORMAT, strtotime(date(DEFAULT_DATE_FORMAT) . ' - ' . EXPIRED_DATE . ' days'));
        return $this->find('all', [
            'conditions' => [
                'Users.group_id' => Group::TRIAL_USER_ID,
                'OR' => [
                    'date(Users.register_date) < ' => $date,
                    'Users.register_date is null'
                ],
                'Users.company_id =' => Company::EXPENSECLOUD_ID
            ]
        ]);
    }
	/**
     * update License
     * @param type $userId, $licenseType
     * @return type
     */
    public function updateLicense($userId,$licenseType)
	{
		$user = $this->get($userId);
        $user->license_type = $licenseType;
		return $this->save($user);
	}

    public function getCountUserExpenseTimeInCompany($companyId){
        return $this->find('all',
            array(
			'conditions' =>
                array(
                    'Users.license_type'	=> User::LICENSE_TYPE_EXPENSETIME,
                    'Users.company_id =' => $companyId
                )
		))->count();
    }

    /**
     * get all subsidiary and parent company
     * @param int $companyId
     * @return array
     */
    public function getSubsidiaryOwner($companyId) {
        ObjectUtils::useTables($this, array('Companies', 'Users'));
        $subOwnerId = $this->CompaniesTable->getCompanyOwnerId($companyId);
        $owner = $this->findById($subOwnerId)
            ->contain(['Companies'])
            ->first();
        return $owner;
    }

	/*
	 * check User To Show SunSet Notification
	 * Free Individual
	 * Free Company
	 * Paid Company: CO/Admin/Free
	 * example: $this->User->isUserToShowSunSetNotification($this->Auth->user());
	 */
	public function isUserToShowSunSetNotification($user)
    {
		ObjectUtils::useTables($this, ['Groups', 'Companies']);
		$groupId = $user["group_id"];
		$companyId = $user["company_id"];
		$isCompanyOwner = $user['is_company_owner'];
		$isCompanyAdmin = $user['is_company_admin'];
		// free -> show
		if ($groupId == Group::TRIAL_USER_ID) {
			return true;
		}
		// COMPANY
		if ($companyId > Company::EXPENSECLOUD_ID) {
			$companyOwner = $this->CompaniesTable->findCompanyOwner($companyId, ["Users.group_id", "Users.subscription_profile_id"]);
			// PAID COMPANY--> shown notification only CO/Admin/Free
			if ($companyOwner->Users->group_id == Group::PAID_USER_ID) {
				// current logged-in user is CO / Admin/BK
				if (($isCompanyOwner) || ($isCompanyAdmin)) {
					return true;
				}
			}
		}
		return false;

	}

    /**
     *
     * @param type $userId
     * @return type
     */
    public function getUserCObyEmployee($userId){
        ObjectUtils::useTables($this, array('Companies'));
        $userInfo = $this->findById($userId)->first();
        if(empty($userInfo)) {
            return 0;
        }
        $compnayInfo = $this->CompaniesTable->findById($userInfo->company_id)
            ->contain(['Owners'])
            ->first();
        return $compnayInfo->owner;
    }
    /**
     * [update Activation Code description]
     * @param  [array] $params [
     *      'activation_code' => $activationCode,
            'subscribe_newsletter' => $subscribeNewsletter,
            'register_ip' => $userIP,
            'referrer' => !empty($referral) ? $referral : null
     * ]
     * @return [Entity\User]   user
     */
    public function updateActivationCode($params)
    {
        $user = $this->findById($params['userId'])->first();
        $user->activation_code = $params['activation_code'];
        $user->subscribe_newsletter = $params['subscribe_newsletter'];
        $user->register_ip = $params['register_ip'];
        $user->referrer = $params['referrer'];
        return $this->save($user);
    }
    /**
     * [isRedirectForExpired check if redirect to dashboard when expired user visit a page
     * true: redirect to /users/dashboard
     * false: not redirect.
     * @param  [string]  $controller 
     * @param  [string]  $action   
     * @return boolean             
     */
    public function isRedirectForExpired($controller, $action)
    {
        $controller = strtolower($controller);        
        $action = strtolower($action);        

        $notRedirectController = [
                'pages', 'payments'
            ];

        if (in_array($controller, $notRedirectController)) {
            return false;
        }
        // [ controller => [actions list]]
        $notRedirectPages = [
            'users' => ['dashboard','logout']
        ];
        
        if (!empty($notRedirectPages[$controller]) && (in_array($action, $notRedirectPages[$controller])))  {
            return false;
        }
        return true;
    }
    /**
     * [get Box942013Closed for dashboard]
     * @param  [array] $user [$this->Auth->user()]
     * @return [bool]       [description]
     */
    public function getBox942013Closed($user)
    {
        ObjectUtils::useTables($this, ['Companies']);
        $box942013Closed = true;
        if ($user['company_id'] != Company::EXPENSECLOUD_ID) {
            $companySettings = $user['company'];
            if (!empty($companySettings) && ($companySettings['owner_id'] == $user['id'])) {
                $box942013Closed = true;
                if ($companySettings['allow_user_integrations'] == Company::ALLOW_USER_INTEGRATIONS_NO) {
                    ObjectUtils::useTables($this, ['Integrations']);
                    $countEmployeeIntegrations = $this->IntegrationsTable->find('all')
                                        ->where([
                                                    'Integrations.user_id <>' => $companySettings['owner_id'],
                                                    'Integrations.company_id' => $companySettings['id'],
                                                    'Integrations.type <>' => Integration::INTEGRATION_TYPE_CUSTOM,
                                        ])
                                        ->count();

                    $closedIntegBoxes = json_decode($user['closed_info_boxes'], true);
                    $box942013Closed = in_array(942013, $closedIntegBoxes) ? true : false;
                    $temVar = true;
                    if (!$countEmployeeIntegrations && !$box942013Closed) {
                        $temVar = true;
                    } elseif (!$box942013Closed && $countEmployeeIntegrations) {
                        $temVar = false;
                    } elseif ($box942013Closed && !$countEmployeeIntegrations) {
                        $temVar = true;
                    }
                    $box942013Closed = $temVar;
                }
            }
        }
        return $box942013Closed;
    }
    /**
     * [get Conversion Popup for dashboard]
     * @param  [array] $user [$this->Auth->user()]
     * @return [string]       [pop up name]
     */
    public function getConversionPopup($user)
    {
        $popupName = '';
        if ($user['conversion_popup'] && $user['conversion_popup'] != User::CONVERSION_POPUP_PAYING) {
            switch ($user['conversion_popup']) {
                case User::CONVERSION_POPUP_UPSELL:
                    $popupName = 'upsellLightbox';
                    break;
                case User::CONVERSION_POPUP_THANKS:
                    $popupName = 'thanksLightbox';
                    break;
                case User::CONVERSION_POPUP_SURVEY:
                    $popupName = 'downgradeLightbox';
                    break;
            }

            $enUser = $this->get($user['id']);
            if (!empty($enUser)) {
                $enUser->conversion_popup = null;
                $this->save($enUser);
            //    $this->reSyncIndividualUserInfo(['conversion_popup' => null]);
            }
        }
        return $popupName;
    }

    /**
     * Is the existing password already in use?
     * @param string $email
     * @param string $password
     * @return boolean
     */
    public function isRepeatPassword($email, $password) {

        if( 
            $this->findActiveUserByEmailAndPassword($email, $password) || 
            $this->findActiveUserByEmailAndOldPassword($email, $password)
        ) {

            return true;
        }

        return false;
    }

    /*
     [validate Reset Password Form description]
     * @param  [Entity\User] $user [description]
     * @param  [array] $data [post data]
     * @return [type]       [description]
     */
    public function validateResetPasswordForm($user, $data)
    {
        if (empty($user)) {
            // return $this->redirect(['action' => 'login'])
            return [
               'status' => RESPONSE_STATUS_FAILED,
               'redirect' => true,
               'flashMessage' => 'Invalid link',
               'redirectUrl' => ['action' => 'login']
            ];
        }
        // is locked user
        if ($user->status == User::STATUS_LOCK) {
            // $this->Flash->error('Your account was locked, please contact support@trinetcloud.com.', 'auth');
            return [
               'status' => RESPONSE_STATUS_FAILED,
               'redirect' => true,
               'flashMessage' => 'Your account was locked, please contact support@trinetcloud.com.',
               'redirectUrl' => ['action' => 'login']
            ];
            // return $this->redirect(['action' => 'login']);
        }

        // check validate input
        $checkValid = $this->validationResetPassword([
                    'email' => $user->email,
                    'password' => $data['password'],
                    'confirm_password' => $data['confirm_password']
                ]);
        // success
        if ($checkValid['status'] == RESPONSE_STATUS_SUCCESS) {
            return [
               'status' => RESPONSE_STATUS_SUCCESS
            ];
        }
        // fail validate
        $errors = $checkValid['flashMessage'];
        $flashMessage = implode('<br/>', $errors);
        return [
                   'status' => RESPONSE_STATUS_FAILED,
                   'redirect' => false,
                   'flashMessage' => $flashMessage
                ];
    }
    /**
     * [validateResetPasswordLink description]
     * @param  [string] $email    [description]
     * @param  [string] $pass [description]
     * @return [array]        [
                       'status' => RESPONSE_STATUS_FAILED,
                       'redirect' => true,
                       'flashMessage' => '',
                       'redirectUrl' => ['action' => 'login']
                    ];
     */
    public function validateResetPasswordLink($email, $pass)
    {
        // validate when click link
        // Example link : /users/reset_password/cmVnaXN0ZXJfZnJvbWFhNjU2NUB0cmluZXRxYS5jb20/09b5555576b8dc17cf6480debca54acfc8265a99b38c3a2dc08571d37dd4c0ca
        if (!empty($email) && !empty($pass)) {
            $email = base64_decode(strtr($email, '-_', '+/'));
            $email = Utils::sanitizeText($email, ['&', ';', '#', '+']);

            $user = $this->findByEmail($email)->first();

            if (empty($user)) {
                return [
                       'status' => RESPONSE_STATUS_FAILED,
                       'redirect' => true,
                       'flashMessage' => '',
                       'redirectUrl' => ['action' => 'login']
                    ];
                //return $this->redirect(['action' => 'login']);
            }
            
            $resetedPassword = $this->generateResetPaswordCode($user->password);

            if ($resetedPassword != $pass) {
                return [
                       'status' => RESPONSE_STATUS_FAILED,
                       'redirect' => true,
                       'flashMessage' => 'Your action is invalid. Please try it again.',
                       'redirectUrl' => ['action' => 'login']
                ];
            }
            return [
                       'status' => RESPONSE_STATUS_SUCCESS,
                       'email' => $email
                   ];
        }

        return [
               'status' => RESPONSE_STATUS_FAILED,
               'redirect' => true,
               'flashMessage' => 'Invalid link',
               'redirectUrl' => ['action' => 'login']
            ];
    }
    /**
     * check if string contain numberic
     * @param  [string]  $value string password
     * @return bool true if have number
     */
    public function containsNumberic($value)
    {
        $containsDigit = preg_match('/\d/', $value); // At leats 1 digit
        if ($containsDigit == 1) {
                return true;
        }

        return false;
    }
    /**
     * user can set up bookkeeper
     * @param  array $user is $this->Auth->user()
     * @return [type]       [description]
     */
    public function canSetupBookkeeper($user)
    {
        $this->objectUtils->useTables($this, ['Companies']);
        $user = $this->findById($user['id'])
                     ->contain(['Companies'])
                     ->first();
        // empty user ==> false
        if (empty($user)) {
            return false;
        }
        // is personal -> true
        if ($user->company_id == Company::EXPENSECLOUD_ID) {
            return true;
        }
        // not user role (is admin, admintime, bookkeeper, bookkeeper time) -> true
        if ($user->role != User::ROLE_USER) {
            return true;
        }
        // if is company owner -> true
        if ($user->id == $user->company->owner_id) {
            return true;
        }
        // user and allow_user_setup_bookkeeper = no --> false
        if ($user->company->allow_user_setup_bookkeeper == Company::ALLOW_USER_SETUP_BOOKKEEPER_NO) {
            return false;
        }

        return true;
    }
}
