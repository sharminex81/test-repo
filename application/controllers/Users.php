<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends CI_Controller
{
    /**
     * Users constructor.
     */
    function __construct()
    {
        parent::__construct();

        /*$user = $this->session->userdata('details');

        if($user == null) {
            $message['error'] = 'Sorry! Access Denied. You don’t have permission to do.';
            $this->session->set_userdata($message);
            redirect('login','refresh');
        }

        $role = Roles::getName($user['user']->role_id);

        if($role->slug == 'general-user') {
            $message['error'] = 'Sorry! Access Denied. You don’t have permission to do.';
            $this->session->set_userdata($message);
            redirect('login','refresh');
        }*/


    }

    /**
     * It's default page for user controller
     */
    public function index()
    {
        $content['header'] = $this->load->view('common/header', '', true);
        $content['navbar'] = $this->load->view('common/navbar', '', true);
        $content['placeholder'] = $this->load->view('errors/is_permit', '', true);
        $content['footer'] = $this->load->view('common/footer', '', true);
        $this->load->view('dashboard/dashboard', $content);
    }

    /**
     * This is for user's add(user's add form).Firstly check the permission with each user's role
     * if that user is permitted to add a user then he/she would add a new user.
     */
    public function home()
    {
        Utilities::is_permit();
        $roles['roles'] = Roles::getRoles();
        $data['header'] = $this->load->view('common/header', '', true);
        $data['navbar'] = $this->load->view('common/navbar', '', true);
        $data['placeholder'] = $this->load->view('users/add', $roles, true);
        $data['footer'] = $this->load->view('common/footer', '', true);
        $this->load->view('dashboard/dashboard', $data);
    }

    /**
     * This is for user's add.Firstly check user's information with form input data
     * If form validation occurs error then data is not permitted to database insert
     */
    public function create()
    {
        Utilities::is_permit();
        //@TODO try to manage it with the best way
        /**
         * Form validation with valitron that is so easy
         */
        $formData = $_POST['user'];
        $validation = new Valitron\Validator($formData);
        $validation->rule('required', 'first_name')->message('First name is required');
        $validation->rule('required', 'last_name')->message('Last name is required');
        $validation->rule('required', 'email_address')->message('Email address is required');
        $validation->rule('required', 'role_id')->message('Role is required');
        $validation->rule('required', 'password')->message('Password is required');
        $validation->rule('required', 'confirm_password')->message('Confirm password is required');
        $validation->rule('lengthMin', 'password', 6);
        $validation->rule('email', 'email_address')->message('This is invalid email address');
        $userMail = UsersModel::getEmailAddress($formData['email_address']);
        $validation->rule('equals', 'password', 'confirm_password')->message('Password does not matched');

        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $formData['first_name'])) {
            $validation->addInstanceRule('firstName', function () {
                return false;
            });
            $validation->rule('firstName', 'first_name')->message('Alphabetic characters only');
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $formData['last_name'])) {
            $validation->addInstanceRule('lastName', function () {
                return false;
            });
            $validation->rule('lastName', 'last_name')->message('Alphabetic characters only');
        }

        if ($userMail == true) {
            $validation->addInstanceRule('uMail', function () {
                return false;
            });
            $validation->rule('uMail', 'email_address')->message('This email has already registered');
        }

        if (!$validation->validate()) {
            $error['error'] = $validation->errors();
            $oldValue['oldValue'] = $validation->data();
            $this->session->set_userdata($error);
            $this->session->set_userdata($oldValue);
            redirect('users');
        }

        try{
            $userID = UsersModel::addUser();
            $success = true;
        }catch (Exception $exception) {
            $exception->getMessage('This is an error');
            $success = false;
        }

        if($success == true) {
            $message['success'] = 'New user has been created successfully';
            $this->session->set_userdata($message);
            $user = UsersModel::userInfo($userID);
            redirect('users/details/' . $user->uuid . '/overview');
        }
    }

    /**
     * User's information
     * When a new user is created , redirect to his detail page
     */
    public function details()
    {
        $uuid = $this->uri->segment(3);
        $userDetails['details'] = UsersModel::userDetails($uuid);
        $userDetails['role'] = Roles::getName($userDetails['details']['user']->role_id);
        $data['header'] = $this->load->view('common/header', '', true);
        $data['navbar'] = $this->load->view('common/navbar', '', true);
        $data['placeholder'] = $this->load->view('users/details', $userDetails, true);
        $data['footer'] = $this->load->view('common/footer', '', true);
        $this->load->view('dashboard/dashboard', $data);
    }

    /**
     * This is for fetching all users.Firstly check user's role
     * if that user is permitted to see a user then he/she would add a new user.
     * Get all users
     */
    public function lists()
    {
        Utilities::is_permit();
        $users['users'] = UsersModel::getUsers();
        $content['header'] = $this->load->view('common/header', '', true);
        $content['navbar'] = $this->load->view('common/navbar', '', true);
        $content['placeholder'] = $this->load->view('users/list', $users, true);
        $content['footer'] = $this->load->view('common/footer', '', true);
        $this->load->view('dashboard/dashboard', $content);
    }

    /**
     * Just try pagination
     */
    public function testLists()
    {
        $config = array();
        $config["base_url"] = base_url() . "users/testLists";
        $config["total_rows"] = UsersModel::record_count();
        $config["per_page"] = 4;
        $config["uri_segment"] = 3;

        $this->pagination->initialize($config);

        $page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $data["users"] = UsersModel::getUsers1($config["per_page"], $page);
        $data["links"] = $this->pagination->create_links();
        $content['header'] = $this->load->view('common/header', '', true);
        $content['navbar'] = $this->load->view('common/navbar', '', true);
        $content['placeholder'] = $this->load->view('users/test_pagination', $data, true);
        $content['footer'] = $this->load->view('common/footer', '', true);
        $this->load->view('dashboard/dashboard', $content);
    }

    /**
     * Users update view part
     * User's update with timezone, country
     */
    public function update()
    {
        $uuid = $this->uri->segment(3);
        $userDetails['details'] = UsersModel::userDetails($uuid);
        $userDetails['timezones'] = Utilities::getTimezones();
        $userDetails['countries'] = Utilities::getCountries();
        $content['header'] = $this->load->view('common/header', '', true);
        $content['navbar'] = $this->load->view('common/navbar', '', true);
        $content['placeholder'] = $this->load->view('users/update', $userDetails, true);
        $content['footer'] = $this->load->view('common/footer', '', true);
        $this->load->view('dashboard/dashboard', $content);
    }

    /**
     * Users profile update post
     */
    public function profile()
    {
        //@TODO try to manage it with the best way
        /**
         * Form validation with valitron that is so easy
         */

        $uuid = $this->uri->segment(3);
        $userDetails['details'] = UsersModel::userDetails($uuid);
        $userDetails['timezones'] = Utilities::getTimezones();
        $userDetails['countries'] = Utilities::getCountries();
        $userDetails['roles'] = Roles::getRoles();
        $content['header'] = $this->load->view('common/header', '', true);
        $content['navbar'] = $this->load->view('common/navbar', '', true);
        $content['placeholder'] = $this->load->view('users/update', $userDetails, true);
        $content['footer'] = $this->load->view('common/footer', '', true);
        $this->load->view('dashboard/dashboard', $content);
        /*$formData = $_POST['profile'];
        $validation = new Valitron\Validator($formData);
        $validation->rule('required', 'first_name')->message('First name is required');
        $validation->rule('required', 'last_name')->message('Last name is required');
        $validation->rule('required', 'title')->message('Title is required');
        $validation->rule('required', 'gender')->message('Gender is required');


        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $formData['first_name'])) {
            $validation->addInstanceRule('firstName', function () {
                return false;
            });
            $validation->rule('firstName', 'first_name')->message('Alphabetic characters only');
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $formData['last_name'])) {
            $validation->addInstanceRule('lastName', function () {
                return false;
            });
            $validation->rule('lastName', 'last_name')->message('Alphabetic characters only');
        }


        if (!$validation->validate()) {
            $error['error'] = $validation->errors();
            $oldValue['oldValue'] = $validation->data();
            $this->session->set_userdata($error);
            $this->session->set_userdata($oldValue);
            redirect('users/update/' . $formData['user_uuid']);
        }

        try{
            UsersModel::updateProfile($formData['user_id']);
            $success = true;
        }catch (Exception $exception) {
            $exception->getMessage('This is an error');
            $success = false;
        }

        if($success == true) {
            $message['success'] = 'User has been updated successfully';
            $this->session->set_userdata($message);
            $user = UsersModel::userInfo($formData['user_id']);
            redirect('users/details/' . $user->uuid);
        }*/
    }

    /**
     * Users address update post
     */
    public function address()
    {
        var_dump($_POST); die();
    }

    /**
     * User's profile update post
     */
    public function profileInfo()
    {
        $formData = $_POST['profile'];
        $validation = new Valitron\Validator($formData);
        $validation->rule('required', 'first_name')->message('First name is required');
        $validation->rule('required', 'last_name')->message('Last name is required');
        $validation->rule('required', 'title')->message('Title is required');
        $validation->rule('required', 'gender')->message('Gender is required');


        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $formData['first_name'])) {
            $validation->addInstanceRule('firstName', function () {
                return false;
            });
            $validation->rule('firstName', 'first_name')->message('Alphabetic characters only');
        }

        if (!preg_match('/^[a-zA-Z][a-zA-Z ]*$/', $formData['last_name'])) {
            $validation->addInstanceRule('lastName', function () {
                return false;
            });
            $validation->rule('lastName', 'last_name')->message('Alphabetic characters only');
        }

        if ($formData['date_of_birth'] == date("Y-m-d") or $formData['date_of_birth'] > date('Y-m-d')) {
            $validation->addInstanceRule('dateOfBirth', function () {
               return false;
            });
            $validation->rule('dateOfBirth', 'date_of_birth')->message('Invalid birth date');
        }

        //var_dump($_SERVER['HTTP_REFERER']); die();


        if (!$validation->validate()) {
            $errors['errors'] = $validation->errors();
            $oldValue['oldValue'] = $validation->data();
            $this->session->set_userdata($errors);
            $this->session->set_userdata($oldValue);
            redirect($_SERVER['HTTP_REFERER']);
        } else {
            redirect('users/profile/' . $formData['user_uuid']);
        }

        /*try{
            UsersModel::updateProfile($formData['user_id']);
            $success = true;
        }catch (Exception $exception) {
            $exception->getMessage('This is an error');
            $success = false;
        }

        if($success == true) {
            $message['success'] = 'User has been updated successfully';
            $this->session->set_userdata($message);
            $user = UsersModel::userInfo($formData['user_id']);
            redirect('users/profile/' . $user->uuid);
        }
        $updateProfile = UsersModel::updateProfile($_POST['profile']['user_id']);
        redirect('users/profile/' . $_POST['profile']['user_uuid']);*/
    }

    /**
     * User's notes
     */
    public function notes()
    {
        $uuid = $this->uri->segment(3);
        $userDetails['details'] = UsersModel::userDetails($uuid);
        $userDetails['timezones'] = Utilities::getTimezones();
        $userDetails['countries'] = Utilities::getCountries();
        $content['header'] = $this->load->view('common/header', '', true);
        $content['navbar'] = $this->load->view('common/navbar', '', true);
        $content['placeholder'] = $this->load->view('users/notes', $userDetails, true);
        $content['footer'] = $this->load->view('common/footer', '', true);
        $this->load->view('dashboard/dashboard', $content);
    }
}