<?php
/**
 * Plugin Name: Virtual DWV
 * Description: Descrição do meu plugin.
 * Version: 1.0.0
 * Author: Raphael Reis
 * Author URI: https://exemplo.com/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Seu código começa aqui
// Inclua o arquivo custom-fields.php

require_once(plugin_dir_path(__FILE__) . 'custom-fields.php');
require_once(plugin_dir_path(__FILE__) . 'custom-post-type.php');
require_once plugin_dir_path(__FILE__) . '/inc/dmv-integration-ajax-sync.php';
require_once plugin_dir_path(__FILE__) . '/inc/log-file.php';

// Adicione as configurações de autenticação da API da DWV
define('DWV_API_ENDPOINT', get_option('dwv_integration_url', ''));
define('DWV_API_TOKEN', get_option('dwv_integration_token', ''));

// Adicione a função dwv_integration_get_imoveis se não existir
if (!function_exists('dwv_integration_get_imoveis')) {
    function dwv_integration_get_imoveis()
    {
        // Configurações da API
        $endpoint = DWV_API_ENDPOINT . '/integration/properties';
        $token = DWV_API_TOKEN;

        // Cabeçalho com o token de autenticação
        $headers = array(
            'Authorization' => 'Bearer ' . $token
        );

        // Faz a requisição à API
        $response = wp_remote_get($endpoint, array(
            'headers' => $headers
        ));

        // Verifica se a requisição foi bem-sucedida
        if (is_wp_error($response)) {
            return false;
        }

        // Processa a resposta da API
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Retorna os imóveis
        return $data['data'];
    }
}

// Adiciona a página de administração
function dwv_integration_add_admin_page()
{
    add_menu_page(
        'DWV Integration',
        'DWV Integration',
        'manage_options',
        'dwv-integration',
        'dwv_integration_admin_page',
        'dashicons-admin-generic',
        20
    );
}
add_action('admin_menu', 'dwv_integration_add_admin_page');

// Callback da página de administração
function dwv_integration_admin_page()
{
    // Verifica se o usuário tem permissão para acessar a página de administração
    if (!current_user_can('manage_options')) {
        return;
    }

    // Processa o envio do formulário (se houver)
    if (isset($_POST['dwv_integration_settings'])) {
        $token = sanitize_text_field($_POST['dwv_integration_token']);
        $url = sanitize_text_field($_POST['dwv_integration_url']);

        update_option('dwv_integration_token', $token);
        update_option('dwv_integration_url', $url);
    }

    // Obtém as configurações atuais
    $current_token = get_option('dwv_integration_token', '');
    $current_url = get_option('dwv_integration_url', '');

    ?>
    <div class="wrap">
        <h1>DWV Integration</h1>
        <form method="post" action="">
            <h2>Configurações</h2>
            <label for="dwv_integration_token">Token de Autenticação:</label>
            <input type="text" name="dwv_integration_token" id="dwv_integration_token" value="<?php echo esc_attr($current_token); ?>">
            
            <label for="dwv_integration_url">URL da API:</label>
            <input type="text" name="dwv_integration_url" id="dwv_integration_url" value="<?php echo esc_attr($current_url); ?>">

            <?php submit_button('Salvar Configurações', 'primary', 'dwv_integration_settings'); ?>
        </form>

        <hr>

        <div class="lastUpdateInfos">
            <span class="lastUpdateDate"></span>
        </div>
        <h2>Sincronização manual</h2>
        <p>Clique no botão abaixo para sincronizar agora manualmente:</p>
        <button id="syncImoveis" class="btn btn-warning">Sincronizar Agora</button>
        <div class="progress-showing">
            <div class="spinnerLoading">
                <div class="spinnerA"></div>
            </div>
            <div>
                <h3 class="progress-label"></h3>
                <p class="textWarning">Isto pode levar um tempo, aguarde nesta tela até o fim do processo.</p>
            </div>
        </div>

        <hr>

        <h2>Testar Conexão</h2>
        <form method="post" action="">
            <?php submit_button('Testar Conexão', 'secondary', 'dwv_integration_test_connection'); ?>
        </form>
        <?php
        if (isset($_POST['dwv_integration_test_connection'])) {
            $connection_status = dwv_integration_test_connection();
            ?>
            <p style="color:<?php echo $connection_status['color']; ?>"><?php echo $connection_status['message']; ?></p>
            <?php
        }
        ?>
    </div>
    <?php
}

// Função para testar a conexão com a API
function dwv_integration_test_connection()
{
    $token = get_option('dwv_integration_token', '');
    $url = get_option('dwv_integration_url', '');

    // Verifica se as configurações estão preenchidas
    if (empty($token) || empty($url)) {
        return array(
            'message' => 'Erro: As configurações estão incompletas.',
            'color' => 'red'
        );
    }

    // Configurações da API
    $endpoint = $url . '/integration/properties';
    $headers = array(
        'Authorization' => 'Bearer ' . $token
    );

    // Faz uma requisição de teste à API
    $response = wp_remote_get($endpoint, array(
        'headers' => $headers
    ));

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        return array(
            'message' => 'A conexão com a API foi bem-sucedida.',
            'color' => 'green'
        );
    } else {
        return array(
            'message' => 'Erro: Não foi possível estabelecer conexão com a API.',
            'color' => 'red'
        );
    }
}

// Função para sincronizar diariamente
function dwv_integration_sync_daily()
{
    // Consulta à API da DWV
    $imoveis = dwv_integration_get_imoveis();

    // XXXXXXX   R E M O V E   I M O V E I S   XXXXXXXXXXXXXXXXX

    // Variável para armazenar os títulos dos imóveis
    $imoveis_api_names = array();

    // Loop pelos itens do array
    foreach ($imoveis as $item) {
        // Verifica se a chave 'titulo' existe no item
        if (isset($item['title'])) {
            // Adiciona o título à variável $imoveis_api_names
            $imoveis_api_names[] = $item['title'];
        }
    }

    // Obtém todos os posts do tipo 'imovel'
    $args = array(
        'post_type' => 'imovel',
        'posts_per_page' => -1, // -1 para obter todos os posts do tipo 'imovel'
    );

    $imoveis_query = new WP_Query($args);

    // Verifica se há posts
    if ($imoveis_query->have_posts()) {
        // Inicializa o array para armazenar os títulos dos imóveis existentes
        $existentes_imoveis = array();

        // Loop pelos posts do tipo 'imovel'
        while ($imoveis_query->have_posts()) {
            $imoveis_query->the_post();

            // Adiciona o título do post ao array
            $existentes_imoveis[] = get_the_title();
        }

        // Restaura os dados originais do post
        wp_reset_postdata();
    } else {
        // Caso não haja posts do tipo 'imovel'
        $existentes_imoveis = array();
    }

    // POSTS A SEREM EXCLUÍDOS

    foreach ($existentes_imoveis as $titulo_existente) {
        if (!in_array($titulo_existente, $imoveis_api_names)) {
            $args = array(
                'name' => $titulo_existente,
                'post_type' => 'imovel',
                'numberposts' => 1,
                'post_status' => 'any',
            );

            $posts = get_posts($args);

            if ($posts) {
                foreach ($posts as $post) {
                    // Envia o post para a lixeira
                    wp_trash_post($post->ID);
                }
            }
        }
    }

    // Verifica se há imóveis retornados pela API
    if (!empty($imoveis)) {
        foreach ($imoveis as $imovel) {
            // Verifica se o imóvel já existe no WordPress
            $existing_post = get_page_by_title($imovel['title'], OBJECT, 'imovel');

            if ($existing_post) {
                // Extrai a data de atualização do imóvel existente
                $existing_last_updated_at = get_post_meta($existing_post->ID, 'last_updated_at', true);
        
                // Verifica se a data de atualização do imóvel existente é menor do que a nova data de atualização
                if ($existing_last_updated_at && strtotime($existing_last_updated_at) >= strtotime($imovel['last_updated_at'])) {
                    continue; // Pula para o próximo imóvel se a data existente for maior ou igual
                }

            // Extrai o  a ultima atualização
            $constructionStage = isset($imovel['construction_stage']) ? $imovel['construction_stage'] : null;
            update_post_meta($existing_post->ID, 'construction_stage', $constructionStage);
            
            // Extrai o  a ultima atualização
            $last_updated_at = isset($imovel['last_updated_at']) ? $imovel['last_updated_at'] : null;
            update_post_meta($existing_post->ID, 'last_updated_at', $last_updated_at);

            $imovel_status = isset($imovel['status']) ? $imovel['status'] : null;
            update_post_meta($existing_post->ID, 'imovel_status', $imovel_status);

            // Extrai o tipo de exibição dos imoveis
            $address_display_type = isset($imovel['address_display_type']) ? $imovel['address_display_type'] : null;
            update_field('address_display_type', $address_display_type, $existing_post->ID);

            // XXXXXXXXXXXXXXXXXX   U N I D A D E   XXXXXXXXXXXXXXXXXXXXX

            // Extrai o ID da unidade do apartamento
            $apartmentUnitId = isset($imovel['unit']['id']) ? $imovel['unit']['id'] : null;
            update_post_meta($existing_post->ID, 'apartment_unit_id', $apartmentUnitId);

            // Extrai o título do apartamento
            $apartmentTitle = isset($imovel['unit']['title']) ? $imovel['unit']['title'] : null;
            update_post_meta($existing_post->ID, 'apartment_title', $apartmentTitle);

            // Extrai o preço do apartamento
            $apartmentPrice = isset($imovel['unit']['price']) ? $imovel['unit']['price'] : null;
            update_post_meta($existing_post->ID, 'apartment_price', $apartmentPrice);

            // Extrai o tipo do apartamento
            $apartmentType = isset($imovel['unit']['type']) ? $imovel['unit']['type'] : null;
            update_post_meta($existing_post->ID, 'apartment_type', $apartmentType);

            // Extrai o tipo do apartamento
            $apartmentFloorPlanTitle = isset($imovel['unit']['floor_plan']['category']['title']) ? $imovel['unit']['floor_plan']['category']['title'] : null;
            update_field('apartment_floor_plan_title', $apartmentFloorPlanTitle, $existing_post->ID);


            $apartmentFloorPlanTag = isset($imovel['unit']['floor_plan']['category']['tag']) ? $imovel['unit']['floor_plan']['category']['tag'] : null;
            update_field('apartment_floor_plan_tag', $apartmentFloorPlanTag, $existing_post->ID);

            // Extrai o número de vagas de garagem do apartamento
            $apartmentParkingSpaces = isset($imovel['unit']['parking_spaces']) ? $imovel['unit']['parking_spaces'] : null;
            update_post_meta($existing_post->ID, 'apartment_parking_spaces', $apartmentParkingSpaces);

            // Extrai o número de quartos do apartamento
            $apartmentBedrooms = isset($imovel['unit']['dorms']) ? $imovel['unit']['dorms'] : null;
            update_post_meta($existing_post->ID, 'apartment_bedrooms', $apartmentBedrooms);

            // Extrai o número de suítes do apartamento
            $apartmentSuites = isset($imovel['unit']['suites']) ? $imovel['unit']['suites'] : null;
            update_post_meta($existing_post->ID, 'apartment_suites', $apartmentSuites);

            // Extrai o número de banheiros do apartamento
            $apartmentBathrooms = isset($imovel['unit']['bathroom']) ? $imovel['unit']['bathroom'] : null;
            update_post_meta($existing_post->ID, 'apartment_bathrooms', $apartmentBathrooms);

            // Extrai a área privada do apartamento
            $apartmentPrivateArea = isset($imovel['unit']['private_area']) ? $imovel['unit']['private_area'] : null;
            update_post_meta($existing_post->ID, 'apartment_private_area', $apartmentPrivateArea);

            // Extrai a área útil do apartamento
            $apartmentUtilArea = isset($imovel['unit']['util_area']) ? $imovel['unit']['util_area'] : null;
            update_post_meta($existing_post->ID, 'apartment_util_area', $apartmentUtilArea);

            // Extrai a área total do apartamento
            $apartmentTotalArea = isset($imovel['unit']['total_area']) ? $imovel['unit']['total_area'] : null;
            update_post_meta($existing_post->ID, 'apartment_total_area', $apartmentTotalArea);

            // Extrai o estágio de construção
            $apartmentRent = isset($imovel['unit']['rent']) ? $imovel['unit']['rent'] : null;
            update_field('apartment_rent', $apartmentRent, $existing_post->ID);

            $apartmentPaymentConditionsTitle = isset($imovel['unit']['payment_conditions'][0]['title']) ? $imovel['unit']['payment_conditions'][0]['title'] : null;
            update_field('apartment_payment_conditions_title', $apartmentPaymentConditionsTitle, $existing_post->ID);

            $apartmentPaymentConditionsOperatorTitle = isset($imovel['unit']['payment_conditions'][0]['operator']['title']) ? $imovel['unit']['payment_conditions'][0]['operator']['title'] : null;
            update_field('apartment_payment_conditions_operator_title', $apartmentPaymentConditionsOperatorTitle, $existing_post->ID);

            $apartmentPaymentConditionsOperatorType = isset($imovel['unit']['payment_conditions'][0]['operator']['type']) ? $imovel['unit']['payment_conditions'][0]['operator']['type'] : null;
            update_field('apartment_payment_conditions_operator_type', $apartmentPaymentConditionsOperatorType, $existing_post->ID);

            $apartmentPaymentConditionsValue = isset($imovel['unit']['payment_conditions'][0]['value']) ? $imovel['unit']['payment_conditions'][0]['value'] : null;
            update_field('apartment_payment_conditions_value', $apartmentPaymentConditionsValue, $existing_post->ID);
            
            // XXXXXXXXXXXXXXXXXX   B U I L D I N G   XXXXXXXXXXXXXXXXXXXXX
        
            $buildingId = isset($imovel['building']['id']) ? $imovel['building']['id'] : null;
            update_post_meta($existing_post->ID, 'building_id', $buildingId);

            $buildingTitle = isset($imovel['building']['title']) ? $imovel['building']['title'] : null;
            update_post_meta($existing_post->ID, 'building_title', $buildingTitle); 

            $buildingGallery = isset($imovel['building']['gallery']) ? $imovel['building']['gallery'] : null;
            $processedGallery = [];
            // Loop para processar imagens da galeria
            if ($buildingGallery) {
                $index = 1;

                foreach ($buildingGallery as $image) {
                    if (isset($image['url'])) {
                        $url = $image['url'];
                        $tmp_name = download_url($url);

                        if (!is_wp_error($tmp_name)) {
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);

                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name' => basename($url),
                                    'tmp_name' => $tmp_name
                                );

                                $attachment_id = media_handle_sideload($file_array, 0);

                                if (!is_wp_error($attachment_id)) {
                                    $processedGallery[] = $attachment_id;
                                } else {
                                    log_to_file('Erro ao adicionar imagem da galeria: ' . $attachment_id->get_error_message());
                                }
                            } else {
                                log_to_file('Erro ao redimensionar imagem da galeria: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da imagem da galeria: ' . $tmp_name->get_error_message());
                        }
                    }
                    $index++;
                }
            }
            // Atualizar campos ACF com as imagens processadas
            if (!empty($processedGallery)) {
                update_field('field_building_gallery', $processedGallery, $existing_post->ID);
            }

            $buildingArchitecturalPlans = isset($imovel['building']['architectural_plans']) ? $imovel['building']['architectural_plans'] : null;
            $processedArchitecturalPlans = [];
            // Loop para processar imagens das plantas arquitetônicas
            if ($buildingArchitecturalPlans) {
                $index = 1;

                foreach ($buildingArchitecturalPlans as $image) {
                    if (isset($image['url'])) {
                        $url = $image['url'];
                        $tmp_name = download_url($url);

                        if (!is_wp_error($tmp_name)) {
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);

                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name' => basename($url),
                                    'tmp_name' => $tmp_name
                                );

                                $attachment_id = media_handle_sideload($file_array, 0);

                                if (!is_wp_error($attachment_id)) {
                                    $processedArchitecturalPlans[] = $attachment_id;
                                } else {
                                    log_to_file('Erro ao adicionar planta arquitetônica: ' . $attachment_id->get_error_message());
                                }
                            } else {
                                log_to_file('Erro ao redimensionar planta arquitetônica: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da planta arquitetônica: ' . $tmp_name->get_error_message());
                        }
                    }
                    $index++;
                }
            }
            if (!empty($processedArchitecturalPlans)) {
                update_field('field_apartment_additional_galleries', $processedArchitecturalPlans, $existing_post->ID);
            }
            
            $buildingVideo = isset($imovel['building']['video']) ? $imovel['building']['video'] : null;
            update_post_meta($existing_post->ID, 'video_url', $buildingVideo);

            $buildingTour360 = isset($imovel['building']['tour_360']) ? $imovel['building']['tour_360'] : null;
            update_post_meta($existing_post->ID, 'tour360_url', $buildingTour360); 


            $buildingDescription = isset($imovel['building']['description']) ? $imovel['building']['description'] : null;
            update_field('building_description', $buildingDescription, $existing_post->ID);
            
            
            // Extrai endereço do building
            $streetName = isset($imovel['building']['address']['street_name']) ? $imovel['building']['address']['street_name'] : null;
            update_field('field_street_name', $streetName, $existing_post->ID);
            $streetNumber = isset($imovel['building']['address']['street_number']) ? $imovel['building']['address']['street_number'] : null;
            update_field('field_street_number', $streetNumber, $existing_post->ID);
            $neighborhood = isset($imovel['building']['address']['neighborhood']) ? $imovel['building']['address']['neighborhood'] : null;
            update_field('field_neighborhood', $neighborhood, $existing_post->ID);
            $complement = isset($imovel['building']['address']['complement']) ? $imovel['building']['address']['complement'] : null;
            update_field('field_complement', $complement, $existing_post->ID);
            $zipCode = isset($imovel['building']['address']['zip_code']) ? $imovel['building']['address']['zip_code'] : null;
            update_field('field_zip_code', $zipCode, $existing_post->ID);
            $city = isset($imovel['building']['address']['city']) ? $imovel['building']['address']['city'] : null;              
            update_field('field_city', $city, $existing_post->ID);
            $state = isset($imovel['building']['address']['state']) ? $imovel['building']['address']['state'] : null;
            update_field('field_state', $state, $existing_post->ID);
            $country = isset($imovel['building']['address']['country']) ? $imovel['building']['address']['country'] : null;
            update_field('field_country', $country, $existing_post->ID);
            $latitude = isset($imovel['building']['address']['latitude']) ? $imovel['building']['address']['latitude'] : null;
            update_field('field_latitude', $latitude, $existing_post->ID);
            $longitude = isset($imovel['building']['address']['longitude']) ? $imovel['building']['address']['longitude'] : null;
            update_field('field_longitude', $longitude, $existing_post->ID);
        
                        
            // O campo "address" conterá os detalhes do endereço do edifício.
            
            $buildingTextAddress = isset($imovel['building']['text_address']) ? $imovel['building']['text_address'] : null;
            update_field('building_text_address', $buildingTextAddress, $existing_post->ID);

            
            // O campo "text_address" conterá o endereço formatado do edifício.
            
            $buildingIncorporation = isset($imovel['building']['incorporation']) ? $imovel['building']['incorporation'] : null;
            update_field('building_incorporation', $buildingIncorporation, $existing_post->ID);

            
            // O campo "incorporation" conterá informações sobre a incorporação do edifício.
            
            $buildingCover = isset($imovel['building']['cover']) ? $imovel['building']['cover'] : null;
            $coverUrl = null;
            if ($buildingCover && isset($buildingCover['url'])) {
                $coverUrl = $buildingCover['url'];
            
                // Baixa a imagem
                $tmp_name = download_url($coverUrl);
            
                if (!is_wp_error($tmp_name)) {
                    // Redimensiona e comprime a imagem
                    $image_data = wp_get_image_editor($tmp_name);
            
                    if (!is_wp_error($image_data)) {
                        $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                        $image_data->save($tmp_name);
            
                        // Adiciona a imagem otimizada ao WordPress
                        $file_array = array(
                            'name' => basename($coverUrl),
                            'tmp_name' => $tmp_name
                        );
            
                        $attachment_id = media_handle_sideload($file_array, 0);
            
                        if (!is_wp_error($attachment_id)) {
                            // Adiciona a imagem à galeria de imagens do post
                            $gallery = get_post_gallery($existing_post->ID, false);
            
                            if (empty($gallery)) {
                                $gallery = $attachment_id;
                            } else {
                                $gallery .= "," . $attachment_id;
                            }
            
                            // Atualiza a galeria de imagens do post
                            update_post_meta($existing_post->ID, '_gallery_images', $gallery);
            
                            // Define a imagem como imagem principal (imagem em destaque) do post
                            set_post_thumbnail($existing_post->ID, $attachment_id);
            
                        } else {
                            log_to_file('Erro ao adicionar imagem da capa: ' . $attachment_id->get_error_message());
                        }
                    } else {
                        log_to_file('Erro ao redimensionar imagem da capa: ' . $image_data->get_error_message());
                    }
                } else {
                    log_to_file('Erro ao fazer download da imagem da capa: ' . $tmp_name->get_error_message());
                }
            } else {
                // Se não houver uma imagem de capa, configure uma imagem padrão
                $default_image_url = home_url('/wp-content/plugins/elementor/assets/images/placeholder.png'); // O caminho para a imagem padrão começa a partir do diretório raiz do WordPress
            
                $tmp_name = download_url($default_image_url);
            
                if (!is_wp_error($tmp_name)) {
                    $image_data = wp_get_image_editor($tmp_name);
            
                    if (!is_wp_error($image_data)) {
                        $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                        $image_data->save($tmp_name);
            
                        $file_array = array(
                            'name' => basename($default_image_url),
                            'tmp_name' => $tmp_name
                        );
            
                        $attachment_id = media_handle_sideload($file_array, 0);
            
                        if (!is_wp_error($attachment_id)) {
                            // Define a imagem padrão como imagem em destaque do post
                            set_post_thumbnail($existing_post->ID, $attachment_id);
            
                        } else {
                            log_to_file('Erro ao adicionar imagem padrão como capa: ' . $attachment_id->get_error_message());
                        }
                    } else {
                        log_to_file('Erro ao redimensionar imagem padrão: ' . $image_data->get_error_message());
                    }
                } else {
                    log_to_file('Erro ao fazer download da imagem padrão: ' . $tmp_name->get_error_message());
                }
            }
            

            $buildingFeatures = isset($imovel['building']['features']) ? $imovel['building']['features'] : null;
            if ($buildingFeatures) {
                $featureTags = [];
                $featureTypes = [];

                foreach ($buildingFeatures as $feature) {
                    if (isset($feature['tags']) && is_array($feature['tags'])) {
                        $featureTags = array_merge($featureTags, $feature['tags']);
                    }

                    if (isset($feature['type'])) {
                        $featureTypes[] = $feature['type'];
                    }
                }

                if (!empty($featureTags)) {
                    wp_set_post_terms($existing_post->ID, $featureTags, 'post_tag', true);
                }
                
                // Adicionar os tipos como tags ao post
                if (!empty($featureTypes)) {
                    wp_set_object_terms($existing_post->ID, $featureTypes, 'building_features', true);
                }
            }

            // O campo "delivery_date" conterá a data de entrega do edifício.
            
            $buildingDeliveryDate = isset($imovel['building']['delivery_date']) ? $imovel['building']['delivery_date'] : null;
            update_field('building_delivery_date', $buildingDeliveryDate, $existing_post->ID);
        

            // XXXXXXXXXXXXXXXXXX   C O N S T R U T O R A    XXXXXXXXXXXXXXXXXXXXX

            $constructionCompanyTitle = isset($imovel['construction_company']['title']) ? $imovel['construction_company']['title'] : null;
            update_field('construction_company_title', $constructionCompanyTitle, $existing_post->ID);

            $constructionCompanyWebsite = isset($imovel['construction_company']['site']) ? $imovel['construction_company']['site'] : null;
            update_field('construction_company_website', $constructionCompanyWebsite, $existing_post->ID);

            $constructionCompanyWhatsapp = isset($imovel['construction_company']['whatsapp']) ? $imovel['construction_company']['whatsapp'] : null;
            update_field('construction_company_whatsapp', $constructionCompanyWhatsapp, $existing_post->ID);

            $constructionCompanyBusinessContact = isset($imovel['construction_company']['business_contacts']) ? $imovel['construction_company']['business_contacts'] : null;
            update_field('construction_company_business_contacts', $constructionCompanyBusinessContact, $existing_post->ID);

            $constructionCompanyAdditionalContacts = isset($imovel['construction_company']['additionals_contacts']) ? $imovel['construction_company']['additionals_contacts'] : null;
            update_field('construction_company_additionals_contacts', $constructionCompanyAdditionalContacts, $existing_post->ID);


            $constructionCompanyInstagram = isset($imovel['construction_company']['instagram']) ? $imovel['construction_company']['instagram'] : null;
            update_field('construction_company_instagram', $constructionCompanyInstagram, $existing_post->ID);

            $constructionCompanyLogo = isset($imovel['construction_company']['logo']['url']) ? $imovel['construction_company']['logo']['url'] : null;
            update_field('construction_company_logo', $constructionCompanyLogo, $existing_post->ID);


            // Remove o post da Lixeira
            $args = array(
                'name' => $imovel['title'],
                'post_type' => 'imovel',
                'numberposts' => 1,
                'post_status' => 'any',
            );
    
            $posts = get_posts($args);
    
            if ($posts) {
                foreach ($posts as $post) {
                    // Envia o post para a lixeira
                    wp_publish_post($post->ID);
                }
            }
        }else {
            // Cria um novo post do tipo 'imovel'
            $new_post = array(
                'post_title' => $imovel['title'], // Título do imóvel
                'post_content' => $imovel['description'], // Descrição do imóvel
                'post_status' => 'publish',
                'post_type' => 'imovel',
            );
            // Insere o novo post
            $post_id = wp_insert_post($new_post);

            // XXXXXXXXXXXXXX M E T A D A D O S XXXXXXXXXXXXXXXXXXXXXX
            update_post_meta($post_id, 'id', $imovel['id']);
            update_post_meta($post_id, 'title', $imovel['title']);
            update_post_meta($post_id, 'description', $imovel['description']);
            
            
            // Extrai o  a ultima atualização
            $constructionStage = isset($imovel['construction_stage']) ? $imovel['construction_stage'] : null;
            update_post_meta($post_id, 'construction_stage', $constructionStage);
            
            // Extrai o  a ultima atualização
            $last_updated_at = isset($imovel['last_updated_at']) ? $imovel['last_updated_at'] : null;
            update_post_meta($post_id, 'last_updated_at', $last_updated_at);

            $imovel_status = isset($imovel['status']) ? $imovel['status'] : null;
            update_post_meta($post_id, 'imovel_status', $imovel_status);

            // Extrai o tipo de exibição dos imoveis
            $address_display_type = isset($imovel['address_display_type']) ? $imovel['address_display_type'] : null;
            update_field('address_display_type', $address_display_type, $post_id);

            // XXXXXXXXXXXXXXXXXX   U N I D A D E   XXXXXXXXXXXXXXXXXXXXX

            // Extrai o ID da unidade do apartamento
            $apartmentUnitId = isset($imovel['unit']['id']) ? $imovel['unit']['id'] : null;
            update_post_meta($post_id, 'apartment_unit_id', $apartmentUnitId);

            // Extrai o título do apartamento
            $apartmentTitle = isset($imovel['unit']['title']) ? $imovel['unit']['title'] : null;
            update_post_meta($post_id, 'apartment_title', $apartmentTitle);

            // Extrai o preço do apartamento
            $apartmentPrice = isset($imovel['unit']['price']) ? $imovel['unit']['price'] : null;
            update_post_meta($post_id, 'apartment_price', $apartmentPrice);

            // Extrai o tipo do apartamento
            $apartmentType = isset($imovel['unit']['type']) ? $imovel['unit']['type'] : null;
            update_post_meta($post_id, 'apartment_type', $apartmentType);

            // Extrai o tipo do apartamento
            $apartmentFloorPlanTitle = isset($imovel['unit']['floor_plan']['category']['title']) ? $imovel['unit']['floor_plan']['category']['title'] : null;
            update_field('apartment_floor_plan_title', $apartmentFloorPlanTitle, $post_id);


            $apartmentFloorPlanTag = isset($imovel['unit']['floor_plan']['category']['tag']) ? $imovel['unit']['floor_plan']['category']['tag'] : null;
            update_field('apartment_floor_plan_tag', $apartmentFloorPlanTag, $post_id);

            // Extrai o número de vagas de garagem do apartamento
            $apartmentParkingSpaces = isset($imovel['unit']['parking_spaces']) ? $imovel['unit']['parking_spaces'] : null;
            update_post_meta($post_id, 'apartment_parking_spaces', $apartmentParkingSpaces);

            // Extrai o número de quartos do apartamento
            $apartmentBedrooms = isset($imovel['unit']['dorms']) ? $imovel['unit']['dorms'] : null;
            update_post_meta($post_id, 'apartment_bedrooms', $apartmentBedrooms);

            // Extrai o número de suítes do apartamento
            $apartmentSuites = isset($imovel['unit']['suites']) ? $imovel['unit']['suites'] : null;
            update_post_meta($post_id, 'apartment_suites', $apartmentSuites);

            // Extrai o número de banheiros do apartamento
            $apartmentBathrooms = isset($imovel['unit']['bathroom']) ? $imovel['unit']['bathroom'] : null;
            update_post_meta($post_id, 'apartment_bathrooms', $apartmentBathrooms);

            // Extrai a área privada do apartamento
            $apartmentPrivateArea = isset($imovel['unit']['private_area']) ? $imovel['unit']['private_area'] : null;
            update_post_meta($post_id, 'apartment_private_area', $apartmentPrivateArea);

            // Extrai a área útil do apartamento
            $apartmentUtilArea = isset($imovel['unit']['util_area']) ? $imovel['unit']['util_area'] : null;
            update_post_meta($post_id, 'apartment_util_area', $apartmentUtilArea);

            // Extrai a área total do apartamento
            $apartmentTotalArea = isset($imovel['unit']['total_area']) ? $imovel['unit']['total_area'] : null;
            update_post_meta($post_id, 'apartment_total_area', $apartmentTotalArea);

            // Extrai o estágio de construção
            $apartmentRent = isset($imovel['unit']['rent']) ? $imovel['unit']['rent'] : null;
            update_field('apartment_rent', $apartmentRent, $post_id);

            $apartmentPaymentConditionsTitle = isset($imovel['unit']['payment_conditions'][0]['title']) ? $imovel['unit']['payment_conditions'][0]['title'] : null;
            update_field('apartment_payment_conditions_title', $apartmentPaymentConditionsTitle, $post_id);

            $apartmentPaymentConditionsOperatorTitle = isset($imovel['unit']['payment_conditions'][0]['operator']['title']) ? $imovel['unit']['payment_conditions'][0]['operator']['title'] : null;
            update_field('apartment_payment_conditions_operator_title', $apartmentPaymentConditionsOperatorTitle, $post_id);

            $apartmentPaymentConditionsOperatorType = isset($imovel['unit']['payment_conditions'][0]['operator']['type']) ? $imovel['unit']['payment_conditions'][0]['operator']['type'] : null;
            update_field('apartment_payment_conditions_operator_type', $apartmentPaymentConditionsOperatorType, $post_id);

            $apartmentPaymentConditionsValue = isset($imovel['unit']['payment_conditions'][0]['value']) ? $imovel['unit']['payment_conditions'][0]['value'] : null;
            update_field('apartment_payment_conditions_value', $apartmentPaymentConditionsValue, $post_id);
            
            // XXXXXXXXXXXXXXXXXX   B U I L D I N G   XXXXXXXXXXXXXXXXXXXXX
        
            $buildingId = isset($imovel['building']['id']) ? $imovel['building']['id'] : null;
            update_post_meta($post_id, 'building_id', $buildingId);

            $buildingTitle = isset($imovel['building']['title']) ? $imovel['building']['title'] : null;
            update_post_meta($post_id, 'building_title', $buildingTitle); 

            $buildingGallery = isset($imovel['building']['gallery']) ? $imovel['building']['gallery'] : null;
            $processedGallery = [];
            // Loop para processar imagens da galeria
            if ($buildingGallery) {
                $index = 1;

                foreach ($buildingGallery as $image) {
                    if (isset($image['url'])) {
                        $url = $image['url'];
                        $tmp_name = download_url($url);

                        if (!is_wp_error($tmp_name)) {
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);

                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name' => basename($url),
                                    'tmp_name' => $tmp_name
                                );

                                $attachment_id = media_handle_sideload($file_array, 0);

                                if (!is_wp_error($attachment_id)) {
                                    $processedGallery[] = $attachment_id;
                                } else {
                                    log_to_file('Erro ao adicionar imagem da galeria: ' . $attachment_id->get_error_message());
                                }
                            } else {
                                log_to_file('Erro ao redimensionar imagem da galeria: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da imagem da galeria: ' . $tmp_name->get_error_message());
                        }
                    }
                    $index++;
                }
            }
            // Atualizar campos ACF com as imagens processadas
            if (!empty($processedGallery)) {
                update_field('field_building_gallery', $processedGallery, $post_id);
            }

            $buildingArchitecturalPlans = isset($imovel['building']['architectural_plans']) ? $imovel['building']['architectural_plans'] : null;
            $processedArchitecturalPlans = [];
            // Loop para processar imagens das plantas arquitetônicas
            if ($buildingArchitecturalPlans) {
                $index = 1;

                foreach ($buildingArchitecturalPlans as $image) {
                    if (isset($image['url'])) {
                        $url = $image['url'];
                        $tmp_name = download_url($url);

                        if (!is_wp_error($tmp_name)) {
                            // Redimensiona e comprime a imagem
                            $image_data = wp_get_image_editor($tmp_name);
                            if (!is_wp_error($image_data)) {
                                $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                                $image_data->save($tmp_name);

                                // Adiciona a imagem otimizada ao WordPress
                                $file_array = array(
                                    'name' => basename($url),
                                    'tmp_name' => $tmp_name
                                );

                                $attachment_id = media_handle_sideload($file_array, 0);

                                if (!is_wp_error($attachment_id)) {
                                    $processedArchitecturalPlans[] = $attachment_id;
                                } else {
                                    log_to_file('Erro ao adicionar planta arquitetônica: ' . $attachment_id->get_error_message());
                                }
                            } else {
                                log_to_file('Erro ao redimensionar planta arquitetônica: ' . $image_data->get_error_message());
                            }
                        } else {
                            log_to_file('Erro ao fazer download da planta arquitetônica: ' . $tmp_name->get_error_message());
                        }
                    }
                    $index++;
                }
            }
            if (!empty($processedArchitecturalPlans)) {
                update_field('field_apartment_additional_galleries', $processedArchitecturalPlans, $post_id);
            }
            
            $buildingVideo = isset($imovel['building']['video']) ? $imovel['building']['video'] : null;
            update_post_meta($post_id, 'video_url', $buildingVideo);

            $buildingTour360 = isset($imovel['building']['tour_360']) ? $imovel['building']['tour_360'] : null;
            update_post_meta($post_id, 'tour360_url', $buildingTour360); 


            
            $buildingDescription = isset($imovel['building']['description']) ? $imovel['building']['description'] : null;
            update_field('building_description', $buildingDescription, $post_id);
            
            // Extrai endereço do building
            $streetName = isset($imovel['building']['address']['street_name']) ? $imovel['building']['address']['street_name'] : null;
            update_field('field_street_name', $streetName, $post_id);
            $streetNumber = isset($imovel['building']['address']['street_number']) ? $imovel['building']['address']['street_number'] : null;
            update_field('field_street_number', $streetNumber, $post_id);
            $neighborhood = isset($imovel['building']['address']['neighborhood']) ? $imovel['building']['address']['neighborhood'] : null;
            update_field('field_neighborhood', $neighborhood, $post_id);
            $complement = isset($imovel['building']['address']['complement']) ? $imovel['building']['address']['complement'] : null;
            update_field('field_complement', $complement, $post_id);
            $zipCode = isset($imovel['building']['address']['zip_code']) ? $imovel['building']['address']['zip_code'] : null;
            update_field('field_zip_code', $zipCode, $post_id);
            $city = isset($imovel['building']['address']['city']) ? $imovel['building']['address']['city'] : null;              
            update_field('field_city', $city, $post_id);
            $state = isset($imovel['building']['address']['state']) ? $imovel['building']['address']['state'] : null;
            update_field('field_state', $state, $post_id);
            $country = isset($imovel['building']['address']['country']) ? $imovel['building']['address']['country'] : null;
            update_field('field_country', $country, $post_id);
            $latitude = isset($imovel['building']['address']['latitude']) ? $imovel['building']['address']['latitude'] : null;
            update_field('field_latitude', $latitude, $post_id);
            $longitude = isset($imovel['building']['address']['longitude']) ? $imovel['building']['address']['longitude'] : null;
            update_field('field_longitude', $longitude, $post_id);
        
                        
            // O campo "address" conterá os detalhes do endereço do edifício.
            
            $buildingTextAddress = isset($imovel['building']['text_address']) ? $imovel['building']['text_address'] : null;
            update_field('building_text_address', $buildingTextAddress, $post_id);

            
            // O campo "text_address" conterá o endereço formatado do edifício.
            
            $buildingIncorporation = isset($imovel['building']['incorporation']) ? $imovel['building']['incorporation'] : null;
            update_field('building_incorporation', $buildingIncorporation, $post_id);

            
            // O campo "incorporation" conterá informações sobre a incorporação do edifício.
            
            $buildingCover = isset($imovel['building']['cover']) ? $imovel['building']['cover'] : null;
            $coverUrl = null;
            if ($buildingCover && isset($buildingCover['url'])) {
                $coverUrl = $buildingCover['url'];
            
                // Baixa a imagem
                $tmp_name = download_url($coverUrl);
            
                if (!is_wp_error($tmp_name)) {
                    // Redimensiona e comprime a imagem
                    $image_data = wp_get_image_editor($tmp_name);
            
                    if (!is_wp_error($image_data)) {
                        $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                        $image_data->save($tmp_name);
            
                        // Adiciona a imagem otimizada ao WordPress
                        $file_array = array(
                            'name' => basename($coverUrl),
                            'tmp_name' => $tmp_name
                        );
            
                        $attachment_id = media_handle_sideload($file_array, 0);
            
                        if (!is_wp_error($attachment_id)) {
                            // Adiciona a imagem à galeria de imagens do post
                            $gallery = get_post_gallery($post_id, false);
            
                            if (empty($gallery)) {
                                $gallery = $attachment_id;
                            } else {
                                $gallery .= "," . $attachment_id;
                            }
            
                            // Atualiza a galeria de imagens do post
                            update_post_meta($post_id, '_gallery_images', $gallery);
            
                            // Define a imagem como imagem principal (imagem em destaque) do post
                            set_post_thumbnail($post_id, $attachment_id);
            
                        } else {
                            log_to_file('Erro ao adicionar imagem da capa: ' . $attachment_id->get_error_message());
                        }
                    } else {
                        log_to_file('Erro ao redimensionar imagem da capa: ' . $image_data->get_error_message());
                    }
                } else {
                    log_to_file('Erro ao fazer download da imagem da capa: ' . $tmp_name->get_error_message());
                }
            } else {
                // Se não houver uma imagem de capa, configure uma imagem padrão
                $default_image_url = home_url('/wp-content/plugins/elementor/assets/images/placeholder.png'); // O caminho para a imagem padrão começa a partir do diretório raiz do WordPress
            
                $tmp_name = download_url($default_image_url);
            
                if (!is_wp_error($tmp_name)) {
                    $image_data = wp_get_image_editor($tmp_name);
            
                    if (!is_wp_error($image_data)) {
                        $image_data->resize(800, 600, true); // Redimensiona para as dimensões desejadas
                        $image_data->save($tmp_name);
            
                        $file_array = array(
                            'name' => basename($default_image_url),
                            'tmp_name' => $tmp_name
                        );
            
                        $attachment_id = media_handle_sideload($file_array, 0);
            
                        if (!is_wp_error($attachment_id)) {
                            // Define a imagem padrão como imagem em destaque do post
                            set_post_thumbnail($post_id, $attachment_id);
            
                        } else {
                            log_to_file('Erro ao adicionar imagem padrão como capa: ' . $attachment_id->get_error_message());
                        }
                    } else {
                        log_to_file('Erro ao redimensionar imagem padrão: ' . $image_data->get_error_message());
                    }
                } else {
                    log_to_file('Erro ao fazer download da imagem padrão: ' . $tmp_name->get_error_message());
                }
            }
            

            $buildingFeatures = isset($imovel['building']['features']) ? $imovel['building']['features'] : null;
            if ($buildingFeatures) {
                $featureTags = [];
                $featureTypes = [];

                foreach ($buildingFeatures as $feature) {
                    if (isset($feature['tags']) && is_array($feature['tags'])) {
                        $featureTags = array_merge($featureTags, $feature['tags']);
                    }

                    if (isset($feature['type'])) {
                        $featureTypes[] = $feature['type'];
                    }
                }

                if (!empty($featureTags)) {
                    wp_set_post_terms($post_id, $featureTags, 'post_tag', true);
                }
                
                // Adicionar os tipos como tags ao post
                if (!empty($featureTypes)) {
                    wp_set_object_terms($post_id, $featureTypes, 'building_features', true);
                }
            }

            // O campo "delivery_date" conterá a data de entrega do edifício.
            
            $buildingDeliveryDate = isset($imovel['building']['delivery_date']) ? $imovel['building']['delivery_date'] : null;
            update_field('building_delivery_date', $buildingDeliveryDate, $post_id);
        

            // XXXXXXXXXXXXXXXXXX   C O N S T R U T O R A    XXXXXXXXXXXXXXXXXXXXX

            $constructionCompanyTitle = isset($imovel['construction_company']['title']) ? $imovel['construction_company']['title'] : null;
            update_field('construction_company_title', $constructionCompanyTitle, $post_id);

            $constructionCompanyWebsite = isset($imovel['construction_company']['site']) ? $imovel['construction_company']['site'] : null;
            update_field('construction_company_website', $constructionCompanyWebsite, $post_id);

            $constructionCompanyWhatsapp = isset($imovel['construction_company']['whatsapp']) ? $imovel['construction_company']['whatsapp'] : null;
            update_field('construction_company_whatsapp', $constructionCompanyWhatsapp, $post_id);

            $constructionCompanyBusinessContact = isset($imovel['construction_company']['business_contacts']) ? $imovel['construction_company']['business_contacts'] : null;
            update_field('construction_company_business_contacts', $constructionCompanyBusinessContact, $post_id);

            $constructionCompanyAdditionalContacts = isset($imovel['construction_company']['additionals_contacts']) ? $imovel['construction_company']['additionals_contacts'] : null;
            update_field('construction_company_additionals_contacts', $constructionCompanyAdditionalContacts, $post_id);


            $constructionCompanyInstagram = isset($imovel['construction_company']['instagram']) ? $imovel['construction_company']['instagram'] : null;
            update_field('construction_company_instagram', $constructionCompanyInstagram, $post_id);

            $constructionCompanyLogo = isset($imovel['construction_company']['logo']['url']) ? $imovel['construction_company']['logo']['url'] : null;
            update_field('construction_company_logo', $constructionCompanyLogo, $post_id);
        }
    }
    }
}

// Função para fazer upload de imagem e retornar o ID da imagem
function dwv_integration_upload_image($image_url, $post_id)
{
    // Faz o download da imagem
    $image_data = file_get_contents($image_url);

    // Gera um nome de arquivo único para evitar conflitos
    $file_name = 'dwv_integration_' . md5($image_url . time()) . '.jpg';

    // Define o caminho absoluto para o diretório de uploads
    $upload_dir = wp_upload_dir();

    // Cria o caminho absoluto para o arquivo
    $file_path = $upload_dir['path'] . '/' . $file_name;

    // Salva a imagem no diretório de uploads
    file_put_contents($file_path, $image_data);

    // Define os metadados do arquivo
    $attachment_data = array(
        'post_mime_type' => 'image/jpeg',
        'post_title' => sanitize_file_name($file_name),
        'post_content' => '',
        'post_status' => 'inherit'
    );

    // Insere o arquivo como um anexo
    $attach_id = wp_insert_attachment($attachment_data, $file_path, $post_id);

    // Gera os metadados do anexo
    $attach_data = wp_generate_attachment_metadata($attach_id, $file_path);

    // Atualiza os metadados do anexo
    wp_update_attachment_metadata($attach_id, $attach_data);

    // Retorna o ID do anexo
    return $attach_id;
}

// Função para agendar a sincronização a cada 7 dias
function dwv_integration_schedule_sync()
{
    if (!wp_next_scheduled('dwv_integration_weekly_sync')) {
        wp_schedule_event(time(), 'weekly', 'dwv_integration_weekly_sync');
    }
}
add_action('wp', 'dwv_integration_schedule_sync');

// Adiciona um filtro para o intervalo personalizado de uma semana
function dwv_integration_custom_cron_schedules($schedules)
{
    $schedules['weekly'] = array(
        'interval' => 604800, // 1 semana em segundos
        'display'  => __('A cada 7 dias')
    );

    return $schedules;
}
add_filter('cron_schedules', 'dwv_integration_custom_cron_schedules');

// Callback da sincronização semanal
function dwv_integration_weekly_sync()
{
    dwv_integration_sync_daily();
}
add_action('dwv_integration_weekly_sync', 'dwv_integration_weekly_sync');

// Restante do seu código...

function my_custom_admin_script() {
    wp_enqueue_script('admin-js', plugin_dir_url(__FILE__) . '/assets/js/admin.js', array('jquery'), '1.0.0', true);
    wp_enqueue_style('admin-style', plugin_dir_url(__FILE__) . '/assets/css/admin.css');
    wp_localize_script('admin-js', 'wpurl', array(
        'ajax' => admin_url('admin-ajax.php'),
    ));
}

add_action('admin_enqueue_scripts', 'my_custom_admin_script');
