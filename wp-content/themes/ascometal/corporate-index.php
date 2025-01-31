<?php
/*
Template Name: liste pages corporate
*/

get_header(); ?>
<?php 
$contexte_blocs="nc";
$idBloc=0;
//global $classeRubrique;
global $contexte_blocs;
global $idBloc;
?>

    <div id="page" role="main">
        <?php get_template_part( 'template-parts/header-banner-marches' ); ?>
        
        <article class="corporate">
			<h1>Ascometal</h1>
			
			<div class="row">
				<div class="small-12 columns" <?php post_class( 'main-content') ?> id="post-<?php the_ID(); ?>">
						<?php do_action( 'foundationpress_page_before_entry_content' ); ?>

					<?php /* récupère une liste de toutes les pages */ ?>
					<?php 
                    $args = array(
                        'sort_order' => 'asc',
                        'sort_column' => 'menu_order',
                        'post_type' => 'page',
                        'post_status' => 'publish'
                     ); 
					$pages = get_pages($args);
					foreach ($pages as $page_data) {
						$pageID = 0;
						$pageID = $page_data->ID;
						$idBloc=$pageID;
						$contexte_blocs="avecID";
	//					$classeRubrique="corporate";

						/* si la case "afficher dans les blocs" est cochée, on affiche la page */
						if (types_render_field("afficher-bloc", array("output"=>"raw", "post_id"=>$pageID)) == 1) :
							get_template_part( 'template-parts/content', 'blocs' ); 
						endif;
					}
					?>

				</div>
			</div>
        </article>
    </div>
    <?php get_footer(); ?>