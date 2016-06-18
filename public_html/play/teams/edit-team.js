$(function(){
	var teamType = $("#team_type");
	var schoolYears = $(".school-years");
	
	function configureSchoolDetailSection() {
		var type = teamType.val();
		if (type === '6') {
			schoolYears.slideDown();
		} else {
			schoolYears.slideUp();
		}
	}
	
	configureSchoolDetailSection();
	teamType.change(configureSchoolDetailSection);	
});
