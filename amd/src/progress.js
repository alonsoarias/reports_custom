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
 * Progress report JavaScript module for block_reports_custom.
 *
 * @module     block_reports_custom/progress
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'block_reports_custom/repository',
    'block_reports_custom/filter',
    'core/notification',
    'core/str'
], function(Repository, Filter, Notification, Str) {
    'use strict';

    /**
     * Progress report controller.
     *
     * @class
     */
    var ProgressReport = function() {
        this.form = null;
        this.filterManager = null;
        this.courseSelector = null;
        this.alphabetFilters = [];
        this.reportContainer = null;
    };

    /**
     * Initialize the progress report.
     *
     * @param {Object} config - Configuration object.
     * @param {string} config.formSelector - CSS selector for the filter form.
     * @param {string} config.reportContainerSelector - CSS selector for the report container.
     */
    ProgressReport.prototype.init = function(config) {
        var self = this;

        // Get DOM elements.
        this.form = document.querySelector(config.formSelector || '#filtersForm');
        this.reportContainer = document.querySelector(config.reportContainerSelector || '#reportData');

        if (!this.form || !this.reportContainer) {
            return;
        }

        // Initialize filter manager.
        this.filterManager = new Filter.FilterManager(this.form);

        // Initialize course selector.
        var categorySelect = this.form.querySelector('#category');
        var courseSelect = this.form.querySelector('#course');
        if (categorySelect && courseSelect) {
            this.courseSelector = new Filter.CourseSelector(
                categorySelect,
                courseSelect,
                Repository,
                function() {
                    self.updateReport();
                }
            );
        }

        // Initialize alphabet filters.
        var alphabetContainers = this.form.querySelectorAll('.alphabet-filter');
        alphabetContainers.forEach(function(container) {
            var alphabetFilter = new Filter.AlphabetFilter(container, function() {
                self.updateReport();
            });
            self.alphabetFilters.push(alphabetFilter);
        });

        // Set up event listeners.
        this.setupEventListeners();
    };

    /**
     * Set up event listeners for filter changes.
     */
    ProgressReport.prototype.setupEventListeners = function() {
        var self = this;

        // User type select.
        var userTypeSelect = this.form.querySelector('#usertype');
        if (userTypeSelect) {
            userTypeSelect.addEventListener('change', function() {
                self.updateReport();
            });
        }

        // Date inputs.
        var startDateInput = this.form.querySelector('#startdate');
        var endDateInput = this.form.querySelector('#enddate');
        if (startDateInput) {
            startDateInput.addEventListener('change', function() {
                self.updateReport();
            });
        }
        if (endDateInput) {
            endDateInput.addEventListener('change', function() {
                self.updateReport();
            });
        }

        // ID number input with debounce.
        var idNumberInput = this.form.querySelector('#idnumber');
        if (idNumberInput) {
            idNumberInput.addEventListener('input', function() {
                self.filterManager.debounce(function() {
                    self.updateReport();
                });
            });
        }

        // Pagination links.
        this.setupPaginationListeners();
    };

    /**
     * Set up pagination event listeners.
     */
    ProgressReport.prototype.setupPaginationListeners = function() {
        var self = this;

        // Use event delegation for pagination.
        if (this.reportContainer) {
            this.reportContainer.addEventListener('click', function(e) {
                var link = e.target.closest('.paging a, .pagination a');
                if (link) {
                    e.preventDefault();
                    var href = link.getAttribute('href');
                    var pageMatch = href.match(/page=(\d+)/);
                    if (pageMatch) {
                        self.filterManager.setFilterValue('page', pageMatch[1]);
                        self.updateReport();
                    }
                }
            });
        }
    };

    /**
     * Update the report with current filters.
     */
    ProgressReport.prototype.updateReport = function() {
        var self = this;
        var filterValues = this.filterManager.getFilterValues();

        // Show loading state.
        this.showLoading();

        // Fetch updated report data.
        Repository.fetchReportData(window.location.pathname, filterValues)
            .then(function(html) {
                self.updateReportContent(html);
                self.setupPaginationListeners();
            })
            .catch(function(error) {
                self.showError(error);
            })
            .finally(function() {
                self.hideLoading();
            });
    };

    /**
     * Update the report container content.
     *
     * @param {string} html - The HTML content from the server.
     */
    ProgressReport.prototype.updateReportContent = function(html) {
        // Parse the HTML and extract the report data section.
        var parser = new DOMParser();
        var doc = parser.parseFromString(html, 'text/html');
        var newReportData = doc.querySelector('#reportData');

        if (newReportData && this.reportContainer) {
            this.reportContainer.innerHTML = newReportData.innerHTML;
        }
    };

    /**
     * Show loading indicator.
     */
    ProgressReport.prototype.showLoading = function() {
        if (this.reportContainer) {
            this.reportContainer.classList.add('loading');
            this.reportContainer.style.opacity = '0.5';
        }
    };

    /**
     * Hide loading indicator.
     */
    ProgressReport.prototype.hideLoading = function() {
        if (this.reportContainer) {
            this.reportContainer.classList.remove('loading');
            this.reportContainer.style.opacity = '1';
        }
    };

    /**
     * Show error notification.
     *
     * @param {Error} error - The error object.
     */
    ProgressReport.prototype.showError = function(error) {
        Str.get_string('error', 'core').then(function(errorStr) {
            Notification.alert(errorStr, error.message);
        }).catch(Notification.exception);
    };

    return {
        /**
         * Initialize the progress report module.
         *
         * @param {Object} config - Configuration object.
         */
        init: function(config) {
            var report = new ProgressReport();
            report.init(config || {});
        }
    };
});
