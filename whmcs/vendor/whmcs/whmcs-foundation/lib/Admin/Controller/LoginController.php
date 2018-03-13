<?php 
namespace WHMCS\Admin\Controller;


class LoginController
{
    use \WHMCS\Application\Support\Controller\DelegationTrait;

    public function viewLoginForm(\WHMCS\Http\Message\ServerRequest $request)
    {
        ob_start();
        $response = $this->loginPhp($request);
        $content = ob_get_clean();
        if( $response instanceof \Psr\Http\Message\ResponseInterface ) 
        {
            return $response;
        }

        return (new \WHMCS\Admin\ApplicationSupport\View\Html\ContentWrapper())->setBodyContent($content);
    }

    protected function loginPhp(\WHMCS\Http\Message\ServerRequest $request)
    {
        $adminPasswordResetDisabled = (bool) \WHMCS\Config\Setting::getValue("DisableAdminPWReset");
        $action = $request->get("action");
        $sub = $request->get("sub");
        $incorrect = $request->get("incorrect");
        $redirectUri = $request->get("redirect");
        $logout = $request->get("logout");
        $email = $request->get("email");
        $useBackupCode = $request->get("backupcode");
        $doConnectionTest = $request->get("conntest");
        $verificationToken = $request->get("verify");
        if( $doConnectionTest ) 
        {
            $whmcsurl = "https://licensing28.whmcs.com/license/test.php";
            $postfields = array( "curltest" => "1" );
            $ip = gethostbyname("licensing28.whmcs.com");
            echo "<font style=\"font-size:18px;\">";
            echo "Testing Connection to '" . $whmcsurl . "'...<br />";
            echo "URL resolves to " . $ip . " ... ";
            if( $ip != "184.94.192.9" && $ip != "208.74.124.169" ) 
            {
                echo "Error" . "<br /><font style=\"color:#cc0000;\">" . "The IP whmcs.com is resolving to the wrong IP. " . "Someone on your server appears to be trying to bypass licensing. " . "Please contact your web hosting provider or server administrator to resolve." . "</font>";
            }
            else
            {
                echo "Ok";
            }

            echo "<br />";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $whmcsurl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postfields));
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            $data = curl_exec($ch);
            $responsecode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            echo "Response Code: " . $responsecode . "<br />";
            if( curl_error($ch) ) 
            {
                echo "Curl Error: " . curl_error($ch) . "<br />";
            }
            else
            {
                if( !$data ) 
                {
                    echo "Empty Data Response - Please check CURL Installation<br />";
                }

            }

            curl_close($ch);
            $decoded = json_decode($data, true);
            if( array_key_exists("status", $decoded) && $decoded["status"] == "ok" ) 
            {
                echo "Connection Successful!";
            }
            else
            {
                echo "Connection Failed!" . "<br /><br />Raw Output:<br />" . "<textarea rows=\"20\" cols=\"120\">" . $data . "</textarea>";
            }

            exit();
        }

        $result = select_query("tblconfiguration", "COUNT(*)", array( "setting" => "License" ));
        $data = mysql_fetch_array($result);
        if( !$data[0] ) 
        {
            insert_query("tblconfiguration", array( "setting" => "License" ));
        }

        $licensing = \DI::make("license");
        $licensing->remoteCheck();
        if( $licensing->getStatus() != "Active" ) 
        {
            redir("licenseerror=" . $licensing->getStatus(), \WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
        }

        if( !$licensing->checkOwnedUpdates() ) 
        {
            redir("licenseerror=version", \WHMCS\Utility\Environment\WebHelper::getAdminBaseUrl() . "/licenseerror.php");
        }

        if( \WHMCS\Session::get("adminid") && !\WHMCS\Session::get("2fabackupcodenew") ) 
        {
            return $this->redirectTo("admin-homepage", $request);
        }

        $whmcs = \App::self();
        $adminfolder = $whmcs->get_admin_folder_name();
        if( !$whmcs->in_ssl() && $whmcs->isSSLAvailable() ) 
        {
            $whmcs->redirectSystemURL($whmcs->get_admin_folder_name() . "/" . $whmcs->getCurrentFilename(false));
        }

        if( $action && $adminPasswordResetDisabled ) 
        {
            redir();
        }

        $templatevars = array( "step" => "login", "displayTitle" => "Login", "infoMsg" => "", "successMsg" => "", "errorMsg" => "", "redirectUri" => $redirectUri );
        if( !$action ) 
        {
            if( \WHMCS\Session::get("2faverify") ) 
            {
                if( \WHMCS\Session::get("2fabackupcodenew") ) 
                {
                    $templatevars["infoMsg"] = "Backup Codes are valid once only. It will now be reset.";
                }
                else
                {
                    if( $incorrect ) 
                    {
                        $templatevars["errorMsg"] = "<strong>Second factor invalid.</strong> Please try again.";
                    }
                    else
                    {
                        $templatevars["infoMsg"] = "Your second factor is required to complete the login.";
                    }

                }

            }
            else
            {
                if( $incorrect ) 
                {
                    $templatevars["errorMsg"] = "<strong>Login Failed.</strong> Please Try Again.";
                }
                else
                {
                    if( $logout ) 
                    {
                        $templatevars["displayTitle"] = "Logout";
                        $templatevars["successMsg"] = "You have been successfully logged out.";
                    }

                }

            }

            if( \WHMCS\Session::get("2fabackupcodenew") ) 
            {
                $templatevars["step"] = "twofabackupcode";
                $twofa = new \WHMCS\TwoFactorAuthentication();
                if( $twofa->setAdminID(\WHMCS\Session::get("2faadminid")) ) 
                {
                    $templatevars["successMsg"] = "Your New Backup Code is<br /><strong>" . $twofa->generateNewBackupCode() . "</strong>";
                }
                else
                {
                    $templatevars["errorMsg"] = "An error occurred. Please try again.";
                }

            }
            else
            {
                if( \WHMCS\Session::get("2faverify") ) 
                {
                    $twofa = new \WHMCS\TwoFactorAuthentication();
                    if( $twofa->setAdminID(\WHMCS\Session::get("2faadminid")) ) 
                    {
                        if( !$twofa->isActiveAdmins() || !$twofa->isEnabled() ) 
                        {
                            \WHMCS\Session::destroy();
                            redir();
                        }

                        if( $useBackupCode ) 
                        {
                            $templatevars["step"] = "twofabackupcode";
                        }
                        else
                        {
                            $templatevars["step"] = "twofa";
                            $challenge = $twofa->moduleCall("challenge");
                            if( $challenge ) 
                            {
                                $challenge = str_replace("</form>", "<input type=\"hidden\" name=\"redirect\" value=\"" . $redirectUri . "\"></form>", $challenge);
                                $templatevars["challengeHtml"] = $challenge;
                            }
                            else
                            {
                                $templatevars["errorMsg"] = "Bad 2 Factor Auth Module. Please contact support.";
                            }

                        }

                    }
                    else
                    {
                        $templateVars["errorMsg"] = "An error occurred. Please try again.";
                    }

                }

            }

        }
        else
        {
            if( $action == "reset" ) 
            {
                $templatevars["step"] = "reset";
                $templatevars["displayTitle"] = "Reset Password";
                if( $verificationToken ) 
                {
                    $admin = \WHMCS\User\Admin::wherePasswordResetKey($verificationToken)->whereDisabled(0)->first();
                    if( $admin ) 
                    {
                        if( \Carbon\Carbon::now()->timestamp - \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $admin->passwordResetExpiry)->timestamp <= 0 ) 
                        {
                            $hasher = new \WHMCS\Security\Hash\Password();
                            if( $hasher->verify($admin->passwordResetData . $admin->id . $admin->email, base64_decode($verificationToken)) ) 
                            {
                                if( $sub == "newPassword" ) 
                                {
                                    $auth = new \WHMCS\Auth();
                                    $auth->getInfobyID($admin->id);
                                    $newPassword = $request->get("password");
                                    try
                                    {
                                        $admin->passwordResetKey = "";
                                        $admin->passwordResetData = "";
                                        $admin->passwordResetExpiry = "0000-00-00 00:00:00";
                                        if( $auth->generateNewPasswordHashAndStore($newPassword) && $auth->generateNewPasswordHashAndStoreForApi(md5($newPassword)) ) 
                                        {
                                            $admin->loginAttempts = 0;
                                            $templatevars["successMsg"] = "<strong>Success!</strong> Please login with your new password.";
                                            logActivity("Password Reset Completed for Admin Username " . $admin->username);
                                            $extraParams = array( "firstname" => $admin->firstName, "username" => $admin->username );
                                            $mailer = \WHMCS\Mail\Entity\Admin::factoryByTemplate("Admin Password Reset Confirmation");
                                            $mailer->determineAdminRecipientsAndSender("", 0, $admin->id, false);
                                            foreach( $extraParams as $extraParam => $value ) 
                                            {
                                                $mailer->assign($extraParam, $value);
                                            }
                                            $mailer->send();
                                            $remoteIp = \WHMCS\Utility\Environment\CurrentUser::getIP();
                                            $date = fromMySQLDate(\Carbon\Carbon::now()->toDateTimeString(), true);
                                            $hostname = gethostbyaddr($remoteIp);
                                            sendAdminNotification("system", "Admin Password Reset Completed", "                <p>This is a notification that an admin password reset has been performed by the following user.</p>\n<p>Username: " . $admin->username . "<br />Date/Time: " . $date . "<br />Hostname " . $hostname . "<br />IP Address: " . $remoteIp . "</p>");
                                        }

                                    }
                                    catch( \WHMCS\Exception\Mail\SendFailure $e ) 
                                    {
                                        $templatevars["errorMsg"] = "There was an error sending the confirmation email.";
                                    }
                                    catch( \WHMCS\Exception\Mail\SendHookAbort $e ) 
                                    {
                                    }
                                    catch( \Exception $e ) 
                                    {
                                        $templatevars["errorMsg"] = $e->getMessage();
                                    }
                                    finally 
                                    {
                                        $admin->save();
                                    }
                                }
                                else
                                {
                                    $templatevars["step"] = "reset";
                                    $templatevars["verify"] = $verificationToken;
                                }

                            }

                        }
                        else
                        {
                            $admin->passwordResetExpiry = "0000-00-00 00:00:00";
                            $admin->passwordResetData = "";
                            $admin->passwordResetKey = "";
                            $admin->save();
                            logActivity("Expired Admin Password Reset Link Followed.");
                            $templatevars["errorMsg"] = "Expired Link Followed. Please try again.";
                        }

                    }
                    else
                    {
                        logActivity("Invalid Admin Password Reset Link Followed.");
                        $templatevars["errorMsg"] = "Invalid Link Followed. Please try again.";
                    }

                }
                else
                {
                    if( $sub == "send" ) 
                    {
                        $admin = \WHMCS\User\Admin::where("email", "=", $email)->orWhere("username", "=", $email)->first();
                        if( $admin && $admin->disabled == 1 ) 
                        {
                            $templatevars["errorMsg"] = "<strong>Administrator Disabled</strong>";
                        }
                        else
                        {
                            if( !$admin || $email != $admin->email && $email != $admin->username ) 
                            {
                                logActivity("Admin Password Reset Attempted for invalid Email: " . $email);
                                $templatevars["errorMsg"] = "<strong>User or Email Address Not Found.</strong> Your IP has been logged.";
                            }
                            else
                            {
                                $hasher = new \WHMCS\Security\Hash\Password();
                                $randomString = genRandomVal(mt_rand(20, 40));
                                $verificationToken = base64_encode($hasher->hash($randomString . $admin->id . $admin->email));
                                $admin->passwordResetKey = $verificationToken;
                                $admin->passwordResetData = $randomString;
                                $admin->passwordResetExpiry = \Carbon\Carbon::now()->addHours(2)->toDateTimeString();
                                $admin->save();
                                $url = \App::getSystemURL() . $adminfolder . "/login.php?action=reset&verify=" . $verificationToken;
                                try
                                {
                                    $extraParams = array( "firstname" => $admin->firstName, "username" => $admin->lastName, "pw_reset_url" => $url );
                                    $mailer = \WHMCS\Mail\Entity\Admin::factoryByTemplate("Admin Password Reset Validation");
                                    $mailer->determineAdminRecipientsAndSender("", 0, $admin->id, false);
                                    foreach( $extraParams as $extraParam => $value ) 
                                    {
                                        $mailer->assign($extraParam, $value);
                                    }
                                    $mailer->send();
                                    $templatevars["errorMsg"] = "<strong>Success!</strong> Please check your email for the next step...";
                                    logActivity("Password Reset Initiated for Admin Username " . $admin->username);
                                }
                                catch( \WHMCS\Exception\Mail\SendFailure $e ) 
                                {
                                    $templatevars["errorMsg"] = "There was an error sending the email. Please try again.";
                                }
                                catch( \WHMCS\Exception\Mail\SendHookAbort $e ) 
                                {
                                    $templatevars["errorMsg"] = "This email cannot be sent. Please contact admin for support.";
                                }
                            }

                        }

                    }
                    else
                    {
                        $templatevars["infoMsg"] = "Enter your email address below to begin the process...";
                    }

                }

            }

        }

        $templatevars["showSSLLink"] = \App::isSSLAvailable();
        $templatevars["showPasswordResetLink"] = (bool) (!$adminPasswordResetDisabled);
        $templatevars["languages"] = \WHMCS\Language\AdminLanguage::getLanguages();
        $assetHelper = \DI::make("asset");
        $templatevars["WEB_ROOT"] = $assetHelper->getWebRoot();
        $templatevars["BASE_PATH_CSS"] = $assetHelper->getCssPath();
        $templatevars["BASE_PATH_JS"] = $assetHelper->getJsPath();
        $templatevars["BASE_PATH_FONTS"] = $assetHelper->getFontsPath();
        $templatevars["BASE_PATH_IMG"] = $assetHelper->getImgPath();
        $smarty = new \WHMCS\Smarty(true);
        foreach( $templatevars as $key => $value ) 
        {
            $smarty->assign($key, $value);
        }
        echo $smarty->fetch("login.tpl");
        return null;
    }

}


