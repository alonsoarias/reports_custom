// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Filter module for handling report filters in block_reports_custom.
 *
 * @module     block_reports_custom/filter
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/str', 'core/notification'], function(Str, Notification) {
    'use strict';

    /**
     * Filter manager class.
     *
     * @class
     * @param {HTMLElement} formElement - The filter form element.
     */
    var FilterManager = function(formElement) {
        this.form = formElement;
        this.debounceTimeout = null;
        this.debounceDelay = 300;
    };

    /**
     * Get all current filter values from the form.
     *
     * @returns {Object} The filter values.
     */
    FilterManager.prototype.getFilterValues = function() {
        var formData = new FormData(this.form);
        var values = {};

        formData.forEach(function(value, key) {
            if (value !== '' && value !== null) {
                values[key] = value;
            }
        });

        return values;
    };

    /**
     * Set a filter value.
     *
     * @param {string} name - The filter name.
     * @param {string} value - The filter value.
     */
    FilterManager.prototype.setFilterValue = function(name, value) {
        var input = this.form.querySelector('[name="' + name + '"]');
        if (input) {
            input.value = value;
        }
    };

    /**
     * Get a specific filter value.
     *
     * @param {string} name - The filter name.
     * @returns {string} The filter value.
     */
    FilterManager.prototype.getFilterValue = function(name) {
        var input = this.form.querySelector('[name="' + name + '"]');
        return input ? input.value : '';
    };

    /**
     * Reset all filters to their default values.
     */
    FilterManager.prototype.resetFilters = function() {
        this.form.reset();
        // Also reset hidden inputs for alphabet filters.
        var hiddenInputs = this.form.querySelectorAll('input[type="hidden"]');
        hiddenInputs.forEach(function(input) {
            if (input.name === 'firstname' || input.name === 'lastname') {
                input.value = '';
            }
        });
    };

    /**
     * Debounce a function call.
     *
     * @param {Function} callback - The function to debounce.
     */
    FilterManager.prototype.debounce = function(callback) {
        var self = this;
        if (this.debounceTimeout) {
            clearTimeout(this.debounceTimeout);
        }
        this.debounceTimeout = setTimeout(function() {
            callback.call(self);
        }, this.debounceDelay);
    };

    /**
     * Build query string from filter values.
     *
     * @returns {string} The query string.
     */
    FilterManager.prototype.buildQueryString = function() {
        var values = this.getFilterValues();
        return Object.keys(values)
            .map(function(key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(values[key]);
            })
            .join('&');
    };

    /**
     * Alphabet filter handler class.
     *
     * @class
     * @param {HTMLElement} container - The alphabet filter container.
     * @param {Function} onChange - Callback when filter changes.
     */
    var AlphabetFilter = function(container, onChange) {
        this.container = container;
        this.filterName = container.dataset.filter;
        this.hiddenInput = document.querySelector('input[name="' + this.filterName + '"]');
        this.onChange = onChange;
        this.init();
    };

    /**
     * Initialize the alphabet filter.
     */
    AlphabetFilter.prototype.init = function() {
        var self = this;
        var links = this.container.querySelectorAll('a[data-letter]');

        links.forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                self.selectLetter(this.dataset.letter);
            });
        });
    };

    /**
     * Select a letter in the alphabet filter.
     *
     * @param {string} letter - The letter to select.
     */
    AlphabetFilter.prototype.selectLetter = function(letter) {
        // Update hidden input.
        if (this.hiddenInput) {
            this.hiddenInput.value = letter;
        }

        // Update active state.
        var links = this.container.querySelectorAll('a[data-letter]');
        links.forEach(function(link) {
            link.classList.remove('active');
            if (link.dataset.letter === letter) {
                link.classList.add('active');
            }
        });

        // Trigger change callback.
        if (typeof this.onChange === 'function') {
            this.onChange(this.filterName, letter);
        }
    };

    /**
     * Get the current selected letter.
     *
     * @returns {string} The selected letter.
     */
    AlphabetFilter.prototype.getSelectedLetter = function() {
        return this.hiddenInput ? this.hiddenInput.value : '';
    };

    /**
     * Update active state based on current value.
     */
    AlphabetFilter.prototype.updateActiveState = function() {
        var currentValue = this.getSelectedLetter();
        var links = this.container.querySelectorAll('a[data-letter]');
        links.forEach(function(link) {
            link.classList.remove('active');
            if (link.dataset.letter === currentValue) {
                link.classList.add('active');
            }
        });
    };

    /**
     * Course selector handler class.
     *
     * @class
     * @param {HTMLElement} categorySelect - The category select element.
     * @param {HTMLElement} courseSelect - The course select element.
     * @param {Object} repository - The repository module for AJAX calls.
     * @param {Function} onChange - Callback when selection changes.
     */
    var CourseSelector = function(categorySelect, courseSelect, repository, onChange) {
        this.categorySelect = categorySelect;
        this.courseSelect = courseSelect;
        this.repository = repository;
        this.onChange = onChange;
        this.allOptionText = 'All';
        this.init();
    };

    /**
     * Initialize the course selector.
     */
    CourseSelector.prototype.init = function() {
        var self = this;

        // Load "All" text from first option.
        var firstOption = this.courseSelect.querySelector('option');
        if (firstOption) {
            this.allOptionText = firstOption.textContent;
        }

        this.categorySelect.addEventListener('change', function() {
            self.loadCourses(this.value);
        });

        this.courseSelect.addEventListener('change', function() {
            if (typeof self.onChange === 'function') {
                self.onChange('course', this.value);
            }
        });
    };

    /**
     * Load courses for a category.
     *
     * @param {number} categoryId - The category ID.
     * @returns {Promise} A promise that resolves when courses are loaded.
     */
    CourseSelector.prototype.loadCourses = function(categoryId) {
        var self = this;

        return this.repository.getCourses(categoryId)
            .then(function(courses) {
                self.updateCourseOptions(courses);
                if (typeof self.onChange === 'function') {
                    self.onChange('category', categoryId);
                }
            })
            .catch(function(error) {
                Notification.exception(error);
            });
    };

    /**
     * Update course select options.
     *
     * @param {Array} courses - Array of course objects.
     */
    CourseSelector.prototype.updateCourseOptions = function(courses) {
        // Clear existing options except "All".
        this.courseSelect.innerHTML = '';

        // Add "All" option.
        var allOption = document.createElement('option');
        allOption.value = '';
        allOption.textContent = this.allOptionText;
        this.courseSelect.appendChild(allOption);

        // Add course options.
        courses.forEach(function(course) {
            var option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.fullname;
            this.courseSelect.appendChild(option);
        }, this);
    };

    return {
        FilterManager: FilterManager,
        AlphabetFilter: AlphabetFilter,
        CourseSelector: CourseSelector
    };
});
