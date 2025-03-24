<?php
/**
 * Plugin Name: Gravity Forms API Integration
 * Description: Envia dados do Gravity Forms para uma API externa.
 * Version: 1.0
 * Author: Wilian-N-Silva
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('gform_after_submission', 'enviar_para_api_externa', 10, 2);

function enviar_para_api_externa($entry, $form)
{
    $api_url = get_option('api_url');
    $api_token = get_option('api_token');
    $form_id_desejado = get_option('form_id');

    if ($form['id'] != $form_id_desejado) {
        return;
    }

    $dados = [
        'nome' => rgar($entry, '2'),
        'email' => rgar($entry, '3'),
        'telefone' => rgar($entry, '4'),
        'celular' => rgar($entry, '5'),
        'racaCor' => rgar($entry, '11'),
        'modelo' => rgar($entry, '6'),
        'placa' => rgar($entry, '8'),
        'assunto' => rgar($entry, '9'),
        'rodovia' => rgar($entry, '16'),
        'descricao' => rgar($entry, '10'),
        'aceite' => rgar($entry, '13'),
    ];

    $args = [
        'body' => json_encode($dados),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => $api_token,
        ],
        'method' => 'POST',
        'timeout' => 45,
    ];

    $response = wp_remote_post($api_url . '/Cadastrar/Processo', $args);

    if (is_wp_error($response)) {
        error_log('Erro ao enviar dados para API: ' . $response->get_error_message());
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data || !isset($data['data']['numProcesso'])) {
        error_log('Erro: numProcesso não encontrado na resposta da API.');
        return;
    }

    $num_processo = $data['data']['numProcesso'];
    error_log('Número do processo recebido: ' . $num_processo);

    $campo_anexo = rgar($entry, '12');
    if ($campo_anexo) {
        enviar_anexo_para_api($campo_anexo, $num_processo);
    }
}

function enviar_anexo_para_api($arquivo_url, $num_processo)
{
    $data_envio = gmdate('Y-m-d\TH:i:s.v\Z');
    $api_url = get_option('api_url') . '/InserirAnexo';
    $api_token = get_option('api_token');

    $upload_dir = wp_upload_dir();
    $caminho_local = str_replace(site_url('/wp-content/uploads'), $upload_dir['basedir'], $arquivo_url);

    if (!file_exists($caminho_local)) {
        error_log('Arquivo não encontrado: ' . $caminho_local);
        return;
    }

    $args = [
        'body' => [
            'Arquivo' => new CURLFile($caminho_local, mime_content_type($caminho_local), basename($caminho_local)),
            'NumProcesso' => $num_processo,
            'Data' => $data_envio,

        ],
        'headers' => [
            'Authorization' => $api_token,
        ],
        'method' => 'POST',
        'timeout' => 45,
    ];

    $response = wp_remote_post($api_url, $args);

    if (is_wp_error($response)) {
        error_log('Erro ao enviar anexo para API: ' . $response->get_error_message());
    } else {
        error_log('Anexo enviado com sucesso para API.');
    }
}

function criar_pagina_configuracao_api()
{
    add_options_page(
        'Configuração da API',
        'Gravity Forms x Kria Integration',
        'manage_options',
        'configuracao-api',
        'pagina_configuracao_api_callback'
    );
}
add_action('admin_menu', 'criar_pagina_configuracao_api');

function pagina_configuracao_api_callback()
{
    ?>
    <div class="wrap">
        <h1>Configuração da API</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('grupo_configuracao_api');
            do_settings_sections('configuracao-api');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function registrar_configuracao_api()
{
    register_setting('grupo_configuracao_api', 'api_url');
    register_setting('grupo_configuracao_api', 'api_token');
    register_setting('grupo_configuracao_api', 'form_id'); // Adiciona o número do formulário


    add_settings_section('secao_api', 'Configuração da API', null, 'configuracao-api');

    add_settings_field('campo_api_url', 'URL da API', 'campo_api_url_callback', 'configuracao-api', 'secao_api');
    add_settings_field('campo_api_token', 'Token da API', 'campo_api_token_callback', 'configuracao-api', 'secao_api');
    add_settings_field('campo_form_id', 'Número do Formulário', 'campo_form_id_callback', 'configuracao-api', 'secao_api');

}
add_action('admin_init', 'registrar_configuracao_api');

function campo_api_url_callback()
{
    $valor = get_option('api_url', '');
    echo "<input type='text' name='api_url' value='$valor' class='regular-text'>";
}

function campo_api_token_callback()
{
    $valor = get_option('api_token', '');
    echo "<input type='text' name='api_token' value='$valor' class='regular-text'>";
}

function campo_form_id_callback()
{
    $valor = get_option('form_id', '');
    echo "<input type='number' name='form_id' value='$valor' class='small-text'>";
}