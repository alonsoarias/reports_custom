$(document).ready(function() {
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

    $('#course').change(function() {
        updateReport();
    });

    $(document).on('click', '.alphabet-filter a', function(e) {
        e.preventDefault();
        var letter = $(this).data('letter');
        var filter = $(this).closest('.alphabet-filter').data('filter');
        $('input[name="' + filter + '"]').val(letter);
        updateReport();
    });

    function initializePagination() {
        $('.paging_bar a').on('click', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            $('input[name="page"]').val(page);
            updateReport();
        });
    }

    initializePagination();
});
