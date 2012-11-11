// jQuery File Tree Plugin
//
// Version 1.01
//
// Cory S.N. LaViska
// A Beautiful Site (http://abeautifulsite.net/)
// 24 March 2008
//
// Visit http://abeautifulsite.net/notebook.php?article=58 for more information
//
// Usage: $('.fileTreeDemo').fileTree( options, callback )
//
// Options:  root           - root folder to display; default = /
//           script         - location of the serverside AJAX file to use; default = jqueryFileTree.php
//           folderEvent    - event to trigger expand/collapse; default = click
//           expandSpeed    - default = 500 (ms); use -1 for no animation
//           collapseSpeed  - default = 500 (ms); use -1 for no animation
//           expandEasing   - easing function to use on expand (optional)
//           collapseEasing - easing function to use on collapse (optional)
//           multiFolder    - whether or not to limit the browser to one subfolder at a time
//
// History:
//
// 1.01 - updated to work with foreign characters in directory/file names (12 April 2008)
// 1.00 - released (24 March 2008)
//
// TERMS OF USE
//
// This plugin is dual-licensed under the GNU General Public License and the MIT License and
// is copyright 2008 A Beautiful Site, LLC.
//
if(jQuery) (function($){

	$.extend($.fn, {
		fileTree: function(o) {
			var EXCLUDED = 0;
			var INCLUDED = 1;
			var PARTIAL = 2;

			// Defaults
			if( !o ) var o = {};
			if( o.root === undefined ) o.root = '/';
			if( o.script === undefined ) o.script = 'jqueryFileTree.php';
			if( o.folderEvent === undefined ) o.folderEvent = 'click';
			if( o.expandSpeed === undefined ) o.expandSpeed= 500;
			if( o.collapseSpeed === undefined ) o.collapseSpeed= 500;
			if( o.expandEasing === undefined ) o.expandEasing = null;
			if( o.collapseEasing === undefined ) o.collapseEasing = null;
			if( o.multiFolder === undefined ) o.multiFolder = true;

			$(this).each( function() {

				function showTree(c, t) {
					if (get_checkbox_state(c.find('.checkbox')) === EXCLUDED) {
						alert('Please include this directory to see its contents.');
						return;
					}

					$(c).addClass('wait');
					$.post(o.script, { action: 'file_tree', dir: t }, function(data) {
						$(c).find('.start').remove();
						$(c).removeClass('wait').append(data);
						if( o.root === t ) $(c).find('UL:hidden').show(); else $(c).find('UL:hidden').slideDown({ duration: o.expandSpeed, easing: o.expandEasing });

						bindTree(c);
					});
				}

				function bindTree(t) {
					$(t).find('LI A.tree').bind(o.folderEvent, function() {
						if( $(this).parent().hasClass('directory') ) {
							if( $(this).parent().hasClass('collapsed') ) {
								// Expand
								if( !o.multiFolder ) {
									$(this).parent().parent().find('UL').slideUp({ duration: o.collapseSpeed, easing: o.collapseEasing });
									$(this).parent().parent().find('LI.directory').removeClass('expanded').addClass('collapsed');
								}
								$(this).parent().find('UL').remove(); // cleanup
								showTree( $(this).parent(), escape($(this).attr('rel').match( /.*\// )) );
								$(this).parent().removeClass('collapsed').addClass('expanded');
							} else {
								// Collapse
								collapse(this);
							}
						} else {
							var element = $(this).parent().find('.checkbox');
							if (element.length) {
								checkbox_click(element);
							}
						}
						return false;
					});

					//Bind our check box clicks
					$(t).find('ul').find('.checkbox').bind('click', function() {
						if (get_checkbox_state(this) === PARTIAL)
							return;

						checkbox_click(this);
					});

					// Prevent A from triggering the # on non-click events
					if( o.folderEvent.toLowerCase != 'click' ) $(t).find('LI A').bind('click', function() { return false; });
				}

				// Get the initial file list
				showTree( $(this), escape(o.root) );
			});


			function collapse(ele) {
				$(ele).parent().find('UL').slideUp({ duration: o.collapseSpeed, easing: o.collapseEasing });
				$(ele).parent().removeClass('expanded').addClass('collapsed');
			}

			/**
			 * Just like PHP's dirname
			 * @param path
			 */
			function dirname(path) {
				return path.replace(/\/$/, '').replace(/\/[^\/]*$/, '/');
			}

			/**
			 * Updates the tri state check box based on the state hidden element passed
			 * @param check_box
			 */
			function set_checkbox_state(check_box, new_state) {
				new_state = parseInt(new_state, 10);
				$(check_box).removeClass('checked');
				$(check_box).removeClass('partial');
				switch(new_state) {
					case EXCLUDED:
						$(check_box).addClass('checked');
						break;
					case PARTIAL:
						$(check_box).addClass('partial');
						break;
					default:
						break; //INCLUDED - Do nothing
				}
			}

			/**
			 * Toggles the directory check box to ON, OFF or PARTIAL depending on the state of all its children.
			 * @param clicked
			 */
			function toggle_directory_check(clicked) {
				//Also check its directory if they are all not checked
				var checked_count = 0, total = 0;
				var clicked_parent_dir = dirname($(clicked).attr('rel'));
				$('.checkbox').each(function () {
					if (clicked_parent_dir != o.root) {
						var parent_dir = dirname($(this).attr('rel'));
						if (parent_dir === clicked_parent_dir) {
							var state = get_checkbox_state(this);
							if (state === PARTIAL || state === EXCLUDED) {
								checked_count++;
							}
							total++;
						}
					}
				});

				//Now that we know that the state of all the directories children we can update the parent dir accordingly
				$('.checkbox').each(function () {
					if ($(this).attr('rel') === clicked_parent_dir) {
						if (checked_count === total) {
							set_checkbox_state(this, EXCLUDED);
						} else if (checked_count === 0) {
							set_checkbox_state(this, INCLUDED);
						} else {
							set_checkbox_state(this, PARTIAL);
						}
						toggle_directory_check(this);
					}
				});
			}

			/**
			 * Return the current state of a clicked check box
			 * @param clicked
			 */
			function get_checkbox_state(clicked) {
				var state = INCLUDED;
				if ($(clicked).hasClass('partial')) {
					state = PARTIAL;
				} else if ($(clicked).hasClass('checked')) {
					state = EXCLUDED;
				}
				return state;
			}

			/**
			 * The on click function for a file check box. If the user clicks on a directory then all its open children
			 * need to be updated accordingly.
			 * @param clicked
			 */
			function checkbox_click(clicked) {
				var state = get_checkbox_state(clicked) === EXCLUDED ? INCLUDED : EXCLUDED;
				if (state === EXCLUDED)
					collapse(clicked);

				$.post(o.script, { action: 'file_tree', path: $(clicked).attr('rel'), exclude : !state }, function(data) {
					set_checkbox_state(clicked, state);
					toggle_directory_check(clicked);
				});
			}
		}
	});

})(jQuery);