<?php
/*
WordPress hook search tool
Jesin (http://jesin.tk)
*/

if( isset( $_POST ) && isset( $_POST['location'] ) ):

	header( 'Content-Type: application/json' );
	if( ! is_dir( $_POST['location'] ) && ! is_file( $_POST['location'] ) ) {
		echo json_encode( array( 'status' => 1, 'html' => '<p class="alert alert-danger" style="text-align:center;font-size:24px">The directory/file does not exist</p>' ) );
		die();
	}

	$start_time = microtime(TRUE);
	$location = ( ! empty( $_POST['location'] ) ? $_POST['location'] : '.' );
	$search_hook = ( isset( $_POST['hookname'] ) && ! empty( $_POST['hookname'] ) ) ? $_POST['hookname'] : '*';

	//Get a list for files with a .php extension
	$wp_files = ( is_dir( $location ) ? find_all_files( $location ) : array( $location ) );
	$all_hooks = Array();
	$output = '<table class="table table-hover table-bordered">
	<thead>
	<tr>
		<th>Filters</th>
		<th>Actions</th>
	</tr>
	</thead>';
	$all_filters = "";
	$all_actions = "";
	foreach( $wp_files as $wp_file )
	{
		$filters = "";
		$actions = "";

		$code = file_get_contents( $wp_file );
		
		//Search for actions and filters in the PHP code
		preg_match_all( "/(apply_filters|do_action|do_action_ref_array)\s*\(\s*[\'\"](.+?)\s*[,\)]/", $code, $hooks );
		$hooks[2] = str_replace( "'", "", $hooks[2] );
		$hooks[2] = str_replace( '"', "", $hooks[2] );
		if( !empty( $hooks[2] ) )
		{
			foreach ( array_unique( $hooks[2] ) as $key => $hook )
			{
				if( ( "*" == $search_hook && ( !in_array( $hook, $all_hooks ) || isset( $_POST['duplicates'] ) ) ) || ( FALSE !== strstr( $hook, $search_hook ) && ( !in_array( $hook, $all_hooks ) || isset( $_POST['duplicates'] ) ) ) )
				{
					//Segregate Actions and Filters separately
					if ( "apply_filters" == $hooks[1][ $key ] )
						$filters .= "\n\t\t\t<li>" . str_replace( $search_hook, '<span style="background-color:#EEEEEE;border:1px solid">'.$search_hook.'</span>', $hook ) . "</li>";
					elseif ( "do_action" == $hooks[1][ $key ] || "do_action_ref_array" == $hooks[1][ $key ] )
						$actions .= "\n\t\t\t<li>" . str_replace( $search_hook, '<span style="background-color:#EEEEEE;border:1px solid">'.$search_hook.'</span>', $hook ) . "</li>";
				}
			}


			if ( ! empty( $filters ) || ! empty( $actions ) )
			{
				$output.= "\n\t<tbody>
				<tr>
				<th colspan='2' style='text-align:center'>" . $wp_file . "</th>
				</tr>
				<tr>
				<td><ul>" . $filters . "\n\t\t</ul>\n\t</td>
				<td><ul>" . $actions . "\n\t\t</ul>\n\t</td>
				</tr>
				</tbody>";
			}
			$all_filters .= $filters;
			$all_actions .= $actions;
			$add_hooks[] = $hooks[2];
			$all_hooks = array_2d_to_1d( $add_hooks );
		}
	}
	$output .= "\n</table>\n";
	$end_time = microtime(TRUE);

	//Display the number of Actions and Filters
	$output = '<h2>List of WordPress hooks in <span style="color:#AA0000">' .$location . '</span></h2>
	<p style="font-size:20px;text-align:center">Actions: <strong>' . ( count( explode( '<li>', $all_actions ) ) - 1 ) . '</strong>
		<br />Filters: <strong>' . ( count( explode( '<li>', $all_filters ) ) - 1 ) . '</strong>
		<br />Scan Time: <strong>' . round( ($end_time-$start_time), 5 ) . '</strong> seconds</p>' . $output;
	echo json_encode( array( 'status' => 0, 'html' => $output ) );
	die();

endif;

//Function to get a list of .php files
function find_all_files( $dir )
{
	$root = scandir( $dir );
	foreach( $root as $value )
	{
		if( $value !== '.' && $value !== '..' )
		{
			if( is_file( "$dir/$value" ) && preg_match( '/\.php$/', $value ) )
				$result[] = "$dir/$value";

			if( is_dir( "$dir/$value" ) )
			{
				foreach( find_all_files("$dir/$value") as $value )
				{
					$result[] = $value;
				}
			}
		}
    }
    return ( isset( $result ) ? $result : Array() );
}

//Convert a 2 dimensional array to a single dimension
function array_2d_to_1d( $input_array )
{
	$output_array = array();

	for ($i = 0; $i < count($input_array); $i++)
		for ($j = 0; $j < count($input_array[$i]); $j++)
			$output_array[] = $input_array[$i][$j];

	return $output_array;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>WordPress Hooks Search Tool</title>
<meta charset="UTF-8" />
<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css" />
<script type="text/javascript" src="//code.jquery.com/jquery-latest.js"></script>
<!--[if lt IE 9]>
<script src="//oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
<script src="//oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
<![endif]-->
<style type="text/css">
<!--
html,
body {
	height: 100%;
}

#wrap {
	min-height: 100%;
	height: auto;
	margin: 0 auto -60px;
	padding: 0 0 60px;
}

.container {
	max-width: 800px;
}

#footer {
	height: 60px;
	background-color: #f5f5f5;
}

#wrap > .container {
	padding: 60px 15px 0;
}

.container .text-muted {
	margin: 20px 0;
}

#footer > .container {
	padding-left: 15px;
	padding-right: 15px;
}
.x-item-disabled,
.x-item-disabled * {
    cursor: wait
}
-->
</style>
</head>
<body>
<div id="wrap">
<div class="container">
<h1 style="text-align:center">WordPress Hooks Search Tool</h1>
<p>This tool scans all the PHP files in a directory you specify and lists out the Actions (from <a href="http://codex.wordpress.org/Function_Reference/do_action" target="_blank">do_action</a>) and Filters (from <a href="http://codex.wordpress.org/Function_Reference/apply_filters" target="_blank">apply_filters</a>) defined in these files. To search all files and subdirectories leave the <strong>Search in</strong> field empty. You may also specify a keyword to search for in the hook name.</p>
	<form style="padding-bottom:10px;" id="search-form" role="form" method="post">
		<div id="field1" class="form-group">
			<label  class="control-label" for="location">Search in: <?php echo dirname(__FILE__); ?></label>
			<input type="text" id="location" class="form-control input-lg" name="location" value="<?php echo ( isset( $_POST['location'] ) ? $_POST['location'] : '' ); ?>" />
			<p>Eg. wp-content/plugins</p>
		</div>
		<div id="field2" class="form-group">
			<label class="control-label" for="hookname">Hook Name <span style="color:#AAAAAA">(optional)</span></label>
			<input type="text" id="hookname" class="form-control input-lg" name="hookname" value="<?php echo ( isset( $_POST['hookname'] ) ? $_POST['hookname'] : '' ); ?>" />
		</div>
		<div class="checkbox">
			<label><input type="checkbox" id="duplicates" name="duplicates" <?php isset( $_POST['duplicates'] ) ? print 'checked="checked" ' : ''; ?>/> Show Duplicates</label>
			<p>If unchecked only the first occurrence of a hook is displayed.<br />Eg. The <a href="http://codex.wordpress.org/Plugin_API/Filter_Reference/the_permalink" target="_blank">the_permalink</a> filter is present in both <strong>comment-template.php</strong> and <strong>link-template.php</strong> files, unchecking this box displays only in first one.</p>
		</div>
		<button id="search-btn" type="submit" class="btn btn-primary">Search</button>
	</form>
<div id="results"></div>
</div>
</div>
<div id="footer">
	<div class="container">
		<p class="text-muted" style="text-align:center">WordPress Hooks Search Tool developed by <a target="_blank" href="http://jesin.tk">Jesin</a></p>
	</div>
</div>
<script type="text/javascript">
jQuery(document).ready(function($) {
	$('#search-form').submit(function(e) {
		e.preventDefault();
		$('#search-btn').attr( 'disabled', 'disabled' );
		$('#search-btn').html( 'Searching ...' );
		var location = $('#location').val();
		var hookname = $('#hookname').val();
		if ( location.length == 0 ) {
			$('#field1').addClass( 'has-error' );
			$('#search-btn').removeAttr( 'disabled' ).text( 'Search' );
			$('#search-btn').html( 'Search' );
			return;
		}
		var postdata = {
			ajax: "true",
			location: location,
			hookname: hookname
		};
		if ( $('#duplicates').is(':checked') ) {
			$.extend( postdata, { duplicates: 1 } );
		}
		//console.log( postdata );
		$.post( '', postdata, function( data ) {
			if ( 0 == data.status ) {
				$('#field1').removeClass( 'has-error' );
			} else {
				$('#field1').addClass( 'has-error' );
			}
			$('#search-btn').removeAttr( 'disabled' );
			$('#search-btn').html( 'Search' );
			$('#results').html( data.html );
		}, 'json');
	});
});
</script>
</body>
</html>
