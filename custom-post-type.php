<?php 
// Função para registrar o custom post type
function register_imoveis_post_type() {
    $labels = array(
        'name' => 'Imóveis',
        'singular_name' => 'Imóvel',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
        'hierarchical' => true, // Adiciona suporte a categorias multinível
        'taxonomies' => array('category', 'post_tag'), // Habilita categorias no editor de posts
    );

    register_post_type('imovel', $args);

    // Registra a taxonomia Building Features
    register_taxonomy('building_features', 'imovel', array(
        'label' => 'Building Features',
        'hierarchical' => true,
        'public' => true,
        'rewrite' => array('slug' => 'building-features'),
    ));
}
add_action('init', 'register_imoveis_post_type');

?>
