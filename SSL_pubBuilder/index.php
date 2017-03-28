<?php
if (isset($_POST['schedule_update']) && $_POST['schedule_update'] == "true") {
	exec("/etc/cron.monthly/pubBuilder > /dev/null 2>/dev/null &");
	echo "OK";
	exit();
}
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="IE=edge;" />
<title>Publications</title>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" href="include/css/vertical-tabs.css" />
<script src="https://code.jquery.com/jquery-2.1.1.js"></script>
<script src="https://code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
<script src="include/js/vertical-tabs.js"></script>
<?php
require_once "include/db.inc";


if (!isset($_GET['rcid'])) {
?>
<style>
body {
	font-family: "Trebuchet MS", "Helvetica", "Arial",  "Verdana", "sans-serif";
	font-size: 102.5%;
}
</style>
</head>
<body>
<h1>Select a research centre to view members and publications</h1>
<ul>
<?php

	$research_centre_result = PubBuilder_DB::query("SELECT * FROM pubbuilder_research_centre ORDER BY pubbuilder_research_centre_id");

	while (!$research_centre_result->EOF) {
        	$research_centre_fields = $research_centre_result->fields;

		echo "<li><a href=\"?rcid=" . $research_centre_fields['pubbuilder_research_centre_id'] . "\">" . $research_centre_fields['pubbuilder_research_centre_name'] . ($research_centre_fields['pubbuilder_research_centre_acronym'] != NULL ? " (" . $research_centre_fields['pubbuilder_research_centre_acronym'] . ")" : "") . "</a></li>";

		$research_centre_result->MoveNext();
	}
?>
</ul>
</body>
</html>
<?php	
	exit();
}

$system_result = PubBuilder_DB::query("SELECT * FROM pubbuilder_system");
$system_fields = $system_result->fields;

$is_admin = false;

foreach (explode(",", $system_fields['pubbuilder_system_admin']) as $admin) {
        if ($_SERVER['PHP_AUTH_USER'] == $admin) {
                $is_admin = true;
                break;
        }
}

$research_centre_result = PubBuilder_DB::query("SELECT * FROM pubbuilder_research_centre WHERE pubbuilder_research_centre_id = " . PubBuilder_DB::qstr($_GET['rcid']));
$research_centre_fields = $research_centre_result->fields;

$research_centre_url = $research_centre_fields['pubbuilder_research_centre_url'];
$research_centre_name = $research_centre_fields['pubbuilder_research_centre_name'];
$research_centre_acronym = $research_centre_fields['pubbuilder_research_centre_acronym'];
if ($research_centre_acronym == NULL) {
	$research_centre_acronym = $research_centre_name;
}

?>
<script>

$(function() {
	$( "#tabs" ).tabs({ orientation: "vertical" });

<?php
if ($is_admin) {
?>

	$("#generate_report_button").button().click(function( event ) {
		window.open("report.php?rcid=<?php echo $_GET['rcid']; ?>&year_from=" + $("#year_from_select").val() + "&year_to=" + $("#year_to_select").val() + "&pdf=" + ($("#as_pdf_checkbox").prop('checked') ? "true" : "false") + "&scopus=" + ($("#scopus_checkbox").prop('checked') ? "true" : "false") + "&googlescholar=" + ($("#googlescholar_checkbox").prop('checked') ? "true" : "false") + "&mathscinet=" + ($("#mathscinet_checkbox").prop('checked') ? "true" : "false")<?php echo ($_GET['honorary'] == "true" ? " + \"&honorary=true\"" : ""); ?>);
	});
	$("#schedule_update_button").button().click(function( event ) {
		$.ajax({
			method: "POST",
			url: "index.php",
			data: { schedule_update: "true"}
		}).done(function( msg ) {
			if (msg == "OK") {
				alert( "Update scheduled. This process could one or more hours." );
			} else {
				alert("NOT OK");
			}
		});
	});

	$("#scopus_checkbox").change(onCheck);	
	$("#googlescholar_checkbox").change(onCheck);	
	$("#mathscinet_checkbox").change(onCheck);	

<?php
}
?>

});

var onCheck = function(event) {
	var checkedCount = 0;

	if ($("#scopus_checkbox").prop('checked')) {
		checkedCount++;
	}
	if ($("#googlescholar_checkbox").prop('checked')) {
		checkedCount++;
	}
	if ($("#mathscinet_checkbox").prop('checked')) {
		checkedCount++;
	}

	$("#generate_report_button").prop("disabled", checkedCount == 0);
}

</script>
<style>
.ui-buttonset { margin-top: 3em; }
body {
	font-family: "Trebuchet MS", "Helvetica", "Arial",  "Verdana", "sans-serif";
	font-size: 62.5%;
}
</style>
</head>
<body>
<div style="padding: 20px;">
<a href="<?php echo $research_centre_url; ?>" border=0 style="text-decoration: none;" target="_blank"><span style="color: #939598; font-size: 40pt; position: relative; top: -20px;"><b><?php echo $research_centre_acronym; ?></b></span><img src="images/feduni.png" /></a><br />
<!--<h2><a href="<?php echo $research_centre_url; ?>" target="_blank"><?php echo $research_centre_acronym; ?> Home</a></h2>-->
<?php

$this_year = intval(date("Y"));

if ($is_admin) {
	$years = "";

	$last_year = $this_year - 1;

	for ($year = 1950; $year <= ($last_year + 1); $year++) {
		$years .= "<option value=\"" . $year . "\"" . ($year == $last_year ? " selected" : "") . ">" . $year . "</option>";
	}

	echo
		"<b>Administrative actions:</b>" .
		" | " . 
		"Report from " .
		"<select id=\"year_from_select\">" .
		$years .
		"</select>" .
		" to " .
		"<select id=\"year_to_select\">" .
		$years .
		"</select> " .  
		"<input id=\"scopus_checkbox\" type=\"checkbox\" checked /> Include Scopus " .
//		"<input id=\"googlescholar_checkbox\" type=\"checkbox\" checked /> Include Google Scholar " .
		"<input id=\"mathscinet_checkbox\" type=\"checkbox\" checked /> Include MathSciNet " .
//		"<input id=\"as_pdf_checkbox\" type=\"checkbox\" /> Generate as PDF " .
		"<button id=\"generate_report_button\" style=\"position: relative; top: -5px;\">Generate</button>" .
		" | " . 
		"<button id=\"schedule_update_button\" style=\"position: relative; top: -5px;\">Schedule update</button>" .
		" | "; 
		
}

echo "<h1>Publications" . (isset($_GET['honorary']) ? " by honorary and adjunct staff members" : "") . " (last update: " . date(DATE_RFC850, intval($system_fields['pubbuilder_system_last_update'])) . ")</h1>\n";

$ul = "<ul>\n";
$tabs = "";

$persons_sql = "";

if ($_GET['rcid'] == "0") {
	$persons_sql = 
		"SELECT * FROM pubbuilder_person " .
		"WHERE " .
			"pubbuilder_person_id NOT IN (SELECT DISTINCT pubbuilder_person_research_centre_person_id FROM pubbuilder_person_research_centre) " .
		"ORDER BY pubbuilder_person_surname, pubbuilder_person_given_names";
} else {
	$persons_sql = 
		"SELECT * FROM pubbuilder_person, pubbuilder_person_research_centre " .
		"WHERE " .
			"pubbuilder_person_research_centre_research_centre_id = " .  PubBuilder_DB::qstr($_GET['rcid']) . " AND " .
			"(" .
				"pubbuilder_person_research_centre_start_year <= " . $this_year . " AND " .
				"(" .
					"pubbuilder_person_research_centre_end_year IS NULL OR " .
					"pubbuilder_person_research_centre_end_year >= " . $this_year .
				")" .
			") AND " .
			"pubbuilder_person_id = pubbuilder_person_research_centre_person_id AND " .
			"pubbuilder_person_honorary = '" . (isset($_GET['honorary'])? "T" : "F") . "' " .
		"ORDER BY pubbuilder_person_surname, pubbuilder_person_given_names";
}

$persons_result = PubBuilder_DB::query($persons_sql);

while (!$persons_result->EOF) {
        $person_fields = $persons_result->fields;

	$name = $person_fields['pubbuilder_person_surname'] . ", " . $person_fields['pubbuilder_person_given_names'];
	$person_id = $person_fields['pubbuilder_person_id'];

	$ul .= "<li><a href=\"#" . $person_id . "\">" . $name . "</a></li>\n";
	$tabs .= 
		"<div id=\"" . $person_id . "\">\n" .
		"<h2><a href=\"" . $person_fields['pubbuilder_person_profile_url'] . "\" target=\"_blank\">" . $name . "</a></h2>\n";

	$source_tabs_array = array();
	




	$elsevier_result = PubBuilder_DB::query(
		"SELECT * FROM pubbuilder_person_elsevier " .
		"WHERE pubbuilder_person_elsevier_person_id = " . $person_id
	);
	
	while (!$elsevier_result->EOF) {
        	$elsevier_fields = $elsevier_result->fields;
		
		$elsevier_tab =
			"<a href=\"http://www.scopus.com/authid/detail.url?authorId=" . $elsevier_fields['pubbuilder_person_elsevier_author_id'] . "\" target=\"_blank\">Scopus author profile</a><br /><br />" .
			"<b>h-index:</b> " . (intval($elsevier_fields['pubbuilder_person_elsevier_hindex']) + intval($elsevier_fields['pubbuilder_person_elsevier_1995_hindex'])) . "<br />\n" .
			"<b>Citations:</b> " . (intval($elsevier_fields['pubbuilder_person_elsevier_citations']) + intval($elsevier_fields['pubbuilder_person_elsevier_1995_citations'])) . "<br />\n";

		$elsevier_tab .= getPublications($person_id, "SCOPUS"); 

		array_push($source_tabs_array, array(
			'tab_name'	=>	"Scopus",
			'tab_data'	=>	$elsevier_tab
		));

		$elsevier_result->MoveNext();
	}




	$since = intval(date("Y")) - 5;

	$googlescholar_result = PubBuilder_DB::query(
		"SELECT * FROM pubbuilder_person_googlescholar " .
		"WHERE pubbuilder_person_googlescholar_person_id = " . $person_id
	);
	
	while (!$googlescholar_result->EOF) {
        	$googlescholar_fields = $googlescholar_result->fields;

                $googlescholar_tab =
			"<a href=\"https://scholar.google.com.au/citations?user=" . $googlescholar_fields['pubbuilder_person_googlescholar_author_id'] . "\" target=\"_blank\">Google Scholar author profile</a><br /><br />" .
                        "<div style=\"float: left;\">\n" .
			"<b>All Citations:</b> " . $googlescholar_fields['pubbuilder_person_googlescholar_citations'] . "<br />\n" .
                        "<b>All h-index:</b> " . $googlescholar_fields['pubbuilder_person_googlescholar_hindex'] . "<br />\n" .
                        "<b>All i10-index:</b> " . $googlescholar_fields['pubbuilder_person_googlescholar_i10index'] . "\n" .
			"</div><div>\n" .
			"&nbsp;&nbsp;&nbsp;<b>Since " . $since . ":</b> " . $googlescholar_fields['pubbuilder_person_googlescholar_recent_citations'] . "<br />\n" .
                        "&nbsp;&nbsp;&nbsp;<b>Since " . $since . ":</b> " . $googlescholar_fields['pubbuilder_person_googlescholar_recent_hindex'] . "<br />\n" .
                        "&nbsp;&nbsp;&nbsp;<b>Since " . $since . ":</b> " . $googlescholar_fields['pubbuilder_person_googlescholar_recent_i10index'] . "\n" .
			"</div>\n";

		$googlescholar_tab .= getPublications($person_id, "googlescholar");

                array_push($source_tabs_array, array(
                        'tab_name'      =>      "Google Scholar",
                        'tab_data'      =>      $googlescholar_tab
                ));

                $googlescholar_result->MoveNext();
        }






	$mathscinet_result = PubBuilder_DB::query(
		"SELECT * FROM pubbuilder_person_mathscinet " .
		"WHERE pubbuilder_person_mathscinet_person_id = " . $person_id
	);
	
	while (!$mathscinet_result->EOF) {
        	$mathscinet_fields = $mathscinet_result->fields;

                $mathscinet_tab =
			"<a href=\"http://www.ams.org/mathscinet/search/author.html?mrauthid=" . $mathscinet_fields['pubbuilder_person_mathscinet_author_id'] . "\" target=\"_blank\">MathSciNet author profile</a><br /><br />" .
                        "<b>Earliest Indexed Publication:</b> " . $mathscinet_fields['pubbuilder_person_mathscinet_earliest_publication'] . "<br />\n" .
                        "<b>Total Publications:</b> " . $mathscinet_fields['pubbuilder_person_mathscinet_publications'] . "<br />\n" .
                        "<b>Total Author/Related Publications:</b> " . $mathscinet_fields['pubbuilder_person_mathscinet_related_publications'] . "<br />\n" .
                        "<b>Total Citations:</b> " . $mathscinet_fields['pubbuilder_person_mathscinet_citations'] . "\n";

		$mathscinet_tab .= getPublications($person_id, "MathSciNet");

                array_push($source_tabs_array, array(
                        'tab_name'      =>      "MathSciNet",
                        'tab_data'      =>      $mathscinet_tab
                ));

                $mathscinet_result->MoveNext();
        }






	$source_ul = "<ul>\n";
	$source_tabs = "";
	
	foreach ($source_tabs_array as $source_tab_array) {
		$source_id = str_replace(" ", "_", $source_tab_array['tab_name']) . "-" . $person_id;
		$source_ul .= "<li><a href=\"#" . $source_id . "\">" . $source_tab_array['tab_name'] . "</a></li>\n";

		$source_tabs .= 
			"<div id=\"" . $source_id . "\">\n" .
			$source_tab_array['tab_data'] .
			"</div>\n";
	}

	$tabs .= 
		"<script>$(function() { $( \"#tabs-" . $person_id . "\" ).tabs({ orientation: \"horizontal\" }); });</script>\n" .
		"<div id=\"tabs-" . $person_id . "\">\n" .
		$source_ul . "</ul>\n" .
		$source_tabs .	
		"</div>\n";


	$tabs .= "</div>\n";

	$persons_result->MoveNext();
}

echo 
	"<div id=\"tabs\">\n" .
	$ul . "</ul>\n" . 
	$tabs .
	"</div>\n";



function getPublications($person_id, $source_id) {
	$publication_html = "<ol>\n";

	$publications_result = PubBuilder_DB::query(
		"SELECT * FROM pubbuilder_publication, pubbuilder_person_publication " .
		"WHERE " .
			"pubbuilder_person_publication_person_id = " .  $person_id . " AND " .
			"pubbuilder_publication_source = '" . $source_id . "' AND " .
			"pubbuilder_publication_id = pubbuilder_person_publication_publication_id AND " .
			"pubbuilder_publication_is_new = 'F' AND " .
			"pubbuilder_person_publication_is_new = 'F' " .
		"ORDER BY pubbuilder_publication_year DESC"
	);

	while (!$publications_result->EOF) {
		$publication_fields = $publications_result->fields;

		$authors_result = PubBuilder_DB::query(
			"SELECT pubbuilder_person_publication_author, pubbuilder_person_profile_url " .
			"FROM pubbuilder_person_publication, pubbuilder_person " .
			"WHERE " .
				"pubbuilder_person_publication_publication_id = " . PubBuilder_DB::qstr($publication_fields['pubbuilder_publication_id']) . " AND " .
				"pubbuilder_person_publication_person_id = pubbuilder_person_id"
			
		);

		$authors = $publication_fields['pubbuilder_publication_authors'];

		while (!$authors_result->EOF) {
			$authors_fields = $authors_result->fields;

			$url = $authors_fields['pubbuilder_person_profile_url'];

			if ($url != NULL) {
				$author = $authors_fields['pubbuilder_person_publication_author'];
				$authors = str_replace($author, "<a href=\"" . $url . "\" target=\"_blank\" style=\"color: #000099;\">" . $author . "</a>", $authors);
			}

			$authors_result->MoveNext();
		}
			
		$title = str_replace(">", "&gt;", str_replace("<", "&lt;", $publication_fields['pubbuilder_publication_title']));
		$journal =  $publication_fields['pubbuilder_publication_journal'];
		$volume = "";
		if ($publication_fields['pubbuilder_publication_volume'] != NULL) {
			$volume = ", vol. " . $publication_fields['pubbuilder_publication_volume'];
		}
		$pages = "";
		if ($publication_fields['pubbuilder_publication_page_range'] != NULL) {
			$pages = ", pp. " . $publication_fields['pubbuilder_publication_page_range'];
		}
		$year = "";
		if ($publication_fields['pubbuilder_publication_year'] != NULL) {
			$year = " <span style=\"color: #990099;\">(" . $publication_fields['pubbuilder_publication_year'] . ")</span>, ";
		}
		$doi = "";
		if ($publication_fields['pubbuilder_publication_doi'] != NULL) {
			$doi = ", <a href=\"http://dx.doi.org/" . $publication_fields['pubbuilder_publication_doi'] . "\" target=\"_blank\">" . $publication_fields['pubbuilder_publication_doi'] . "</a>";
		}
		$citations_count = "";
		if ($publication_fields['pubbuilder_publication_citations'] != NULL && intval($publication_fields['pubbuilder_publication_citations']) != 0) {
			$citations_count = ", <span style=\"color: #ff0000;\">(cited " . $publication_fields['pubbuilder_publication_citations'] . " times)</span>";
		}

		if (substr($title, strlen($title) - 1, 1) != ".") {
			$title .= ".";
		}

		$publication_html .= "<li><span style=\"color: #000099;\">" . $authors . "</span> <i style=\"color: #006600;\">" . $title . "</i>" . $year . $journal . $volume . $pages . $doi . $citations_count . ".</li>\n"; 

		$publications_result->MoveNext();
	}

	return $publication_html . "</ol>\n";
}
?>
</div>
</body>
</html>
