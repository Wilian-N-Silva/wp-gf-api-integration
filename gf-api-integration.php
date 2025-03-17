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

function enviar_para_api_externa($entry, $form) {
    $form_id_desejado = 1;

    if ($form['id'] != $form_id_desejado) {
        return;
    }

    $dados = [
        'nome' => rgar($entry, '1'),
        'email' => rgar($entry, '2'),
        'mensagem' => rgar($entry, '3'),
    ];

    $api_url = 'https://sua-api.com/receber-dados';

    $args = [
        'body'    => json_encode($dados),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer SEU_TOKEN_AQUI'
        ],
        'method'  => 'POST',
        'timeout' => 45,
    ];

    $response = wp_remote_post($api_url, $args);

    if (is_wp_error($response)) {
        error_log('Erro ao enviar dados para API: ' . $response->get_error_message());
    } else {
        error_log('Dados enviados com sucesso para API.');
    }
}
