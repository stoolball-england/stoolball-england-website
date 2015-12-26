<?php
ini_set('include_path', ini_get('include_path') . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT'] . '/../classes/');

require_once('page/stoolball-page.class.php');
require_once('authentication/reset-password-form.class.php');

class CurrentPage extends StoolballPage
{
    private $error;
    private $form;
    private $valid_token;
    private $user_to_reset;
    private $saved;

    public function OnPageInit()
    {
        $this->form = new ResetPasswordForm($this->GetSettings(), AuthenticationManager::GetUser());
        $this->RegisterControlForValidation($this->form);
        
        $this->valid_token = isset($_GET['request']) and preg_match('/^[A-Z0-9]{8}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{12}$/', $_GET['request']);
    }

    /**
     * Checks the requested token is valid in the repository
     */
    private function CheckToken() {

        if ($this->valid_token) {
            $auth = $this->GetAuthenticationManager();
            $this->user_to_reset = $auth->ReadUserByPasswordResetToken($_GET['request']);
            if (!$this->user_to_reset) {
                $this->valid_token = false;
            }
        }

        if (!$this->valid_token) {
            http_response_code(400);
        }
    }

    public function OnLoadPageData() {
            
        if (!$this->IsPostback()) {
          $this->CheckToken();  
        }             
    }

    public function OnPostback()
    {
        $this->CheckToken();
        
        if ($this->valid_token and $this->IsValid())
        {
            /* @var $user_with_password User */
            $user_with_password = $this->form->GetDataObject();
            $user_with_password->SetId($this->user_to_reset->GetId());

            if ($user_with_password->GetId() and $user_with_password->GetRequestedPassword())
            {
                $auth = $this->GetAuthenticationManager();
                if (!$auth->SavePassword($user_with_password))
                {
                    $this->error = new XhtmlElement('p', 'Your password could not be changed.', 'validationSummary');
                    return;
                }
                $this->saved = true;
            }
        }
    }

    function OnPrePageLoad()
    {
        $for = '';
        if ($this->valid_token) $for = ' for ' . $this->user_to_reset->GetName();
        
        $this->SetPageTitle("Reset password$for");
        $this->SetContentConstraint($this->ConstrainText());
    }

    function OnPageLoad()
    {
        echo new XhtmlElement('h1', Html::Encode($this->GetPageTitle()));

        if (!is_null($this->error))
        {
            echo $this->error;
        }
        
        if (!$this->valid_token) {
            
            echo "<p>Sorry, this password reset link isn't valid.</p><p>Reset links expire 24 hours after you request them.</p>"; 
            
        } else if (isset($this->saved) and $this->saved) {
            echo '<p>Your password has been reset. Please <a href="' . Html::Encode($this->GetSettings()->GetFolder('Account')) . '">sign in</a>.</p>';
        } 
        
        else {

            echo $this->form;
        }
    }

}
new CurrentPage(new StoolballSettings(), PermissionType::ViewPage(), false);
?>