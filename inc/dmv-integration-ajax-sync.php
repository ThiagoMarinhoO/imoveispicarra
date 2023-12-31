<?php

add_action('wp_ajax_dwv_integration_ajax_sync', 'dwv_integration_ajax_sync');
add_action('wp_ajax_nopriv_dwv_integration_ajax_sync', 'dwv_integration_ajax_sync');

function dwv_integration_ajax_sync() {
    if (isset($_POST['imovel'])) {
        $imovel = $_POST['imovel'];

        $existing_post = get_page_by_title($imovel['title'], OBJECT, 'imovel');
        if ($existing_post) {
            //variável da última atualização do WP_post
            $post_last_update = '';

            $published_at = get_field('field_last_updated_at' , $existing_post);
            $post_modified_date = get_the_modified_date('', $existing_post);

            //Se não tiver sido modificado a variável terá o valor do published At
            if ($post_modified_date) {
                // Exibe a data de modificação se estiver disponível
                $post_last_update = $post_modified_date;
            } else {
                // Exibe a data de publicação original se não houver modificação
                $post_last_update = $published_at;
            }

            if(strtotime($imovel['last_updated_at']) >= strtotime($post_last_update) && $published_at != '') {
                // Exemplo de resposta bem-sucedida
                $response = array(
                    'message' => 'Imóvel já atualizado'
                );

                wp_send_json_success($response);
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

            // Junta o número de quartos com o sufixo "quarto"
            $catApartmentParkingSpaces = $apartmentParkingSpaces > 1 ? strval($apartmentParkingSpaces) . ' vagas de garagem'  : strval($apartmentParkingSpaces) . ' vaga de garagem';

            // Extrai o número de quartos do apartamento
            $apartmentBedrooms = isset($imovel['unit']['dorms']) ? $imovel['unit']['dorms'] : null;
            update_post_meta($existing_post->ID, 'apartment_bedrooms', $apartmentBedrooms);

            // Junta o número de quartos com o sufixo "quarto"
            $catApartmentBedrooms = $apartmentBedrooms > 1 ? strval($apartmentBedrooms) . ' quartos'  : strval($apartmentBedrooms) . ' quarto';

            // Extrai o número de suítes do apartamento
            $apartmentSuites = isset($imovel['unit']['suites']) ? $imovel['unit']['suites'] : null;
            update_post_meta($existing_post->ID, 'apartment_suites', $apartmentSuites);

            // Junta o número de suítes com o sufixo "suíte"
            $catApartmentSuites = $apartmentSuites > 1 ? strval($apartmentSuites) . ' suítes'  : strval($apartmentSuites) . ' suíte';

            // Extrai o número de banheiros do apartamento
            $apartmentBathrooms = isset($imovel['unit']['bathroom']) ? $imovel['unit']['bathroom'] : null;
            update_post_meta($existing_post->ID, 'apartment_bathrooms', $apartmentBathrooms);

            // Junta o número de banheiros com o sufixo "banheiro"
            $catApartmentBathrooms = $apartmentBathrooms > 1 ? strval($apartmentBathrooms) . ' banheiros'  : strval($apartmentBathrooms) . ' banheiro';

            // Adiciona o imóvel nas categorias correspondentes
            wp_set_object_terms($existing_post->ID, array($catApartmentParkingSpaces , $catApartmentBedrooms , $catApartmentSuites , $catApartmentBathrooms), 'category');

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
            
                            // Log para verificar se a imagem foi definida como imagem em destaque
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

            // Exemplo de resposta bem-sucedida
            $response = array(
                'message' => 'Imóvel atualizado com sucesso'
            );

            wp_send_json_success($response);
        }else{
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

            // Junta o número de quartos com o sufixo "quarto"
            $catApartmentParkingSpaces = $apartmentParkingSpaces > 1 ? strval($apartmentParkingSpaces) . ' vagas de garagem'  : strval($apartmentParkingSpaces) . ' vaga de garagem';

            // Adiciona o número de quartos na categoria x quartos
            // wp_set_object_terms($post_id, array($catApartmentParkingSpaces), 'category');

            // Extrai o número de quartos do apartamento
            $apartmentBedrooms = isset($imovel['unit']['dorms']) ? $imovel['unit']['dorms'] : null;
            update_post_meta($post_id, 'apartment_bedrooms', $apartmentBedrooms);

            // Junta o número de quartos com o sufixo "quarto"
            $catApartmentBedrooms = $apartmentBedrooms > 1 ? strval($apartmentBedrooms) . ' quartos'  : strval($apartmentBedrooms) . ' quarto';

            // Adiciona o número de quartos na categoria x quartos
            // wp_set_object_terms($post_id, array($catApartmentBedrooms), 'category');

            // Extrai o número de suítes do apartamento
            $apartmentSuites = isset($imovel['unit']['suites']) ? $imovel['unit']['suites'] : null;
            update_post_meta($post_id, 'apartment_suites', $apartmentSuites);

            // Junta o número de suítes com o sufixo "suíte"
            $catApartmentSuites = $apartmentSuites > 1 ? strval($apartmentSuites) . ' suítes'  : strval($apartmentSuites) . ' suíte';

            // Adiciona o número de suítes na categoria x suítes
            // wp_set_object_terms($post_id, array($catApartmentSuites), 'category');

            // Extrai o número de banheiros do apartamento
            $apartmentBathrooms = isset($imovel['unit']['bathroom']) ? $imovel['unit']['bathroom'] : null;
            update_post_meta($post_id, 'apartment_bathrooms', $apartmentBathrooms);
            
            // Junta o número de banheiros com o sufixo "banheiro"
            $catApartmentBathrooms = $apartmentBathrooms > 1 ? strval($apartmentBathrooms) . ' banheiros'  : strval($apartmentBathrooms) . ' banheiro';

            // Adiciona o número de banheiros na categoria x banheiros
            wp_set_object_terms($post_id, array($catApartmentBedrooms, $catApartmentBathrooms , $catApartmentSuites , $catApartmentParkingSpaces), 'category');

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
            
                            // Log para verificar se a imagem padrão foi definida como imagem em destaque
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

            // Exemplo de resposta bem-sucedida
            $response = array(
                'message' => 'Imóvel cadastrado com sucesso'
            );

            wp_send_json_success($response);
        }
    }
}