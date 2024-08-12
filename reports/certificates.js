$(document).ready(function() {
    // Function to update the report dynamically
    function updateReport() {
        $.ajax({
            url: window.location.href,  // Make sure this points to the correct URL
            type: 'GET',
            data: $('#filtersForm').serialize(),  // Serializes the form's elements.
            success: function(data) {
                $('#reportData').html($(data).find('#reportData').html());
                initializePagination();  // Reinitialize pagination after update
            },
            error: function() {
                alert("Error updating report.");
            }
        });
    }

    // Function to update active filters for the alphabetical navigation
    function updateActiveFilters() {
        $('.alphabet-filter a').removeClass('active');
        $('.alphabet-filter').each(function() {
            var filter = $(this).data('filter');
            var value = $('input[name="' + filter + '"]').val();
            $(this).find('a[data-letter="' + value + '"]').addClass('active');
        });
    }

    // Handler for changes in category which updates courses dynamically
    $('#category').change(function() {
        var categoryId = $(this).val();
        $.ajax({
            url: 'get_courses.php',  // Make sure this URL is correct and accessible
            type: 'GET',
            data: { category: categoryId },
            success: function(data) {
                var courses = JSON.parse(data);
                $('#course').empty().append('<option value="">All</option>');
                $.each(courses, function(index, course) {
                    $('#course').append('<option value="' + course.id + '">' + course.fullname + '</option>');
                });
                updateReport();  // Update report after changing courses
            },
            error: function() {
                alert("Error loading courses.");
            }
        });
    });

    // Event handlers for filters including courses, user type, and date fields
    $('#course, #usertype').change(function() {
        updateReport();
    });

    // Specifically handling date changes
    $('#startdate, #enddate').change(function() {
        // Validate if dates are in the correct range or format if necessary
        updateReport();
    });

    // Handling changes in the ID number input dynamically
    $('#idnumber').on('input', function() {
        updateReport();
    });

    // Alphabetical filter handling
    $(document).on('click', '.alphabet-filter a', function(e) {
        e.preventDefault();
        var letter = $(this).data('letter');
        var filter = $(this).closest('.alphabet-filter').data('filter');
        $('input[name="' + filter + '"]').val(letter);
        updateReport();  // Update report after changing filters
    });

    // Initialize pagination dynamically
    function initializePagination() {
        $('.paging_bar a').on('click', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            $('input[name="page"]').val(page);
            updateReport();  // Update report when pagination changes
        });
    }

    // Initialize functions on page load
    initializePagination();
    updateActiveFilters();
});
