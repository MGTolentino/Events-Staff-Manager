jQuery(document).ready(function($) {
    
    if (typeof console !== 'undefined' && console.log) {
        console.log('Events Staff Manager: Filtros de usuario aplicados');
    }
    
    $(document).on('DOMContentLoaded', function() {
        checkUserRestrictions();
    });
    
    function checkUserRestrictions() {
        
        if ($('.ltb-leads-table').length > 0) {
            if (typeof console !== 'undefined' && console.log) {
                console.log('Events Staff Manager: Tabla de leads detectada, filtros activos');
            }
        }
    }
    
    checkUserRestrictions();
});