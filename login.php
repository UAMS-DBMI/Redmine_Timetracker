<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="X-UA-Compatible" content="IE=edge"/>
        <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
        <title>Redmine Time Tracker</title>
        <link href="public/stylesheets/login.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="public/javascripts/jquery-1.11.2.min.js"></script>
        <link href="public/bootstrap-3.3.4-dist/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="public/bootstrap-3.3.4-dist/js/bootstrap.min.js"></script>
    </head>
    <body>

        <?php

        //error_reporting(-1);
        //define(LDAP_OPT_DIAGNOSTIC_MESSAGE, 0x0032);
        session_start();

        if($_GET['action'] == "signout"){
            $_SESSION['login_error'] = NULL;
            $_SESSION['loggedin'] = "false";
            $_SESSION['username'] = NULL;
            $_SESSION['userid'] = NULL;
        }

        $return = "";
        if($_GET['return'] != NULL){
            $return = $_GET['return'];
        }

        if(isset($_POST['username']) && isset($_POST['password'])){

            // get username and password from form
            $username = $_POST['username'];
            $password = $_POST['password'];

            $test = "";

            function authenticate($user,$pass){

                // prevents guest account access
                if($pass == ""){
                    return false;
                }

                try{

                    $configs = include('config.php');

                    $ldap_host = $configs['ldapHost'];
                    $ldap_port = $configs['ldapPort'];
                    $ldap_binddn = $configs['ldapBinddn'];
                    $ldap_searchdn = $configs['ldapSearchdn'];
                    $ldap_pass = $configs['ldapPass'];

                    // call the ldap connect function
                    $ldap_db = ldap_connect($ldap_host, $ldap_port);

                    // bind the connection
                    $lookup_bind_success = ldap_bind($ldap_db, $ldap_binddn, $ldap_pass);

                    if($lookup_bind_success){

                        // search for dn of logged in user
                        $result = ldap_search($ldap_db, $ldap_searchdn, "sAMAccountName=$user");

                        // retrieve user dn for bind
                        $ldap_userdn = $result[0]["dn"];

                        $user_bind_success = ldap_bind($ldap_db, $ldap_userdn, $pass);

                        ldap_close($ldap_db);

                        if($user_bind_success){
                            // valid credentials
                            return true;
                        }
                        else{
                            // invalid credentials

                            /*
                            if (ldap_get_option($database, LDAP_OPT_DIAGNOSTIC_MESSAGE, $extended_error)) {
                                echo "Error Binding to LDAP: $extended_error";
                            } else {
                                echo "Error Binding to LDAP: No additional information is available.";
                            }
                            */
                            return false;
                        }
                    }
                    else{
                        ldap_close($ldap_db);
                        return false;
                    }
                }
                catch(Exception $e){

                    ldap_close($ldap_db);
                    return false;
                }
            }

            // call authenticate function
            if(authenticate($username,$password)){

                // authenticate successful
        
                // Check if user is valid Redmine user
                require_once 'db.php';
                $userid = RedmineDB::getInstance()->getUserIDFromUserName($username);
        
                if (empty($userid)){
                    session_start();
                    $_SESSION['login_error'] = "You are not a valid Redmine User";

                    // redirect
                    header("Location: login.php");
                }
                else{
                    // set session
                    session_start();
                    $_SESSION['login_error'] = NULL;  
                    $_SESSION['loggedin'] = "true";
                    $_SESSION['username'] = $username;
                    $_SESSION['userid'] = $userid;

                    // redirect
                    header("Location: index.php");
                }
            }
            else{
                // authenticate fails
                session_start();

                $_SESSION['login_error'] = "Invalid Userid and/or Password"; #$_SESSION['login_test'];

                // redirect
                header("Location: login.php");
            }
        }else{
        ?>
            <div class="wrapper">
            <form class="form-signin" action="" method="POST">
                <h2 class="form-signin-heading" style="text-align: center">Redmine Time Tracker</h2>
                <h5 style="text-align: center">Please provide your UAMS credentials.</h5>
                <input id="username" name="username" type="text" class="form-control" placeholder="Username" required="" autofocus="" />
                <br />
                <input id="password" name="password" type="password" class="form-control" placeholder="Password" required=""/>      
                <br />
                <?php 
                    session_start();

                    if ($_SESSION['login_error'] != NULL){
                        print "<div style='color:red;font-weight:bold'>".$_SESSION['login_error']."</div>";
                        print "<br />";
                    }
                } 
                ?> 
                <button class="btn btn-lg btn-primary btn-block" type="submit" name="submit" value="Submit">Login</button>   
            </form>
            </div>
    </body>
</html>