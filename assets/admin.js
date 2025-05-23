jQuery(document).ready(function($) {
    
    $('.esm-cities-select, .esm-categories-select').select2({
        placeholder: 'Seleccionar opciones...',
        allowClear: true,
        closeOnSelect: false,
        width: '100%'
    });
    
    $('.esm-save-restrictions').on('click', function() {
        var button = $(this);
        var userId = button.data('user-id');
        var row = button.closest('tr');
        var statusSpan = row.find('.esm-save-status');
        
        var cities = row.find('.esm-cities-select').val() || [];
        var categories = row.find('.esm-categories-select').val() || [];
        
        cities = cities.filter(function(city) {
            return city !== '';
        });
        
        categories = categories.filter(function(category) {
            return category !== '';
        });
        
        button.addClass('loading').text('Guardando...');
        statusSpan.removeClass('success error').text('');
        
        $.ajax({
            url: esm_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'esm_save_user_restrictions',
                nonce: esm_ajax.nonce,
                user_id: userId,
                cities: cities,
                categories: categories
            },
            success: function(response) {
                if (response.success) {
                    statusSpan.addClass('success').text('✓ Guardado');
                    updateCurrentSelection(row, cities, categories);
                    
                    setTimeout(function() {
                        statusSpan.fadeOut(function() {
                            $(this).text('').removeClass('success').show();
                        });
                    }, 3000);
                } else {
                    statusSpan.addClass('error').text('✗ Error');
                }
            },
            error: function() {
                statusSpan.addClass('error').text('✗ Error de conexión');
            },
            complete: function() {
                button.removeClass('loading').text('Guardar');
            }
        });
    });
    
    function updateCurrentSelection(row, cities, categories) {
        var citiesText = cities.length > 0 ? cities.join(', ') : 'Todas las ciudades';
        var categoriesText = categories.length > 0 ? categories.join(', ') : 'Todas las categorías';
        
        row.find('td:nth-child(3) .esm-current-selection small').html('<strong>Actual:</strong> ' + citiesText);
        row.find('td:nth-child(4) .esm-current-selection small').html('<strong>Actual:</strong> ' + categoriesText);
    }
    
    $('.esm-cities-select, .esm-categories-select').on('change', function() {
        var row = $(this).closest('tr');
        var saveButton = row.find('.esm-save-restrictions');
        var statusSpan = row.find('.esm-save-status');
        
        saveButton.addClass('button-primary').removeClass('button-secondary');
        statusSpan.removeClass('success error').text('');
    });
    
    $('.esm-select-all').on('click', function() {
        var target = $(this).data('target');
        var userId = $(this).data('user-id');
        var row = $(this).closest('tr');
        var select = row.find('.esm-' + target + '-select');
        
        select.find('option').prop('selected', true);
        select.trigger('change');
    });
    
    $('.esm-clear-all').on('click', function() {
        var target = $(this).data('target');
        var userId = $(this).data('user-id');
        var row = $(this).closest('tr');
        var select = row.find('.esm-' + target + '-select');
        
        select.val(null);
        select.trigger('change');
    });
    
    $('details summary').on('click', function() {
        var details = $(this).parent();
        setTimeout(function() {
            if (details.attr('open')) {
                details.find('ul').slideDown(200);
            } else {
                details.find('ul').slideUp(200);
            }
        }, 10);
    });
});