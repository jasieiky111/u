<?php
/**
 * Plugin Name: CubaShop Kiosko
 * Description: Cliente WordPress para el sistema interno de ventas CubaShop Kiosko. Sin checkout público.
 * Version: 0.1.0
 * Requires PHP: 8.1
 * Author: CubaShop
 * License: MIT
 */

defined('ABSPATH') || exit;

define('CSK_VERSION', '0.1.0');
define('CSK_OPTION_API_URL', 'csk_api_url');

add_action('admin_menu', function () {
    add_menu_page(
        'CubaShop Kiosko',
        'CubaShop Kiosko',
        'read',
        'cubashop-kiosko',
        'csk_render_dashboard',
        'dashicons-store',
        3
    );
});

function csk_render_dashboard(): void {
    if (!is_user_logged_in()) {
        wp_die(esc_html__('Debes iniciar sesión.', 'cubashop-kiosko'));
    }

    echo '<div class="wrap"><h1>CubaShop Kiosko</h1>';
    echo '<p>Panel interno. Las ventas son registradas exclusivamente por trabajadores autorizados.</p>';
    echo '<p><strong>Estado:</strong> núcleo de integración en desarrollo.</p></div>';
}

add_action('rest_api_init', function () {
    register_rest_route('cubashop-kiosko/v1', '/health', [
        'methods'  => WP_REST_Server::READABLE,
        'callback' => function () {
            return new WP_REST_Response([
                'ok' => true,
                'plugin_version' => CSK_VERSION,
                'public_checkout' => false,
            ], 200);
        },
        'permission_callback' => function () {
            return current_user_can('read');
        },
    ]);
});

function csk_api_request(string $path, string $method = 'GET', array $body = []): array|WP_Error {
    $base = trim((string) get_option(CSK_OPTION_API_URL, ''), '/');
    if ($base === '') {
        return new WP_Error('csk_api_not_configured', 'Backend CubaShop Kiosko no configurado.');
    }

    $args = [
        'method'  => strtoupper($method),
        'timeout' => 15,
        'headers' => [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ],
    ];

    if ($body !== []) {
        $args['body'] = wp_json_encode($body);
    }

    $response = wp_remote_request($base . '/' . ltrim($path, '/'), $args);
    if (is_wp_error($response)) {
        return $response;
    }

    $status = wp_remote_retrieve_response_code($response);
    $json = json_decode(wp_remote_retrieve_body($response), true);

    if ($status < 200 || $status >= 300) {
        return new WP_Error('csk_api_error', 'El backend rechazó la solicitud.', ['status' => $status, 'body' => $json]);
    }

    return is_array($json) ? $json : [];
}
