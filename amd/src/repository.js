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
 * Repository module for AJAX calls in block_reports_custom.
 *
 * @module     block_reports_custom/repository
 * @copyright  2025 Alonso Arias <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/ajax', 'core/config'], function(Ajax, Config) {
    'use strict';

    /**
     * Get the base URL for AJAX requests.
     *
     * @returns {string} The base URL.
     */
    var getBaseUrl = function() {
        return Config.wwwroot + '/blocks/reports_custom/reports/';
    };

    /**
     * Fetch courses by category.
     *
     * @param {number} categoryId - The category ID.
     * @returns {Promise} A promise resolving to an array of courses.
     */
    var getCourses = function(categoryId) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            var url = getBaseUrl() + 'get_courses.php?category=' + encodeURIComponent(categoryId);

            xhr.open('GET', url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        resolve(data);
                    } catch (e) {
                        reject(new Error('Invalid JSON response'));
                    }
                } else {
                    reject(new Error('Request failed with status: ' + xhr.status));
                }
            };

            xhr.onerror = function() {
                reject(new Error('Network error'));
            };

            xhr.send();
        });
    };

    /**
     * Fetch enrolled users.
     *
     * @param {Object} params - The parameters for the request.
     * @param {number} [params.course] - The course ID.
     * @param {number} [params.category] - The category ID.
     * @param {string} [params.usertype] - The user type.
     * @returns {Promise} A promise resolving to an array of users.
     */
    var getUsers = function(params) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            var queryString = Object.keys(params)
                .filter(function(key) {
                    return params[key] !== null && params[key] !== undefined && params[key] !== '';
                })
                .map(function(key) {
                    return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
                })
                .join('&');

            var url = getBaseUrl() + 'get_users.php' + (queryString ? '?' + queryString : '');

            xhr.open('GET', url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    try {
                        var data = JSON.parse(xhr.responseText);
                        resolve(data);
                    } catch (e) {
                        reject(new Error('Invalid JSON response'));
                    }
                } else {
                    reject(new Error('Request failed with status: ' + xhr.status));
                }
            };

            xhr.onerror = function() {
                reject(new Error('Network error'));
            };

            xhr.send();
        });
    };

    /**
     * Fetch report data via AJAX.
     *
     * @param {string} reportUrl - The report URL.
     * @param {Object} params - The filter parameters.
     * @returns {Promise} A promise resolving to the HTML content.
     */
    var fetchReportData = function(reportUrl, params) {
        return new Promise(function(resolve, reject) {
            var xhr = new XMLHttpRequest();
            var queryString = Object.keys(params)
                .filter(function(key) {
                    return params[key] !== null && params[key] !== undefined && params[key] !== '';
                })
                .map(function(key) {
                    return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
                })
                .join('&');

            var url = reportUrl + (queryString ? '?' + queryString : '');

            xhr.open('GET', url, true);
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(xhr.responseText);
                } else {
                    reject(new Error('Request failed with status: ' + xhr.status));
                }
            };

            xhr.onerror = function() {
                reject(new Error('Network error'));
            };

            xhr.send();
        });
    };

    return {
        getCourses: getCourses,
        getUsers: getUsers,
        fetchReportData: fetchReportData
    };
});
