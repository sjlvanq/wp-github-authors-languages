<?php
/*
Plugin Name: GitHub Author's Programming Languages
Description: Wordpress plugin. Displays a summary of the programming languages used by the author in their public GitHub projects. This is displayed in the description field of the author's posts page.
Version: dev-1.0
Requires PHP: 7.0
Author: Silvano Emanuel Roqués
Author URI: http://lode.uno/
Licence: GPLv2
Licence URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

//======================================================================
//            WP Author's Programming Languages on GitHub
//======================================================================
//                        Wordpress Plugin
// ---------------------------------------------------------------------

// don't call the file directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

//-----------------------------------------------------
// Add a GitHub Username profile field
//-----------------------------------------------------
// ToDo: check if username exists.

add_filter( 'user_contactmethods', 'plggithub_add_github_contactmethod');

function plggithub_add_github_contactmethod($methods){
	$methods['github']   = __( 'Github Username'   );
	return $methods;
}

function plggithub_get_github_contactmethod($user){
	$github_user = get_the_author_meta( 'github' );
	return $github_user;
}

//-----------------------------------------------------
// Show summary of author's programming languages 
//-----------------------------------------------------

function plggithub_getlanguages($user){
	$github_user = plggithub_get_github_contactmethod($user); // ToDo: check if not set
	$response = wp_remote_get( 'https://api.github.com/users/'.$github_user.'/repos' );
	$body     = wp_remote_retrieve_body( $response );
	
	$data = json_decode($body, true);
	$languages_sum = [];
	foreach ($data as $repo) {
		$language = $repo['language'];
		if (!is_null($language)){
			$languages_sum[$language] = ($languages_sum[$language] ?? 0) + 1;
		}
	}
	arsort($languages_sum);
	return $languages_sum;
}

function plggithub_create_summary_graph($languages_sum){
	$graph = '<div class="chart">';
	//$total = array_sum($languages_sum);
	$max = max($languages_sum);
	echo $max;
	foreach ( $languages_sum as $k => $v ){
		$graph.='<div style="width: '.$v*100/$max.'%">'.$k.'</div>';
	}
	$graph .= '</div>';
	return $graph;
}

/* Show summary on author's posts page */
// ToDo: Check if repository is empty
function plggithub_show_summary_graph($desc){
    if (is_author()) { // author's posts page
		$languages_sum = plggithub_getlanguages(the_author());
	}
    return plggithub_create_summary_graph($languages_sum);
}

add_filter('get_the_archive_description', 'plggithub_show_summary_graph');

add_action('wp_enqueue_scripts', 'plggithub_setup_style');
function plggithub_setup_style() {
    wp_register_style( 'plggithub_style', plugins_url("css/style.css",__FILE__) );
    wp_enqueue_style( 'plggithub_style' );
}
