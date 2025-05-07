<?php
/**
 * Plugin Name: Gravity Forms API Integration
 * Description: Envia dados do Gravity Forms para uma API externa.
 * Version: 1.7
 * Author: Wilian-N-Silva
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

add_action('gform_after_submission', 'enviar_para_api_externa', 10, 2);

function limparTexto($texto)
{
    return preg_replace('/[()\s-]/', '', $texto);
}
function enviar_para_api_externa($entry, $form)
{
    $api_url = get_option('api_url');

    // $api_token = obter_token_api();
    $api_token = 'teste';
    if (!$api_token) {
        return;
    }

    $form_id_desejado = get_option('form_id');

    if ($form['id'] != $form_id_desejado) {
        return;
    }

    $dados = [
        'nome' => strtoupper(rgar($entry, '2')),
        'email' => rgar($entry, '3'),
        'telefone' => limparTexto(rgar($entry, '5')),
        'cidade' => null,
        'estado' => null,
        'proprietario' => null,
        'numDocumento' => null,
        'nomeCondutor' => null,
        // 'marca' => strtoupper(rgar($entry, '18')),
        'modelo' => strtoupper(rgar($entry, '6')),
        'cor' => null,
        'placa' => strtoupper(limparTexto(rgar($entry, '8'))),
        'ano' => null,
        'data' => gmdate('Y-m-d\TH:i:s.v\Z'),
        'hora' => gmdate('Y-m-d\TH:i:s.v\Z'),
        'rodovia' => rgar($entry, '16'),
        'origem' => null,
        'destino' => null,
        'km' => 0,
        'descricao' => rgar($entry, '10'),
        'dataOcorrencia' => gmdate('Y-m-d\TH:i:s.v\Z'),
        'compartilharDados' => true,
        'usuario' => [
            'uf' => null,
            'sexo' => null,
            'tratamento' => null,
            'logradouro' => null,
            'numeroResidencia' => null,
            'cep' => null,
            'cpf' => null,
            'rg' => null,
            'racaCor' => rgar($entry, '11')
        ],
    ];


    $args = [
        'body' => json_encode($dados),
        'headers' => [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_token,
        ],
        'method' => 'POST',
        'timeout' => 45,
    ];

    $response = wp_remote_post($api_url . '/Cadastrar/Processo', $args);
    /* error_log($response);

    if (is_wp_error($response)) {
        $erro = $response->get_error_message();
        registrar_log_plugin("Erro ao enviar para API: $erro");
        return;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);


    error_log($data);
    registrar_log_plugin($data);

    if (!$data || !isset($data['data']['numProcesso'])) {
        error_log('Erro: numProcesso não encontrado na resposta da API.');
        registrar_log_plugin("Erro ao enviar para API: Erro: numProcesso não encontrado na resposta da API.");
        return;
    }
*/
    // $num_processo = $data['data']['numProcesso'];
    $num_processo = 123;
    error_log('Número do processo recebido: ' . $num_processo);

    registrar_log_plugin("Anexos encontrados: " . print_r(maybe_unserialize(rgar($entry, '12')), true));

    if (maybe_unserialize(rgar($entry, '12'))) {
        enviar_anexo_para_api($entry, $num_processo);
    }

}

/*function enviar_anexo_para_api($entry, $num_processo)
{
    $campo_id = 12; // ID do campo de anexo
    $arquivos_urls = rgar($entry, $campo_id);
    $data_envio = gmdate('Y-m-d\TH:i:s.v\Z');
    // $api_token = obter_token_api();
    $api_token = 'abc';

    if (!$api_token)
        return;

    if (!$arquivos_urls) {
        registrar_log_plugin("Campo de anexo vazio.");
        return;
    }

    // Garante que é array
    if (!is_array($arquivos_urls)) {
        $arquivos_urls = [$arquivos_urls];
    }

    $upload_dir = wp_upload_dir();
    $arquivos = [];

    foreach ($arquivos_urls as $url) {
        $caminho_local = str_replace(site_url('/wp-content/uploads'), $upload_dir['basedir'], $url);
        if (!file_exists($caminho_local)) {
            registrar_log_plugin("Arquivo não encontrado: $url");
            continue;
        }

        $arquivos[] = new CURLFile($caminho_local, mime_content_type($caminho_local), basename($caminho_local));
    }

    if (empty($arquivos)) {
        registrar_log_plugin("Nenhum arquivo encontrado para upload.");
        return;
    }

    // Configura multipart como o cURL original
    $post_fields = [
        'NumProcesso' => $num_processo,
        'Data' => $data_envio,
    ];

    // Adiciona múltiplos arquivos com chave "Arquivo[]"
    foreach ($arquivos as $i => $arquivo) {
        $post_fields["Arquivo[$i]"] = $arquivo;
    }

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => get_option('api_url') . '/InserirAnexo',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_fields,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $api_token,
        ],
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        registrar_log_plugin("Erro cURL ao enviar anexo: " . curl_error($ch));
    } else {
        registrar_log_plugin("Resposta do envio de anexo (HTTP $http_code): $response");
    }

    curl_close($ch);
}*/

function enviar_anexo_para_api($entry, $num_processo)
{
    $campo_id = 12; // ID do campo de anexo
    $arquivos_json = rgar($entry, $campo_id);
    $data_envio = gmdate('Y-m-d\TH:i:s.v\Z');
    $api_token = 'abc';
    $api_url = get_option('api_url') . '/InserirAnexo';

    if (!$api_token) {
        registrar_log_plugin("Token da API não configurado.");
        return;
    }

    if (!$arquivos_json) {
        registrar_log_plugin("Campo de anexo vazio.");
        return;
    }

    $arquivos_urls = json_decode($arquivos_json, true);

    if (empty($arquivos_urls)) {
        registrar_log_plugin("Nenhum anexo válido encontrado após decodificação JSON.");
        return;
    }

    $upload_dir = wp_upload_dir();
    $multipart = [
        ['name' => 'NumProcesso', 'contents' => $num_processo],
        ['name' => 'Data', 'contents' => $data_envio],
    ];

    foreach ($arquivos_urls as $url) {
        $caminho_local = str_replace(
            site_url('/wp-content/uploads'),
            $upload_dir['basedir'],
            $url
        );

        if (!file_exists($caminho_local)) {
            registrar_log_plugin("Arquivo não encontrado: $caminho_local");
            continue;
        }

        $multipart[] = [
            'name' => 'Arquivo',
            'contents' => fopen($caminho_local, 'r'),
            'filename' => basename($caminho_local),
        ];
    }

    if (count($multipart) <= 2) {
        registrar_log_plugin("Nenhum arquivo válido encontrado para upload.");
        return;
    }

    $client = new Client();

    try {
        $response = $client->request('POST', $api_url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_token,
            ],
            'multipart' => $multipart,
            'timeout' => 45,
        ]);

        $status_code = $response->getStatusCode();
        $body = $response->getBody()->getContents();

        registrar_log_plugin("Resposta da API (HTTP $status_code): $body");
    } catch (RequestException $e) {
        registrar_log_plugin("Erro ao enviar arquivos: " . $e->getMessage());
        if ($e->hasResponse()) {
            registrar_log_plugin("Resposta da API: " . $e->getResponse()->getBody()->getContents());
        }
    }
}
function obter_token_api()
{
    $url = rtrim(get_option('api_url'), '/') . '/Autenticacao/GerarToken';

    $dados_login = [
        'idUsuario' => 1,
        'usuario' => get_option('api_user'),
        'senha' => get_option('api_password'),
        'role' => get_option('api_role'),
    ];

    $args = [
        'body' => json_encode($dados_login),
        'headers' => [
            'Content-Type' => 'application/json',
        ],
        'method' => 'POST',
        'timeout' => 30,
    ];

    $response = wp_remote_post($url, $args);

    if (is_wp_error($response)) {
        error_log('Erro ao autenticar: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($body['token'])) {
        error_log('Token não encontrado na resposta de autenticação.');
        return false;
    }

    return $body['token'];
}

function registrar_log_plugin($mensagem, $arquivo = 'log_requisicoes.txt')
{
    $diretorio = plugin_dir_path(__FILE__);
    $caminho_arquivo = $diretorio . $arquivo;

    $data_hora = date('Y-m-d H:i:s');
    $mensagem_formatada = "[{$data_hora}] {$mensagem}\n";

    file_put_contents($caminho_arquivo, $mensagem_formatada, FILE_APPEND);
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
    register_setting('grupo_configuracao_api', 'api_user');
    register_setting('grupo_configuracao_api', 'api_password');
    register_setting('grupo_configuracao_api', 'api_role');
    register_setting('grupo_configuracao_api', 'form_id'); // Adiciona o número do formulário


    add_settings_section('secao_api', 'Configuração da API', null, 'configuracao-api');

    add_settings_field('campo_api_url', 'URL da API', 'campo_api_url_callback', 'configuracao-api', 'secao_api');

    add_settings_field('campo_api_user', 'Usuário', 'campo_api_user_callback', 'configuracao-api', 'secao_api');
    add_settings_field('campo_api_password', 'Senha', 'campo_api_password_callback', 'configuracao-api', 'secao_api');
    add_settings_field('campo_api_role', 'Role', 'campo_api_role_callback', 'configuracao-api', 'secao_api');

    add_settings_field('campo_form_id', 'Número do Formulário', 'campo_form_id_callback', 'configuracao-api', 'secao_api');

}
add_action('admin_init', 'registrar_configuracao_api');

function campo_api_url_callback()
{
    $valor = get_option('api_url', '');
    echo "<input type='text' name='api_url' value='$valor' class='regular-text'>";
}

function campo_api_user_callback()
{
    $valor = get_option('api_user', '');
    echo "<input type='text' name='api_user' value='$valor' class='regular-text'>";
}

function campo_api_password_callback()
{
    $valor = get_option('api_password', '');
    echo "<input type='password' name='api_password' value='$valor' class='regular-text'>";
}

function campo_api_role_callback()
{
    $valor = get_option('api_role', '');
    echo "<input type='text' name='api_role' value='$valor' class='regular-text'>";
}


function campo_form_id_callback()
{
    $valor = get_option('form_id', '');
    echo "<input type='number' name='form_id' value='$valor' class='small-text'>";
}