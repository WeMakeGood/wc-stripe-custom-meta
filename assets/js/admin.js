/**
 * Admin Page JavaScript
 *
 * Handles dynamic metadata row management for the Stripe custom metadata settings.
 *
 * @package WC_Stripe_Custom_Meta
 */

(function($) {
	'use strict';

	$(document).ready(function() {
		// Handle "Add Row" button for static metadata
		$(document).on('click', '#add-static-metadata-row', function(e) {
			e.preventDefault();
			addMetadataRow();
		});

		// Function to add a new metadata row
		function addMetadataRow() {
			var tbody = $('#static-metadata-tbody');
			var rowCount = tbody.find('tr').length;

			// Create new row HTML
			var newRow = $('<tr class="static-metadata-row">' +
				'<td>' +
				'<input type="text" name="static_metadata_keys[]" maxlength="40" class="regular-text" placeholder="e.g., store_name" />' +
				'</td>' +
				'<td>' +
				'<input type="text" name="static_metadata_values[]" maxlength="500" class="large-text" placeholder="e.g., My Store" />' +
				'</td>' +
				'<td class="action-column">' +
				'<button type="button" class="button button-link-delete remove-metadata-row">Remove</button>' +
				'</td>' +
				'</tr>');

			// Append to table
			tbody.append(newRow);
		}

		// Handle "Remove" button for metadata rows
		$(document).on('click', '.remove-metadata-row', function(e) {
			e.preventDefault();
			$(this).closest('tr').remove();

			// Ensure at least one empty row remains
			var tbody = $('#static-metadata-tbody');
			if (tbody.find('tr').length === 0) {
				addMetadataRow();
			}
		});

		// Collapsible sections (optional enhancement)
		$(document).on('click', '.wc-stripe-meta-section-toggle', function(e) {
			e.preventDefault();
			$(this).closest('.wc-stripe-meta-section').toggleClass('collapsed');
		});
	});

})(jQuery);
