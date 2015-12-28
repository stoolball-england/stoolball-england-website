<?php
require_once('xhtml/forms/xhtml-form.class.php');

class PersonalInfoForm extends XhtmlForm
{
	var $o_person;

	function PersonalInfoForm(User $o_person)
	{
		$this->o_person = $o_person;

		parent::XhtmlForm();
	}

	function OnPreRender()
	{
		$s_selected_gender = (isset($_POST['gender'])) ? stripslashes($_POST['gender']) : stripslashes($this->o_person->GetGender());
		$s_occupation = (isset($_POST['occupation'])) ? stripslashes($_POST['occupation']) : stripslashes($this->o_person->GetOccupation());
		$s_location = (isset($_POST['location'])) ? stripslashes($_POST['location']) : stripslashes($this->o_person->GetLocation());
		$s_interests = (isset($_POST['interests'])) ? stripslashes($_POST['interests']) : stripslashes($this->o_person->GetInterests());

		$s_text = '<div class="formPart">' . "\n" .
				'<div class="formLabel">Gender</div>' . "\n" .
				'<div class="formControl">' . "\n";

		$s_text .= '<label for="gender-male"><input type="radio" name="gender" id="gender-male" value="male"';
		if ('male' == $s_selected_gender) $s_text .= ' checked="checked"';
		$s_text .= ' /> male</label>';

		$s_text .= '<label for="gender-female"><input type="radio" name="gender" id="gender-female" value="female"';
		if ('female' == $s_selected_gender) $s_text .= ' checked="checked"';
		$s_text .= ' /> female</label>';

		$s_text .= '</div>' . "\n" .
			'</div>' . "\n" .
			'<div class="formPart">' . "\n" .
				'<label for="occupation" class="formLabel">Occupation</label>' . "\n" .
				'<div class="formControl"><input type="text" size="50" maxlength="255" name="occupation" id="occupation" value="' . $s_occupation . '" /></div>' . "\n" .
			'</div>' . "\n" .
			'<div class="formPart">' . "\n" .
				'<label for="location" class="formLabel">Location</label>' . "\n" .
				'<div class="formControl"><input type="text" size="50" maxlength="100" name="location" id="location" value="' . $s_location . '" /></div>' . "\n" .
			'</div>' . "\n" .
			'<div class="formPart">' . "\n" .
				'<label for="interests" class="formLabel">Interests</label>' . "\n" .
				'<div class="formControl">' .
					'<textarea name="interests" id="interests" rows="7" cols="40">' . $s_interests . '</textarea></div>' . "\n" .
			'</div>' . "\n" .

			'<div class="formPart"><div class="formControl">' . "\n" .

			'<input type="submit" class="submit" value="Save" /></div></div>' . "\n" .

			'<script type="text/javascript">' . "\n" .
	        "document.getElementById('gender-male').focus();\n" .
	        '</script>' . "\n\n";

		$this->AddControl($s_text);
	}
}
?>