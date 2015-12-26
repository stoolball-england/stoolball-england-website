<?php
require_once ('data/data-edit-control.class.php');
require_once ('data/related-id-editor.class.php');
require_once("authentication/role.class.php");

class PersonEditControl extends DataEditControl
{
	/**
	 * Aggregated editor for security roles
	 *
	 * @var RelatedIdEditor
	 */
	private $roles_editor;

	/**
	 * Creates a new PersonEditControl
	 *
	 * @param SiteSettings $o_settings
	 */
	public function __construct(SiteSettings $o_settings)
	{
		# set up element
		$this->SetDataObjectClass('User');
		parent::__construct($o_settings);
		$this->roles_editor = new RelatedIdEditor($this->GetSettings(), $this, "roles", "Roles", array("Role"), "Role", false, "getRoleId", "setRoleId", "setRoleName");
	}

    /**
     * Sets the security roles to which the user could belong
     * @param $roles Role[]
     */
    public function SetRoles($roles) 
    {
        $this->roles_editor->SetPossibleDataObjects($roles);
    }
    
	/**
	 * @return void
	 * @desc Re-build from data posted by this control the data object this control
	 * is editing
	 */
	function BuildPostedDataObject()
	{
		$user = new User();

		$user->SetId($_POST['item']);
		$user->SetFirstName($_POST['name_first']);
		$user->SetLastName($_POST['name_last']);
		$user->SetName($_POST['known_as']);
		$user->SetEmail($_POST['email']);
        $user->SetAccountDisabled(isset($_POST['disabled']));

        $roles = $this->roles_editor->DataObjects()->GetItems();
        foreach ($roles as $role) 
        {
            $user->Roles()->Add($role);
        }
		$this->SetDataObject($user);
	}

	function CreateControls()
	{
		require_once ('xhtml/forms/form-part.class.php');
		require_once ('xhtml/forms/checkbox.class.php');
		require_once ('xhtml/forms/textbox.class.php');

        $this->AddCssClass('legacy-form');
		
		$user = $this->GetDataObject();

		# add id
		$id_box = new TextBox('item', (string)$user->GetId());
		$id_box->SetMode(TextBoxMode::Hidden());
		$this->AddControl($id_box);

		# add name
		$name_box = new TextBox('known_as', $user->GetName(), $this->IsValidSubmit());
		$name_box->AddAttribute('maxlength', 210);
		$name = new FormPart('Nickname', $name_box);
		$this->AddControl($name);

		# add first name
		$fname_box = new TextBox('name_first', $user->GetFirstName(), $this->IsValidSubmit());
		$fname_box->AddAttribute('maxlength', 100);
		$fname = new FormPart('First name', $fname_box);
		$this->AddControl($fname);

		# add last name
		$lname_box = new TextBox('name_last', $user->GetLastName(), $this->IsValidSubmit());
		$lname_box->AddAttribute('maxlength', 100);
		$lname = new FormPart('Last name', $lname_box);
		$this->AddControl($lname);

		# add email
		$email_box = new TextBox('email', $user->GetEmail(), $this->IsValidSubmit());
		$email_box->AddAttribute('maxlength', 100);
		$email = new FormPart('Email', $email_box);
		$this->AddControl($email);

        # Is account disabled?
        $this->AddControl(new CheckBox("disabled", "Account disabled", 1, $user->GetAccountDisabled(), $this->IsValidSubmit()));
        
		# Permissions
		if (!$this->IsPostback() or $this->IsValidSubmit())
		{
			foreach ($user->Roles() as $role)
			{
				/* @var $role Role */
				$this->roles_editor->DataObjects()->Add($role);
			}
		}
		$this->AddControl(new FormPart("Roles", $this->roles_editor));
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	function CreateValidators()
	{
		if (!$this->IsInternalPostback())
		{
			require_once ('data/validation/required-field-validator.class.php');
			require_once ('data/validation/plain-text-validator.class.php');
			require_once ('data/validation/length-validator.class.php');
			require_once ('data/validation/email-validator.class.php');

			$this->a_validators[] = new RequiredFieldValidator('known_as', 'Please add the person\'s nickname');
			$this->a_validators[] = new PlainTextValidator('known_as', 'Please use only letters, numbers and simple punctuation in the nickname');
			$this->a_validators[] = new LengthValidator('known_as', 'Please make the nickname shorter', 0, 210);
			$this->a_validators[] = new PlainTextValidator('name_first', 'Please use only letters, numbers and simple punctuation in the first name');
			$this->a_validators[] = new LengthValidator('name_first', 'Please make the first name shorter', 0, 100);
			$this->a_validators[] = new PlainTextValidator('name_last', 'Please use only letters, numbers and simple punctuation in the last name');
			$this->a_validators[] = new LengthValidator('name_last', 'Please make the last name shorter', 0, 100);
			$this->a_validators[] = new EmailValidator('email', 'Please enter a valid email address');
			$this->a_validators[] = new PlainTextValidator('email', 'Please use only letters, numbers and simple punctuation in the email address');
			$this->a_validators[] = new LengthValidator('email', 'Please make the email address shorter', 0, 100);
		}

		$a_aggregated_validators = array();
		$a_aggregated_validators = $this->roles_editor->GetValidators();
		$this->a_validators = array_merge($this->a_validators, $a_aggregated_validators);
	}

}
?>