const {
    socialityAdminProfile,
    jQuery: $,
} = window;

const $document = $( document );

// Find SVG.
function findSVG( name ) {
    let result = '';

    if ( name && socialityAdminProfile.icons ) {
        socialityAdminProfile.icons.forEach( ( data ) => {
            if ( ! result && data.title === name ) {
                result = data.svg;
            }
        } );
    }

    return result;
}

// init icon picker
function initIconpicker() {
    $( '.sociality-icp' ).iconpicker( {
        //component:'span'
        input: '.iconpicker-input',
        icons: socialityAdminProfile.icons,
        placement: 'bottomLeft',
    } )
        .on( 'iconpickerShow', function( e ) {
            // Update icons in list.
            e.iconpickerInstance.iconpicker.find( '.iconpicker-item i[class]' ).each( function() {
                const $icon = $( this );
                const iconName = $icon.attr( 'class' );

                $icon.html( findSVG( iconName ) );
            } );
        } )
        .on( 'iconpickerSelected', function( e ) {
            // Update selected icon.
            $( this ).find( '.iconpicker-component i' ).removeAttr( 'class' ).html( findSVG( e.iconpickerValue ) );
        } );
}
initIconpicker();

// update array indexes in names
function updateIconPickerIndexes( $parent ) {
    let i = 0;
    $parent.children( '.input-group' ).each( function() {
        $( this ).find( '.iconpicker-component > input' ).attr( 'name', 'user_sociality_links[' + i + '][icon]' );
        $( this ).find( '.iconpicker-component' ).next().attr( 'name', 'user_sociality_links[' + i + '][url]' );
        i++;
    } );

    initIconpicker();
}

// add new icons
$document.on( 'click', '.sociality-icon-picker-add', function() {
    const newItem = `
    <div class="input-group sociality-icp">
        <span class="btn btn-default iconpicker-component input-group-btn">
            <i>Icon</i>
            <input type="hidden" class="iconpicker-input" name="user_sociality_links[1][icon]">
        </span>
        <input class="form-control" type="url" placeholder="https://..." name="user_sociality_links[1][url]">
        <span class="btn btn-danger sociality-icon-picker-remove input-group-btn">
            <i class="dashicons dashicons-no-alt"></i>
        </span>
    </div>`;

    const $insertAfter = $( this ).closest( '.sociality-icon-picker' ).children( '.input-group:last' );

    if ( $insertAfter.length ) {
        $insertAfter.after( newItem );
    } else {
        $( this ).closest( '.sociality-icon-picker' ).prepend( newItem );
    }

    updateIconPickerIndexes( $( this ).closest( '.sociality-icon-picker' ) );
} );

// remove icons
$document.on( 'click', '.sociality-icon-picker-remove', function( e ) {
    e.preventDefault();

    // eslint-disable-next-line
    const yes = window.confirm( 'Are you sure? Selected social link with icon will be removed.' );

    if ( yes ) {
        const $list = $( this ).closest( '.sociality-icon-picker' );
        $( this ).parent().remove();
        updateIconPickerIndexes( $list );
    }
} );
