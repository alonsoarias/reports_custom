$(document).ready(function() {
    // Función para actualizar el reporte
    function updateReport() {
        $.ajax({
            url: window.location.href,
            type: 'GET',
            data: $('#filtersForm').serialize(),
            success: function(data) {
                $('#reportData').html($(data).find('#reportData').html());
                initializePagination();
            },
            error: function() {
                alert("Error updating report.");
            }
        });
    }

    // Función para actualizar filtros activos en la navegación alfabética
    function updateActiveFilters() {
        $('.alphabet-filter a').removeClass('active');
        $('.alphabet-filter').each(function() {
            var filter = $(this).data('filter');
            var value = $('input[name="' + filter + '"]').val();
            $(this).find('a[data-letter="' + value + '"]').addClass('active');
        });
    }

    // Evento change para el filtro de categoría
    $('#category').change(function() {
        var categoryId = $(this).val();
        $.ajax({
            url: 'get_courses.php',
            type: 'GET',
            data: { category: categoryId },
            success: function(data) {
                var courses = JSON.parse(data);
                $('#course').empty().append('<option value="">All</option>');
                $.each(courses, function(index, course) {
                    $('#course').append('<option value="' + course.id + '">' + course.fullname + '</option>');
                });
                updateReport();
            },
            error: function() {
                alert("Error loading courses.");
            }
        });
    });

    // Evento change para los filtros de curso y tipo de usuario
    $('#course, #usertype').change(function() {
        updateReport();
    });

    // Evento change para los campos de fecha
    $('#startdate, #enddate').change(function() {
        updateReport();
    });

    // Evento input para el campo de texto idnumber
    $('#idnumber').on('input', function() {
        updateReport();
    });

    // Evento click para los filtros alfabéticos
    $(document).on('click', '.alphabet-filter a', function(e) {
        e.preventDefault();
        var letter = $(this).data('letter');
        var filter = $(this).closest('.alphabet-filter').data('filter');
        $('input[name="' + filter + '"]').val(letter);
        updateReport();
    });

    // Función para inicializar la paginación
    function initializePagination() {
        $('.paging_bar a').on('click', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            $('input[name="page"]').val(page);
            updateReport();
        });
    }

    // Inicialización de funciones al cargar la página
    initializePagination();
    updateActiveFilters();
});
