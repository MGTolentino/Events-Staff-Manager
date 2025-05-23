jQuery(document).ready(function($) {
    
    if (typeof console !== 'undefined' && console.log) {
        console.log('Events Staff Manager: Plugin cargado');
    }
    
    // Debug de restricciones de usuario
    function debugUserRestrictions() {
        $.ajax({
            url: '/wp-admin/admin-ajax.php',
            type: 'POST',
            data: {
                action: 'esm_debug_restrictions'
            },
            success: function(response) {
                if (response.success) {
                    console.log('Events Staff Manager - Debug:', response.data);
                    
                    if (response.data.should_apply && response.data.allowed_cities && response.data.allowed_cities.length > 0) {
                        console.log('✅ Filtros ACTIVOS para ciudades:', response.data.allowed_cities);
                    } else if (response.data.should_apply) {
                        console.log('⚠️ Usuario es ejecutivo pero SIN restricciones específicas');
                    } else {
                        console.log('❌ Filtros NO aplicables para este usuario');
                    }
                } else {
                    console.log('Error en debug:', response);
                }
            },
            error: function() {
                console.log('Error al hacer debug de restricciones');
            }
        });
    }
    
    // Ejecutar debug si estamos en la página de leads
    if (window.location.pathname.includes('/leads/')) {
        debugUserRestrictions();
    }
    
    $(document).on('DOMContentLoaded', function() {
        checkUserRestrictions();
    });
    
    function checkUserRestrictions() {
        if ($('.ltb-leads-table').length > 0 || $('.leads-pipeline-container').length > 0) {
            if (typeof console !== 'undefined' && console.log) {
                console.log('Events Staff Manager: Vista de leads detectada');
            }
        }
    }
    
    checkUserRestrictions();
});