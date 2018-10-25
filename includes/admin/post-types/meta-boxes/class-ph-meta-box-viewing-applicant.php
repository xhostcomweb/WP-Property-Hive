<?php
/**
 * Viewing Applicant Details
 *
 * @author 		PropertyHive
 * @category 	Admin
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * PH_Meta_Box_Viewing_Applicant
 */
class PH_Meta_Box_Viewing_Applicant {

	/**
	 * Output the metabox
	 */
	public static function output( $post ) {
        global $wpdb, $thepostid;
        
        echo '<div class="propertyhive_meta_box">';
        
        echo '<div class="options_group">';
        
        $applicant_contact_id = get_post_meta( $post->ID, '_applicant_contact_id', true );

        if ( !empty($applicant_contact_id) )
        {
            $contact = new PH_Contact($applicant_contact_id);

            echo '<p class="form-field">
            
                <label>' . __('Name', 'propertyhive') . '</label>
                
                <a href="' . get_edit_post_link($applicant_contact_id, '') . '" data-viewing-applicant-id="' . $applicant_contact_id . '" data-viewing-applicant-name="' . get_the_title($applicant_contact_id) . '">' . get_the_title($applicant_contact_id) . '</a>
                
            </p>';

            echo '<p class="form-field">
            
                <label>' . __('Telephone Number', 'propertyhive') . '</label>
                
                ' . $contact->telephone_number . '
                
            </p>';

            echo '<p class="form-field">
            
                <label>' . __('Email Address', 'propertyhive') . '</label>
                
                <a href="mailto:' . $contact->email_address . '">' .  $contact->email_address  . '</a>
                
            </p>';
        }
        else
        {
            echo '<p class="form-field">
            
                <label for="viewing_applicant_search">' . __('Search Applicants', 'propertyhive') . '</label>
                
                <span style="position:relative;">

                    <input type="text" name="viewing_applicant_search" id="viewing_applicant_search" style="width:100%;" placeholder="' . __( 'Search Existing Contacts', 'propertyhive' ) . '..." autocomplete="false">

                    <div id="viewing_search_applicant_results" style="display:none; position:absolute; z-index:99; background:#EEE; left:0; width:100%; border:1px solid #999; overflow-y:auto; max-height:150px;"></div>

                    <div id="viewing_selected_applicants" style="display:none;"></div>

                </span>
                
            </p>';

            echo '<input type="hidden" name="_applicant_contact_ids" id="_applicant_contact_ids" value="">';
?>
<script>

var viewing_selected_applicants = [];
<?php if (isset($_GET['applicant_contact_id']) && $_GET['applicant_contact_id'] != '') { ?>
viewing_selected_applicants[<?php echo $_GET['applicant_contact_id']; ?>] = ({ post_title: '<?php echo get_the_title($_GET['applicant_contact_id']); ?>' });
<?php } ?>

jQuery(document).ready(function($)
{
    viewing_update_selected_applicants();

    $('#viewing_applicant_search').on('keyup keypress', function(e)
    {
        var keyCode = e.charCode || e.keyCode || e.which;
        if (keyCode == 13)
        {
            event.preventDefault();
            return false;
        }
    });

    $('#viewing_applicant_search').keyup(function()
    {
        var keyword = $(this).val();

        if (keyword.length == 0)
        {
            $('#viewing_search_applicant_results').html('');
            $('#viewing_search_applicant_results').hide();
            return false;
        }

        if (keyword.length < 3)
        {
            $('#viewing_search_applicant_results').html('<div style="padding:10px;">Enter ' + (3 - keyword.length ) + ' more characters...</div>');
            $('#viewing_search_applicant_results').show();
            return false;
        }

        var data = {
            action:         'propertyhive_search_contacts',
            keyword:        keyword,
            security:       '<?php echo wp_create_nonce( 'search-contacts' ); ?>',
        };
        $.post( '<?php echo admin_url('admin-ajax.php'); ?>', data, function(response) 
        {
            if (response == '' || response.length == 0)
            {
                $('#viewing_search_applicant_results').html('<div style="padding:10px;">No results found for \'' + keyword + '\'</div>');
            }
            else
            {
                $('#viewing_search_applicant_results').html('<ul style="margin:0; padding:0;"></ul>');
                for ( var i in response )
                {
                    $('#viewing_search_applicant_results ul').append('<li style="margin:0; padding:0;"><a href="' + response[i].ID + '" style="color:#666; display:block; padding:7px 10px; background:#FFF; border-bottom:1px solid #DDD; text-decoration:none;">' + response[i].post_title + '</a></li>');
                }
            }
            $('#viewing_search_applicant_results').show();
        });
    });

    $('body').on('click', '#viewing_search_applicant_results ul li a', function(e)
    {
        e.preventDefault();

        viewing_selected_applicants = []; // reset to only allow one applicant for now
        viewing_selected_applicants.push( { id: $(this).attr('href'), post_title: $(this).text() } );

        $('#viewing_search_applicant_results').html('');
        $('#viewing_search_applicant_results').hide();

        $('#viewing_applicant_search').val('');

        viewing_update_selected_applicants();
    });

    $('body').on('click', 'a.viewing-remove-applicant', function(e)
    {
        e.preventDefault();

        var applicant_id = $(this).attr('href');

        for (var key in viewing_selected_applicants) 
        {
            if (viewing_selected_applicants[key].id == applicant_id ) 
            {
                viewing_selected_applicants.splice(key, 1);
            }
        }

        viewing_update_selected_applicants();
    });
});

function viewing_update_selected_applicants()
{
    jQuery('#_applicant_contact_ids').val('');

    if ( viewing_selected_applicants.length > 0 )
    {
        jQuery('#viewing_selected_applicants').html('<ul></ul>');
        for ( var i in viewing_selected_applicants )
        {
            jQuery('#viewing_selected_applicants ul').append('<li><a href="' + viewing_selected_applicants[i].id + '" class="viewing-remove-applicant" data-viewing-applicant-id="' + viewing_selected_applicants[i].id + '" data-viewing-applicant-name="' + viewing_selected_applicants[i].post_title + '" style="color:inherit; text-decoration:none;"><span class="dashicons dashicons-no-alt"></span></a> ' + viewing_selected_applicants[i].post_title + '</li>');

            jQuery('#_applicant_contact_ids').val(viewing_selected_applicants[i].id);
        }
        jQuery('#viewing_selected_applicants').show();
    }
    else
    {
        jQuery('#viewing_selected_applicants').html('');
        jQuery('#viewing_selected_applicants').hide();
    }

    jQuery('#_applicant_contact_ids').trigger('change');
}

</script>
<?php
        }

        do_action('propertyhive_viewing_applicant_fields');
	    
        echo '</div>';
        
        echo '</div>';
        
    }

    /**
     * Save meta box data
     */
    public static function save( $post_id, $post ) {
        global $wpdb;

        if ( isset($_POST['_applicant_contact_ids']) && !empty($_POST['_applicant_contact_ids']) )
        {
            update_post_meta( $post_id, '_applicant_contact_id', $_POST['_applicant_contact_ids'] );
        }
    }

}
