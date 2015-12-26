<?php
require_once('xhtml/xhtml-element.class.php');
require_once('xhtml/forms/form-part.class.php');

class XhtmlForm extends XhtmlElement
{
	var $a_validators;

	/**
	 * Cached result of $this.IsValid()
	 * @access protected
	 * @var bool
	 */
	var $b_valid;
	private $b_show_validation_errors = true;
	private $settings;
	private $data_connection;
    private $csrf_token;
    private $csrf_validator_added;

	/**
	 * @return XhtmlForm
	 * @desc Creates an XHTML form
	 */
	function XhtmlForm()
	{
		parent::XhtmlElement('form');
		$this->SetNavigateUrl(htmlentities($_SERVER['REQUEST_URI']));
		$this->AddAttribute('method', 'post');
        $this->csrf_token = isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : base64_encode(mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB)));
	}

	/**
	 * @return void
	 * @param string $s_url
	 * @desc Sets the page the form posts to
	 */
	function SetNavigateUrl($s_url)
	{
		$this->AddAttribute('action', (string)$s_url);
	}

	/**
	 * @return string
	 * @desc Gets the page the form posts to
	 */
	function GetNavigateUrl()
	{
		return $this->GetAttribute('action');
	}

	/**
	 * @return bool
	 * @desc Is this request apparently the result of a posted form
	 */
	function IsPostback()
	{
		return ($_SERVER['REQUEST_METHOD'] == 'POST');
	}

	/**
	 * Sets whether to show a summary of validation errors
	 *
	 * @param bool $b_show
	 */
	public function SetShowValidationErrors($b_show)
	{
		$this->b_show_validation_errors = (bool)$b_show;
	}

	/**
	 * Gets whether to show a summary of validation errors
	 *
	 * @return bool
	 */
	public function GetShowValidationErrors()
	{
		return $this->b_show_validation_errors;
	}

	/**
	 * @return void
	 * @desc Create DataValidator objects to validate the edit control
	 */
	public function CreateValidators()
	{
		# Method designed to be overriden
	}

	/**
	 * @return void
	 * @param DataValidator $o_valid
	 * @desc Adds a validator to the form.
	 */
	function AddValidator($o_valid)
	{
		if ($o_valid instanceof DataValidator)
		{
			if (!isset($this->a_validators)) $this->a_validators = array();
			$this->a_validators[] = $o_valid;
		}
	}

	/**
	 * @return DataValidator[]
	 * @desc Gets DataValidator objects to validate the edit control
	 */
	function &GetValidators()
	{
		if (!isset($this->a_validators))
		{
			$this->a_validators = array();
			$this->CreateValidators();
		}

		return $this->a_validators;
	}

	/**
	 * @return bool
	 * @desc Test whether all registered DataValidators are valid
	 */
	public function IsValid()
	{
		/* @var $o_validator DataValidator */

		# Reject requests for validation if request is not a postback
		if (strtoupper($this->GetAttribute("method")) == "POST" and !$this->IsPostback()) return true;

		# Create validators if not yet done
		if (!isset($this->a_validators))
		{
			$this->a_validators = array();
			$this->CreateValidators();
		}

		# Check for cached validation result
		if ($this->b_valid == null)
		{
			# Validate
			$b_valid = true;

			if (is_array($this->a_validators))
			{
		        # For POST forms, add CSRF validator where it will always be applied
		        # Exclude  when validation errors not shown as that's effectively an indication that this is part of a larger form, 
		        # and in that situation this validator breaks internal  postbacks
	            if (strtoupper($this->GetAttribute("method")) == "POST" and $this->IsPostback() and 
	                !$this->csrf_validator_added and
	                $this->b_show_validation_errors and 
	                (!isset($_POST['securitytoken']) or !isset($_SESSION['csrf_token']) or $_POST['securitytoken'] != $_SESSION['csrf_token'])
                    )
                {
                    require_once('data/validation/required-field-validator.class.php');
                    $fail = new RequiredFieldValidator(array("this_validator_will_fail"),"The security information for this form isn't correct. Try again, or let us know if there's a problem.");
                    $fail->SetValidIfNotFound(false);
                    $this->a_validators[] = $fail;
                    $this->csrf_validator_added = true; # Hack, because this validator was getting added twice
                }
                
        		foreach($this->a_validators as $o_validator)
				{
					if ($o_validator->RequiresSettings()) $o_validator->SetSiteSettings($this->settings);
					if ($o_validator->RequiresDataConnection()) $o_validator->SetDataConnection($this->data_connection);
					if (strtoupper($this->GetAttribute("method")) == "GET") $o_validator->a_data = $_GET;

					# Order of this line is vital. IsValid() must come first in the test to ensure it always runs,
					# rather than short circuiting when $b_valid is false. This is because it must run when the page is
					# first being validated and database resources are available, and cache its result. That way when
					# it is read again later by the validation summary it has the cached result and doesn't need to go
					# to the now unavailable database.
					$b_valid = ($o_validator->IsValid() and $b_valid);
				}
			}

			# Cache result
			$this->b_valid = $b_valid;
		}

		# Return cached result
		return $this->b_valid;
	}

	/**
	 * @return string
	 * @desc Gets the XHTML representation of the element
	 */
	function __toString()
	{
		# display validator errors
		$xhtml = '';
		if (!$this->IsValid() and $this->b_show_validation_errors)
		{
			require_once('data/validation/validation-summary.class.php');
			$a_controls = array($this);
			$xhtml .= new ValidationSummary($a_controls);
		}
        
        # Add CSRF token in a hidden field, and store the same token in session
        if (strtoupper($this->GetAttribute("method")) == "POST")
        {
            $this->AddControl('<input type="hidden" name="securitytoken" value="' . htmlentities($this->csrf_token, ENT_QUOTES, "UTF-8", false) . '" />');
            $this->InvalidateXhtml();
            $_SESSION['csrf_token'] = $this->csrf_token;
        }
        
		return $xhtml . parent::__toString();
	}

	/**
	 * Adds resources which may be needed by validators
	 * @param SiteSettings $settings
	 * @param MySqlConnection $data_connection
	 * @return void
	 */
	public function AddValidationResources(SiteSettings $settings, MySqlConnection $data_connection)
	{
		$this->settings = $settings;
		$this->data_connection = $data_connection;
	}

	/**
	 * Gets site settings to use for validation
	 * @return SiteSettings
	 */
	protected function GetValidationSettings() { return $this->settings; }

	/**
	 * Gets a data connection to use for validation
	 * @return MySqlConnection
	 */
	protected function GetValidationDataConnection() { return $this->data_connection; }
}
?>