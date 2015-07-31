function getSearchParameters() {
      var prmstr = window.location.search.substr(1);
      return prmstr != null && prmstr != "" ? transformToAssocArray(prmstr) : {};
}

function transformToAssocArray( prmstr ) {
    var params = {};
    var prmarr = prmstr.split("&");
    for ( var i = 0; i < prmarr.length; i++) {
        var tmparr = prmarr[i].split("=");
        params[tmparr[0]] = tmparr[1];
    }
    return params;
}

jQuery(document).ready(function() {
	//Set up onclick events
	jQuery('.wpsc-dictionary-edit-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word = jQuery(this).attr('id').split('-')[2]; 

		var parent_id = parent.attr('id').split('-')[2];
		show_editor(parent_id, old_word);
	});

	jQuery('.wpsc-edit-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');
		var old_word = jQuery(this).attr('id').split('-')[2]; 

		var parent_id = parent.attr('id').split('-')[2];
		show_editor(parent_id, old_word);
	});

	jQuery('.wpsc-suggest-button').click(function( event ) {
		event.preventDefault();
		var parent = jQuery(this).closest('.wpsc-row');

		var parent_id = parent.attr('id').split('-')[2];
		show_suggestions(parent_id);
	});

	jQuery('.wpsc-cancel-suggest-button').click(function() {
		var parent = jQuery(this).closest('tr');
		hide_editor(parent);
	});

	jQuery('.wpsc-cancel-button').click(function() {
		var parent = jQuery(this).closest('tr');
		hide_editor(parent);
	});

	jQuery('.wpsc-update-button').click(function() {
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[3]; 
		var updated_word = jQuery('#wpsc-edit-row-' + old_word_id + ' .wpsc-edit-field').attr('value'); //Get the new word
		var old_word = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-dictionary-edit-button').attr('id').split('-')[2]; //Get the old word

		//alert("?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word); //This is for testing
		var page_params = getSearchParameters();
		var sorting = '';
		if (page_params['orderby'] != 'undefined') sorting += '&orderby=' + page_params['orderby'];
		if (page_params['order'] != 'undefined') sorting += '&order=' + page_params['order'];

		old_word = old_word.replace('(','%28');
		updated_word = updated_word.replace('(','%28');

		var page_num = GetURLParameter('paged');
		window.location.href = "?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word + "&paged=" + page_num + sorting; //Refresh the page, passing the word to be updated via PHP
	});

	jQuery('.wpsc-edit-update-button').click(function() {
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[3]; 
		var updated_word = jQuery('#wpsc-edit-row-' + old_word_id + ' .wpsc-edit-field').attr('value'); //Get the new word
		var old_word = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('id').split('-')[2]; //Get the old word
		var page_name = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('page'); //Get the page name
		var page_type = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('page_type'); //Get the page type	

		//alert("?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word + "&page_name=" + page_name); //This is for testing
		var page_params = getSearchParameters();
		var sorting = '';
		if (page_params['orderby'] != 'undefined') sorting += '&orderby=' + page_params['orderby'];
		if (page_params['order'] != 'undefined') sorting += '&order=' + page_params['order'];

		old_word = old_word.replace('(','%28');
		updated_word = updated_word.replace('(','%28');

		var page_num = GetURLParameter('paged');
		window.location.href = encodeURI("?page=wp-spellcheck.php&old_word=" + old_word + "&new_word=" + updated_word + "&page_name=" + page_name + "&page_type=" + page_type + "&paged=" + page_num + sorting); //Refresh the page, passing the word to be updated via PHP
	});

	jQuery('.wpsc-update-suggest-button').click(function() {
		var old_word_id = jQuery(this).closest('tr').attr('id').split('-')[3]; 
		var updated_word = jQuery('#wpsc-suggest-row-' + old_word_id + ' .wpsc-suggested-spelling-list').attr('value'); //Get the new word
		var old_word = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('id').split('-')[2]; //Get the old word
		var page_name = jQuery('#wpsc-row-' + old_word_id).find('#wpsc-page-name').attr('page'); //Get the page name
		var page_type = jQuery('#wpsc-row-' + old_word_id).find('.wpsc-edit-button').attr('page_type'); //Get the page type	

		//alert("?page=wp-spellcheck-dictionary.php&old_word=" + old_word + "&new_word=" + updated_word + "&page_name=" + page_name); //This is for testing
		var page_num = GetURLParameter('paged');
		var page_params = getSearchParameters();
		var sorting = '';
		if (page_params['orderby'] != 'undefined') sorting += '&orderby=' + page_params['orderby'];
		if (page_params['order'] != 'undefined') sorting += '&order=' + page_params['order'];

		window.location.href = "?page=wp-spellcheck.php&old_word=" + old_word + "&new_word=" + updated_word + "&page_name=" + page_name + "&page_type=" + page_type + "&paged=" + page_num + sorting; //Refresh the page, passing the word to be updated via PHP
	});
});
	
//Display the editor for a single word
function show_editor(parent_id, old_word) {
	var parent = jQuery('#wpsc-row-' + parent_id),
		editor_id = 'wpsc-edit-row-' + parent_id,
		edit_row;

	//Remove all other quick edit fields
	parent.closest('table').find('tr.wpsc-editor').each(function() {
		hide_editor(jQuery(this));
	});

	//Create edit field for the selected word
	edit_row = jQuery('#wpsc-editor-row').clone(true).attr('id', editor_id);
	edit_row.toggleClass('alternate', parent.hasClass('alternate'));
	//Add the word to the field
	edit_row.find('input[type=text]').attr('value', old_word);
	parent.after(edit_row);

	edit_row.show();
	edit_row.find('input').focus();
}

//Display the spelling suggestions for a single word
function show_suggestions(parent_id) {
	var parent = jQuery('#wpsc-row-' + parent_id),
		suggest_id = 'wpsc-suggest-row-' + parent_id,
		suggest_row;

	//Remove all other suggestion or quick edit fields
	parent.closest('table').find('tr.wpsc-editor').each(function() {
		hide_editor(jQuery(this));
	});

	//Create suggestion field for the selected word
	suggest_row = jQuery('#wpsc-suggestion-row').clone(true).attr('id', suggest_id);
	suggest_row.toggleClass('alternate', parent.hasClass('alternate'));
	parent.after(suggest_row);

	//Populate the data for the suggested spellings
	var old_words = jQuery('#wpsc-row-' + parent_id).find('.wpsc-suggest-button').attr('suggestions'); //Get the suggested words
	for (x = 1; x <= 4; x++) {
		jQuery('#wpsc-suggest-row-' + parent_id).find('#wpsc-suggested-spelling-' + x).attr('value',old_words.split('-')[x - 1]);
		jQuery('#wpsc-suggest-row-' + parent_id).find('#wpsc-suggested-spelling-' + x).html(old_words.split('-')[x - 1]);
	}

	suggest_row.show();	
}

//Hide the editor
function hide_editor(parent_id) {
	var edit_row = isNaN(parent_id) ? parent_id : jQuery('#wpsc-edit-row' + parent_id);
	//alert('test');
	edit_row.remove();
}

//Used to retrieve URL parameters
function GetURLParameter(sParam)
{
    var sPageURL = window.location.search.substring(1);
    var sURLVariables = sPageURL.split('&');
    for (var i = 0; i < sURLVariables.length; i++)
    {
        var sParameterName = sURLVariables[i].split('=');
        if (sParameterName[0] == sParam)
        {
            return sParameterName[1];
        }
    }
}

//Used for the popup message on the results page
jQuery(document).ready(function() {
var mouseover_visible = false;
jQuery('.wpsc-mouseover-button-post').mouseenter(function() {
jQuery('.wpsc-mouseover-text-post').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
}).mouseleave(function() {
jQuery('.wpsc-mouseover-text-post').animate({opacity: 0}, 400);
mouseover_visible = false;
});
jQuery('.wpsc-mouseover-button-post').click(function() {
if (!mouseover_visible) {
jQuery('.wpsc-mouseover-text-post').stop();
jQuery('.wpsc-mouseover-text-post').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
} else {
jQuery('.wpsc-mouseover-text-post').animate({opacity: 0}, 400);
mouseover_visible = false;
}
});
});

jQuery(document).ready(function() {
var mouseover_visible = false;
jQuery('.wpsc-mouseover-button-page').mouseenter(function() {
jQuery('.wpsc-mouseover-text-page').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
}).mouseleave(function() {
jQuery('.wpsc-mouseover-text-page').animate({opacity: 0}, 400);
mouseover_visible = false;
});
jQuery('.wpsc-mouseover-button-page').click(function() {
if (!mouseover_visible) {
jQuery('.wpsc-mouseover-text-page').stop();
jQuery('.wpsc-mouseover-text-page').animate({opacity: 1.0}, 400, function() { mouseover_visible = true; });
} else {
jQuery('.wpsc-mouseover-text-page').animate({opacity: 0}, 400);
mouseover_visible = false;
}
});
});