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
//           loadMessage    - Message to display while initial tree loads (can be HTML)
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
			if( o.root == undefined ) o.root = '/';
			if( o.script == undefined ) o.script = 'jqueryFileTree.php';
			if( o.folderEvent == undefined ) o.folderEvent = 'click';
			if( o.expandSpeed == undefined ) o.expandSpeed= 500;
			if( o.collapseSpeed == undefined ) o.collapseSpeed= 500;
			if( o.expandEasing == undefined ) o.expandEasing = null;
			if( o.collapseEasing == undefined ) o.collapseEasing = null;
			if( o.multiFolder == undefined ) o.multiFolder = true;
			if( o.loadMessage == undefined ) o.loadMessage = 'Loading...';

			$(this).each( function() {

				function showTree(c, t) {
					$(c).addClass('wait');
					$(".jqueryFileTree.start").remove();
					$.post(o.script, { action: 'file_tree', dir: t }, function(data) {
						$(c).find('.start').html('');
						$(c).removeClass('wait').append(data);
						if( o.root == t ) $(c).find('UL:hidden').show(); else $(c).find('UL:hidden').slideDown({ duration: o.expandSpeed, easing: o.expandEasing });

						//Check that the list of files that we got from the server have not already
						//been included or excluded in the UI.
						$('.checkbox').each(function () {
                            var dir = escape(dirname($(this).attr('rel')));
                            if (dir == t) {
                                var state = get_include_state($(this).attr('rel'));
                                if (state !== false) {
                                    set_checkbox_state(this, state);
                                }
                            }
						});
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
								$(this).parent().find('UL').slideUp({ duration: o.collapseSpeed, easing: o.collapseEasing });
								$(this).parent().removeClass('expanded').addClass('collapsed');
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
						checkbox_click(this);
					});

					// Prevent A from triggering the # on non-click events
					if( o.folderEvent.toLowerCase != 'click' ) $(t).find('LI A').bind('click', function() { return false; });
				}
				// Loading message
				$(this).html('<ul class="jqueryFileTree start"><li class="wait">' + o.loadMessage + '<li></ul>');
				// Get the initial file list
				showTree( $(this), escape(o.root) );
			});

			/**
			 * Updates the tri state check box based on the state hidden element passed
			 * @param check_box
			 */
			function set_checkbox_state(check_box, new_state) {
				new_state = parseInt(new_state);
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
			 * Toggles the hidden list input with what has changed
			 * @param element
			 */
			function set_include_state(element) {
                var file = $(element).attr('rel');
                var state = get_checkbox_state(element);
				var file_tree_list = JSON.parse($('#file_tree_list').val());
				var in_list = false;
                for (var i = 0; i < file_tree_list.length; i++) {
					if (file_tree_list[i][0] == file) {
						file_tree_list[i][1] = state;
                        in_list = true;
						break;
					}
				}
                if (!in_list) {
                    file_tree_list.push([file, state])
                }
				$('#file_tree_list').val(JSON.stringify(file_tree_list));
			}

			/**
			 * Get the file state from the local list
			 * @param file
			 * @return int
			 */
			function get_include_state(file) {
				var file_list = JSON.parse($('#file_tree_list').val());
				for (var i = 0; i < file_list.length; i++) {
					if (file_list[i][0] == file) {
						return file_list[i][1];
					}
				}
				return false;
			}

			/**
			 * Just like PHP's dirname
			 * @param path
			 */
			function dirname(path) {
				return path.replace(/\/$/, '').replace(/\/[^\/]*$/, '/');
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
						if (parent_dir == clicked_parent_dir) {
							var state = get_checkbox_state(this);
							if (state == PARTIAL || state == EXCLUDED) {
								checked_count++;
							}
							total++;
						}
					}
				});

				//Now that we know that the state of all the directories children we can update the parent dir accordingly
				$('.checkbox').each(function () {
					if ($(this).attr('rel') == clicked_parent_dir) {
						if (checked_count == total) {
							set_checkbox_state(this, EXCLUDED);
 						} else if (checked_count == 0) {
							set_checkbox_state(this, INCLUDED);
						} else {
							set_checkbox_state(this, PARTIAL);
						}
                        set_include_state(this);
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
			 * Set all the children of a directory to a state
			 * @param parent
			 */
			function set_directory_children(parent, state) {
				//If this is an expanded directory recursively update all its children
				if ($(parent).parent().hasClass('expanded') && $(parent).hasClass('directory')) {
					$('.checkbox').each(function () {
						if (dirname($(this).attr('rel')) == $(parent).attr('rel')) {
							set_checkbox_state(this, state);
							set_include_state(this);
							set_directory_children(this, state);
						}
					});
				}
			}

			/**
			 * The on click function for a file check box. If the user clicks on a directory then all its open children
			 * need to be updated accordingly.
			 * @param clicked
			 */
			function checkbox_click(clicked) {
				var state = get_checkbox_state(clicked) == EXCLUDED ? INCLUDED : EXCLUDED;

				set_checkbox_state(clicked, state);
				set_include_state(clicked);

				set_directory_children(clicked, state);
                toggle_directory_check(clicked);
			}
		}
	});

})(jQuery);