<?php
if (!defined('ABSPATH')) {
    exit;
}

$esm = new Events_Staff_Manager();
$sales_executives = $esm->get_sales_executives();
$available_cities = $esm->get_available_cities();
$available_categories = $esm->get_available_categories();
?>

<div class="wrap">
    <h1>Events Staff Manager</h1>
    <p>Gestiona las funcionalidades de los ejecutivos de ventas para filtrar leads por ciudad y categoría.</p>
    
    <div class="esm-container">
        <?php if (empty($sales_executives)): ?>
            <div class="notice notice-warning">
                <p>No se encontraron usuarios con el rol "ejecutivo_de_ventas".</p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Ejecutivo de Ventas</th>
                        <th>Email</th>
                        <th>Ciudades Permitidas</th>
                        <th>Categorías Permitidas</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales_executives as $user): ?>
                        <?php
                        $allowed_cities = get_user_meta($user->ID, 'esm_allowed_cities', true);
                        $allowed_categories = get_user_meta($user->ID, 'esm_allowed_categories', true);
                        
                        if (!is_array($allowed_cities)) $allowed_cities = array();
                        if (!is_array($allowed_categories)) $allowed_categories = array();
                        ?>
                        <tr data-user-id="<?php echo $user->ID; ?>">
                            <td>
                                <strong><?php echo esc_html($user->display_name); ?></strong>
                                <br>
                                <small>Usuario: <?php echo esc_html($user->user_login); ?></small>
                            </td>
                            <td><?php echo esc_html($user->user_email); ?></td>
                            <td>
                                <div class="esm-select-container">
                                    <button type="button" class="button button-secondary esm-select-all" data-target="cities" data-user-id="<?php echo $user->ID; ?>">
                                        Seleccionar todas
                                    </button>
                                    <button type="button" class="button button-secondary esm-clear-all" data-target="cities" data-user-id="<?php echo $user->ID; ?>">
                                        Limpiar
                                    </button>
                                </div>
                                <select name="cities[]" class="esm-cities-select" multiple="multiple" style="width: 100%;">
                                    <?php foreach ($available_cities as $city): ?>
                                        <option value="<?php echo esc_attr($city); ?>" 
                                                <?php selected(in_array($city, $allowed_cities)); ?>>
                                            <?php echo esc_html($city); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="esm-current-selection">
                                    <small>
                                        <strong>Actual:</strong> 
                                        <?php 
                                        if (empty($allowed_cities)) {
                                            echo 'Todas las ciudades';
                                        } else {
                                            echo esc_html(implode(', ', $allowed_cities));
                                        }
                                        ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div class="esm-select-container">
                                    <button type="button" class="button button-secondary esm-select-all" data-target="categories" data-user-id="<?php echo $user->ID; ?>">
                                        Seleccionar todas
                                    </button>
                                    <button type="button" class="button button-secondary esm-clear-all" data-target="categories" data-user-id="<?php echo $user->ID; ?>">
                                        Limpiar
                                    </button>
                                </div>
                                <select name="categories[]" class="esm-categories-select" multiple="multiple" style="width: 100%;">
                                    <?php foreach ($available_categories as $category): ?>
                                        <option value="<?php echo esc_attr($category); ?>" 
                                                <?php selected(in_array($category, $allowed_categories)); ?>>
                                            <?php echo esc_html($category); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="esm-current-selection">
                                    <small>
                                        <strong>Actual:</strong> 
                                        <?php 
                                        if (empty($allowed_categories)) {
                                            echo 'Todas las categorías';
                                        } else {
                                            echo esc_html(implode(', ', $allowed_categories));
                                        }
                                        ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="button button-primary esm-save-restrictions" 
                                        data-user-id="<?php echo $user->ID; ?>">
                                    Guardar
                                </button>
                                <span class="esm-save-status"></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="esm-info-box">
        <h3>Información del Sistema</h3>
        <p><strong>Ciudades disponibles:</strong> <?php echo count($available_cities); ?></p>
        <p><strong>Categorías disponibles:</strong> <?php echo count($available_categories); ?></p>
        <p><strong>Ejecutivos de ventas:</strong> <?php echo count($sales_executives); ?></p>
        
        <details>
            <summary>Ver todas las ciudades</summary>
            <ul>
                <?php foreach ($available_cities as $city): ?>
                    <li><?php echo esc_html($city); ?></li>
                <?php endforeach; ?>
            </ul>
        </details>
        
        <details>
            <summary>Ver todas las categorías</summary>
            <ul>
                <?php foreach ($available_categories as $category): ?>
                    <li><?php echo esc_html($category); ?></li>
                <?php endforeach; ?>
            </ul>
        </details>
    </div>
</div>