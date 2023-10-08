<?php
/*
Plugin Name: Gerador de Artigos GPT-3.5-Turbo
Description: Um plugin que utiliza a API do GPT-3.5-Turbo para gerar artigos com base nas entradas do usuário.
Version: 3.7
Author: JOAO JLABS
*/

function gpt3_enqueue_scripts($hook) {
    if ($hook != 'toplevel_page_gpt3-artigo-gerador') {
        return;
    }
    wp_enqueue_script('gpt3-admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin.js', array('jquery'), '1.0.0', true);
}

add_action('admin_enqueue_scripts', 'gpt3_enqueue_scripts');

function gpt3_admin_menu() {
    add_menu_page('Gerador de Artigos GPT-3.5-Turbo', 'Gerador GPT-3.5-Turbo', 'manage_options', 'gpt3-artigo-gerador', 'gpt3_admin_page', 'dashicons-edit', 100);
}

add_action('admin_menu', 'gpt3_admin_menu');

function gpt3_admin_page() {
    echo '<div class="wrap">';
    echo '<h2>Gerador de Artigos GPT-3.5-Turbo</h2>';

    echo '<form method="post" action="" enctype="multipart/form-data">';
    echo '<p><label>Keyword Principal:</label> <input type="text" name="keyword_principal"></p>';
    echo '<p><label>Keywords Obrigatórias (separadas por vírgulas):</label> <input type="text" name="keywords_obrigatorias"></p>';
    echo '<p><label>Resumo/Contexto:</label> <textarea name="resumo" rows="5" cols="40"></textarea></p>';
    echo '<p><label>Upload da Imagem Destacada:</label> <input type="file" name="featured_image" id="featured_image"></p>';
    echo '<input type="submit" value="Gerar Artigo" class="button button-primary">';
    echo '</form>';

    if ($_POST) {
        $titulo = sanitize_text_field($_POST['keyword_principal']);
        $keywords = sanitize_text_field($_POST['keywords_obrigatorias']);
        $resumo = sanitize_textarea_field($_POST['resumo']);
        
        $conteudo = gerar_artigo_via_gpt($titulo, $keywords, $resumo);
        if ($conteudo) {
            $post_id = wp_insert_post(array(
                'timeout' => 20,
                'post_title' => $titulo,
                'post_content' => $conteudo,
                'post_status' => 'draft',
                'post_type' => 'post'
            ));
            
            if (isset($_FILES['featured_image']) && $_FILES['featured_image']['size'] > 0) {
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');

                $attachment_id = media_handle_upload('featured_image', $post_id);

                if (!is_wp_error($attachment_id)) {
                    set_post_thumbnail($post_id, $attachment_id);
                } else {
                    echo '<p>Erro ao fazer upload da imagem: ' . $attachment_id->get_error_message() . '</p>';
                }
            }

            if ($post_id) {
                echo '<p>Artigo gerado com sucesso! <a href="' . admin_url('post.php?action=edit&post=' . $post_id) . '">Ver artigo</a></p>';
            } else {
                echo '<p>Erro ao gerar o artigo JLABS. Por favor, tente novamente.</p>';
            }
        }
    }

    echo '</div>';
}

function gerar_artigo_via_gpt($titulo, $keywords, $resumo) {
    $api_url = "https://api.openai.com/v1/chat/completions";
    $api_key = "CHAVEAPI É AQUI VIU !!!! GALERO DO GIT";  

    $messages = array(
        array(
            "role" => "system",
            "content" => "Você é um assistente útil que escreve artigos estruturados como um profissional em SEO e redação."
        ),
        array(
            "role" => "user",
            "content" => "Por favor, escreva um artigo informativo sobre o tema '{$titulo}'. O artigo deve ser bem estruturado com cabeçalhos formatados em HTML como <h1>, <h2> e <h3>, e incorporar as palavras-chave: '{$keywords}'. Contexto adicional: '{$resumo}'."
        )
    );
    
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode(array(
            'model' => 'gpt-3.5-turbo',
            'messages' => $messages
        )),
        'timeout' => 30 // SE SEUS SERVIDORES FOREM DE PAPEL DÁ UM UP PRA 50
    ));

    if (is_wp_error($response)) {
        echo '<p>Erro ao comunicar com a API do GPT-3: ' . $response->get_error_message() . '</p>';
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['error'])) {
        echo '<p>Erro da API do GPT-3: ' . $data['error']['message'] . '</p>';
        return null;
    }

    if (empty($data['choices'][0]['message']['content'])) {
        echo '<p>Erro ao comunicar com a API do GPT-3 ou conteúdo retornado vazio.</p>';
        return null;
    }

    return $data['choices'][0]['message']['content'];
}

?>
