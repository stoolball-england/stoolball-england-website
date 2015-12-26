<?php
	/*
	*	Example usage of the StandardAnalyzer
	*	Copyright (c) 2008, Kenneth Katzgrau <kjk34@njit.edu>, CodeFury.net
	*
	*	This file should have come in a package with a Lucene index already built.
	*	If it did not, you can enable line 20 to create an index.
	*
	*	The purpose of this example is to show an example using the StandardAnalyser
	*		to search a Lucene index, and also to build one. This takes the $q
	*		variable supplied below, searches the index in 'data/', and writes
	*		the hits to the page. There are five test documents in the index. Try
	*		searching for something like 'Algorithm', which brings up document 4,
	*		which contains the word 'algorithms' (Note the basic stmming).
	*/

	require_once 'Zend/Search/Lucene.php';
	require_once 'StandardAnalyzer/Analyzer/Standard/English.php';
	
	//buildSampleIndex(); Enable this to completely rebuild the test index.
	
	/* This is the search term. Try using different forms such as 'algorithms', 'Algorithms', etc... */
	$q = 'algorithm';
	
	/*
	*	If you aren't setting your default analyzer as the standardanalyzer (see Readme.txt for more), 
	*		don't forget this line when building AND searching over an index
	*/
	Zend_Search_Lucene_Analysis_Analyzer::setDefault
	( new StandardAnalyzer_Analyzer_Standard_English() );

	/* Create a new index object */
	$index = new Zend_Search_Lucene('data/', false);
	
	/* Here we are going to search over multiple fields. we are just creating the string for right now */
	$title_query 	= "title:($q)";
	$content_query 	= "content:($q)";
	$tags_query 	= "tags:($q)";
	
	/* Parse the query */
	$query = Zend_Search_Lucene_Search_QueryParser::parse("$title_query $content_query $tags_query");
	
	/* Execute the query (I am not usually this verbose in my commentary */
	$hits = $index->find($query);
	
	/* Print out the results */
	foreach($hits as $hit)
	{
		$id = $hit->docId;
		$title = $hit->title;
		$content = $hit->content;
		$score = $hit->score;
		
		echo "<p>
				<b>$id - $title - $score</b><br>
				<i>$content</i>
			  </p>";
	}
	
	function buildSampleIndex()
	{
		/* This function shows the creation of a very simple (and informative!) index. */
		Zend_Search_Lucene_Analysis_Analyzer::setDefault(new StandardAnalyzer_Analyzer_Standard_English());
		
		$index = new Zend_Search_Lucene('data/', true);
		
		$index->addDocument( createDocument("1", 
											"Selected Reading from Wikipedia - Doc Holiday",
											"Doc Holliday was born in Griffin, Georgia, to Henry Burroughs Holliday and Alice Jane Holliday (née McKey).[1] His father served in both the Mexican-American War and the Civil War."));
											
		$index->addDocument( createDocument("2", 
											"Selected Reading from Wikipedia - Open Source Initiative",
											"The Open Source Initiative is an organization dedicated to promoting open-source software. The organization was founded in February 1998, by Bruce Perens and Eric S. Raymond, when Netscape Communications Corporation published the source code for its flagship Netscape Communicator product as free software due to lowering profit margins and competition with Microsoft's Internet Explorer software."));
		
		$index->addDocument( createDocument("3", 
											"Selected Reading from Wikipedia - Catacombs of Paris",
											"The Catacombs of Paris are a famous underground ossuary in Paris, France. Organized in a renovated section of the city's vast network of subterranean tunnels and caverns towards the end of the 18th century, it became a tourist attraction on a small scale from the early 19th century, and was open to the public on a regular basis from 1867."));
											
		$index->addDocument( createDocument("4", 
											"Selected Reading from Wikipedia - Donald Knuth",
											"Knuth has been called the father of the analysis of algorithms, contributing to the development of, and systematizing formal mathematical techniques for, the rigorous analysis of the computational complexity of algorithms, and in the process popularizing asymptotic notation."));											
											
		$index->addDocument( createDocument("5", 
											"Selected Reading from Wikipedia - Tuned Mass Damper",
											"A tuned mass damper, also known as an active mass damper (AMD) or harmonic absorber, is a device mounted in structures to prevent discomfort, damage or outright structural failure by vibration. They are most frequently used in power transmission, automobiles, and in buildings."));
		
		$index->addDocument( createDocument("6", 
											"Selected Reading from Wikipedia - Theory of Everything",
											"A theory of everything (TOE) is a hypothetical theory of theoretical physics that fully explains and links together all known physical phenomena."));
		
		$index->commit();
	}
	
	function & createDocument($id, $name, $d)
	{
		$doc = new Zend_Search_Lucene_Document();

		// Create Fields
		$docId 		= Zend_Search_Lucene_Field::Text( 'docId', $id	,	'UTF-8' );
		$title 		= Zend_Search_Lucene_Field::Text( 'title', $name,	'UTF-8' );
		$content 	= Zend_Search_Lucene_Field::Text( 'content', $d,	'UTF-8' );
		
		//Boost fields
		$title->boost 	= 1.8;
		
		// Add to doc
		$doc->addField( $docId );
		$doc->addField( $title );
		$doc->addField( $content ); 
		
		return $doc;
	}
?>	