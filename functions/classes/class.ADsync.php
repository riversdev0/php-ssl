<?php

/**
 * AD usersync class
 */
class ADsync
{

    /**
     * AD server details
     * @var array
     */
    public $server = false;

    /**
     * AD connection
     * @var string
     */
    private $provider = false;

    /**
     * ADLdap search
     * @var array
     */
    private $search = false;

    /**
     * Databse object / resource
     * @var bool
     */
    private $Database = false;

    /**
     * Constructor
     * @method __construct
     * @param  Database_PDO $database
     */
    public function __construct(Database_PDO $database, object $ad_server)
    {
        // db
        $this->Database = $database;
        // fetch AD server
        $this->connect_ad_server($ad_server);
    }

    /**
     * Fetch all AD servers from database and store autocreate server indexes array
     * @method fetch_ad_servers
     * @return void
     */
    private function connect_ad_server(object $ad_server)
    {
        // loop and check for AD
        if ($ad_server !== false) {
            // save server
            $this->server = $ad_server;
            // set config
            $config = [
                "base_dn" => $ad_server->base_dn,
                "hosts" => explode(";", $ad_server->domain_controllers),
                "use_ssl" => (bool)$ad_server->use_ssl,
                "use_tls" => (bool)$ad_server->use_tls,
                "username" => $ad_server->adminUsername,
                "password" => $ad_server->adminPassword,
                "account_suffix" => $ad_server->account_suffix
            ];

            # adLDAP script
            require(dirname(__FILE__) . "/assets/composer/vendor/autoload.php");

            # open connection
            try {
                // Initialize adLDAP
                $ad = new \Adldap\Adldap();
                // add provider
                $ad->addProvider($config);
                // connect
                $this->provider = $ad->connect();
            }
            catch (\Adldap\Auth\BindException $e) {
                die($e->getMessage());
            }
            catch (\Adldap\Configuration\ConfigurationException $e) {
                die($e->getMessage());
            }
        }
    }

    /**
     * Init search
     * @method init_search
     * @return [type]
     */
    public function init_search()
    {
        // init
        $this->search = $this->provider->search();
    }

    /**
     * Sets parameters for result
     * @method set_search_params
     * @param  array $params
     */
    public function set_search_result_params($params = "")
    {
        if (sizeof($params) > 0) {
            $this->search->select($params);
        }
    }

    /**
     * Try to authenticate user to AD wiih specifiec credentials
     * @method ad_user_authenticate
     * @param  string $username
     * @param  string $password
     * @return bool
     */
    public function ad_user_authenticate($username = "", $password = "")
    {
        return $this->provider->auth()->attempt($username, $password);
    }

    /**
     * Get user info from AD
     * @method ad_user_info
     * @param  string $username
     * @return array
     */
    public function ad_user_info($username = "")
    {
        $user = $this->search->select(['cn', 'samaccountname', 'mobile', 'mail', 'memberof'])->findBy('samaccountname', $username);
        // check result
        if ($user instanceof \Adldap\Models\User) {
            return $user->getAttributes();
        }
        else {
            return false;
        }
    }

    /**
     * Get user info from AD by email
     * @method ad_user_info
     * @param  string $username
     * @return array
     */
    public function ad_user_info_by_email($email = "")
    {
        // init search
        $this->init_search();
        // search
        $user = $this->search->select(['cn', 'samaccountname', 'mobile', 'mail', 'memberof'])->findBy('mail', $email);
        // check result
        if ($user instanceof \Adldap\Models\User) {
            $useratt = $user->getAttributes();
            return @$useratt['samaccountname'][0];
        }
        else {
            return false;
        }
    }

    /**
     * Get user info from AD
     * @method ad_user_info
     * @param  string $username
     * @return array
     */
    public function ad_user_photo($username = "")
    {
        $user = $this->search->select(['thumbnailphoto', 'mobile'])->findBy('samaccountname', $username);
        // check result
        if ($user instanceof \Adldap\Models\User) {
            return $user->getAttributes();
        }
        else {
            return false;
        }
    }

    /**
     * Get user info from AD - full DN
     * @method ad_user_info_by_dn
     * @param  string $username
     * @return array
     */
    public function ad_user_info_by_dn($dn)
    {
        $user = $this->search->select(['cn', 'samaccountname', 'mobile', 'mail'])->findByDn($dn);
        // check result
        if ($user instanceof \Adldap\Models\User) {
            return $user->getAttributes();
        }
        else {
            return false;
        }
    }

    /**
     * Get AD members from AD group and return DN
     *
     * @method ad_group_get_members
     * @param  string $groupname
     * @return array|false
     */
    public function ad_group_get_members($groupname = "")
    {
        // search
        $group = $this->search->groups()->find($groupname);
        // check result
        if ($group instanceof \Adldap\Models\Group) {
            return $group->getAttributes();
        }
        else {
            return false;
        }
    }


    /**
     * Autocreate AD user
     * @method user_autocreate
     * @param  string $username
     * @return bool
     */
    public function create_pw_user($username)
    {
        // set var
        $this->user_tmp = [];
        // get info
        $userinfo = $this->ad_user_info($username);
        // check and create values
        if (isset($userinfo['samaccountname'])) {
            $user = [
                "username" => strtolower($userinfo['samaccountname'][0]),
                "email" => strtolower($userinfo['mail'][0]),
                "name" => $userinfo['cn'][0],
                "last_tel" => $userinfo['mobile'][0],
                "created" => date("Y-m-d H:i:s")
            ];
        }
        else {
            return false;
        }

        // save, needed gor UI
        $this->user_tmp = $user;

        // create
        try {
            $this->Database->create_object("users", $user);
        }
        catch (Exception $e) {
            //die($e->getMessage());
            return false;
        }

        // save id
        $this->user_tmp['id'] = $this->Database->lastInsertId();

        // ok
        return true;
    }
}