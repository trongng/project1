<?php
if ($_GET['pdf'] == "true") {
	require_once('include/tcpdf/examples/lang/eng.php');
	require_once('include/tcpdf/tcpdf.php');

	// create new PDF document
	$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, false, 'ISO-8859-1', false);
	
	$pdf->setFontSubsetting(false);
	
	// set document information
	$pdf->SetCreator(PDF_CREATOR);
	$pdf->SetAuthor('CIAO');
	$pdf->SetTitle('CIAO Publications Report');
	$pdf->SetSubject('CIAO Publications Report');
	$pdf->SetKeywords('CIAO Publications Report');
	$pdf->SetPrintHeader(false);
	$pdf->SetPrintFooter(false);
	
	// set default header data
	$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 006', PDF_HEADER_STRING);
	
	// set default monospaced font
	$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
	// set margins
	$pdf->SetMargins(PDF_MARGIN_LEFT, 10, PDF_MARGIN_RIGHT);
	$pdf->SetHeaderMargin(10);
	$pdf->SetFooterMargin(10);
	
	// set auto page breaks
	$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
	
	// set image scale factor
	$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
	
	// set some language-dependent strings (optional)
	if (@file_exists(dirname(__FILE__).'/lang/eng.php')) {
		require_once(dirname(__FILE__).'/lang/eng.php');
		$pdf->setLanguageArray($l);
	}
	
	// ---------------------------------------------------------
	
	// set font
	$pdf->SetFont('helvetica', '', 9);

	$pdf->AddPage();

	$pdf->SetCellPadding(0.5);
	$pdf->SetFont('helvetica', 'B', 14);
	$pdf->Cell(0, 0, "CIAO Publications Report", 0, 1, "C");
	$pdf->Ln();			

	$pdf->lastPage();

	$pdf->Output("CIAO_Publications_Report_" . ($_GET['year_from'] == $_GET['year_to'] ? $_GET['year_to'] : $_GET['year_from'] . "_to_" . $_GET['year_to']) . ".pdf", 'I');	
} else {
?>
<html>
<head>
<title>CIAO Publications Report</title>
<link rel="stylesheet" href="http://code.jquery.com/ui/1.11.0/themes/smoothness/jquery-ui.css" />
<script src="http://code.jquery.com/jquery-2.1.1.js"></script>
<script src="http://code.jquery.com/ui/1.11.0/jquery-ui.js"></script>
</head>
<body>
<?php
require_once "include/db.inc";

echo "<h2>CIAO Report - " . ($_GET['year_from'] == $_GET['year_to'] ? $_GET['year_to'] : $_GET['year_from'] . " to " . $_GET['year_to']) . "</h2>";

$publications_array = getPublications();

foreach ($publications_array as $person_array) {
	echo "<h2>" . $person_array['name'] . "</h2>";

	foreach ($person_array['publications'] as $person_publications_array) {
		echo $person_publications_array['title'] . "<br>"; 
	}
}

?>
</body>
</html>
<?php
}


function getPublications() {
	$persons_result = PubBuilder_DB::query("SELECT * FROM pubbuilder_person ORDER BY pubbuilder_person_surname, pubbuilder_person_given_names");

	$publications_array = array();

	while (!$persons_result->EOF) {
		$person_publications_array = array();

        	$person_fields = $persons_result->fields;

		$name = $person_fields['pubbuilder_person_surname'] . ", " . $person_fields['pubbuilder_person_given_names'];
		$person_id = $person_fields['pubbuilder_person_id'];


		$publications_result = PubBuilder_DB::query(
			"SELECT * FROM pubbuilder_publication, pubbuilder_person_publication " .
			"WHERE " .
				"pubbuilder_person_publication_person_id = " .  $person_id . " AND " .
				"pubbuilder_publication_id = pubbuilder_person_publication_publication_id AND " .
				"pubbuilder_publication_year >= " . PubBuilder_DB::qstr($_GET['year_from']) . " AND " .
				"pubbuilder_publication_year <= " . PubBuilder_DB::qstr($_GET['year_to']) . " " .
			"ORDER BY pubbuilder_publication_source, pubbuilder_publication_year DESC"
		);

		$publication_html = "";

		$last_source = "";

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
			$source = $publication_fields['pubbuilder_publication_source'];

			if (strlen($last_source) == 0) {
				$publication_html .= "<h4>" . $source . "</h4><ol>";
			} else if (strlen($last_source) > 0 && $last_source != $source) {
				$publication_html .= "</ol><h4>" . $source . "</h4><ol>";
			}


			while (!$authors_result->EOF) {
				$authors_fields = $authors_result->fields;

				$url = $authors_fields['pubbuilder_person_profile_url'];

				if ($url != NULL) {
					$author = $authors_fields['pubbuilder_person_publication_author'];
					$authors = str_replace($author, "<a href=\"" . $url . "\" target=\"_blank\" style=\"color: #000099;\">" . $author . "</a>", $authors);
				}

				$authors_result->MoveNext();
			}
			
			$title = $publication_fields['pubbuilder_publication_title'];
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
			//	$citations_count = ", <span style=\"color: #ff0000;\">(cited " . $publication_fields['pubbuilder_publication_citations'] . " times)</span>";
			}

			$publication_html .= "<li><span style=\"color: #000099;\">" . $authors . "</span> <i style=\"color: #006600;\">" . $title . ".</i>" . $year . $journal . $volume . $pages . $doi . $citations_count . ".</li>\n"; 

			$last_source = $source;

			array_push($person_publications_array, array(
				'authors'	=>	$authors,
				'source'	=>	$source,
				'title'		=> 	$title,
				'journal'	=>	$journal,
				'volume'	=>	$volume,
				'pages'		=>	$pages,
				'year'		=>	$year,
				'doi'		=>	$doi,
				'citations'	=>	$citations_count
			));

			$publications_result->MoveNext();
		}

		if ($publications_result->RecordCount() > 0) {
//			echo 
//				"<h3>" . $name . "</h3>" .
//				$publication_html . "</ol>";

			array_push($publications_array, array(
				'name'		=>	$name,
				'publications'	=>	$person_publications_array
			));
		}


		$persons_result->MoveNext();
	}

	return $publications_array;
}

?>
