<?php
/*
Plugin Name: credito para reserva
Description: Tracks user expenses and provides a shortcode to display a user expense panel
Version: 1.0
Author: inovetime
*/




define('DEFAULT_EXPENSE_LIMIT', 5000);  // Limite de crédito padrão

// Função para adicionar gastos a um usuário
function add_expense($user_id, $amount) {
    if (current_user_can('editor') || current_user_can('administrator') || current_user_can('contributor')) {
        $current_total = get_user_meta($user_id, 'total_expense', true);
        if (!is_numeric($current_total)) {
            $current_total = 0;
        }
        $new_total = $current_total + $amount;
        update_user_meta($user_id, 'total_expense', $new_total);
    }
}

// Função para definir o limite de gastos de um usuário
function set_expense_limit($user_id, $limit) {
    if (current_user_can('editor') || current_user_can('administrator') || current_user_can('contributor')) {
        update_user_meta($user_id, 'expense_limit', $limit);
    }
}


// Shortcode para exibir o painel de gastos do usuário
function user_expense_panel_shortcode() {
 

    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $total_expense = get_user_meta($user_id, 'total_expense', true);
        $expense_limit = get_user_meta($user_id, 'expense_limit', true);
        $current_day = date('j'); // Pega o dia do mês


        if (!is_numeric($expense_limit)) {
            $expense_limit = DEFAULT_EXPENSE_LIMIT;
        }

        $percentage = ($total_expense / $expense_limit) * 100;

        // Verificar se o WooCommerce está ativo
        if (class_exists('WooCommerce')) {
            // Verificar se o usuário clicou no botão "Efetuar pagamento"
            if (isset($_POST['efetuar_pagamento']) && isset($_POST['payment_amount'])) {
                $payment_amount = floatval($_POST['payment_amount']);

               // Criar um produto para a fatura principal
                $product_id = create_automatic_product($user_id, $total_expense);

                // Calcular o valor dos royalties como 8% do valor total da fatura
                $royalties = $total_expense * 0.08;

                // Criar um produto para a fatura dos royalties
                $royalties_product_id = create_automatic_product($user_id, $royalties, 'royalties');

                // Redirecionar o usuário para a página de checkout com os produtos no carrinho
                if ($product_id && $royalties_product_id) {
                    // Limpar o carrinho antes de adicionar novos produtos
                    WC()->cart->empty_cart();

                    // Adicionar produtos ao carrinho
                    WC()->cart->add_to_cart($product_id);
                    WC()->cart->add_to_cart($royalties_product_id);

                    // Redirecionar para a página de checkout
                    wp_redirect(wc_get_checkout_url());
                    exit;
                }
            }
        }
         // Obtenha o dia atual
    $current_day = date('j');

    // Verifique se o dia atual é 1 ou 15 e se o total de gastos é maior que zero
    if (($current_day == 2 || $current_day == 3|| $current_day == 4|| $current_day == 5|| $current_day == 6|| $current_day == 7|| $current_day == 10|| $current_day == 11|| $current_day == 12|| $current_day == 13|| $current_day == 14||$current_day == 15|| $current_day == 16|| $current_day == 17|| $current_day == 18|| $current_day == 19|| $current_day == 20|| $current_day == 21) && $total_expense > 0) {
        // Exiba o botão "Efetuar pagamento" apenas se o dia for 5 ou 20 e os gastos totais forem maiores que zero
        echo '<form method="post">';
        echo '<input type="hidden" name="payment_amount" value="' . $total_expense . '">';
        echo '<input type="submit" name="efetuar_pagamento" value="Efetuar pagamento">';
        echo '</form>';
    }
    if (isset($_POST['efetuar_pagamento']) && isset($_POST['payment_amount'])) {
    // ... Seu código existente de processamento de pagamento aqui ...

    // Zere os gastos totais do usuário após o pagamento
    update_user_meta($user_id, 'total_expense', 0);
}



        ob_start();
        ?>


        <style>
            .progress-bar {
                background-color: #f3f3f3;
                border-radius: 13px;
                height: 20px;
                width: 100%;
            }
            .progress-bar-inner {
                background-color: #4caf50;
                border-radius: 13px;
                height: 100%;
                width: <?php echo $percentage; ?>%;
            }
            body {
                background: #EEE;
                /* font-size:0.9em !important; */
            }
            .invoice {
                width: 970px !important;
                margin: 50px auto;
            }
            .invoice-header {
                padding: 25px 25px 15px;
            }
            .invoice-header h1 {
                margin: 0;
            }
            .invoice-header .media .media-body {
                font-size: 0.9em;
                margin: 0;
            }
            .invoice-body {
                border-radius: 10px;
                padding: 25px;
                background: #FFF;
            }
            .invoice-footer {
                padding: 15px;
                font-size: 0.9em;
                text-align: center;
                color: #999;
            }
            .logo {
                max-height: 70px;
                border-radius: 10px;
            }
            .dl-horizontal {
                margin: 0;
            }
            .dl-horizontal dt {
                float: left;
                width: 80px;
                overflow: hidden;
                clear: left;
                text-align: right;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .dl-horizontal dd {
                margin-left: 90px;
            }
            .rowamount {
                padding-top: 15px !important;
            }
            .rowtotal {
                font-size: 1.3em;
            }
            .colfix {
                width: 12%;
            }
            .mono {
                font-family: monospace;
            }
        </style>
   
       <div class="user-expense-panel">
            <form method="post" action="">
                <div class="progress-bar">
                    <div class="progress-bar-inner"></div>
                </div>

                <?php wp_nonce_field('efetuar_pagamento', 'payment_nonce'); ?>

                <div>
                    Fatura: R$ <?php echo number_format($total_expense, 2, ',', '.'); ?> / R$ <?php echo number_format($expense_limit, 2, ',', '.'); ?>
                </div>
                <?php if ($total_expense >= $expense_limit): ?>
                    <input type="hidden" name="payment_amount" value="<?php echo ($total_expense - $expense_limit); ?>">
                    <button type="submit" name="efetuar_pagamento" class="payment-button">Efetuar pagamento</button>
                <?php endif; ?>
            </form>
        </div>
        <?php if ($total_expense >= $expense_limit && ($current_day == 5 || $current_day == 20)): 
                      $product_id = create_automatic_product($user_id, $total_expense);
                      $is_paid = get_post_meta($product_id, '_paid', true);
                      if($is_paid !== 'yes'): ?>
                    <input type="hidden" name="payment_amount" value="<?php echo ($total_expense - $expense_limit); ?>">
                    <button type="submit" name="efetuar_pagamento" class="payment-button">Efetuar pagamento</button>
                <?php endif; endif; ?>
            </form>
        </div>


        <?php
        return ob_get_clean();
    } else {
        return "Você deve estar logado para ver o painel de gastos.";
    }
}

// função para criar produto automatico
function create_automatic_product($user_id, $amount, $type = 'main_invoice') {
    // Obtenha o timestamp atual
    $current_time = time();

    // Define o título do produto com o ID do usuário e o timestamp para torná-lo único
    $product_title = $type == 'main_invoice' ? 'Fatura principal - User ' . $user_id . ' - ' . $current_time : 'Royalties - User ' . $user_id . ' - ' . $current_time;

    // Verifica se o produto já existe
    $existing_product = get_page_by_title($product_title, OBJECT, 'product');

    if ($existing_product) {
        // Se o produto existir, verifica a última vez que uma fatura foi criada
        $last_invoice_time = get_post_meta($existing_product->ID, 'invoice_time', true);
        $time_difference = time() - $last_invoice_time;
        
        if($time_difference < 48 * 60 * 60) {
            // Se passou menos de 48 horas desde a última fatura, não cria uma nova
            return false;
        } else {
            // Se passou mais de 48 horas, atualiza o preço
            update_post_meta($existing_product->ID, '_price', $amount);
            update_post_meta($existing_product->ID, '_regular_price', $amount);
            
            // Atualiza a hora da fatura
            update_post_meta($existing_product->ID, 'invoice_time', $current_time);

            // Retorna o ID do produto existente
            return $existing_product->ID;
        }
    }

    // Se o produto não existir, cria um novo
    $post_id = wp_insert_post(array(
        'post_title' => $product_title,
        'post_type' => 'product',
        'post_status' => 'publish',
        'post_author' => $user_id
    ));

    if ($post_id) {
        // Define o preço do produto
        update_post_meta($post_id, '_price', $amount);
        update_post_meta($post_id, '_regular_price', $amount);
        
        // Define o produto como virtual (não requer envio)
        update_post_meta($post_id, '_virtual', 'yes');

        // Define a hora da fatura
        update_post_meta($post_id, 'invoice_time', $current_time);

        // Define a categoria do produto como Fatura Principal ou Royalties
        wp_set_object_terms($post_id, $type == 'main_invoice' ? 'Fatura Principal' : 'Royalties', 'product_cat');

        
        // Armazena o ID do usuário a quem o produto é destinado como um campo personalizado
        update_post_meta($post_id, '_user_id', $user_id);

        // Retorna o ID do produto
        return $post_id;
    }

    return false;
}

add_action('woocommerce_before_single_product', 'restrict_product_access');
function restrict_product_access() {
    global $product, $post;

    // Recupera o ID do usuário a quem o produto é destinado
    $product_user_id = get_post_meta($post->ID, '_user_id', true);

    // Se o usuário atual não for o usuário designado, redireciona para a página inicial
    if ($product_user_id && get_current_user_id() != $product_user_id) {
        wp_redirect(home_url());
        exit;
    }
}

add_shortcode('user_expense_panel', 'user_expense_panel_shortcode');

add_action( 'pre_get_posts', 'custom_pre_get_posts_query' );
function custom_pre_get_posts_query( $q ) {

    if (!is_user_logged_in() || !$q->is_main_query() || !$q->is_post_type_archive()) return;

    if (!is_admin() && is_shop()) {
        $user_id = get_current_user_id();

        $meta_query = array(
            'relation' => 'OR',
            array(
                'key'     => '_user_id',
                'value'   => $user_id,
                'compare' => '=',
            ),
            array(
                'key'     => '_user_id',
                'compare' => 'NOT EXISTS',
            ),
        );

        $q->set( 'meta_query', $meta_query );
    }

    remove_action( 'pre_get_posts', 'custom_pre_get_posts_query' );
}




// Adicionar página de administração
add_action('admin_menu', 'add_expense_admin_page');
function add_expense_admin_page() {
    $page_hook_suffix = add_menu_page(
        'Gerenciar Gastos',
        'Gerenciar Gastos',
        'manage_options',
        'gerenciar-gastos',
        'render_expense_admin_page'
    );

    // Enfileirar o script jQuery UI Sortable apenas na página de Gerenciar Gastos
    add_action('admin_enqueue_scripts', function($hook) use ($page_hook_suffix) {
        if ($hook != $page_hook_suffix) return;
        wp_enqueue_script('jquery-ui-sortable');
    });
}


// Renderizar página de administração
function render_expense_admin_page() {

  // quadro kaban 
 $users = get_users();
    ?>
    <h2>Quadro Kanban dos usuários</h2>
    <div id="user-kanban-board" class="user-kanban-board">
        <div id="low" class="kanban-column">
            <h3>Baixo</h3>
            <?php foreach ($users as $user): ?>
                <?php $total_expense = get_user_meta($user->ID, 'total_expense', true); ?>
                <?php if ($total_expense > 0 && $total_expense <= 1000): ?>
                    <div class="user-card" data-user-id="<?php echo $user->ID; ?>">
                        <div class="avatar">
                            <?php echo get_avatar( $user->ID ); ?>
                        </div>
                        <h3><?php echo $user->display_name; ?></h3>
                        <p>Total gasto: <?php echo $total_expense; ?></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div id="medium" class="kanban-column">
            <h3>Médio</h3>
            <?php foreach ($users as $user): ?>
                <?php $total_expense = get_user_meta($user->ID, 'total_expense', true); ?>
                <?php if ($total_expense > 1000 && $total_expense <= 3000): ?>
                    <div class="user-card" data-user-id="<?php echo $user->ID; ?>">
                        <div class="avatar">
                            <?php echo get_avatar( $user->ID ); ?>
                        </div>
                        <h3><?php echo $user->display_name; ?></h3>
                        <p>Total gasto: <?php echo $total_expense; ?></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <div id="high" class="kanban-column">
            <h3>Alto</h3>
            <?php foreach ($users as $user): ?>
                <?php $total_expense = get_user_meta($user->ID, 'total_expense', true); ?>
                <?php if ($total_expense > 3000): ?>
                    <div class="user-card" data-user-id="<?php echo $user->ID; ?>">
                        <div class="avatar">
                            <?php echo get_avatar( $user->ID ); ?>
                        </div>
                        <h3><?php echo $user->display_name; ?></h3>
                        <p>Total gasto: <?php echo $total_expense; ?></p>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="dialog" title="Detalhes dos Gastos">
        <p id="dialog-content"></p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $(".kanban-column").sortable({
            connectWith: ".kanban-column",
            handle: ".user-card",
            placeholder: "card-placeholder"
        });

        $( "#dialog" ).dialog({
            autoOpen: false,
            show: {
                effect: "blind",
                duration: 1000
            },
            hide: {
                effect: "explode",
                duration: 1000
            }
        });

        $( ".user-card" ).on( "click", function() {
            // Aqui você precisa buscar os detalhes dos gastos do usuário pelo ID do usuário.
            var userId = $(this).data("user-id");
            var userDetails = "Detalhes dos gastos para o usuário ID " + userId; // Substituir por uma chamada AJAX para obter os detalhes reais do usuário

            $( "#dialog-content" ).text(userDetails);
            $( "#dialog" ).dialog( "open" );
        });
    });
 $( ".user-card" ).on( "click", function() {
    var userId = $(this).data("user-id");
    $.ajax({
        url: ajaxurl, // AJAX URL é definido pelo WordPress - você deve passar isso para o seu script
        type: "POST",
        data: {
            'action': 'get_user_expense_details', // o 'action' deve corresponder ao nome do seu gancho de ação
            'user_id': userId
        },
        success: function(response) {
            // 'response' é o retorno do seu gancho de ação
            $( "#dialog-content" ).html(response);
            $( "#dialog" ).dialog( "open" );
        },
        error: function(errorThrown){
            console.log(errorThrown);
        }
    });
});
// Isso irá processar a chamada AJAX feita pelo quadro Kanban
add_action('wp_ajax_get_user_expense_details', 'get_user_expense_details');
function get_user_expense_details() {
    // Certifique-se de que o usuário tem permissão para fazer essa ação
    if (current_user_can('manage_options')) {
        $user_id = $_POST['user_id'];
        $total_expense = get_user_meta($user_id, 'total_expense', true);
        $expense_limit = get_user_meta($user_id, 'expense_limit', true);

        $user_info = get_userdata($user_id);
        $user_name = $user_info->first_name . " " . $user_info->last_name;

        echo "<p>O total de despesas para " . $user_name . " é: " . $total_expense . "</p>";
        echo "<p>O limite de despesas para " . $user_name . " é: " . $expense_limit . "</p>";
    }

    // Sempre morra no final de uma chamada AJAX
    die();
}


    </script>

    
    


    
    <style>
        .user-kanban-board {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
        }
        .user-kanban-card {
            flex: 0 0 30%;
            margin: 1em;
            padding: 1em;
            border: 1px solid #ccc;
            border-radius: 1em;

        }
        .user-kanban-board {
    display: flex;
    justify-content: space-between;
}

.kanban-column {
    width: 30%;
    border: 1px solid #ccc;
    padding: 10px;
    box-sizing: border-box;
}

.user-card {
    padding: 10px;
    border: 1px solid #ccc;
    margin-bottom: 10px;
    cursor: move;
}

.card-placeholder {
    height: 50px;
    background-color: #f9f9f9;
    border: 2px dashed #ccc;
}

<style>
    body {
        background-color: #f5f5f5;
        font-family: 'Arial', sans-serif;
    }

    h2 {
        color: #444;
        margin-bottom: 1em;
    }

    .form-container {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 2em;
        margin-bottom: 2em;
    }

    .form-container form label {
        display: block;
        margin-bottom: .5em;
        font-weight: bold;
    }

    .form-container form input[type="text"],
    .form-container form input[type="number"],
    .form-container form input[type="date"],
    .form-container form select {
        width: 100%;
        padding: .5em;
        margin-bottom: 1em;
        border: 1px solid #ddd;
    }

    .form-container form input[type="submit"] {
        background-color: #4CAF50;
        color: white;
        padding: .5em 2em;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 1em;
    }

    .form-container form input[type="submit"]:hover {
        background-color: #45a049;
    }

    .user-expense-list h3 {
        color: #333;
    }

    .user-expense-list ul {
        list-style-type: none;
        padding: 0;
        margin: 0;
    }

    .user-expense-list ul li {
        background-color: #fff;
        padding: .5em;
        margin-bottom: .5em;
        border: 1px solid #ddd;
    }

    .kanban-board {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
    }

    .kanban-column {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 1em;
        width: calc(33% - 1em);
        margin-bottom: 1em;
        box-sizing: border-box;
    }

    .user-card {
        background-color: #f9f9f9;
        border: 1px solid #ccc;
        padding: 1em;
        margin-bottom: 1em;
    }

    .avatar {
        margin-bottom: .5em;
    }

    .user-card h3 {
        margin: 0 0 .5em;
        color: #333;
    }

    .user-card p {
        margin: 0;
        color: #666;
    }
</style>
    </style>
    
    <?php



    // quadro kaban final




 $users = get_users();




// Verificar se o formulário de limpeza foi enviado
    if (isset($_POST['clear_user_id'])) {
        clear_expenses($_POST['clear_user_id']);
        echo '<p>Todos os gastos para o usuário ' . $_POST['clear_user_id'] . ' foram removidos.</p>';
    }

    // Renderizar formulário para limpar gastos
    ?>
     <h2>Definir Limite de Crédito para Usuário</h2>
    <form method="post" action="">
        <label for="user_id">Usuário:</label>
        <select id="user_id" name="user_id" required>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>
            <?php endforeach; ?>
        </select>

        <label for="limit">Limite de Crédito:</label>
        <input type="number" id="limit" name="limit" step="0.01" required>

        <input type="submit" value="Definir Limite">
    </form>

    
    <h2>Remover todos os gastos de um usuário</h2>
    <form method="post" action="">
        <label for="clear_user_id">Usuário:</label>
        <select id="clear_user_id" name="clear_user_id" required>
            <?php foreach ($users as $user): ?>
                <option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>
            <?php endforeach; ?>
        </select>

        <input type="submit" value="Remover todos os gastos">
    </form>
    <?php

    

    // Verificar se o formulário de edição/remoção foi enviado
if (isset($_POST['edit_user_id']) && isset($_POST['new_amount'])) {
    edit_expense($_POST['edit_user_id'], $_POST['new_amount']);
    echo '<p>Os gastos para o usuário ' . $_POST['edit_user_id'] . ' foram alterados para: ' . $_POST['new_amount'] . '</p>';
}
if (isset($_POST['remove_user_id']) && isset($_POST['remove_amount'])) {
    remove_expense($_POST['remove_user_id'], $_POST['remove_amount']);
    echo '<p>Os gastos para o usuário ' . $_POST['remove_user_id'] . ' foram reduzidos em: ' . $_POST['remove_amount'] . '</p>';
}

    // Verificar se o formulário foi enviado
    if (isset($_POST['user_id']) && isset($_POST['product']) && isset($_POST['amount']) && isset($_POST['date'])) {
        // Adicionar gasto ao usuário
        add_expense($_POST['user_id'], $_POST['amount']);

        // Adicionar detalhe de gasto com o canal de venda
    add_expense_detail($_POST['user_id'], $_POST['product'], $_POST['amount'], $_POST['date'], $_POST['canal_de_venda']);

       // Exibir detalhe do gasto adicionado
    $user_info = get_userdata($_POST['user_id']);
    echo '<p>Adicionado gasto para ' . $user_info->display_name . ': ' . $_POST['product'] . ' - ' . $_POST['amount'] . ' - ' . $_POST['date'] . ' - Canal de Venda: ' . $_POST['canal_de_venda'] . '</p>';

    }

    // Obter todos os usuários
    $users = get_users();

    // Renderizar formulário
    ?>
  <h2>Adicionar Gastos ao franqueado</h2>
<form method="post" action="">
    <label for="user_id">Usuário:</label>
    <select id="user_id" name="user_id" required>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="product">Produto:</label>
    <input type="text" id="product" name="product" required>

    <label for="amount">Valor:</label>
    <input type="number" id="amount" name="amount" step="0.01" required>

    <label for="date">Data:</label>
    <input type="date" id="date" name="date" placeholder="dd/mm/aaaa" required>

    <label for="canal_de_venda">Canal de Venda:</label>
    <select id="canal_de_venda" name="canal_de_venda" required>
        <option value="">Escolha o canal de venda</option>
        <?php
        // Array com as opções de canal de venda
        $canais_de_venda = array(
            'Dropshipping(suaLoja)',
            'Mercado Livre',
            'Shopee',
            'Amazon',
            'Americanas',
            'Magalu',
            'Shein'
        );

        foreach ($canais_de_venda as $canal): ?>
            <option value="<?php echo $canal; ?>"><?php echo $canal; ?></option>
        <?php endforeach; ?>
    </select>

    <input type="submit" name="add_expense" value="Adicionar Gasto">
</form>

<?php
// Verifica se o formulário foi enviado
if (isset($_POST['add_expense'])) {
    $user_id = $_POST['user_id'];
    $product = $_POST['product'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $canal_de_venda = $_POST['canal_de_venda'];

    // Verifica se o usuário atual pode adicionar gastos (deve ser editor ou administrador)
    if (current_user_can('editor') || current_user_can('administrator')) {
        // Obtém os gastos existentes do usuário ou inicializa um array vazio
        $user_expenses = get_user_meta($user_id, 'expense_details', true);
        if (!$user_expenses || !is_array($user_expenses)) {
            $user_expenses = array();
        }

             // Atualiza os gastos do usuário com o novo array de gastos
        update_user_meta($user_id, 'expense_details', $user_expenses);

        echo '<p>Gasto adicionado com sucesso para o usuário.</p>';
    } else {
        echo '<p>Você não tem permissão para adicionar gastos.</p>';
    }
}
?>

  
<h2>Remover gastos do usuário</h2>
<form method="post" action="">
    <label for="remove_user_id">Usuário:</label>
    <select id="remove_user_id" name="remove_user_id" required>
        <?php foreach ($users as $user): ?>
            <option value="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></option>
        <?php endforeach; ?>
    </select>

    <label for="remove_amount">Valor a remover:</label>
    <input type="number" id="remove_amount" name="remove_amount" step="0.01" required>

    <input type="submit" value="Remover Gasto">
</form>

<!-- Exibir todos os gastos dos usuários -->
<h2>Gastos dos usuários:</h2>
<div class="user-buttons">
    <?php foreach ($users as $user): ?>
        <?php $expenses = get_user_meta($user->ID, 'expense_details', true); ?>
        <?php if (is_array($expenses) && count($expenses) > 0): ?>
            <button class="open-expenses" data-user-id="<?php echo $user->ID; ?>"><?php echo $user->display_name; ?></button>
            <div class="expense-details" id="user-<?php echo $user->ID; ?>">
                <div class="modal-content">
                    <span class="close-button">×</span>
                    <h3><?php echo $user->display_name; ?></h3>
                    <table>
                        <tr>
                            <th>Produto</th>
                            <th>Valor</th>
                            <th>Data</th>
                            <th>Canal de Venda</th> <!-- Coluna "Canal de Venda" adicionada aqui -->
                        </tr>
                        <?php foreach ($expenses as $expense): ?>
                            <tr>
                                <td><?php echo $expense['product']; ?></td>
                                <td>R$<?php echo number_format($expense['amount'], 2, ',', '.'); ?></td>
                                <?php
                                $adjusted_date = ltrim($expense['date'], '-');
                                $timestamp = strtotime($adjusted_date);
                                ?>
                                <td><?php echo date('d/m/Y', $timestamp); ?></td>
                                <td><?php echo $expense['canal_de_venda']; ?></td> <!-- Incluindo o Canal de Venda na mesma coluna -->
                            </tr>
                        <?php endforeach; ?>
                    </table>
                    <button class="close-expenses">Fechar</button>
                </div>
            </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>





<style>

    <style>

    .user-buttons {
        width: 90%;
        margin: 20px auto;
        display: flex;   /* Adiciona flexbox */
        flex-wrap: wrap; /* Permite que os itens de flex envolvam para a próxima linha */
        justify-content: space-between; /* Espaça os itens de flex igualmente */
    }

    .expense-details {
        /* ...outros estilos... */
        width: calc(50% - 10px); /* Define a largura para 50%, subtrai um pouco para o espaço entre */
        box-sizing: border-box;  /* Garante que o padding e a borda estejam incluídos na largura total */
    }

    @media (max-width: 600px) {
        /* Se a largura da tela for 600px ou menos, faça os cards preencherem a largura total */
        .expense-details {
            width: 100%;
        }
    }

    body {
        font-family: Arial, sans-serif;
        line-height: 1.6;
    }

    form {
        width: 70%;
        margin: 20px auto;
        background: #f4f4f4;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 2px 2px 15px rgba(0, 0, 0, .1);
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
    }

    label {
        display: block;
        margin: 10px 0;
        font-weight: bold;
    }

    input[type="text"], input[type="number"], input[type="date"], select {
        width: 100%;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }

    input[type="submit"] {
        display: block;
        width: 100%;
        padding: 10px;
        background: #333;
        color: #fff;
        border: none;
        border-radius: 5px;
        margin-top: 20px;
    }

    input[type="submit"]:hover {
        background: #444;
        cursor: pointer;
    }

    .user-buttons {
        width: 70%;
        margin: 20px auto;
    }

    .open-expenses {
        display: block;
        width: 100%;
        padding: 10px;
        margin-bottom: 10px;
        background: #333;
        color: #fff;
        border: none;
        border-radius: 5px;
        text-align: center;
    }

    .expense-details {
        display: none;
        margin-top: 20px;
        background: #f4f4f4;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 2px 2px 15px rgba(0, 0, 0, .1);
    }

    .close-button {
        float: right;
        font-size: 30px;
        color: #333;
        cursor: pointer;
    }

    .modal-content h3 {
        text-align: center;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    table, th, td {
        border: 1px solid #ddd;
    }

    th, td {
        padding: 10px;
        text-align: left;
    }

    .close-expenses {
        display: block;
        width: 100%;
        padding: 10px;
        background: #333;
        color: #fff;
        border: none;
        border-radius: 5px;
        margin-top: 20px;
        text-align: center;
    }

    .close-expenses:hover {
        background: #444;
        cursor: pointer;
    }
</style>

    
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

    document.addEventListener('DOMContentLoaded', function() {
    let buttons = document.querySelectorAll('.open-expenses');
    buttons.forEach(function(button) {
        button.addEventListener('click', function() {
            let userId = button.getAttribute('data-user-id');
            document.getElementById('user-' + userId).style.display = 'block';
        });
    });

    let closeButtons = document.querySelectorAll('.close-expenses');
    closeButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            button.parentElement.style.display = 'none';
        });
    });
});
    document.addEventListener('DOMContentLoaded', function() {
    let buttons = document.querySelectorAll('.open-expenses');
    buttons.forEach(function(button) {
        button.addEventListener('click', function() {
            let userId = button.getAttribute('data-user-id');
            document.getElementById('user-' + userId).style.display = 'block';
        });
    });

    let closeButtons = document.querySelectorAll('.close-expenses, .close-button');
    closeButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            button.closest('.expense-details').style.display = 'none';
        });
    });
});
document.addEventListener('DOMContentLoaded', (event) => {
    // Obtenha todos os botões que abrem o popup e atribua a eles um evento de clique.
    const openButtons = document.querySelectorAll('.open-expenses');
    openButtons.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const userId = e.target.getAttribute('data-user-id');
            const modal = document.querySelector('#user-' + userId);
            modal.style.display = 'block';
        });
    });

    // Obtenha todos os botões de fechamento e atribua a eles um evento de clique.
    const closeButtons = document.querySelectorAll('.close-button');
    closeButtons.forEach((btn) => {
        btn.addEventListener('click', (e) => {
            const modal = e.target.parentNode.parentNode;
            modal.style.display = 'none';
        });
    });
});

</script>

    <?php
}

function add_expense_detail($user_id, $product, $amount) {
    if (current_user_can('editor') || current_user_can('administrator')  || current_user_can('contributor')|| current_user_can('client')) {
        $current_expense_details = get_user_meta($user_id, 'expense_details', true);
        if (!is_array($current_expense_details)) {
            $current_expense_details = array();
        }

        // Converter a data para o formato aaaa-mm-dd
        $date_parts = explode('/', $_POST['date']);
        $formatted_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];

        $new_expense_detail = array(
            'product' => $product,
            'amount' => $amount,
            'date' => $formatted_date,
            'canal_de_venda' => $_POST['canal_de_venda'], // Incluir o canal de venda no novo gasto
        );
        array_push($current_expense_details, $new_expense_detail);
        update_user_meta($user_id, 'expense_details', $current_expense_details);
    }
}



function remove_expense($user_id, $amount) {
    if (current_user_can('editor') || current_user_can('administrator') || current_user_can('contributor')) {
        $current_total = get_user_meta($user_id, 'total_expense', true);
        if (!is_numeric($current_total)) {
            $current_total = 0;
        }
        $new_total = $current_total - $amount;
        if ($new_total < 0) {
            $new_total = 0;
        }
        update_user_meta($user_id, 'total_expense', $new_total);
    }
}

function edit_expense($user_id, $new_amount) {
    if (current_user_can('editor') || current_user_can('administrator')
 || current_user_can('contributor')
|| current_user_can('client')) {
        update_user_meta($user_id, 'total_expense', $new_amount);
    }
}
// Função para remover todos os gastos de um usuário
function clear_expenses($user_id) {
    if (current_user_can('editor') || current_user_can('administrator') || current_user_can('contributor')) {
        update_user_meta($user_id, 'total_expense', 0);
        update_user_meta($user_id, 'expense_details', array());
    }
}
function enqueue_ui_style()  {
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
}
add_action( 'wp_enqueue_scripts', 'enqueue_ui_style' );


function add_jquery_ui() {
    wp_enqueue_script( 'jquery-ui-dialog' );
}
add_action( 'wp_enqueue_scripts', 'add_jquery_ui' );


function add_jquery_ui_css() {
    wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
}
add_action( 'wp_enqueue_scripts', 'add_jquery_ui_css' );

function user_expense_details_table($user_id) {
    $expense_details = get_user_meta($user_id, 'expense_details', true);

    if (is_array($expense_details) && count($expense_details) > 0) {
        ob_start();
        ?>
        <h2>Detalhes de Gastos</h2>
        <table style="width: 100%; border-collapse: collapse;">
           <tr style="background-color: #f2f2f2;">
              <th style="border: 1px solid #ddd; padding: 8px;">Produto</th>
              <th style="border: 1px solid #ddd; padding: 8px;">Valor(Custo)</th>
              <th style="border: 1px solid #ddd; padding: 8px;">Data</th>
              <th style="border: 1px solid #ddd; padding: 8px;">Canal de Vendas</th>
           </tr>

            <?php foreach ($expense_details as $expense): ?>
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $expense['product']; ?></td>
                    
                    <td style="border: 1px solid #ddd; padding: 8px;">R$<?php echo number_format($expense['amount'], 2, ',', '.'); ?></td>
                    <?php
                    $adjusted_date = ltrim($expense['date'], '-');
                    $timestamp = strtotime($adjusted_date);
                    ?>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo date('d/m/Y', $timestamp); ?></td>
                    <td style="border: 1px solid #ddd; padding: 8px;"><?php echo $expense['canal_de_venda']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
        return ob_get_clean();
    } else {
        return "Nenhum detalhe de gasto encontrado.";
    }
}


function user_expense_details_shortcode() {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $user_expense_details_table = user_expense_details_table($user_id);
        wp_enqueue_style('wp-jquery-ui-dialog'); // Carrega o CSS do jQuery UI Dialog
        wp_enqueue_script('jquery-ui-dialog'); // Carrega o script do jQuery UI Dialog

        ob_start();
        ?>
        <div class="user-expense-details">
            <?php echo $user_expense_details_table; ?>
        </div>
        <script>
            jQuery(document).ready(function($) {
                // Código JavaScript aqui para exibir o diálogo quando o botão for clicado
                // (Você pode manter o mesmo código JavaScript usado anteriormente aqui)
            });
        </script>
        <?php
        return ob_get_clean();
    } else {
        return "Você deve estar logado para ver os detalhes de gastos.";
    }
}

add_shortcode('user_expense_details', 'user_expense_details_shortcode');



function format_date($date) {
    $formatted_date = DateTime::createFromFormat('Y-m-d', $date);
    return $formatted_date ? $formatted_date->format('d/m/Y') : 'Data inválida';
}
function process_limit_form() {
    if (isset($_POST['user_id']) && isset($_POST['limit'])) {
        $user_id = $_POST['user_id'];
        $limit = $_POST['limit'];

        // Chame a função para definir o limite de crédito do usuário
        set_expense_limit($user_id, $limit);

        // Exiba uma mensagem de sucesso
        echo '<p>Limite de crédito definido com sucesso para o usuário ID ' . $user_id . ': R$' . number_format($limit, 2, ',', '.') . '</p>';
    }
}
add_action('admin_init', 'process_limit_form');

// Criação de email para os franqueados
function check_date_and_send_email() {
    // Obter o dia atual
    $current_day = date('j');

 // Obter o ID do template do banco de dados
    $template_id = get_option('template_id', '');

    // Obter o template do e-mail
    $template = get_email_template($template_id);
    if (!$template) {
        error_log('Template não encontrado.');
        return;
    }

    
    // Obter os dias de lembrete do banco de dados
    $reminder_days = explode(',', get_option('reminder_days', ''));

    // Verificar se o dia atual está nos dias de lembrete
    if (in_array($current_day, $reminder_days)) {
        // Buscar todos os usuários
        $users = get_users();

        // Iterar sobre cada usuário
        foreach ($users as $user) {
            // Obter o total de despesas do usuário
            $total_expense = get_user_meta($user->ID, 'total_expense', true);

            // Se o usuário tem alguma despesa
            if ($total_expense > 10) {
                // Aqui você pode enviar um e-mail para o usuário
                $to = $user->user_email;

                // Assunto do email
                $subject = get_option('email_subject', '');

              // Conteúdo do email
    $message = str_replace(
        ['[nome_do_usuário]', '[valor_devido]', '[link_para_pagamento]'], 
        [$user->user_login, $total_expense, "https://inovetime.com.br/teste-3/"], 
        $template
    );


                // Enviar o email
                $mail_success = wp_mail($to, $subject, $message);

                // Debug
                error_log("Enviando email para {$user->display_name} ({$to}) com valor devido de {$total_expense}.");
                if (!$mail_success) {
                    error_log("Falha ao enviar o email para {$to}.");
                }
            }
        }
    }
}


function email_settings_page() {
    add_menu_page(
        'Configurações do Email',
        'Configurações do Email',
        'manage_options',
        'email-settings',
        'email_settings_page_content',
        'dashicons-email',
        20
    );
}
add_action('admin_menu', 'email_settings_page');

function email_settings_page_content() {
    // Verificar se o formulário foi submetido
    if (isset($_POST['email_subject']) && isset($_POST['email_content'])) {
        // Atualizar as opções no banco de dados
        update_option('email_subject', sanitize_text_field($_POST['email_subject']));
        update_option('email_content', wp_kses_post($_POST['email_content']));
        update_option('reminder_days', sanitize_text_field($_POST['reminder_days']));
    }

    // Verificar se o formulário foi submetido
    if (isset($_POST['email_subject']) && isset($_POST['email_content']) && isset($_POST['template_id'])) {
        // ...

        // Atualizar a opção 'template_id' no banco de dados
        update_option('template_id', sanitize_text_field($_POST['template_id']));
    }

    // Se o botão de envio de email de teste foi pressionado, enviar o email de teste
    if (isset($_POST['send_test']) && isset($_POST['test_user'])) {
        $test_user_id = sanitize_text_field($_POST['test_user']);
        $test_user = get_user_by('id', $test_user_id);

        if ($test_user) {
            $total_expense = get_user_meta($test_user->ID, 'total_expense', true);

            $to = sanitize_email($test_user->user_email);
            $subject = get_option('email_subject', '');

            $message_template = get_option('email_content', '');
            $message = str_replace(
                ['[nome_do_usuário]', '[valor_devido]', '[link_para_pagamento]'], 
                [$test_user->display_name, $total_expense, "http://example.com/payment-page"], 
                $message_template
            );

            wp_mail($to, $subject, $message);
        } else {
            echo 'Usuário não encontrado.';
        }
    }

    if (isset($_POST['send_all'])) {
        send_email_to_all_users();
    }

    echo '<div class="admin-email-editor">';
    // ... seu código de formulário aqui ...
    

    // Obter as opções do banco de dados
    $email_subject = get_option('email_subject', '');
    $email_content = get_option('email_content', '');
    $reminder_days = get_option('reminder_days', '2,3,5,15,17,20');


// Obter todos os modelos
    global $wpdb;
    $templates = $wpdb->get_results("SELECT * FROM wp_email_templates");

    // ...

    // Campo de seleção de modelo
    echo '<label for="template_id">Escolha o Modelo:</label><br>';
    echo '<select id="template_id" name="template_id">';
    foreach ($templates as $template) {
        echo '<option value="' . esc_attr($template->id) . '">' . esc_html($template->template_name) . '</option>';
    }
    echo '</select><br>';


    // Obter todos os usuários
    $users = get_users();

    // Exibir o formulário
    echo '<form method="post">';
    echo '<label for="email_subject">Assunto do Email:</label><br>';
    echo '<input type="text" id="email_subject" name="email_subject" value="' . esc_attr($email_subject) . '"><br>';
    echo '<label for="email_content">Conteúdo do Email:</label><br>';
    echo '<textarea id="email_content" name="email_content">' . esc_textarea($email_content) . '</textarea><br>';
    echo '<label for="reminder_days">Dias para enviar lembretes (separados por vírgulas):</label><br>';
    echo '<input type="text" id="reminder_days" name="reminder_days" value="' . esc_attr($reminder_days) . '"><br>';

    // Campo de seleção de usuário para teste
    echo '<label for="test_user">Usuário para teste:</label><br>';
    echo '<select id="test_user" name="test_user">';
    foreach ($users as $user) {
        echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . '</option>';
    }
    echo '</select><br>';

    echo '<input type="submit" name="send_test" value="Enviar Teste"><br>';
    echo '<input type="submit" name="send_all" value="Enviar para todos"><br>'; // Novo botão
    echo '<input type="submit" value="Salvar">';
    echo '</form>';
    echo '</div>';

}

if (!wp_next_scheduled('check_date_and_send_email')) {
    wp_schedule_event(time(), 'daily', 'check_date_and_send_email');
}
add_action('check_date_and_send_email', 'check_date_and_send_email');


// Adicione isso ao seu functions.php ou a um plugin personalizado

// 2. Funções para inserir e recuperar modelos

function insert_email_template($template_name, $email_subject, $email_content) {
    global $wpdb;

    $wpdb->insert('wp_email_templates', array(
      'template_name' => $template_name,
      'email_subject' => $email_subject,
      'email_content' => $email_content,
    ));
}

function get_email_template($template_id) {
    global $wpdb;

    $template = $wpdb->get_row("SELECT * FROM wp_email_templates WHERE id = $template_id");
    return $template;
}

// 3. Página de administração




function email_templates_page_content() {
    // Inserir novo modelo
    if (isset($_POST['new_template_name']) && isset($_POST['new_email_subject']) && isset($_POST['new_email_content'])) {
        insert_email_template($_POST['new_template_name'], $_POST['new_email_subject'], $_POST['new_email_content']);

        // Adicionar uma mensagem de sucesso
        add_settings_error(
            'emailTemplates',
            'emailTemplateAdded',
            'Novo modelo de email adicionado com sucesso.',
            'updated'
        );
    }

    // Mostrar todos os modelos
    global $wpdb;
    $templates = $wpdb->get_results("SELECT * FROM wp_email_templates");

    echo '<div class="wrap">';
    echo '<h1>Modelos de Email</h1>';

    // Mostrar as mensagens
    settings_errors('emailTemplates');

    foreach ($templates as $template) {
        echo '<h2>' . esc_html($template->template_name) . '</h2>';
        echo '<p>Assunto: ' . esc_html($template->email_subject) . '</p>';
        echo '<p>Conteúdo: ' . esc_html($template->email_content) . '</p>';
    }

    // Formulário para adicionar um novo modelo
    echo '<h2>Adicionar novo modelo</h2>';
    echo '<form method="post">';
    echo '<label for="new_template_name">Nome do Modelo:</label><br>';
    echo '<input type="text" id="new_template_name" name="new_template_name"><br>';
    echo '<label for="new_email_subject">Assunto do Email:</label><br>';
    echo '<input type="text" id="new_email_subject" name="new_email_subject"><br>';
    echo '<label for="new_email_content">Conteúdo do Email:</label><br>';
    echo '<textarea id="new_email_content" name="new_email_content"></textarea><br>';
    echo '<input type="submit" value="Adicionar Modelo">';
    echo '</form>';
    echo '</div>';
}

function add_email_templates_page() {
    add_menu_page('Modelos de Email', 'Modelos de Email', 'manage_options', 'email-templates', 'email_templates_page_content');
}
add_action('admin_menu', 'add_email_templates_page');


// final de email teste





function custom_admin_css() {
    echo '
    
       <style>
    .admin-email-editor {
        max-width: 600px;
        margin: auto;
        background: #f1f1f1;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0px 0px 10px rgba(0,0,0,0.1);
    }
    .admin-email-editor form {
        display: flex;
        flex-direction: column;
    }
    .admin-email-editor label {
        font-weight: 600;
        margin-top: 10px;
    }
    .admin-email-editor input[type=text], 
    .admin-email-editor select, 
    .admin-email-editor textarea {
        width: 100%;
        padding: 10px;
        margin-top: 5px;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
    .admin-email-editor input[type=submit] {
        margin-top: 20px;
        padding: 10px;
        border: none;
        color: #fff;
        background: #333;
        cursor: pointer;
        border-radius: 5px;
    }
    .admin-email-editor input[type=submit]:hover {
        background: #555;
    }
</style>

    

    ';
}
add_action('admin_head', 'custom_admin_css');

// Função para definir a capacidade personalizada "gerenciar_plugin_credito_reserva" e adicioná-la aos usuários desejados
function add_custom_role_gerenciar_plugin_credito_reserva() {
    add_role('gerenciar_plugin_credito_reserva', 'Gerenciar Plugin Crédito Reserva', array(
        'read' => true,
        'edit_posts' => true,
        'upload_files' => true,
        // Adicione aqui outras capacidades que você deseja conceder aos usuários com essa função
    ));
}

// Vincular a função à ativação do plugin
register_activation_hook(__FILE__, 'add_custom_role_gerenciar_plugin_credito_reserva');




?>
