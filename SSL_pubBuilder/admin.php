<html>
<head>
<style>
input {
	width: 500px;
}
.error {
	color: #ff0000;
}
</style>
</head>
<body>
<?php
require_once "include/db.inc";

$system_result = PubBuilder_DB::query("SELECT * FROM pubbuilder_system");
$system_fields = $system_result->fields;

$is_admin = false;

foreach (explode(",", $system_fields['pubbuilder_system_admin']) as $admin) {
        if ($_SERVER['PHP_AUTH_USER'] == $admin) {
                $is_admin = true;
                break;
        }
}


if (!$is_admin) {
	die("Unauthorised access");
}

$has_added_staff_member = false;
$has_updated_staff_member = false;
$has_error = false;
$has_first_name = true;
$has_surname = true;
$has_scopus = true;
$has_google_scholar = true;
$has_mathscinet = true;

if (isset($_POST['first_name'])) {
	$has_added_staff_member = true;

	if (strlen(trim($_POST['first_name'])) == 0) {
		$has_first_name = false;
		$has_error = true;
	}
	if (strlen(trim($_POST['surname'])) == 0) {
		$has_surname = false;
		$has_error = true;
	}
	if (strlen(trim($_POST['scopus'])) == 0) {
		$has_scopus = false;
	}
	if (strlen(trim($_POST['google_scholar'])) == 0) {
		$has_google_scholar = false;
	}
	if (strlen(trim($_POST['mathscinet'])) == 0) {
		$has_mathscinet = false;
	}
	if (!$has_scopus && !$has_google_scholar && !$has_mathscinet) {
		$has_error = true;
	}
} else if (isset($_POST['staff_member'])) {
	$has_updated_staff_member = true;

	if (strlen(trim($_POST['start_year'])) == 0 || !is_numeric(trim($_POST['start_year']))) {
		$has_error = true;
	}
}

if ($has_added_staff_member) {
	if (!$has_error) {
		$person_exists_result = PubBuilder_DB::query(
			"SELECT * FROM pubbuilder_person WHERE " .
				"UPPER(pubbuilder_person_given_names) = " . PubBuilder_DB::qstr(strtoupper(trim($_POST['first_name']))) . " AND " .
				"UPPER(pubbuilder_person_surname) = " . PubBuilder_DB::qstr(strtoupper(trim($_POST['surname'])))
		);

		if ($person_exists_result->RecordCount() > 0) {
			$has_error = true;
		} else {		
			PubBuilder_DB::query(
				"INSERT INTO pubbuilder_person (" .
					"pubbuilder_person_given_names, " .
					"pubbuilder_person_surname, " .
					"pubbuilder_person_profile_url, " .
					"pubbuilder_person_honorary " .
				") VALUES (" .
					PubBuilder_DB::qstr(trim($_POST['first_name'])) . ", " .
					PubBuilder_DB::qstr(trim($_POST['surname'])) . ", " .
					(strlen(trim($_POST['profile_url'])) == 0 ? "NULL" : PubBuilder_DB::qstr(trim($_POST['profile_url']))) . ", " .
					PubBuilder_DB::qstr(trim($_POST['honorary']) == "on" ? "T" : "F") .
				")"
			);

			$last_id = PubBuilder_DB::lastID();

			PubBuilder_DB::query(
				"INSERT INTO pubbuilder_person_research_centre VALUES (" .
					$last_id . ", " .
					PubBuilder_DB::qstr(trim($_POST['research_centre'])) . ", " .
					(strlen(trim($_POST['start_year'])) == 0 || !is_numeric(trim($_POST['start_year'])) ? "0" : PubBuilder_DB::qstr(trim($_POST['start_year']))) . ", " .
					(strlen(trim($_POST['end_year'])) == 0 || !is_numeric(trim($_POST['end_year'])) ? "NULL" : PubBuilder_DB::qstr(trim($_POST['end_year']))) .
				")"
			);

			if ($has_scopus) {
				PubBuilder_DB::query(
					"INSERT INTO pubbuilder_person_elsevier (pubbuilder_person_elsevier_person_id, pubbuilder_person_elsevier_author_id) VALUES (" .
						$last_id . ", " .
						PubBuilder_DB::qstr(trim($_POST['scopus'])) .
					")"
				);
			}
			if ($has_google_scholar) {
				PubBuilder_DB::query(
					"INSERT INTO pubbuilder_person_googlescholar (pubbuilder_person_googlescholar_person_id, pubbuilder_person_googlescholar_author_id) VALUES (" .
						$last_id . ", " .
						PubBuilder_DB::qstr(trim($_POST['google_scholar'])) .
					")"	
				);
			}
			if ($has_mathscinet) {
				PubBuilder_DB::query(
					"INSERT INTO pubbuilder_person_mathscinet (pubbuilder_person_mathscinet_person_id, pubbuilder_person_mathscinet_author_id) VALUES (" .
						$last_id . ", " .
						PubBuilder_DB::qstr(trim($_POST['mathscinet'])) .
					")"
				);
			}

			echo "<h2>New staff member <i>" . $_POST['surname'] . ", " . $_POST['first_name'] . "</i> added.</h2>";
		}
	}
} else if ($has_updated_staff_member) {
	if (!$has_error) {
		PubBuilder_DB::query(
			"UPDATE pubbuilder_person SET pubbuilder_person_honorary = " . PubBuilder_DB::qstr(trim($_POST['honorary']) == "on" ? "T" : "F") . " " .
			"WHERE pubbuilder_person_id = " . PubBuilder_DB::qstr(trim($_POST['staff_member']))
		);

		$is_end_year_null = strlen(trim($_POST['end_year'])) == 0 || !is_numeric(trim($_POST['end_year']));

		PubBuilder_DB::query(
			"UPDATE pubbuilder_person_research_centre SET " .
				"pubbuilder_person_research_centre_start_year = " . PubBuilder_DB::qstr(trim($_POST['start_year'])) . ", " .
				"pubbuilder_person_research_centre_end_year = " . ($is_end_year_null ? "NULL" : PubBuilder_DB::qstr(trim($_POST['end_year']))) .
			" WHERE pubbuilder_person_research_centre_person_id = " . PubBuilder_DB::qstr(trim($_POST['staff_member']))
		);

		echo "<h2>Staff member updated with start year: <i>" . $_POST['start_year'] . "</i> and end year: <i>"  . ($is_end_year_null ? "NOT SET" : $_POST['end_year']) . "</i>.</h2>";
	}
}

$research_centres_html = "";
$research_centre_result = PubBuilder_DB::query("SELECT * FROM pubbuilder_research_centre ORDER BY pubbuilder_research_centre_id");

while (!$research_centre_result->EOF) {
        $research_centre_fields = $research_centre_result->fields;
	$rcid = intval($research_centre_fields['pubbuilder_research_centre_id']);

	$research_centres_html .= "<option value=\"" . $rcid . "\"" . ((!isset($_POST['research_centre']) && $rcid == 1) || (isset($_POST['research_centre']) && intval($_POST['research_centre']) == $rcid) ? " selected" : "") . ">" . $research_centre_fields['pubbuilder_research_centre_name'] . ($research_centre_fields['pubbuilder_research_centre_acronym'] != NULL ? " (" . $research_centre_fields['pubbuilder_research_centre_acronym'] . ")" : "") . "</option>";

	$research_centre_result->MoveNext();
}
$staff_members_html = "";
$staff_member_result = PubBuilder_DB::query(
	"SELECT * FROM pubbuilder_person, pubbuilder_person_research_centre " .
	"WHERE " .
		"pubbuilder_person_id = pubbuilder_person_research_centre_person_id AND " .
		"pubbuilder_person_research_centre_research_centre_id = 1 " .
	"ORDER BY pubbuilder_person_surname, pubbuilder_person_given_names"
);

while (!$staff_member_result->EOF) {
        $staff_member_fields = $staff_member_result->fields;

	$staff_members_html .= "<option value=\"" . $staff_member_fields['pubbuilder_person_id'] . "\">" . $staff_member_fields['pubbuilder_person_surname'] . ", " . $staff_member_fields['pubbuilder_person_given_names'] . ($staff_member_fields['pubbuilder_person_honorary'] == "T" ? " (Honorary)" : "") . " [START: " . ($staff_member_fields['pubbuilder_person_research_centre_start_year'] == NULL ? "NOT SET" : $staff_member_fields['pubbuilder_person_research_centre_start_year']) . " END: " . ($staff_member_fields['pubbuilder_person_research_centre_end_year'] == NULL ? "NOT SET" : $staff_member_fields['pubbuilder_person_research_centre_end_year']) . "]</option>";

	$staff_member_result->MoveNext();
}
?>
<h1>New staff member</h1>
<?php
if ($has_added_staff_member && $has_error) {
	echo "<h2 class=\"error\">Required fields are missing (note: only one of either Scopus, Google Scholar or MathSciNet IDs is required), or first name and surname already exists.</h2>";
}
?>
<form action="admin.php" method="post">
<table border=1>
<tr><td<?php echo ($has_added_staff_member && !$has_first_name ? " class=\"error\"" : ""); ?>>* First name:</td><td><input type="text" name="first_name" value="<?php echo (isset($_POST['first_name']) && $has_error ? $_POST['first_name'] : ""); ?>" /></td></tr>
<tr><td<?php echo ($has_added_staff_member && !$has_surname ? " class=\"error\"" : ""); ?>>* Surname:</td><td><input type="text" name="surname" value="<?php echo (isset($_POST['surname']) && $has_error ? $_POST['surname'] : ""); ?>" /></td></tr>
<tr><td>Honorary:</td><td><input type="checkbox" name="honorary" <?php echo (isset($_POST['honorary']) && trim($_POST['honorary']) == "on" && $has_error ? " checked" : ""); ?> /></td></tr>
<tr><td>Staff profile website URL:</td><td><input type="text" name="profile_url" value="<?php echo (isset($_POST['profile_url']) && $has_error ? $_POST['profile_url'] : ""); ?>" /></td></tr>
<tr><td>Research centre:</td><td><select name="research_centre"><?php echo $research_centres_html; ?></select></td></tr>
<tr><td>Research centre start year:</td><td><input type="text" name="start_year" value="<?php echo (isset($_POST['start_year']) && $has_error ? $_POST['start_year'] : ""); ?>" /></td></tr>
<tr><td>Research centre end year:</td><td><input type="text" name="end_year" value="<?php echo (isset($_POST['end_year']) && $has_error ? $_POST['end_year'] : ""); ?>" /></td></tr>
<tr><td<?php echo ($has_added_staff_member && !$has_scopus && !$has_google_scholar && !$has_mathscinet ? " class=\"error\"" : ""); ?>>- Scopus author ID:</td><td><input type="text" name="scopus" value="<?php echo (isset($_POST['scopus']) && $has_error ? $_POST['scopus'] : ""); ?>" /></td></tr>
<tr><td<?php echo ($has_added_staff_member && !$has_scopus && !$has_google_scholar && !$has_mathscinet ? " class=\"error\"" : ""); ?>>- Google scholar ID: (eg. HXuUmFsAAAAJ)</td><td><input type="text" name="google_scholar" value="<?php echo (isset($_POST['google_scholar']) && $has_error ? $_POST['google_scholar'] : ""); ?>" /></td></tr>
<tr><td<?php echo ($has_added_staff_member && !$has_scopus && !$has_google_scholar && !$has_mathscinet ? " class=\"error\"" : ""); ?>>- MathSciNet author ID:</td><td><input type="text" name="mathscinet" value="<?php echo (isset($_POST['matscinet']) && $has_error ? $_POST['mathscinet'] : ""); ?>" /></td></tr>
<tr><td colspan=2><input type="submit" value="Add new staff member" /></td></tr>
</table>
</form>
<h1>Update staff member</h1>
<?php
if ($has_updated_staff_member && $has_error) {
	echo "<h2 class=\"error\">Required fields are missing.</h2>";
}
?>
<form action="admin.php" method="post">
<table border=1>
<tr><td>Staff member:</td><td><select name="staff_member"><?php echo $staff_members_html; ?></select></td></tr>
<tr><td>Honorary:</td><td><input type="checkbox" name="honorary" <?php echo (isset($_POST['honorary']) && trim($_POST['honorary']) == "on" && $has_error ? " checked" : ""); ?> /></td></tr>
<tr><td<?php echo ($has_updated_staff_member && $has_error ? " class=\"error\"" : ""); ?>>* Research centre start year:</td><td><input type="text" name="start_year" value="<?php echo (isset($_POST['start_year']) && $has_error ? $_POST['start_year'] : ""); ?>" /></td></tr>
<tr><td>Research centre end year:</td><td><input type="text" name="end_year" value="<?php echo (isset($_POST['end_year']) && $has_error ? $_POST['end_year'] : ""); ?>" /></td></tr>
<tr><td colspan=2><input type="submit" value="Update staff member" /></td></tr>
</table>
</form>
</body>
</html>
